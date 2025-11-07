<?php

$EM_CONF[$_EXTKEY] = [
	'title' => 'Copy Presets',
	'description' => 'Allows editors to quickly copy content elements from a list of copy presets in the page layout module',
	'category' => 'be',
	'author' => 'Amadeus Kiener',
	'state' => 'stable',
	'version' => '1.0.0',
	'constraints' => [
		'depends' => [
			'typo3' => '13.4.0-13.99.99',
		],
		'conflicts' => [],
		'suggests' => [
			'container' => '',
		],
	],
];