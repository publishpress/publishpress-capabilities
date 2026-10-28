<?php
/** MCP grant regressions; included only by the isolated domain smoke suite. */
$protected = ['administrator', 'manage_options', 'edit_users', 'promote_users', 'create_users', 'delete_users', 'remove_users',
    'activate_plugins', 'install_plugins', 'update_plugins', 'delete_plugins', 'edit_plugins', 'resume_plugins',
    'switch_themes', 'edit_themes', 'install_themes', 'update_themes', 'delete_themes', 'resume_themes', 'edit_files',
    'update_core', 'unfiltered_html', 'unfiltered_upload', 'manage_network', 'manage_sites', 'create_sites', 'delete_sites',
    'upgrade_network', 'setup_network', 'manage_network_users', 'manage_network_plugins', 'manage_network_themes',
    'manage_network_options', 'manage_capabilities', 'manage_capabilities_roles', 'manage_capabilities_future'];
$protected = array_merge($protected, ['add_users', 'promote_user', 'edit_user', 'delete_user', 'deactivate_plugins', 'activate_plugin', 'deactivate_plugin', 'resume_plugin', 'resume_theme', 'upload_plugins', 'upload_themes', 'install_languages', 'update_languages', 'edit_css', 'customize', 'delete_site', 'update_php', 'update_https', 'manage_privacy_options', 'export_others_personal_data', 'erase_others_personal_data']);
add_role('mcp_guard_role', 'Guard fixture', ['read' => true]);
$baseline = wp_roles()->roles;
foreach ($protected as $capability) {
    foreach ([false, true] as $preview) {
        $result = $call('update-role-capabilities', ['role' => 'mcp_guard_role', 'changes' => [
            ['capability' => 'edit_posts', 'action' => 'grant'], ['capability' => $capability, 'action' => 'grant'],
        ], 'dry_run' => $preview]);
        $check(is_wp_error($result) && wp_roles()->roles === $baseline, 'Reject mixed protected grant without partial writes: ' . $capability);
    }
}
get_role('mcp_guard_role')->add_cap('manage_options', false);
$check(is_wp_error($call('update-role-capabilities', ['role' => 'mcp_guard_role', 'changes' => [['capability' => 'manage_options', 'action' => 'grant']]])), 'A denied capability is still a new grant when enabled');
get_role('mcp_guard_role')->add_cap('manage_options', true);
$check(!is_wp_error($call('update-role-capabilities', ['role' => 'mcp_guard_role', 'changes' => [['capability' => 'manage_options', 'action' => 'remove']]])), 'Removing a preexisting protected grant remains allowed');
$check(!get_role('mcp_guard_role')->has_cap('manage_options'), 'Protected removal is persisted');
$check(!is_wp_error($call('update-role-capabilities', ['role' => 'administrator', 'changes' => [['capability' => 'manage_options', 'action' => 'grant']]])), 'An unchanged administrator grant is allowed');
$check(is_wp_error($call('create-role', ['slug' => 'mcp_admin_clone', 'name' => 'Forbidden clone', 'source_role' => 'administrator'])) && !get_role('mcp_admin_clone'), 'Administrator cloning is rejected before creating a role');
get_role('mcp_guard_role')->add_cap('manage_capabilities_future', true);
$check(is_wp_error($call('create-role', ['slug' => 'mcp_admin_clone', 'name' => 'Forbidden clone', 'source_role' => 'mcp_guard_role'])), 'A custom privileged source cannot bypass clone restrictions');
foreach (['administrator', 'mcp_guard_role', 'editor'] as $role) {
    $before = get_userdata($newUser)->roles;
    foreach ([false, true] as $preview) {
        $check(is_wp_error($call('update-user-roles', ['user_id' => (int) $newUser, 'roles' => [$role], 'dry_run' => $preview])) && get_userdata($newUser)->roles === $before, 'Reject new privileged role assignment: ' . $role);
    }
}
$native = \PublishPress\Capabilities\UserRoleAssignment::update((int) $newUser, ['administrator']);
$check(!is_wp_error($native) && in_array('administrator', get_userdata($newUser)->roles, true), 'Shared wp-admin assignment still permits administrator when authorized');
$keep = $call('update-user-roles', ['user_id' => (int) $newUser, 'roles' => ['administrator', 'subscriber'], 'dry_run' => true]);
$check(!is_wp_error($keep), 'Existing administrator membership can remain while adding a safe role');
\PublishPress\Capabilities\UserRoleAssignment::update((int) $newUser, ['subscriber']);
get_role('mcp_guard_role')->remove_cap('manage_capabilities_future');
$baseline = wp_roles()->roles;
foreach (['manage_options', 'promote_users', 'update_plugins', 'unfiltered_html', 'manage_capabilities_future'] as $capability) {
    $roles = $baseline;
    $roles['mcp_guard_role']['capabilities'][$capability] = true;
    foreach ([false, true] as $selected) {
        $payload = $selected ? ['publishpress_capabilities_export' => ['type' => 'selected_roles', 'version' => 1], 'roles' => ['mcp_guard_role' => $roles['mcp_guard_role']]] : $roles;
        foreach ([false, true] as $preview) {
            $check(is_wp_error($call('import-configuration', ['configuration' => ['user_roles' => $payload], 'dry_run' => $preview])) && wp_roles()->roles === $baseline, 'Import cannot bypass protected grants: ' . $capability);
        }
    }
}
$roles = $baseline;
$roles['mcp_new_admin'] = ['name' => 'Forbidden import', 'capabilities' => ['manage_options' => true]];
$check(is_wp_error($call('import-configuration', ['configuration' => ['user_roles' => $roles]])) && !get_role('mcp_new_admin'), 'Import cannot create a privileged custom role');
$roles = $baseline;
$roles['mcp_guard_role']['capabilities']['create_users'] = true;
update_option('cme_backup_auto_mcp_guard', $roles);
update_option('cme_backup_index', ['cme_backup_auto_mcp_guard']);
$check(is_wp_error($call('restore-backup', ['backup' => 'cme_backup_auto_mcp_guard'])) && wp_roles()->roles === $baseline, 'Backup restoration cannot bypass protected grants');
$check(is_wp_error(\PublishPress\Capabilities\Mcp\AdvancedOperations::networkSync(['role' => 'administrator', 'sync_roles' => true])), 'Privileged network role copies are blocked before queueing');
$check(is_wp_error($call('reset-roles')) && wp_roles()->roles === $baseline, 'MCP recovery cannot introduce grants or partially reset roles');
remove_role('mcp_guard_role');

$check(is_wp_error($call('create-role', ['slug' => 'manage_capabilities_future', 'name' => 'Forbidden role name'])), 'A privileged role slug cannot create an implicit user capability');
add_role('manage_capabilities_future', 'Native role-slug fixture', ['read' => true]);
$check(is_wp_error($call('update-user-roles', ['user_id' => (int) $newUser, 'roles' => ['manage_capabilities_future']])), 'Role names that grant protected primitive capabilities are rejected');
remove_role('manage_capabilities_future');

$mixed = (array) $call('export-configuration')['configuration'];
$definitions = wp_roles()->roles;
$definitions['subscriber']['capabilities']['manage_options'] = true;
unset($mixed['user_roles']);
$mixed['user_roles'] = $definitions;
$writesObserved = 0;
$observe = static function ($value) use (&$writesObserved) { ++$writesObserved; return $value; };
add_filter('pre_update_option', $observe);
try {
    $check(is_wp_error($call('import-configuration', ['configuration' => $mixed])) && $writesObserved === 0, 'Mixed configuration validates every grant before writing any section or backup');
} finally {
    remove_filter('pre_update_option', $observe);
}
