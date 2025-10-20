<?php

declare(strict_types=1);

namespace UBOS\CopyPresets\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;

class CopyPresetService
{
	public function __construct(
		private readonly ConnectionPool $connectionPool,
		private readonly IconFactory $iconFactory
	) {}

	/**
	 * Get the current backend user
	 */
	private function getBackendUser(): BackendUserAuthentication
	{
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Get all copy preset pages (doktype = 200) that the user has access to
	 */
	public function getCopyPresetPages(): array
	{
		$backendUser = $this->getBackendUser();
		$queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

		// Keep default restrictions but allow hidden preset pages
		$queryBuilder->getRestrictions()
			->removeByType(\TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction::class);

		$queryBuilder
			->select('uid', 'title', 'perms_everybody', 'perms_userid', 'perms_user', 'perms_groupid', 'perms_group')
			->from('pages')
			->where(
				$queryBuilder->expr()->eq(
					'doktype',
					$queryBuilder->createNamedParameter(200)
				)
			);

		// Add page permissions WHERE clause - user must have read permission
		if (!$backendUser->isAdmin()) {
			$permissionClause = $backendUser->getPagePermsClause(1);
			if ($permissionClause) {
				$queryBuilder->andWhere($permissionClause);
			}
		}

		$queryBuilder->orderBy('title');

		$pages = $queryBuilder
			->executeQuery()
			->fetchAllAssociative();

		// Filter by DB mounts - user must have access via their mount points
		if (!$backendUser->isAdmin()) {
			$accessiblePages = [];
			foreach ($pages as $page) {
				if ($backendUser->doesUserHaveAccess($page, 1)) {
					$accessiblePages[] = $page;
				}
			}
			return $accessiblePages;
		}

		return $pages;
	}

	/**
	 * Get all content elements from copy preset pages, grouped by page
	 * Only returns elements from pages the user has access to
	 */
	public function getGroupedPresets(): array
	{
		$presetPages = $this->getCopyPresetPages();

		if (empty($presetPages)) {
			return [];
		}

		$pageUids = array_column($presetPages, 'uid');

		$queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

		// Keep default restrictions but allow hidden content in preset pages
		$queryBuilder->getRestrictions()
			->removeByType(\TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction::class);

		$contentElements = $queryBuilder
			->select('uid', 'pid', 'CType', 'header', 'colPos')
			->from('tt_content')
			->where(
				$queryBuilder->expr()->in(
					'pid',
					$queryBuilder->createNamedParameter($pageUids, \Doctrine\DBAL\ArrayParameterType::INTEGER)
				)
			)
			->orderBy('pid')
			->addOrderBy('sorting')
			->executeQuery()
			->fetchAllAssociative();

		// Group by page
		$grouped = [];
		$pageMap = array_column($presetPages, 'title', 'uid');

		foreach ($contentElements as $element) {
			$pageUid = $element['pid'];
			if (!isset($grouped[$pageUid])) {
				$grouped[$pageUid] = [
					'pageTitle' => $pageMap[$pageUid] ?? 'Unknown',
					'pageUid' => $pageUid,
					'elements' => [],
				];
			}

			// Add icon information
			$element['icon'] = $this->iconFactory->getIconForRecord('tt_content', $element, 'small')->render();

			$grouped[$pageUid]['elements'][] = $element;
		}

		return $grouped;
	}

	/**
	 * Copy a content element to a target position using DataHandler
	 * Uses the same command structure as TYPO3's standard copy/paste functionality
	 * This ensures all hooks (including b13/container) are properly triggered
	 *
	 * @throws \RuntimeException if user doesn't have permission to copy the element
	 */
	public function copyPreset(int $presetUid, int $presetPid, int $targetPid, int $targetColPos, int $uidPid, int $sysLanguageUid = 0): ?int
	{
		// Verify user has access to the preset element's page
		if (!$this->canUserCopyElement($presetUid, $presetPid)) {
			throw new \RuntimeException(
				'Access denied: You do not have permission to copy this content element.',
				1234567890
			);
		}

		// Initialize DataHandler
		$dataHandler = GeneralUtility::makeInstance(DataHandler::class);

		// Build the target specification
		if ($uidPid < 0) {
			// Insert after specific element (negative value)
			$target = $uidPid;
		} else {
			// Insert at beginning of page/column
			$target = $targetPid;
		}

		// Use the extended copy command format with 'update' array
		// This is the same format used by TYPO3's standard copy/paste buttons
		// and ensures all DataHandler hooks (including container extension) work correctly
		$cmd = [
			'tt_content' => [
				$presetUid => [
					'copy' => [
						'action' => 'paste',
						'target' => $target,
						'update' => [
							'colPos' => $targetColPos,
							'sys_language_uid' => $sysLanguageUid,
						],
					],
				],
			],
		];

		// Execute the copy command
		$dataHandler->start([], $cmd);
		$dataHandler->process_cmdmap();

		// Get the UID of the newly copied element
		$newUid = null;
		if (!empty($dataHandler->copyMappingArray_merged['tt_content'][$presetUid])) {
			$newUid = (int)$dataHandler->copyMappingArray_merged['tt_content'][$presetUid];
		}

		return $newUid;
	}

	/**
	 * Check if the current user has permission to copy a content element
	 */
	private function canUserCopyElement(int $contentUid, int $pageUid): bool
	{
		$backendUser = $this->getBackendUser();

		// Admin users can copy everything
		if ($backendUser->isAdmin()) {
			return true;
		}

		// Check if page is a preset page (doktype 200)
		$queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
		$queryBuilder->getRestrictions()
			->removeAll()
			->add(GeneralUtility::makeInstance(DeletedRestriction::class));

		$page = $queryBuilder
			->select('uid', 'doktype', 'perms_everybody', 'perms_userid', 'perms_user', 'perms_groupid', 'perms_group')
			->from('pages')
			->where(
				$queryBuilder->expr()->eq(
					'uid',
					$queryBuilder->createNamedParameter($pageUid)
				)
			)
			->executeQuery()
			->fetchAssociative();

		if (!$page || (int)$page['doktype'] !== 200) {
			return false;
		}

		// Check if user has read access to the page (permission 1)
		return $backendUser->doesUserHaveAccess($page, 1);
	}
}