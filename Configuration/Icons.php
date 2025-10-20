<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
	'apps-pagetree-copy-preset' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:copy_presets/Resources/Public/Icons/doktype-copy-preset.svg',
	],
	'actions-copy-preset' => [
		'provider' => SvgIconProvider::class,
		'source' => 'EXT:copy_presets/Resources/Public/Icons/actions-copy-preset.svg',
	],
];