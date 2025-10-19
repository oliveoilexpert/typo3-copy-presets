<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Register AJAX route identifier for frontend use
// The actual route is defined in Configuration/Backend/Routes.php
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['copy_preset_execute'] = 'copy_preset_execute';