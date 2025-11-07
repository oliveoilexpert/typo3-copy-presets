<?php

declare(strict_types=1);

return [
	'dependencies' => ['core', 'backend'],
	'imports' => [
		'@amdeu/copy-presets/' => [
			'path' => 'EXT:copy_presets/Resources/Public/JavaScript/',
			'exclude' => [],
		],
	],
];