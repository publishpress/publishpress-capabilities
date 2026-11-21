<?php

function sanitize_key($key) {
    return preg_replace('/[^a-z0-9_]+/', '_', strtolower((string) $key));
}

function sanitize_text_field($value) {
    return is_scalar($value) ? (string) $value : '';
}

$allowed_feature = '';

function current_user_can($capability) {
    return in_array($capability, ['read', 'manage_capabilities_backup'], true)
        || $capability === $GLOBALS['allowed_feature'];
}

function is_multisite() {
    return false;
}

function wp_die($message = '', $title = '', $args = []) {
    throw new RuntimeException($message, $args['response'] ?? 500);
}

function check_admin_referer($action) {}

function pp_capabilities_backup_sections() {
    return ['capsman_admin_menu_backup' => ['options' => ['capsman_admin_menus']]];
}

function get_option($key, $default = false) {
    return [
        'capsman_backup' => ['editor' => ['name' => 'Editor', 'capabilities' => ['read' => true]]],
        'capsman_admin_menus_backup' => ['editor' => []],
    ][$key] ?? $default;
}

function update_option($key, $value) {
    throw new RuntimeException('Unauthorized restore must not write ' . $key);
}

function esc_html__($text, $domain = null) {
    return $text;
}

require_once __DIR__ . '/../includes/backup-handler.php';

$backup_handler_source = file_get_contents(__DIR__ . '/../includes/backup-handler.php');

if (false === strpos($backup_handler_source, 'new WP_Filesystem_Direct(null)')) {
    fwrite(STDERR, "Backup imports should use direct filesystem access for completed local uploads.\n");
    exit(1);
}

if (false !== strpos($backup_handler_source, '! WP_Filesystem()')) {
    fwrite(STDERR, "Backup imports must not require negotiated FTP or SSH credentials.\n");
    exit(1);
}

$handler = new Capsman_BackupHandler(new stdClass());
foreach ([
    ['capsman_admin_menus', 'capsman_admin_menu_backup', 'admin_menus'],
    ['ppc_admin_menus_settings', 'capsman_admin_menu_backup', 'admin_menus'],
    ['cme_admin_menus_restriction_priority', 'capsman_settings_backup', 'admin_menus'],
    ['capsman_feature_restrict_post', 'capsman_editor_features_backup', 'editor_features'],
    ['capsman_disabled_admin_features', 'capsman_admin_features_backup', 'admin_features'],
    ['pp_capabilities_admin_styles', 'capsman_admin_styles_backup', 'admin_styles'],
    ['cme_admin_notice_options', 'capsman_admin_notices_backup', 'admin_notices'],
    ['capsman_disabled_frontend_features', 'capsman_frontend_features_backup', 'frontend_features'],
    ['capsman_profile_features_elements', 'capsman_profile_features_backup', 'profile_features'],
    ['capsman_role_redirects', 'capsman_redirects_backup', 'redirects'],
    ['capsman_nav_item_menus', 'capsman_nav_menu_backup', 'nav_menus'],
    ['cme_role_same_page_redirect_cookie', 'capsman_settings_backup', 'settings'],
] as [$option_key, $section_key, $capability_suffix]) {
    if ($handler->can_write_backup_option($option_key, $section_key)) {
        throw new RuntimeException('Backup permission alone must not permit writing ' . $option_key);
    }
    $allowed_feature = 'manage_capabilities_' . $capability_suffix;
    if (!$handler->can_write_backup_option($option_key, $section_key)) {
        throw new RuntimeException('Matching feature permission must permit writing ' . $option_key);
    }
    $allowed_feature = '';
}
if (!$handler->can_write_backup_option('user_roles', 'roles')) {
    throw new RuntimeException('Role-only backups should keep the existing permission policy.');
}

$input = [
    'editor' => [
        'name' => 'Editor',
        'capabilities' => [
            'read' => '1',
            'manage_capabilities' => '1',
            'manage_capabilities_backup' => '1',
            'manage_options' => '1',
        ],
    ],
];

$result = $handler->santize_import_role($input);

if (!isset($result['editor']['capabilities']['read']) || '1' !== $result['editor']['capabilities']['read']) {
    fwrite(STDERR, "Expected read capability to be preserved.\n");
    exit(1);
}

if (isset($result['editor']['capabilities']['manage_capabilities'])) {
    fwrite(STDERR, "manage_capabilities should be stripped from imported roles for non-administrators.\n");
    exit(1);
}

if (!isset($result['editor']['capabilities']['manage_capabilities_backup']) || '1' !== $result['editor']['capabilities']['manage_capabilities_backup']) {
    fwrite(STDERR, "Expected backup capability to be preserved.\n");
    exit(1);
}

if (isset($result['editor']['capabilities']['manage_options'])) {
    fwrite(STDERR, "Other admin-only capabilities should be stripped from imported roles.\n");
    exit(1);
}

echo "PASS: imported roles only preserve capabilities the current user can grant.\n";

$wpdb = (object) ['prefix' => 'wp_'];
$_POST = ['restore_backup' => '1', 'select_restore' => 'restore'];
$handler = new Capsman_BackupHandler((object) ['ID' => 'capsman']);
$denial_status = 0;
try {
    $handler->processBackupTool();
} catch (RuntimeException $error) {
    $denial_status = $error->getCode();
}
if (403 !== $denial_status) {
    throw new RuntimeException('Backup-only restore must respond with HTTP 403 before writing.');
}
echo "PASS: Backup-only restore of Admin Menus settings returns 403.\n";
