<?php

/**
 * Verify ability metadata is translated without changing the API contract.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || count($argv) !== 4) {
    exit("Supply a WordPress bootstrap, adapter directory and Capabilities abilities file.\n");
}
$_SERVER['HTTP_HOST'] = 'mcp-test.example';
require $argv[1];
if (getenv('MCP_SMOKE_PRO') === '1') {
    define('PUBLISHPRESS_CAPS_PRO_VERSION', 'integration-fixture');
}
add_filter('gettext', static function ($translated, $original, $domain) {
    return $domain === 'capability-manager-enhanced' ? 'translated: ' . $original : $translated;
}, 10, 3);
require __DIR__ . '/bootstrap-capabilities.php';
$checks = 0;
foreach (wp_get_abilities() as $name => $ability) {
    if (strpos($name, 'publishpress-capabilities/') !== 0) {
        continue;
    }
    foreach ([$ability->get_label(), $ability->get_description()] as $text) {
        if (strpos($text, 'translated: ') !== 0) {
            throw new RuntimeException('Untranslated metadata for ' . $name);
        }
        ++$checks;
    }
    if ($ability->get_name() !== $name || strpos($name, 'translated:') !== false) {
        throw new RuntimeException('Translated action identifier.');
    }
    ++$checks;
}
if ($checks < 120) {
    throw new RuntimeException('Expected at least 40 registered actions.');
}
$result = \PublishPress\Capabilities\Mcp\RoleReadAbilities::getRole(['role' => 'unknown-mcp-role']);
if (!is_wp_error($result) || strpos($result->get_error_message(), 'translated: ') !== 0) {
    throw new RuntimeException('Untranslated domain error.');
}
++ $checks;
echo "Completed $checks translation checks with WordPress $wp_version.\n";
