<?php

/**
 * Exercise typed settings against the real Pro helper with dependency markers.
 * Dependency markers test availability and lock contracts, not full integrations.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || count($argv) !== 5) {
    exit("Supply WordPress, adapter, abilities and Pro helper paths.\n");
}
$_SERVER['HTTP_HOST'] = 'mcp-test.example';
require $argv[1];
if ($GLOBALS['table_prefix'] !== 'mcp_smoke_') {
    throw new RuntimeException('Use the isolated mcp_smoke_ fixture.');
}
define('PUBLISHPRESS_CAPS_PRO_VERSION', 'integration-fixture');
require __DIR__ . '/bootstrap-capabilities.php';
define('PUBLISHPRESS_CAPS_PRO_ABSPATH', dirname($argv[4], 3));
wp_get_abilities();
$actor = new WP_User();
$actor->ID = 1;
$actor->allcaps = ['read' => true, 'manage_capabilities_settings' => true];
$GLOBALS['current_user'] = $actor;
$ability = wp_get_ability('publishpress-capabilities/update-plugin-settings');
$fields = \PublishPress\Capabilities\Mcp\ProSettingsOperations::fields();
$original = [];
foreach ($fields as $field => $schema) {
    $original[$field] = get_option($field, null);
}
$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};
try {
    $blocked = $ability->execute(['settings' => ['cme_custom_status_control' => true, 'cme_display_branding' => false]]);
    $check(is_wp_error($blocked), 'Missing status dependency must reject the entire patch');
    $check(class_exists('PublishPress\\Capabilities\\Pro', false), 'REST settings load the real Pro helper without admin bootstrap');
    $check(get_option('cme_display_branding', null) === $original['cme_display_branding'], 'Rejected patches cannot partially change branding');
    require __DIR__ . '/fixtures/statuses-marker.php';
    register_post_status('mcp-review', ['internal' => false, 'public' => false, 'private' => false]);
    $capabilities = \PublishPress\Capabilities\Mcp\RoleOperations::catalog([])['capabilities'];
    $check(in_array('status_change_mcp_review', $capabilities, true), 'Registered custom status capabilities are discoverable before assignment');
    $enabled = $ability->execute(['settings' => ['cme_custom_status_control' => true]]);
    $check(!is_wp_error($enabled) && get_option('cme_custom_status_control'), 'Available status control can be enabled');
    $blocked = $ability->execute(['settings' => ['cme_custom_status_postmeta_caps' => true]]);
    $check(is_wp_error($blocked), 'Postmeta permissions require a supporting integration');
    require __DIR__ . '/fixtures/status-control-marker.php';
    $blocked = $ability->execute(['settings' => ['cme_custom_status_control' => false]]);
    $check(is_wp_error($blocked) && get_option('cme_custom_status_control'), 'Integration-controlled status settings stay locked');
    define('PRESSPERMIT_PRO_VERSION', 'dependency-marker');
    $enabled = $ability->execute(['settings' => ['presspermit_privacy_statuses_enabled' => true]]);
    $check(!is_wp_error($enabled), 'Privacy settings require the available Permissions status integration');
    define('PPS_PRIVACY_STATUSES_LOCKED', true);
    $blocked = $ability->execute(['settings' => ['presspermit_privacy_statuses_enabled' => false]]);
    $check(is_wp_error($blocked) && get_option('presspermit_privacy_statuses_enabled'), 'Site-locked privacy settings cannot be changed');
    define('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED', true);
    $blocked = $ability->execute(['settings' => ['presspermit_custom_privacy_edit_caps' => true]]);
    $check(is_wp_error($blocked), 'Site-locked privacy edit capabilities cannot be changed');
    echo "Completed $checks Pro settings contract checks with WordPress $wp_version.\n";
} finally {
    foreach ($original as $field => $value) {
        if ($value === null) {
            delete_option($field);
        } else {
            update_option($field, $value);
        }
    }
}
