<?php

declare(strict_types=1);

namespace Amdeu\CopyPresets\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;

use Amdeu\CopyPresets\Service\CopyPresetService;
use Amdeu\CopyPresets\Service\ContentDefenderService;

#[AsController]
class CopyPresetWizardController
{
	public function __construct(
		private readonly CopyPresetService $copyPresetService,
		private readonly ContentDefenderService $contentDefenderService,
		private readonly UriBuilder $uriBuilder,
		private readonly BackendViewFactory $backendViewFactory,
		private readonly FlashMessageService $flashMessageService,
	) {}

	/**
	 * Show the wizard with grouped presets
	 */
	public function showWizardAction(ServerRequestInterface $request): ResponseInterface
	{
		$queryParams = $request->getQueryParams();
		$targetPid = (int)($queryParams['id'] ?? 0);
		$languageUid = (int)($queryParams['sys_language_uid'] ?? 0);
		$colPos = (int)($queryParams['colPos'] ?? 0);
		$uidPid = (int)($queryParams['uid_pid'] ?? 0);
		$txContainerParent = (int)($queryParams['tx_container_parent'] ?? 0);

		// Get grouped presets - automatically filtered by user permissions
		$groupedPresets = $this->copyPresetService->getGroupedPresets();

		if (empty($groupedPresets)) {
			$view = $this->backendViewFactory->create($request);
			return new HtmlResponse($view->render('NoPresets'));
		}

		// Get content_defender restrictions for the target colPos
		$allowedCTypes = $this->contentDefenderService->getAllowedCTypesForColumn($targetPid, $colPos, $txContainerParent);

		// Convert to format expected by typo3-backend-new-record-wizard
		$categories = [];
		foreach ($groupedPresets as $group) {
			// Filter elements by content_defender restrictions
			$filteredElements = $this->contentDefenderService->filterPresetsByAllowedCTypes(
				$group['elements'],
				$allowedCTypes
			);

			// Skip empty groups
			if (empty($filteredElements)) {
				continue;
			}

			$items = [];
			foreach ($filteredElements as $element) {
				$executeUrl = (string)$this->uriBuilder->buildUriFromRoute(
					'copy_preset_execute',
					[
						'preset_uid' => $element['uid'],
						'preset_pid' => $group['pageUid'],
						'id' => $targetPid,
						'sys_language_uid' => $languageUid,
						'colPos' => $colPos,
						'uid_pid' => $uidPid,
						'tx_container_parent' => $txContainerParent,
					]
				);

				$items[] = [
					'identifier' => 'preset_' . $element['uid'],
					'label' => $element['header'] ?: '[No Header]',
					'description' => $element['CType'],
					'icon' => $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$element['CType']] ?? '',
					'url' => $executeUrl,
					'requestType' => 'location',
				];
			}

			$categories['preset_group_' . $group['pageUid']] = [
				'identifier' => 'preset_group_' . $group['pageUid'],
				'label' => $group['pageTitle'],
				'items' => $items,
			];
		}

		// Check if all groups were filtered out
		if (empty($categories)) {
			$view = $this->backendViewFactory->create($request);
			return new HtmlResponse($view->render('NoPresets'));
		}

		$view = $this->backendViewFactory->create($request);

		$view->assignMultiple([
			'categoriesJson' => GeneralUtility::jsonEncodeForHtmlAttribute($categories, false),
		]);

		return new HtmlResponse($view->render('Wizard'));
	}

	/**
	 * Execute the copy action and redirect back
	 */
	public function executeCopyAction(ServerRequestInterface $request): ResponseInterface
	{
		$queryParams = $request->getQueryParams();
		$presetUid = (int)($queryParams['preset_uid'] ?? 0);
		$presetPid = (int)($queryParams['preset_pid'] ?? 0);
		$targetPid = (int)($queryParams['id'] ?? 0);
		$colPos = (int)($queryParams['colPos'] ?? 0);
		$uidPid = (int)($queryParams['uid_pid'] ?? 0);
		$languageUid = (int)($queryParams['sys_language_uid'] ?? 0);
		$txContainerParent = (int)($queryParams['tx_container_parent'] ?? 0);

		if ($presetUid === 0 || $targetPid === 0) {
			$this->addFlashMessage(
				'Invalid parameters provided for copy operation.',
				'Copy Error',
				ContextualFeedbackSeverity::ERROR
			);
			return $this->redirectToPageModule($targetPid);
		}

		try {
			// Perform the copy - this will throw exception if user lacks permission
			$newUid = $this->copyPresetService->copyPreset(
				$presetUid,
				$presetPid,
				$targetPid,
				$colPos,
				$uidPid,
				$languageUid,
				$txContainerParent
			);

			if ($newUid) {
				$this->addFlashMessage(
					'Content element has been successfully copied.',
					'Copy Successful',
					ContextualFeedbackSeverity::OK
				);
			} else {
				$this->addFlashMessage(
					'Failed to copy content element. Please try again.',
					'Copy Failed',
					ContextualFeedbackSeverity::WARNING
				);
			}
		} catch (\RuntimeException $e) {
			// Permission denied or other runtime error
			$this->addFlashMessage(
				$e->getMessage(),
				'Permission Denied',
				ContextualFeedbackSeverity::ERROR
			);
		} catch (\Exception $e) {
			// Unexpected error
			$this->addFlashMessage(
				'An unexpected error occurred: ' . $e->getMessage(),
				'Error',
				ContextualFeedbackSeverity::ERROR
			);
		}

		// Redirect back to page module
		return $this->redirectToPageModule($targetPid);
	}

	/**
	 * Add a flash message to the queue
	 */
	private function addFlashMessage(
		string $message,
		string $title,
		ContextualFeedbackSeverity $severity
	): void {
		$flashMessage = GeneralUtility::makeInstance(
			FlashMessage::class,
			$message,
			$title,
			$severity,
			true
		);

		$messageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
		$messageQueue->enqueue($flashMessage);
	}

	/**
	 * Redirect to page module
	 */
	private function redirectToPageModule(int $pageId): ResponseInterface
	{
		$url = (string)$this->uriBuilder->buildUriFromRoute(
			'web_layout',
			['id' => $pageId]
		);

		return new RedirectResponse($url);
	}
}