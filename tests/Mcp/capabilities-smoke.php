<?php

/**
 * Exercise Capabilities read/write abilities on a disposable WordPress installation.
 * Run: php capabilities-smoke.php wp-load.php adapter-directory capabilities/includes/mcp-abilities.php
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 1.2.0
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli' || count($argv) !== 4) {
    exit("Supply a disposable WordPress bootstrap, adapter directory and Capabilities abilities file.\n");
}
$_SERVER['HTTP_HOST'] = 'mcp-test.example';
require $argv[1];
if ($GLOBALS['table_prefix'] !== 'mcp_smoke_') {
    throw new RuntimeException('Write integration tests require the isolated mcp_smoke_ fixture.');
}
if (getenv('MCP_SMOKE_PRO') === '1') {
    define('PUBLISHPRESS_CAPS_PRO_VERSION', 'integration-fixture');
}
require __DIR__ . '/bootstrap-capabilities.php';
require $argv[2] . '/mcp-adapter.php';
wp_get_abilities();

use PublishPress\Capabilities\Mcp\AbilityRegistry;
use PublishPress\Capabilities\Mcp\FeatureOperations;

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
    echo "PASS: $message\n";
};
$bootstrap = \PublishPress\Capabilities\Mcp\Bootstrap::instance();
$hookCount = count($GLOBALS['wp_filter']['wp_abilities_api_init']->callbacks[10]);
require $argv[3];
$check(
    $bootstrap === \PublishPress\Capabilities\Mcp\Bootstrap::instance(),
    'Repeated loading retains the same integration instance'
);
$check(
    count($GLOBALS['wp_filter']['wp_abilities_api_init']->callbacks[10]) === $hookCount,
    'Repeated loading does not duplicate ability registration hooks'
);
$call = static function (string $name, array $input = []) {
    $ability = wp_get_ability('publishpress-capabilities/' . $name);
    if (!$ability) {
        throw new RuntimeException('Ability missing: ' . $name);
    }
    return $ability->execute($input);
};
$caps = ['read' => true, 'administrator' => true, 'manage_options' => true, 'list_users' => true,
    'edit_users' => true, 'edit_posts' => true, 'edit_published_posts' => true, 'delete_posts' => true];
foreach (['dashboard', 'roles', 'editor_features', 'admin_features', 'admin_styles', 'admin_notices',
    'admin_menus', 'frontend_features', 'profile_features', 'nav_menus', 'redirects', 'backup', 'settings', 'user_testing'] as $suffix) {
    $caps['manage_capabilities_' . $suffix] = true;
}
$caps['manage_capabilities'] = true;
$actor = new WP_User();
$actor->ID = 1;
$actor->allcaps = $caps;
$GLOBALS['current_user'] = $actor;

global $wpdb;
$beforeOptions = $wpdb->get_results("SELECT option_name, option_value, autoload FROM {$wpdb->options}", OBJECT_K);
$beforeRoles = wp_roles()->roles;
$passwordMeta = get_user_meta(1, '_application_passwords', true);
$temporaryPostIds = [];
$temporaryUserIds = [];
$capturedErrors = [];
set_error_handler(static function ($level, $message) use (&$capturedErrors) {
    if (($level & error_reporting()) && ($level & (E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE))) {
        $capturedErrors[] = $message;
    }
    return false;
});

try {
    get_role('administrator')->add_cap('manage_capabilities', true);
    require_once dirname($argv[3]) . '/functions-admin.php';
    $check(!array_diff(pp_capabilities_settings_options(), array_keys(FeatureOperations::settingFields())), 'Every built-in settings option has a typed MCP field');
    require __DIR__ . '/settings-format-cases.php';
    $catalog = $call('list-features');
    $check(!is_wp_error($catalog) && count($catalog['features']) >= 14, 'Every registered menu is discoverable');
    $check(!array_diff(array_keys(pp_capabilities_sub_menu_lists(true)), array_column($catalog['features'], 'feature')), 'Feature discovery includes every menu slug from the plugin registry');

    foreach (['editor-features', 'admin-features', 'admin-styles', 'admin-notices', 'admin-menus', 'profile-features', 'redirects', 'frontend-features', 'nav-menus'] as $feature) {
        $check(!is_wp_error($call('set-feature-enabled', ['feature' => $feature, 'enabled' => true])), 'Enable feature: ' . $feature);
    }
    require __DIR__ . '/feature-format-cases.php';
    $create = $call('create-role', ['slug' => 'mcp_test_role', 'name' => 'MCP Test Role', 'source_role' => 'subscriber']);
    $check(!is_wp_error($create) && get_role('mcp_test_role')->has_cap('read'), 'Create role from an existing source');
    $check(is_wp_error($call('create-role', ['slug' => 'mcp_test_role', 'name' => 'Duplicate'])), 'Duplicate role creation is rejected');
    $rename = $call('rename-role', ['role' => 'mcp_test_role', 'name' => 'Renamed role']);
    $check(!is_wp_error($rename) && wp_roles()->role_names['mcp_test_role'] === 'Renamed role', 'Rename preserves the role slug');
    $changes = ['role' => 'mcp_test_role', 'changes' => [['capability' => 'mcp_custom_cap', 'action' => 'grant']]];
    $preview = $call('update-role-capabilities', $changes + ['dry_run' => true]);
    $check(!is_wp_error($preview) && !$preview['applied'] && !get_role('mcp_test_role')->has_cap('mcp_custom_cap'), 'Capability preview makes no changes');
    $patch = $call('update-role-capabilities', $changes);
    $check(!is_wp_error($patch) && get_role('mcp_test_role')->has_cap('mcp_custom_cap'), 'Capability grant is persisted');
    $deny = $call('update-role-capabilities', ['role' => 'mcp_test_role', 'changes' => [['capability' => 'mcp_custom_cap', 'action' => 'deny']]]);
    $check(!is_wp_error($deny) && get_role('mcp_test_role')->capabilities['mcp_custom_cap'] === false, 'Explicit capability denial is retained');
    $remove = $call('update-role-capabilities', ['role' => 'mcp_test_role', 'changes' => [['capability' => 'mcp_custom_cap', 'action' => 'remove']]]);
    $check(!is_wp_error($remove) && !array_key_exists('mcp_custom_cap', get_role('mcp_test_role')->capabilities), 'Capability removal differs from explicit denial');
    $lockout = $call('update-role-capabilities', ['role' => 'administrator', 'changes' => [['capability' => 'manage_capabilities', 'action' => 'deny']]]);
    $check(is_wp_error($lockout) && get_role('administrator')->has_cap('manage_capabilities'), 'Administrator access cannot be removed');
    $actor->allcaps['manage_capabilities'] = false;
    $check(is_wp_error($call('update-role-capabilities', $changes)), 'Administrator role does not bypass an explicitly denied action capability');
    $actor->allcaps['manage_capabilities'] = true;
    $check(!is_wp_error($call('list-capabilities')) && !is_wp_error($call('compare-roles', ['role' => 'subscriber', 'other_role' => 'editor'])), 'Capability discovery and role comparison execute');

    $newUser = wp_insert_user([
        'user_login' => 'mcp_' . str_replace('-', '', wp_generate_uuid4()),
        'user_pass' => wp_generate_password(32), 'role' => 'subscriber',
    ]);
    $check(!is_wp_error($newUser), 'Create a disposable target user for role assignment');
    $temporaryUserIds[] = (int) $newUser;
    $roleRequest = ['user_id' => (int) $newUser, 'roles' => ['author'], 'dry_run' => true];
    $check(is_wp_error($call('update-user-roles', $roleRequest)), 'User-role assignment requires promotion permission');
    $actor->allcaps['promote_users'] = true;
    update_option('cme_capabilities_edit_user_multi_roles', '');
    $check(is_wp_error($call('update-user-roles', $roleRequest)), 'Multiple-role editing must be enabled before assignment');
    update_option('cme_capabilities_edit_user_multi_roles', '1');
    $rolePreview = $call('update-user-roles', $roleRequest);
    $check(!is_wp_error($rolePreview) && !$rolePreview['applied'] && get_userdata($newUser)->roles === ['subscriber'], 'User-role dry-run preserves the target roles');
    $check(is_wp_error($call('update-user-roles', ['user_id' => (int) $newUser, 'roles' => ['missing_role']])), 'Unknown assignment roles are rejected');
    add_role('bbp_participant', 'Forum participant', ['read' => true]);
    $forumUser = new WP_User($newUser);
    $forumUser->add_role('bbp_participant');
    $assigned = $call('update-user-roles', ['user_id' => (int) $newUser, 'roles' => ['author']]);
    $check(!is_wp_error($assigned) && in_array('author', get_userdata($newUser)->roles, true) && in_array('bbp_participant', get_userdata($newUser)->roles, true), 'User-role assignment preserves forum roles');
    $check($assigned['after'] === array_values(get_userdata($newUser)->roles), 'Role assignment returns the saved role order');
    update_option('pp_capabilities_mcp_test_role_role_option', ['disable_role' => 1]);
    $check(is_wp_error($call('update-user-roles', ['user_id' => (int) $newUser, 'roles' => ['mcp_test_role']])), 'Disabled roles cannot be newly assigned');
    update_option('pp_capabilities_mcp_test_role_role_option', []);
    $actor->allcaps['edit_users'] = false;
    $check(is_wp_error($call('update-user-roles', ['user_id' => (int) $newUser, 'roles' => ['subscriber']])), 'Role assignment requires permission to edit the target user');
    $actor->allcaps['edit_users'] = true;
    require_once dirname($argv[3]) . '/admin-load.php';
    $savedRequest = $_REQUEST;
    $savedPost = $_POST;
    try {
        $_REQUEST['_wpnonce'] = wp_create_nonce('update-user_' . $newUser);
        $_POST['pp_roles'] = ['subscriber'];
        $profileController = (new ReflectionClass('PP_Capabilities_Admin_UI'))->newInstanceWithoutConstructor();
        $profileController->action_profile_update($newUser);
        $check(in_array('subscriber', get_userdata($newUser)->roles, true) && in_array('bbp_participant', get_userdata($newUser)->roles, true), 'The native profile form reuses the shared role-assignment rules');
    } finally {
        $_REQUEST = $savedRequest;
        $_POST = $savedPost;
    }


    $check(is_wp_error($call('update-user-roles', ['user_id' => 1, 'roles' => ['subscriber']])), 'Self-demotion is rejected');
    require __DIR__ . '/grant-policy-cases.php';
    $options = $call('update-role-options', ['role' => 'mcp_test_role', 'settings' => ['disable_code_editor' => true]]);
    $check(!is_wp_error($options) && !empty(get_option('pp_capabilities_mcp_test_role_role_option')['disable_code_editor']), 'Role options are persisted');
    $check(is_wp_error($call('update-role-options', ['role' => 'subscriber', 'settings' => ['disable_role' => true]])), 'Default-role disabling is rejected');
    $check(is_wp_error($call('delete-role', ['role' => 'editor'])), 'System-role deletion is rejected');

    foreach (['editor-features', 'admin-features', 'frontend-features', 'profile-features', 'nav-menus'] as $feature) {
        $input = ['feature' => $feature, 'role' => 'mcp_test_role', 'settings' => ['disabled' => []]];
        $result = $call('update-feature-settings', $input);
        $check(!is_wp_error($result), 'Save restrictions: ' . $feature . (is_wp_error($result) ? ' ' . $result->get_error_message() : ''));
    }
    foreach (['editor-features', 'admin-features', 'frontend-features', 'profile-features', 'nav-menus', 'admin-menus', 'admin-styles'] as $feature) {
        $result = $call('get-feature-choices', ['feature' => $feature, 'role' => 'mcp_test_role']);
        $check(!is_wp_error($result), 'Discover feature choices: ' . $feature . (is_wp_error($result) ? ' ' . $result->get_error_message() : ''));
    }
    foreach (array_keys(FeatureOperations::definitions()) as $feature) {
        $capability = 'manage_capabilities_' . str_replace('-', '_', $feature);
        $actor->allcaps[$capability] = false;
        $check(is_wp_error($call('get-feature-choices', ['feature' => $feature, 'role' => 'mcp_test_role'])), 'Discovery requires the screen capability: ' . $feature);
        $check(is_wp_error($call('get-feature-settings', ['feature' => $feature, 'role' => 'mcp_test_role'])), 'Saved data requires the screen capability: ' . $feature);
        $actor->allcaps[$capability] = true;
        $call('set-feature-enabled', ['feature' => $feature, 'enabled' => false]);
        $check(is_wp_error($call('get-feature-choices', ['feature' => $feature, 'role' => 'mcp_test_role'])), 'Disabled screen data cannot be discovered: ' . $feature);
        $call('set-feature-enabled', ['feature' => $feature, 'enabled' => true]);
    }
    $check(is_wp_error($call('get-feature-choices', ['feature' => 'profile-features'])), 'Profile discovery requires an explicit existing role');
    $check(is_wp_error($call('get-feature-choices', ['feature' => 'editor-features', 'post_type' => 'missing-type'])), 'Editor discovery rejects unsupported post types');
    $editorChoices = $call('get-feature-choices', ['feature' => 'editor-features']);
    $check($editorChoices['choices']->{__('Top Bar - Right', 'capability-manager-enhanced')}['publish']['restriction_value'] === 'publish', 'Editor choices expose the native saved restriction keys');
    $redirectChoices = $call('get-feature-choices', ['feature' => 'redirects']);
    $check(isset($redirectChoices['choices']->discovery_context['settings_fields']->login_redirect), 'Settings-only screens expose their supported fields');
    $notices = $call('update-feature-settings', ['feature' => 'admin-notices', 'role' => 'mcp_test_role', 'settings' => ['notice_type_remove' => ['info'], 'enable_toolbar_access' => true]]);
    $check(!is_wp_error($notices) && get_option('cme_admin_notice_options')['mcp_test_role']['notice_type_remove'] === ['info'], 'Admin notices use their existing saved format');
    delete_option('ppc_admin_menus_menu');
    delete_option('ppc_admin_menus_submenu');
    $missingMenus = $call('get-feature-choices', ['feature' => 'admin-menus']);
    $check(!$missingMenus['choices']->discovery->available && !empty($missingMenus['choices']->discovery->refresh_url), 'Missing menu snapshot provides recovery instructions');
    $menuBefore = get_option('capsman_admin_menus');
    $check(is_wp_error($call('update-feature-settings', ['feature' => 'admin-menus', 'role' => 'mcp_test_role', 'settings' => ['disabled_menus' => ['menu-posts']]])) && get_option('capsman_admin_menus') === $menuBefore, 'Guessed menu identifiers fail without changing settings');
    require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    $oldScreen = $GLOBALS['current_screen'] ?? null;
    $oldMenu = $GLOBALS['menu'] ?? null;
    $oldSubmenu = $GLOBALS['submenu'] ?? null;
    set_current_screen('dashboard');
    $GLOBALS['menu'] = [
        5 => ['<span>Posts</span>', 'edit_posts', 'edit.php', '', 'menu-top', 'menu-posts'],
        6 => ['Tools', 'edit_posts', 'tools.php'],
        7 => ['', 'read', 'separator1'],
        8 => ['Customizer', 'edit_theme_options', 'customize.php?return=private-value'],
        9 => ['Sensitive action', 'read', 'action.php?_wpnonce=secret'],
    ];
    $GLOBALS['submenu'] = [
        'edit.php' => [['Categories', 'manage_categories', 'edit-tags.php?taxonomy=category']],
        'tools.php' => [['Categories tool', 'manage_options', 'edit-tags.php?taxonomy=category']],
        'themes.php' => [['Customizer', 'edit_theme_options', 'customize.php?return=private-value']],
    ];
    $capturePriority = has_action('admin_menu', [\PublishPress\Capabilities\Mcp\MenuDiscovery::class, 'capture']);
    $check($capturePriority === PHP_INT_MAX - 99, 'Capture runs after custom menus and before menu restrictions');
    $check(has_action('wp_dashboard_setup', [\PublishPress\Capabilities\Mcp\InterfaceCapture::class, 'dashboard']) === 98, 'Dashboard capture runs before widget restrictions');
    \PublishPress\Capabilities\Mcp\MenuDiscovery::capture();
    $choices = $call('get-feature-choices', ['feature' => 'admin-menus'])['choices'];
    $ids = array_column($choices->discovery->menus, 'restriction_value');
    $check(in_array('edit.php', $ids, true) && !in_array('menu-posts', $ids, true), 'Discovery returns actual menu slugs rather than DOM identifiers');
    $check($choices->discovery->menus[0]['label'] === 'Posts', 'Menu labels omit HTML');
    $check(!in_array('customize.php', $ids, true) && strpos(wp_json_encode($choices), 'secret') === false && strpos(wp_json_encode($choices), 'private-value') === false, 'Discovery excludes nonce links and transient Customizer return URLs');
    $childIds = array_column($choices->discovery->submenus, 'restriction_value');
    $check(in_array('themes.php|customize.php', $childIds, true), 'Customizer submenu uses the native normalized restriction identifier');
    $check(in_array('edit.php|edit-tags.php?taxonomy=category', $childIds, true) && in_array('tools.php|edit-tags.php?taxonomy=category', $childIds, true), 'Submenu identifiers preserve their actual parent');
    unset($actor->allcaps['manage_capabilities_admin_menus']);
    $GLOBALS['menu'] = [5 => ['Changed', 'read', 'changed.php']];
    \PublishPress\Capabilities\Mcp\MenuDiscovery::capture();
    $check(get_option('ppc_admin_menus_menu')[5][2] === 'edit.php', 'Unauthorized visits cannot replace menu snapshots');
    $actor->allcaps['manage_capabilities_admin_menus'] = true;
    $GLOBALS['current_screen'] = $oldScreen;
    $GLOBALS['menu'] = $oldMenu;
    $GLOBALS['submenu'] = $oldSubmenu;
    $preview = $call('update-feature-settings', ['feature' => 'admin-menus', 'role' => 'mcp_test_role', 'dry_run' => true, 'settings' => ['disabled_submenus' => ['edit.php|edit-tags.php?taxonomy=category']]]);
    $check(!is_wp_error($preview), 'Discovered submenu identifiers validate in dry runs');
    $check(is_wp_error($call('update-feature-settings', ['feature' => 'admin-menus', 'role' => 'mcp_test_role', 'dry_run' => true, 'settings' => ['disabled_submenus' => ['edit.php|missing.php']]])), 'Dry runs reject unknown submenu identifiers');
    $savedMenus = (array) get_option('capsman_admin_menus', []);
    $savedMenus['mcp_test_role'] = ['legacy-removed-plugin'];
    update_option('capsman_admin_menus', $savedMenus);
    $keep = $call('update-feature-settings', ['feature' => 'admin-menus', 'role' => 'mcp_test_role', 'settings' => ['disabled_submenus' => []]]);
    $check(!is_wp_error($keep), 'Existing undiscovered restrictions can be retained');
    $remove = $call('update-feature-settings', ['feature' => 'admin-menus', 'role' => 'mcp_test_role', 'settings' => ['disabled_menus' => []]]);
    $check(!is_wp_error($remove), 'Existing undiscovered restrictions can be removed');
    $beforeMenuBulk = get_option('capsman_admin_menus');
    $badMenuBulk = $call('bulk-update-feature-settings', ['feature' => 'admin-menus', 'roles' => ['mcp_test_role', 'subscriber'], 'settings' => ['disabled_menus' => ['not-discovered.php']]]);
    $check(is_wp_error($badMenuBulk) && get_option('capsman_admin_menus') === $beforeMenuBulk, 'Unknown bulk menu identifiers fail before any role changes');
    $menus = $call('update-feature-settings', ['feature' => 'admin-menus', 'role' => 'mcp_test_role', 'settings' => ['disabled_menus' => ['edit.php'], 'disabled_submenus' => []]]);
    $check(!is_wp_error($menus) && get_option('capsman_admin_menus')['mcp_test_role'] === ['edit.php'], 'Admin menu restrictions persist');
    $redirect = $call('update-feature-settings', ['feature' => 'redirects', 'role' => 'mcp_test_role', 'settings' => ['custom_redirect' => true, 'login_redirect' => '/welcome']]);
    $check(!is_wp_error($redirect), 'Site-local redirects save successfully');
    $check(is_wp_error($call('update-feature-settings', ['feature' => 'redirects', 'role' => 'mcp_test_role', 'settings' => ['login_redirect' => 'https://unrelated.example/']])), 'External redirect targets are rejected');
    $check(is_wp_error($call('update-feature-settings', ['feature' => 'admin-features', 'role' => 'mcp_test_role', 'settings' => ['blogname' => 'bad']])), 'Feature actions cannot modify arbitrary options');
    $call('set-feature-enabled', ['feature' => 'admin-features', 'enabled' => false]);
    $check(is_wp_error($call('get-feature-settings', ['feature' => 'admin-features', 'role' => 'mcp_test_role'])), 'Disabled features reject their actions');
    $call('set-feature-enabled', ['feature' => 'admin-features', 'enabled' => true]);

    $element = $call('save-interface-element', ['feature' => 'profile-features', 'role' => 'mcp_test_role', 'label' => 'Contact section', 'selector' => '#contact-info']);
    $check(!is_wp_error($element), 'Create a profile element');
    $profileChoices = $call('get-feature-choices', ['feature' => 'profile-features', 'role' => 'mcp_test_role']);
    $check($profileChoices['choices']->{$element['id']}['restriction_value'] === '#contact-info', 'Profile discovery returns the selector used by role restrictions');
    $otherProfile = $call('get-feature-choices', ['feature' => 'profile-features', 'role' => 'subscriber']);
    $check(!isset($otherProfile['choices']->{$element['id']}), 'Profile discovery stays within the selected role');
    $check(!is_wp_error($call('delete-interface-element', ['feature' => 'profile-features', 'role' => 'mcp_test_role', 'id' => $element['id']])), 'Delete a profile element');
    $frontend = $call('save-interface-element', ['feature' => 'frontend-features', 'label' => 'Footer', 'selector' => 'footer', 'pages' => ['whole_site']]);
    $check(!is_wp_error($frontend), 'Create a frontend element');
    $frontendChoices = $call('get-feature-choices', ['feature' => 'frontend-features']);
    $check($frontendChoices['choices']->{$frontend['id']}['restriction_value'] === $frontend['id'] && !empty($frontendChoices['choices']->{$frontend['id']}['pages']), 'Frontend discovery returns saved IDs and page scopes');
    $check(!is_wp_error($call('delete-interface-element', ['feature' => 'frontend-features', 'id' => $frontend['id']])), 'Delete a frontend element');
    $check(!is_wp_error($call('update-admin-menu-presentation', ['action' => 'rename', 'role' => 'mcp_test_role', 'menu' => 'edit.php', 'title' => 'Articles'])), 'Rename an admin menu');
    $style = $call('update-feature-settings', ['feature' => 'admin-styles', 'role' => 'mcp_test_role', 'settings' => ['admin_replace_howdy' => 'Hello', 'hide_color_scheme_ui' => true]]);
    $check(!is_wp_error($style), 'Admin styles reuse the existing settings sanitizer');
    $settings = $call('update-plugin-settings', ['settings' => ['cme_capabilities_add_user_multi_roles' => true]]);
    $check(!is_wp_error($settings) && get_option('cme_capabilities_add_user_multi_roles') === '1', 'Plugin settings use the UI storage format');
    $check(!is_wp_error($call('get-type-capability-settings')), 'Discover post-type and taxonomy capability settings');
    $typeSettings = $call('update-type-capability-settings', ['settings' => ['post_types' => ['post', 'page'], 'taxonomies' => ['category'], 'detailed_taxonomies' => ['category']]]);
    $check(!is_wp_error($typeSettings) && !empty(get_option('cme_detailed_taxonomies')['category']), 'Save type-specific and detailed taxonomy settings');
    $check(is_wp_error($call('update-type-capability-settings', ['settings' => ['post_types' => ['missing_type']]])), 'Unknown post types are rejected before writes');
    $level = $call('set-role-level', ['role' => 'mcp_test_role', 'level' => 2]);
    $check(!is_wp_error($level) && get_role('mcp_test_role')->has_cap('level_2'), 'Legacy role levels use capability patch validation');
    $check(is_wp_error($call('set-feature-enabled', ['feature' => 'roles', 'enabled' => true, 'network_sync' => true])), 'Single-site requests cannot queue network-wide changes');
    $check(!wp_has_ability('publishpress-capabilities/sync-role-network'), 'Network-only action is absent on single-site installations');
    $settingsCases = [
        'Enable Multiples roles on User Edit screen under Capabilities Settings Roles' => 'cme_capabilities_edit_user_multi_roles',
        'Enable multiple roles on Add New User' => 'cme_capabilities_add_user_multi_roles',
        'Enable Show private post types under Capabilities Settings Editor Features' => 'cme_editor_features_private_post_type',
    ];
    foreach ($settingsCases as $prompt => $field) {
        $beforeSettings = $call('get-plugin-settings')['settings'];
        $savedSetting = $call('update-plugin-settings', ['settings' => [$field => true]]);
        $afterSettings = $call('get-plugin-settings');
        $check(!is_wp_error($savedSetting) && $afterSettings['settings']->$field, 'Prompt setting can be saved and read back: ' . $prompt);
        $check(!empty($afterSettings['schema']->$field['description']), 'Prompt setting has a discoverable field description: ' . $field);
        foreach ((array) $beforeSettings as $other => $value) {
            if ($other !== $field && $afterSettings['settings']->$other !== $value) {
                throw new RuntimeException('Updating a prompt setting changed an unrelated field.');
            }
        }
    }
    $profileOptions = $call('update-role-options', ['role' => 'mcp_test_role', 'settings' => ['role_editor' => ['block_editor']]]);
    $check(!is_wp_error($profileOptions) && get_option('pp_capabilities_mcp_test_role_role_option')['role_editor'] === ['block_editor'], 'Editor preferences match the existing UI keys');
    $user = $call('get-user-capabilities', ['user_id' => 1]);
    $check(!is_wp_error($user) && $user['user_id'] === 1, 'Inspect a user’s effective permissions');
    $userAccess = $call('check-user-capability', ['user_id' => 1, 'capability' => 'read']);
    $check(!is_wp_error($userAccess) && $userAccess['allowed'], 'Check effective user capability without identity switching');
    $check(is_wp_error($call('test-user-access', ['user_id' => 1, 'capability' => 'read'])), 'User Testing preserves its own-user restriction');
    $check(is_wp_error($call('update-admin-menu-presentation', ['action' => 'rename'])), 'Incomplete menu operations return structured errors');
    $guest = $call('update-feature-settings', ['feature' => 'frontend-features', 'role' => 'ppc_guest', 'settings' => ['disabled' => []]]);
    $check(!is_wp_error($guest), 'Frontend restrictions support logged-out visitors');
    $createdStyle = $call('save-admin-style', ['name' => 'MCP test style', 'settings' => ['custom_scheme_base' => '#112233', 'custom_scheme_text' => '#ffffff']]);
    $check(!is_wp_error($createdStyle), 'Create a sanitized custom style and generate its CSS');
    if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
        $extraStyle = $call('save-admin-style', ['name' => 'Second style', 'settings' => []]);
        $check(is_wp_error($extraStyle), 'Free custom-style limit is enforced');
        $check(!wp_has_ability('publishpress-capabilities/manage-custom-admin-menu'), 'Pro-only custom menu action is absent in Free');
    } else {
        $menu = $call('manage-custom-admin-menu', ['action' => 'save', 'type' => 'menu', 'slug' => 'mcp-example', 'title' => 'Example', 'capability' => 'read', 'position_after' => 'tools.php']);
        $check(!is_wp_error($menu) && isset(get_option('ppc_admin_menus_custom_menus')['menus']['mcp-example']), 'Pro custom menus use the existing saved format');
        $check(get_option('ppc_admin_menus_custom_menus')['menus']['mcp-example']['menu_position_after'] === 'tools.php', 'Custom menus retain position-after placement');
        update_option('ppc_feature_post_metaboxes_data', ['post' => ['mcp-box' => ['id' => 'mcp-box', 'title' => 'Saved metabox']]]);
        $editorChoices = $call('get-feature-choices', ['feature' => 'editor-features', 'post_type' => 'post']);
        $check(!is_wp_error($editorChoices) && isset($editorChoices['choices']->captured_metaboxes->{'mcp-box'}), 'Saved metabox metadata is discoverable without browser capture');

        $check(!is_wp_error($call('manage-custom-admin-menu', ['action' => 'delete', 'type' => 'menu', 'slug' => 'mcp-example'])), 'Delete a Pro custom menu');
        foreach (['editor-block', 'editor-classic', 'admin-css', 'admin-url'] as $kind) {
            $rule = $call('save-custom-rule', ['kind' => $kind, 'label' => 'MCP rule', 'value' => $kind === 'admin-url' ? '/wp-admin/tools.php' : '.mcp-test', 'wildcard' => true]);
            $check(!is_wp_error($rule), 'Create Pro custom restriction: ' . $kind);
            $check(!is_wp_error($call('list-custom-rules', ['kind' => $kind])), 'List Pro custom restriction: ' . $kind);
            if ($kind === 'editor-block' || $kind === 'editor-classic') {
                $editorChoices = $call('get-feature-choices', ['feature' => 'editor-features', 'post_type' => 'post', 'editor' => $kind === 'editor-classic' ? 'classic' : 'block']);
                $check(!is_wp_error($editorChoices) && in_array($rule['id'], array_column($editorChoices['choices']->custom_rules, 'id'), true), 'Editor discovery includes saved custom selectors: ' . $kind);
            } else {
                $adminChoices = $call('get-feature-choices', ['feature' => 'admin-features']);
                $group = $kind === 'admin-css' ? 'css' : 'urls';
                $check(!is_wp_error($adminChoices) && in_array($rule['restriction_value'], array_column($adminChoices['choices']->custom_rules[$group], 'restriction_value'), true), 'Admin discovery includes saved custom rules: ' . $kind);
            }

            $check(!is_wp_error($call('delete-custom-rule', ['kind' => $kind, 'id' => $rule['id']])), 'Delete Pro custom restriction: ' . $kind);
        }
        $postElement = $call('save-interface-element', ['feature' => 'frontend-features', 'label' => 'Post element', 'selector' => '.mcp-post', 'pages' => ['single_pages']]);
        $postId = wp_insert_post(['post_title' => 'MCP override fixture', 'post_status' => 'draft', 'post_author' => 1]);
        $temporaryPostIds[] = $postId;
        $override = $call('set-post-frontend-elements', ['post_id' => $postId, 'elements' => [$postElement['id']]]);
        $check(!is_wp_error($override) && get_post_meta($postId, '_ppc_frontendelements', true) === [$postElement['id']], 'Pro per-post frontend overrides persist with edit-post permission');
        $actor->allcaps['edit_posts'] = false;
        $check(is_wp_error($call('get-post-frontend-elements', ['post_id' => $postId])), 'Pro post override inspection enforces object permissions');
        $actor->allcaps['edit_posts'] = true;
    }
    $check(!is_wp_error($call('delete-admin-style', ['slug' => $createdStyle['slug']])), 'Delete custom style and generated CSS');
    $capture = $call('prepare-profile-capture', ['enabled' => true, 'role' => 'mcp_test_role']);
    $check(!is_wp_error($capture) && $capture['expires'] > current_time('timestamp') && strpos($capture['capture_url'], 'pp-capabilities-profile-features') !== false, 'Prepare the existing profile capture window and browser page');
    $check(!is_wp_error($call('prepare-profile-capture', ['enabled' => false])) && !get_option('capsman_profile_features_role'), 'Disable profile capture access');
    $check(!is_wp_error($call('update-admin-feature-ui-settings', ['hide_submenu' => true])) && !empty(get_option('ppc_admin_features_settings')['hide_submenu']), 'Admin Features presentation preference is supported');
    $uiChoices = $call('get-feature-choices', ['feature' => 'admin-features']);
    $check(!is_wp_error($uiChoices) && $uiChoices['choices']->ui_settings->hide_submenu, 'Admin Features presentation settings are readable');

    $previewSettings = $call('update-feature-settings', ['feature' => 'admin-features', 'role' => 'mcp_test_role', 'settings' => ['disabled' => ['ppc_adminbar||updates']], 'dry_run' => true]);
    $check(!is_wp_error($previewSettings) && empty(get_option('capsman_disabled_admin_features')['mcp_test_role']), 'Feature preview does not persist settings');
    $bulk = $call('bulk-update-feature-settings', ['feature' => 'admin-features', 'roles' => ['mcp_test_role', 'subscriber'], 'settings' => ['disabled' => ['ppc_adminbar||updates']]]);
    $check(!is_wp_error($bulk) && get_option('capsman_disabled_admin_features')['subscriber'] === ['ppc_adminbar||updates'], 'Bulk feature settings apply to all validated roles');
    $beforeBulk = get_option('capsman_disabled_admin_features');
    $badBulk = $call('bulk-update-feature-settings', ['feature' => 'admin-features', 'roles' => ['mcp_test_role', 'missing_role'], 'settings' => ['disabled' => []]]);
    $check(is_wp_error($badBulk) && get_option('capsman_disabled_admin_features') === $beforeBulk, 'Invalid bulk targets fail before any changes');
    $call('update-plugin-settings', ['settings' => ['cme_capabilities_application_password_capabilities' => true]]);
    $password = WP_Application_Passwords::create_new_application_password(1, ['name' => 'MCP test fixture']);
    $check(!is_wp_error($password), 'Create a temporary application-password fixture');
    $subject = pp_capabilities_application_password_subject_value(1, $password[1]['uuid']);
    $scopes = $call('list-application-password-scopes');
    $check(!is_wp_error($scopes) && strpos(wp_json_encode($scopes), $password[0]) === false, 'Application-password discovery does not return password secrets');
    $scope = $call('update-application-password-scope', ['subject' => $subject, 'denied' => ['manage_capabilities']]);
    $check(!is_wp_error($scope), 'Application-password restrictions reuse the existing scope service'
        . (is_wp_error($scope) ? ': ' . $scope->get_error_message() : ''));
    do_action('application_password_did_authenticate', get_userdata(1), $password[1]);
    $check(is_wp_error($call('list-roles')), 'Real application-password capability filter denies a restricted administrator');
    update_option(pp_capabilities_application_password_option_name(), []);
    foreach (array_keys(FeatureOperations::definitions()) as $feature) {
        $capability = 'manage_capabilities_' . str_replace('-', '_', $feature);
        $scope = $call('update-application-password-scope', ['subject' => $subject, 'denied' => [$capability]]);
        $check(!is_wp_error($scope) && is_wp_error($call('get-feature-choices', ['feature' => $feature, 'role' => 'mcp_test_role']))
            && is_wp_error($call('get-feature-settings', ['feature' => $feature, 'role' => 'mcp_test_role'])), 'Application-password scope protects feature data: ' . $feature);
    }
    update_option(pp_capabilities_application_password_option_name(), []);

    if (defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
        $branding = $call('update-plugin-settings', ['settings' => ['cme_display_branding' => false]]);
        $check(!is_wp_error($branding) && !$branding['settings']->cme_display_branding, 'Pro branding preference uses typed settings');
        $statusGuard = $call('update-plugin-settings', ['settings' => ['cme_custom_status_control' => true]]);
        $check(is_wp_error($statusGuard), 'Pro status settings reject unavailable integration dependencies');
    } else {
        $check(is_wp_error($call('update-plugin-settings', ['settings' => ['cme_display_branding' => false]])), 'Free settings reject Pro-only fields');
    }
    $backup = $call('create-backup');
    $check(!is_wp_error($backup) && $backup['backup'] === 'capsman_backup', 'Create full plugin backup in the existing UI slot');
    $check(!is_wp_error($call('list-backups')), 'List saved backups');
    $export = $call('export-configuration');
    $check(!is_wp_error($export) && isset($export['configuration']->user_roles), 'Export includes role definitions');
    $check(is_wp_error($call('import-configuration', ['configuration' => ['blogname' => 'not allowed']])), 'Import rejects unsupported WordPress settings');
    $selectedExport = $call('export-configuration', ['sections' => ['user_roles'], 'roles' => ['subscriber']]);
    $check(!is_wp_error($selectedExport) && array_keys($selectedExport['configuration']->user_roles['roles']) === ['subscriber'], 'Selective export limits output to selected roles');
    $selectedPreview = $call('import-configuration', ['configuration' => (array) $selectedExport['configuration'], 'dry_run' => true]);
    $check(!is_wp_error($selectedPreview) && !$selectedPreview['applied'], 'Selected-role exports round-trip through import validation');
    $rolesBeforeSelectedImport = wp_roles()->roles;
    $selectedImport = $call('import-configuration', ['configuration' => (array) $selectedExport['configuration']]);
    $unselectedRoles = wp_roles()->roles;
    unset($rolesBeforeSelectedImport['subscriber'], $unselectedRoles['subscriber']);
    $check(!is_wp_error($selectedImport) && $unselectedRoles === $rolesBeforeSelectedImport
        && get_role('subscriber')->has_cap('read'), 'Selected-role imports preserve unselected roles');

    $check(is_wp_error($call('export-configuration', ['sections' => ['blogname']])), 'Selective export rejects non-plugin sections');
    $check(is_wp_error($call('export-configuration', ['sections' => ['user_roles'], 'roles' => ['missing_role']])), 'Selective export rejects unknown roles');
    $restore = $call('restore-backup', ['backup' => 'capsman_backup', 'dry_run' => true]);
    $check(!is_wp_error($restore) && !$restore['applied'], 'Backup restore supports validation without mutation');
    $restored = $call('restore-backup', ['backup' => 'capsman_backup']);
    $check(!is_wp_error($restored) && $restored['applied'], 'Restore a validated full plugin backup');
    $check(!is_wp_error($call('delete-role', ['role' => 'mcp_test_role'])) && !get_role('mcp_test_role'), 'Delete custom role through the plugin role manager');
    $reset = $call('reset-roles');
    $check(is_wp_error($reset) && get_role('administrator')->has_cap('manage_capabilities'), 'MCP reset cannot bypass the grant policy; native recovery remains available');
    $check(!$capturedErrors, 'Feature execution produces no PHP notices or warnings: ' . implode('; ', $capturedErrors));
    echo "Completed $checks feature integration checks with WordPress $wp_version.\n";
} finally {
    restore_error_handler();
    foreach ($temporaryPostIds as $postId) {
        wp_delete_post($postId, true);
    }
    require_once ABSPATH . 'wp-admin/includes/user.php';
    foreach ($temporaryUserIds as $userId) {
        wp_delete_user($userId);
    }
    // The fixture has an explicit private table prefix; restore its options exactly.
    $now = $wpdb->get_col("SELECT option_name FROM {$wpdb->options}");
    foreach ($now as $name) {
        if (!isset($beforeOptions[$name])) {
            delete_option($name);
        }
    }
    foreach ($beforeOptions as $name => $option) {
        update_option($name, maybe_unserialize($option->option_value), $option->autoload);
    }
    if ($passwordMeta === '') {
        delete_user_meta(1, '_application_passwords');
    } else {
        update_user_meta(1, '_application_passwords', $passwordMeta);
    }
    wp_roles()->for_site();
}
