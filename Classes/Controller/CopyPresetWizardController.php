<?php

declare(strict_types=1);

namespace UBOS\CopyPresets\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UBOS\CopyPresets\Service\CopyPresetService;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;

class CopyPresetWizardController
{
	public function __construct(
		private readonly CopyPresetService $copyPresetService,
		private readonly UriBuilder $uriBuilder,
		private readonly BackendViewFactory $backendViewFactory,
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

		// Get grouped presets
		$groupedPresets = $this->copyPresetService->getGroupedPresets();

		if (empty($groupedPresets)) {
			$view = $this->backendViewFactory->create($request);
			return new HtmlResponse($view->render('NoPresets'));
		}

		// Convert to format expected by typo3-backend-new-record-wizard
		$categories = [];
		foreach ($groupedPresets as $group) {
			$items = [];
			foreach ($group['elements'] as $element) {
				$executeUrl = (string)$this->uriBuilder->buildUriFromRoute(
					'copy_preset_execute',
					[
						'preset_uid' => $element['uid'],
						'id' => $targetPid,
						'sys_language_uid' => $languageUid,
						'colPos' => $colPos,
						'uid_pid' => $uidPid,
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
		$targetPid = (int)($queryParams['id'] ?? 0);
		$languageUid = (int)($queryParams['sys_language_uid'] ?? 0);
		$colPos = (int)($queryParams['colPos'] ?? 0);
		$uidPid = (int)($queryParams['uid_pid'] ?? 0);
		$presetUid = (int)($queryParams['preset_uid'] ?? 0);
		$returnUrl = (string)($queryParams['returnUrl'] ?? '');

		if ($presetUid === 0 || $targetPid === 0) {
			// Redirect back on error
			return $this->redirectToPageModule($targetPid);
		}

		// Perform the copy
		$this->copyPresetService->copyPreset(
			$presetUid,
			$targetPid,
			$colPos,
			$uidPid,
			$languageUid
		);

		// Redirect back to page module
		return $this->redirectToPageModule($targetPid);
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