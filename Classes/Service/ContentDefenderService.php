<?php

declare(strict_types=1);

namespace UBOS\CopyPresets\Service;

use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\DebugUtility;

class ContentDefenderService
{
	public function __construct(
		private readonly PackageManager $packageManager,
		private readonly ConnectionPool $connectionPool,
	) {}

	/**
	 * Check if content_defender extension is installed and active
	 */
	public function isContentDefenderActive(): bool
	{
		return $this->packageManager->isPackageActive('content_defender');
	}

	/**
	 * Check if container extension is installed and active
	 */
	public function isContainerActive(): bool
	{
		return $this->packageManager->isPackageActive('container');
	}

	/**
	 * Get allowed CTypes for a specific colPos on a page
	 * Returns null if no restrictions, empty array if nothing allowed, or array of allowed CTypes
	 */
	public function getAllowedCTypesForColumn(int $pageId, int $colPos, int $containerUid = 0): ?array
	{
		if (!$this->isContentDefenderActive()) {
			return null;
		}
		try {
			if ($containerUid > 0 && $this->isContainerActive()) {
				$containerTcaRegistry = GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class);
				$queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
				$containerElement = $queryBuilder
					->select('CType')
					->from('tt_content')
					->where(
						$queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($containerUid))
					)
					->executeQuery()
					->fetchAllAssociative();
				$containerCType = $containerElement['CType'] ?? $containerElement[0]['CType'];
				$columnConfiguration = $containerTcaRegistry->getContentDefenderConfiguration($containerCType, $colPos);
				$columnConfiguration['maxitems'] = 0;
			} else {
				// Use content_defender's BackendLayoutConfiguration class
				$backendLayoutConfiguration = \IchHabRecht\ContentDefender\BackendLayout\BackendLayoutConfiguration::createFromPageId($pageId);
				$columnConfiguration = $backendLayoutConfiguration->getConfigurationByColPos($colPos);
			}

			if (empty($columnConfiguration)) {
				return null; // No restrictions for this column
			}

			$allowedCTypes = null;
			$disallowedCTypes = [];

			// Check for allowed configuration
			if (!empty($columnConfiguration['allowed.']['CType'])) {
				$allowedCTypes = GeneralUtility::trimExplode(',', $columnConfiguration['allowed.']['CType'], true);
			}

			// Check for disallowed configuration
			if (!empty($columnConfiguration['disallowed.']['CType'])) {
				$disallowedCTypes = GeneralUtility::trimExplode(',', $columnConfiguration['disallowed.']['CType'], true);
			}

			// If we have allowed CTypes, return them
			if ($allowedCTypes !== null) {
				// Remove disallowed from allowed
				if (!empty($disallowedCTypes)) {
					$allowedCTypes = array_diff($allowedCTypes, $disallowedCTypes);
				}
				return $allowedCTypes;
			}

			// If we only have disallowed, return "all except disallowed"
			if (!empty($disallowedCTypes)) {
				// Return a special marker that indicates "all except these"
				return ['__disallowed' => $disallowedCTypes];
			}

			return null; // No restrictions
		} catch (\Exception $e) {
			// If something goes wrong, don't restrict anything
			return null;
		}
	}

	/**
	 * Filter preset items based on content_defender restrictions
	 */
	public function filterPresetsByAllowedCTypes(array $presetItems, ?array $allowedCTypes): array
	{
		if ($allowedCTypes === null) {
			// No restrictions
			return $presetItems;
		}

		// Check if we have a "disallowed" marker
		if (isset($allowedCTypes['__disallowed'])) {
			$disallowedCTypes = $allowedCTypes['__disallowed'];
			return array_filter($presetItems, function ($item) use ($disallowedCTypes) {
				return !in_array($item['CType'], $disallowedCTypes, true);
			});
		}

		// Filter by allowed CTypes
		if (empty($allowedCTypes)) {
			// Nothing is allowed
			return [];
		}

		return array_filter($presetItems, function ($item) use ($allowedCTypes) {
			return in_array($item['CType'], $allowedCTypes, true);
		});
	}
}