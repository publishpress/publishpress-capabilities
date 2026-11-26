<?php

/**
 * Programmatic role operations shared by PublishPress abilities.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

use PublishPress\Capabilities\Pro;

class RoleOperations
{
    /** @since 2.54.0 */
    public static function canManage(string $capability): bool
    {
        $feature = $capability === 'manage_capabilities' ? 'capabilities'
            : str_replace('_', '-', str_replace('manage_capabilities_', '', $capability));
        return current_user_can($capability) && pp_capabilities_feature_enabled($feature);
    }

    /** @since 2.54.0 */
    public static function editable(string $slug): bool
    {
        if (!current_user_can('administrator') && !(is_multisite() && is_super_admin())) {
            require_once dirname(__DIR__, 2) . '/framework/lib/users.php';
            $actor = wp_get_current_user();
            $role = get_role($slug);
            if (
                $slug === 'administrator' || in_array($slug, $actor->roles, true)
                || ($role && ak_caps2level($role->capabilities) > ak_caps2level($actor->allcaps))
            ) {
                return false;
            }
        }
        return get_role($slug) && function_exists('pp_capabilities_is_editable_role')
            && pp_capabilities_is_editable_role($slug, ['force_refresh' => true, 'publishpress_mcp_role_management' => true]);
    }

    /** @since 2.54.0 */
    public static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error('publishpress_' . $code, $message);
    }

    /** @since 2.54.0 */
    public static function snapshot(): void
    {
        pp_capabilities_autobackup();
    }

    /** @since 2.54.0 */
    public static function saved(string $slug, string $operation): void
    {
        global $capsman;
        if (is_object($capsman) && method_exists($capsman, 'log_db_roles')) {
            $capsman->log_db_roles();
        }
        wp_cache_delete('ppc_role_count_users_cache', 'count');
        do_action('publishpress_capabilities_mcp_role_updated', $slug, $operation);
    }

    /** @since 2.54.0 */
    public static function catalog(array $input): array
    {
        $names = [];
        foreach (wp_roles()->roles as $role) {
            $names = array_merge($names, array_keys($role['capabilities']));
        }
        foreach (get_post_types([], 'objects') as $type) {
            $names = array_merge($names, array_values((array) $type->cap));
        }
        foreach (get_taxonomies([], 'objects') as $taxonomy) {
            $names = array_merge($names, array_values((array) $taxonomy->cap));
        }
        if (ProSettingsOperations::loadHelper()) {
            if (Pro::customStatusPermissionsAvailable() || Pro::presspermitStatusControlActive()) {
                foreach (get_post_stati(['internal' => false, 'public' => false, 'private' => false], 'objects') as $status) {
                    if (!empty($status->_builtin)) {
                        continue;
                    }
                    $names[] = 'status_change_' . str_replace('-', '_', $status->name);
                    foreach (get_post_types([], 'objects') as $type) {
                        foreach (['edit_post', 'delete_post', 'edit_others_posts', 'delete_others_posts', 'set_posts_status'] as $property) {
                            $capability = in_array($property, ['edit_post', 'delete_post'], true)
                                ? $property : ($type->cap->$property ?? '');
                            if ($capability) {
                                $names = array_merge($names, Pro::getStatusCaps($capability, $type->name, $status->name));
                            }
                        }
                    }
                }
            }
        }
        $names = array_values(array_unique($names));
        sort($names);
        $search = $input['search'] ?? '';
        $names = array_values(array_filter($names, static function ($name) use ($search) {
            return $search === '' || stripos($name, $search) !== false;
        }));
        return ['capabilities' => $names];
    }

    /** @since 2.54.0 */
    public static function compare(array $input)
    {
        $left = get_role($input['role']);
        $right = get_role($input['other_role']);
        if (!$left || !$right) {
            return self::error('role_not_found', __('Role not found.', 'capability-manager-enhanced'));
        }
        $differences = [];
        foreach (array_unique(array_merge(array_keys($left->capabilities), array_keys($right->capabilities))) as $name) {
            $a = $left->capabilities[$name] ?? null;
            $b = $right->capabilities[$name] ?? null;
            if ($a !== $b) {
                $differences[] = ['capability' => (string) $name, 'role' => $a, 'other_role' => $b];
            }
        }
        return ['differences' => $differences];
    }

    /** @since 2.54.0 */
    public static function create(array $input)
    {
        $slug = $input['slug'];
        if (GrantPolicy::isProtected($slug)) {
            return GrantPolicy::error();
        }
        if (!preg_match('/^[a-z][a-z0-9_]{1,39}$/D', $slug) || get_role($slug)) {
            return self::error('invalid_role', __('Use an unused role slug, 2–40 lowercase letters, numbers or underscores, starting with a letter.', 'capability-manager-enhanced'));
        }
        if (in_array($slug, self::catalog([])['capabilities'], true)) {
            return self::error('role_collision', __('A capability already uses this name.', 'capability-manager-enhanced'));
        }
        $caps = [];
        if (!empty($input['source_role'])) {
            if (!self::editable($input['source_role'])) {
                return self::error('role_forbidden', __('The source role is not editable.', 'capability-manager-enhanced'));
            }
            $caps = get_role($input['source_role'])->capabilities;
            foreach (array_keys($caps) as $cap) {
                if (!current_user_can('administrator') && !current_user_can($cap)) {
                    unset($caps[$cap]);
                }
            }
        }
        $policy = GrantPolicy::validateCapabilities([], $caps);
        if (is_wp_error($policy)) {
            return $policy;
        }
        $name = sanitize_text_field($input['name']);
        if ($name === '') {
            return self::error('invalid_name', __('A role name is required.', 'capability-manager-enhanced'));
        }
        self::snapshot();
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.custom_role_add_role -- Portable WordPress.org plugin uses the core role API.
        if (!add_role($slug, $name, $caps)) {
            return self::error('create_failed', __('The role could not be created.', 'capability-manager-enhanced'));
        }
        if (!empty($input['source_role'])) {
            $keys = ['capsman_disabled_admin_features', 'capsman_disabled_profile_features', 'capsman_profile_features_elements',
                'capsman_disabled_frontend_features', 'capsman_role_redirects', 'capsman_nav_item_menus',
                'pp_capabilities_admin_styles_roles', 'cme_admin_notice_options'];
            foreach (array_unique(array_merge(['post', 'page'], apply_filters('pp_capabilities_feature_post_types', []))) as $postType) {
                $keys[] = 'capsman_feature_restrict_' . $postType;
                $keys[] = 'capsman_feature_restrict_classic_' . $postType;
            }
            foreach ($keys as $key) {
                $values = (array) get_option($key, []);
                if (isset($values[$input['source_role']])) {
                    $values[$slug] = $values[$input['source_role']];
                    update_option($key, $values, false);
                }
            }
            require_once dirname(__DIR__) . '/features/admin-menus/admin-menus-functions.php';
            if (!has_action('pp_capabilities_after_role_copied', 'pp_capabilities_admin_menus_after_role_copied')) {
                pp_capabilities_admin_menus_after_role_copied($slug, $input['source_role']);
            }
            do_action('pp_capabilities_after_role_copied', $slug, $input['source_role']);
        }
        self::saved($slug, 'create');
        return RoleReadAbilities::getRole(['role' => $slug]);
    }

    /** @since 2.54.0 */
    public static function rename(array $input)
    {
        if (!self::editable($input['role'])) {
            return self::error('role_forbidden', __('The role is not editable.', 'capability-manager-enhanced'));
        }
        $name = sanitize_text_field($input['name']);
        if ($name === '') {
            return self::error('invalid_name', __('A role name is required.', 'capability-manager-enhanced'));
        }
        self::snapshot();
        $roles = wp_roles();
        $roles->roles[$input['role']]['name'] = $name;
        $roles->role_names[$input['role']] = $name;
        update_option($roles->role_key, $roles->roles);
        self::saved($input['role'], 'rename');
        return RoleReadAbilities::getRole($input);
    }

    /** @since 2.54.0 */
    public static function patch(array $input)
    {
        $slug = $input['role'];
        if (!self::editable($slug)) {
            return self::error('role_forbidden', __('The role is not editable.', 'capability-manager-enhanced'));
        }
        $role = get_role($slug);
        $before = $role->capabilities;
        $after = $before;
        foreach ($input['changes'] as $change) {
            $cap = $change['capability'];
            if (!current_user_can('administrator') && (!current_user_can($cap) || $cap === 'manage_capabilities')) {
                return self::error('capability_forbidden', __('You cannot change that capability.', 'capability-manager-enhanced'));
            }
            if ($change['action'] === 'grant' && empty($before[$cap]) && GrantPolicy::isProtected($cap)) {
                return GrantPolicy::error();
            }
            if ($change['action'] === 'remove') {
                unset($after[$cap]);
            } else {
                $after[$cap] = $change['action'] === 'grant';
            }
        }
        if ($slug === 'administrator' && (empty($after['manage_capabilities']) || empty($after['manage_options']) || empty($after['read']))) {
            return self::error('administrator_lockout', __('Administrators must retain read, manage_options and manage_capabilities.', 'capability-manager-enhanced'));
        }
        if (!empty($before['read']) && empty($after['read']) && $slug === get_option('default_role')) {
            return self::error('read_required', __('The default role must retain read access.', 'capability-manager-enhanced'));
        }
        if (!empty($before['read']) && empty($after['read'])) {
            require_once dirname(__DIR__) . '/manager.php';
            if (_cme_is_read_removal_blocked($slug)) {
                return self::error('read_required', __('This role must retain read access to avoid dashboard lockout.', 'capability-manager-enhanced'));
            }
        }
        $result = ['role' => $slug, 'before' => (object) $before, 'after' => (object) $after, 'applied' => false];
        if (!empty($input['dry_run'])) {
            return $result;
        }
        self::snapshot();
        foreach (array_diff_key($before, $after) as $cap => $grant) {
            $role->remove_cap($cap);
        }
        foreach ($after as $cap => $grant) {
            if (!array_key_exists($cap, $before) || $before[$cap] !== $grant) {
                $role->add_cap($cap, $grant);
            }
        }
        self::saved($slug, 'capabilities');
        $result['applied'] = true;
        return $result;
    }

    /** @since 2.54.0 */
    public static function delete(array $input)
    {
        $slug = $input['role'];
        require_once dirname(__DIR__) . '/roles/class/class-pp-roles-manager.php';
        $manager = new \Pp_Roles_Manager();
        if (!self::editable($slug) || $manager->is_system_role($slug) || $slug === get_option('default_role')) {
            return self::error('protected_role', __('System, default and uneditable roles cannot be deleted.', 'capability-manager-enhanced'));
        }
        if (in_array($slug, wp_get_current_user()->roles, true)) {
            return self::error('own_role', __('You cannot delete your own role.', 'capability-manager-enhanced'));
        }
        self::snapshot();
        $moved = $manager->delete_role($slug);
        if ($moved === false) {
            return self::error('delete_failed', __('The role could not be deleted.', 'capability-manager-enhanced'));
        }
        delete_option('pp_capabilities_' . $slug . '_role_option');
        self::saved($slug, 'delete');
        return ['deleted' => $slug, 'users_moved_to_default_role' => (int) $moved];
    }

    /** @since 2.54.0 */
    public static function options(array $input)
    {
        if (!get_role($input['role'])) {
            return self::error('role_not_found', __('Role not found.', 'capability-manager-enhanced'));
        }
        $settings = (array) get_option('pp_capabilities_' . $input['role'] . '_role_option', []);
        if (is_multisite()) {
            $settings['include_new_sites'] = in_array($input['role'], (array) get_site_option('cme_autocreate_roles', []), true);
        }
        if (defined('PRESSPERMIT_ACTIVE')) {
            $settings['permissions_only'] = in_array($input['role'], (array) pp_capabilities_get_permissions_option('supplemental_role_defs'), true);
        }
        return ['role' => $input['role'], 'settings' => (object) $settings];
    }

    /** @since 2.54.0 */
    public static function updateOptions(array $input)
    {
        $slug = $input['role'];
        if (!self::editable($slug)) {
            return self::error('role_forbidden', __('The role is not editable.', 'capability-manager-enhanced'));
        }
        $changes = $input['settings'];
        if (isset($changes['permissions_only']) && !defined('PRESSPERMIT_ACTIVE')) {
            return self::error('permissions_unavailable', __('PublishPress Permissions is not active.', 'capability-manager-enhanced'));
        }
        if (isset($changes['include_new_sites']) && (!is_multisite() || !is_super_admin() || !is_main_site())) {
            return self::error('network_forbidden', __('Only the main-site network administrator can configure new-site roles.', 'capability-manager-enhanced'));
        }
        if (isset($changes['role_editor']) && in_array('classic_editor', $changes['role_editor'], true) && !class_exists('Classic_Editor')) {
            return self::error('editor_unavailable', __('Classic Editor is not installed.', 'capability-manager-enhanced'));
        }
        if (!empty($changes['disable_woocommerce_admin_restrictions']) && !defined('WC_PLUGIN_FILE')) {
            return self::error('woocommerce_unavailable', __('WooCommerce is not active.', 'capability-manager-enhanced'));
        }
        foreach (['disable_role', 'disable_role_user_login', 'block_dashboard_access'] as $key) {
            if (!empty($changes[$key]) && ($slug === 'administrator' || in_array($slug, wp_get_current_user()->roles, true))) {
                return self::error('role_lockout', __('You cannot lock out administrators or your own role.', 'capability-manager-enhanced'));
            }
        }
        if (!empty($changes['disable_role']) && $slug === get_option('default_role')) {
            return self::error('default_role', __('The default role cannot be disabled.', 'capability-manager-enhanced'));
        }
        self::snapshot();
        $key = 'pp_capabilities_' . $slug . '_role_option';
        if (isset($changes['permissions_only'])) {
            $roles = array_diff((array) pp_capabilities_get_permissions_option('supplemental_role_defs'), [$slug]);
            if ($changes['permissions_only']) {
                $roles[] = $slug;
            }
            pp_capabilities_update_permissions_option('supplemental_role_defs', array_values(array_unique($roles)));
            _cme_pp_default_pattern_role($slug);
            if (function_exists('pp_refresh_options')) {
                pp_refresh_options();
            }
            unset($changes['permissions_only']);
        }
        if (isset($changes['include_new_sites'])) {
            $roles = array_diff((array) get_site_option('cme_autocreate_roles', []), [$slug]);
            if ($changes['include_new_sites']) {
                $roles[] = $slug;
            }
            update_site_option('cme_autocreate_roles', array_values(array_unique($roles)));
            unset($changes['include_new_sites']);
        }
        update_option($key, array_merge((array) get_option($key, []), $changes), false);
        if (!empty($changes['disable_woocommerce_admin_restrictions'])) {
            get_role($slug)->add_cap('view_admin_dashboard');
        }
        self::saved($slug, 'options');
        return self::options($input);
    }

    /** @since 2.54.0 */
    public static function setLevel(array $input)
    {
        if (!self::editable($input['role']) || ($input['role'] === 'administrator' && $input['level'] !== 10)) {
            return self::error('role_level', __('This role level cannot be changed.', 'capability-manager-enhanced'));
        }
        $changes = [];
        for ($level = 0; $level <= 10; ++$level) {
            $changes[] = ['capability' => 'level_' . $level, 'action' => $level <= $input['level'] ? 'grant' : 'remove'];
        }
        return self::patch(['role' => $input['role'], 'changes' => $changes, 'dry_run' => $input['dry_run'] ?? false]);
    }
}
