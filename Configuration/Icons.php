<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
	'apps-pagetree-copy-preset' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:copy_presets/Resources/Public/Icons/copy-presets-button.svg',
	],
	'actions-copy-preset' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:copy_presets/Resources/Public/Icons/actions-copy-preset.svg',
	],
];