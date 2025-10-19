<?php

declare(strict_types=1);

use UBOS\CopyPresets\Controller\CopyPresetWizardController;

return [
	'copy_preset_wizard' => [
		'path' => '/wizard/copy-preset',
		'target' => CopyPresetWizardController::class . '::showWizardAction',
	],
	'copy_preset_execute' => [
		'path' => '/wizard/copy-preset/execute',
		'target' => CopyPresetWizardController::class . '::executeCopyAction',
	],
];