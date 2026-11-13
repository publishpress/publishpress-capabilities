<?php

/**
 * Load the Capabilities Abilities API integration.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

require_once __DIR__ . '/mcp/bootstrap.php';
require_once __DIR__ . '/mcp/role-read-abilities.php';
require_once __DIR__ . '/mcp/grant-policy.php';
require_once __DIR__ . '/mcp/role-operations.php';
require_once __DIR__ . '/mcp/pro-settings-operations.php';
require_once __DIR__ . '/mcp/feature-operations.php';
require_once __DIR__ . '/mcp/interface-operations.php';
require_once __DIR__ . '/mcp/interface-capture.php';
require_once __DIR__ . '/mcp/menu-discovery.php';
require_once __DIR__ . '/mcp/backup-operations.php';
require_once __DIR__ . '/mcp/user-operations.php';
require_once __DIR__ . '/mcp/advanced-operations.php';
require_once __DIR__ . '/mcp/advanced-registry.php';
require_once __DIR__ . '/mcp/custom-rule-operations.php';
require_once __DIR__ . '/mcp/ability-registry.php';

Bootstrap::instance();
