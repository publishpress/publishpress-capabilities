<?php
/** Native backup lifecycle shared by the admin screen and MCP. */
declare(strict_types=1);

$values = [
    'test_user_roles' => ['editor' => ['name' => 'Editor', 'capabilities' => ['read' => true]]],
    'native_features' => ['editor' => ['hidden' => true]],
    'capsman_backup' => ['previous' => true],
    'capsman_backup_datestamp' => 123,
    'unrelated' => ['keep' => true],
];
$wpdb = (object) ['prefix' => 'test_'];
function get_option($key, $default = false) { return $GLOBALS['values'][$key] ?? $default; }
function update_option($key, $value, $autoload = null) { $GLOBALS['values'][$key] = $value; return true; }
function current_time($type) { return 456; }
function __($text, $domain = '') { return $text; }
function esc_html__($text, $domain = '') { return $text; }
function is_multisite() { return false; }
function current_user_can($capability) { return $capability === 'manage_capabilities_backup'; }
function wp_die($message) { throw new RuntimeException($message); }
function pp_capabilities_backup_sections() {
    return [['label' => 'Features', 'options' => ['native_features', 'unset_features']]];
}
function check_admin_referer($action) { $GLOBALS['nonce_checked'] = $action; }
function ak_admin_notify($message) { $GLOBALS['notice'] = $message; }
require dirname(__DIR__) . '/includes/backup-handler.php';
$checks = 0;
$check = static function ($condition, $message) use (&$checks) {
    if (!$condition) { throw new RuntimeException($message); }
    ++$checks;
};
$handler = new Capsman_BackupHandler((object) ['ID' => 'capsman']);
$sections = $handler->createBackup();
$check($sections === ['user_roles', 'native_features', 'unset_features'], 'Native sections retained');
$check($values['capsman_backup'] === $values['test_user_roles'], 'Role snapshot uses native role option');
$check($values['native_features_backup'] === $values['native_features'], 'Native feature snapshot retained');
$check($values['unset_features_backup'] === false, 'Absent native options retain false representation');
$check($values['capsman_backup_initial'] === ['previous' => true], 'Previous backup preserved as initial');
$check($values['capsman_backup_initial_datestamp'] === 123, 'Initial timestamp preserved');
$check($values['capsman_last_backup'] === 'Roles and Capabilities, Features', 'Native labels deduplicated');
$check($values['capsman_backup_datestamp'] === 456, 'Native clock used');
$handler->createBackup(['user_roles' => ['reviewed' => true], 'native_features' => []]);
$check($values['capsman_backup'] === ['reviewed' => true] && $values['native_features_backup'] === [], 'Reviewed MCP snapshot retains exact values');
$check($values['capsman_backup_initial'] === ['previous' => true], 'Initial backup never overwritten');
$_POST = ['save_backup' => '1'];
$handler->processBackupTool();
$check(($GLOBALS['nonce_checked'] ?? '') === 'pp-capabilities-backup', 'Admin nonce remains required');
$check(($GLOBALS['notice'] ?? '') === 'New backup saved.', 'Native admin completion retained');
$check($values['capsman_backup'] === $values['test_user_roles'], 'Admin path uses shared native backup');
$check($values['unrelated'] === ['keep' => true], 'Unrelated options preserved');
echo "PASS: $checks native backup lifecycle checks.\n";
