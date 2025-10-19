<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Add new doktype for Copy Preset Pages
$GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'][] = [
	'label' => 'LLL:EXT:copy_presets/Resources/Private/Language/locallang_tca.xlf:pages.doktype.200',
	'value' => 200,
	'icon' => 'apps-pagetree-copy-preset',
	'group' => 'special',
];

// Add icon for pages tree
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][200] = 'apps-pagetree-copy-preset';

// Allow backend users to create this page type
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
	'options.pageTree.doktypesToShowInNewPageDragArea := addToList(200)'
);