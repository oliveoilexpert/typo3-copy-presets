<?php

declare(strict_types=1);

return [
	'dependencies' => ['core', 'backend'],
	'imports' => [
		'@ubos/copy-presets/' => [
			'path' => 'EXT:copy_presets/Resources/Public/JavaScript/',
			'exclude' => [],
		],
	],
];