<?php

/**
 * Load the Capabilities domain helpers for isolated integration fixtures.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 1.2.0
 */

declare(strict_types=1);

$capabilitiesRoot = dirname($argv[3], 2);
foreach ([
    'CME_FILE' => $capabilitiesRoot . '/capsman-enhanced.php',
    'PUBLISHPRESS_CAPS_ABSPATH' => $capabilitiesRoot,
    'PUBLISHPRESS_CAPS_VERSION' => '2.54.0',
    'CAPSMAN_VERSION' => '2.54.0',
] as $constantName => $constantValue) {
    if (!defined($constantName)) {
        define($constantName, $constantValue);
    }
}
require_once $capabilitiesRoot . '/includes/functions.php';
require_once $argv[3];
