<?php

declare(strict_types=1);

namespace UBOS\CopyPresets\Service;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;

class CopyPresetService
{
	public function __construct(
		private readonly ConnectionPool $connectionPool,
		private readonly IconFactory $iconFactory
	) {}

	/**
	 * Get all copy preset pages (doktype = 200)
	 */
	public function getCopyPresetPages(): array
	{
		$queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
		$queryBuilder->getRestrictions()->removeAll();

		return $queryBuilder
			->select('uid', 'title')
			->from('pages')
			->where(
				$queryBuilder->expr()->eq(
					'doktype',
					$queryBuilder->createNamedParameter(200)
				)
			)
			->orderBy('title')
			->executeQuery()
			->fetchAllAssociative();
	}

	/**
	 * Get all content elements from copy preset pages, grouped by page
	 */
	public function getGroupedPresets(): array
	{
		$presetPages = $this->getCopyPresetPages();

		if (empty($presetPages)) {
			return [];
		}

		$pageUids = array_column($presetPages, 'uid');

		$queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
		$queryBuilder->getRestrictions()->removeAll();

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
	 */
	public function copyPreset(int $presetUid, int $targetPid, int $targetColPos, int $uidPid, int $sysLanguageUid = 0): ?int
	{
		$dataHandler = GeneralUtility::makeInstance(DataHandler::class);

		// Build the target specification
		// If uidPid is negative, it means "after element with abs(uidPid)"
		// If uidPid is positive or 0, use targetPid
		if ($uidPid < 0) {
			// Insert after specific element
			$target = $uidPid; // Already negative
		} else {
			// Insert at beginning of column
			$target = $targetPid;
		}

		// Prepare copy command
		$cmd = [
			'tt_content' => [
				$presetUid => [
					'copy' => $target,
				],
			],
		];

		// Execute the copy
		$dataHandler->start([], $cmd);
		$dataHandler->process_cmdmap();

		// Get the UID of the newly copied element
		$newUid = null;
		if (!empty($dataHandler->copyMappingArray_merged['tt_content'][$presetUid])) {
			$newUid = (int)$dataHandler->copyMappingArray_merged['tt_content'][$presetUid];

			// Now update colPos and sys_language_uid if needed
			if ($newUid > 0) {
				$data = [
					'tt_content' => [
						$newUid => [
							'colPos' => $targetColPos,
							'sys_language_uid' => $sysLanguageUid,
						],
					],
				];
				$dataHandler = GeneralUtility::makeInstance(DataHandler::class);
				$dataHandler->start($data, []);
				$dataHandler->process_datamap();
			}
		}

		return $newUid;
	}
}