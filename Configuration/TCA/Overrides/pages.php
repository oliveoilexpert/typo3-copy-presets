<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Add new doktype for Copy Preset Pages
$GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'][] = [
	'label' => 'LLL:EXT:copy_presets/Resources/Private/Language/locallang_tca.xlf:pages.doktype.3151625',
	'value' => 3151625,
	'icon' => 'apps-pagetree-copy-preset',
	'group' => 'special',
];

// Add icon for pages tree
$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][3151625] = 'apps-pagetree-copy-preset';