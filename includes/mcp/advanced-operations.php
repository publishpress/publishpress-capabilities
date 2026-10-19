<?php

/**
 * Capability mapping, recovery, per-post overrides and multisite operations.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class AdvancedOperations
{
    /** @since 2.54.0 */
    public static function profileCapture(array $input)
    {
        if (!$input['enabled']) {
            delete_option('capsman_profile_features_role');
            return ['enabled' => false, 'expires' => 0, 'capture_url' => ''];
        }
        $role = $input['role'] ?? '';
        if (!RoleOperations::editable($role)) {
            return RoleOperations::error('role_forbidden', __('Choose an editable role for profile capture.', 'capability-manager-enhanced'));
        }
        $expires = (int) current_time('timestamp') + 5 * MINUTE_IN_SECONDS;
        update_option('capsman_profile_features_role', ['role' => $role, 'expires' => $expires], false);
        update_option('capsman_profile_features_elements_testing_role', $role, false);
        $url = add_query_arg(['page' => 'pp-capabilities-profile-features', 'role' => $role,
            'refresh_element' => 1, 'role_refresh' => 1], admin_url('admin.php'));
        return ['enabled' => true, 'expires' => $expires, 'capture_url' => $url];
    }

    /** @since 2.54.0 */
    public static function adminFeatureSettings(array $input): array
    {
        $settings = (array) get_option('ppc_admin_features_settings', []);
        $settings['hide_submenu'] = (int) $input['hide_submenu'];
        update_option('ppc_admin_features_settings', $settings, false);
        return ['hide_submenu' => (bool) $settings['hide_submenu']];
    }

    /** @since 2.54.0 */
    private static function prefix(): string
    {
        return defined('PPC_VERSION') && !defined('PRESSPERMIT_VERSION') ? 'pp' : 'presspermit';
    }

    /** @since 2.54.0 */
    public static function typeSettings(array $input): array
    {
        $prefix = self::prefix();
        return ['settings' => [
            'post_types' => array_keys(array_filter((array) get_option($prefix . '_enabled_post_types', ['post' => true, 'page' => true]))),
            'taxonomies' => array_keys(array_filter((array) get_option($prefix . '_enabled_taxonomies', []))),
            'detailed_taxonomies' => array_keys(array_filter((array) get_option('cme_detailed_taxonomies', []))),
            'separate_create_capability' => (bool) get_option($prefix . '_define_create_posts_cap', false),
        ], 'choices' => ['post_types' => array_values(get_post_types()), 'taxonomies' => array_values(get_taxonomies())]];
    }

    /** @since 2.54.0 */
    public static function updateTypes(array $input)
    {
        $previous = self::typeSettings([])['settings'];
        $settings = array_merge($previous, $input['settings']);
        foreach (['post_types', 'taxonomies', 'detailed_taxonomies'] as $key) {
            foreach ($input['settings'][$key] ?? [] as $name) {
                if (!($key === 'post_types' ? post_type_exists($name) : taxonomy_exists($name))) {
                    return RoleOperations::error('type_unknown', __('A post type or taxonomy does not exist.', 'capability-manager-enhanced'));
                }
            }
            foreach ($previous[$key] as $name) {
                if (!($key === 'post_types' ? post_type_exists($name) : taxonomy_exists($name))) {
                    $settings[$key][] = $name;
                }
            }
            $settings[$key] = array_values(array_unique($settings[$key]));
        }
        if (isset($input['settings']['taxonomies']) && !isset($input['settings']['detailed_taxonomies'])) {
            $settings['detailed_taxonomies'] = array_values(array_intersect($settings['detailed_taxonomies'], $settings['taxonomies']));
        }
        $settings['taxonomies'] = array_values(array_unique(array_merge($settings['taxonomies'], $settings['detailed_taxonomies'])));
        $prefix = self::prefix();
        foreach (['post_types' => 'enabled_post_types', 'taxonomies' => 'enabled_taxonomies'] as $key => $option) {
            $value = array_fill_keys($settings[$key], true);
            update_option($prefix . '_' . $option, $value);
            if ($key === 'post_types') {
                update_option('cme_enabled_post_types', $value);
            }
            if (defined('PRESSPERMIT_ACTIVE')) {
                pp_capabilities_update_permissions_option($option, $value);
            }
        }
        update_option('cme_detailed_taxonomies', array_fill_keys($settings['detailed_taxonomies'], true));
        update_option($prefix . '_define_create_posts_cap', $settings['separate_create_capability']);
        if (function_exists('pp_refresh_options') && defined('PRESSPERMIT_ACTIVE')) {
            pp_refresh_options();
        }
        return self::typeSettings([]);
    }

    /** @since 2.54.0 */
    public static function resetRoles(array $input)
    {
        require_once dirname(__DIR__) . '/functions-admin.php';
        require_once dirname(__DIR__) . '/recovery.php';
        RoleOperations::snapshot();
        add_filter('cme_publishpress_capabilities_capabilities', 'cme_publishpress_capabilities_capabilities');
        try {
            \PublishPress\Capabilities\Recovery::resetRoles();
        } catch (\RuntimeException $error) {
            return RoleOperations::error('reset_failed', $error->getMessage());
        }
        RoleOperations::saved('', 'reset');
        return ['reset' => true];
    }

    /** @since 2.54.0 */
    public static function networkSync(array $input)
    {
        require_once dirname(__DIR__) . '/handler.php';
        $handler = new \CapsmanHandler();
        $result = $handler->queueRoleSync($input['role'], $input['sync_roles'] ?? true, $input['sync_options'] ?? false);
        return is_wp_error($result) ? $result : ['token' => $result];
    }

    /** @since 2.54.0 */
    public static function frontendPost(array $input)
    {
        if (!get_post($input['post_id'])) {
            return RoleOperations::error('post_missing', __('Post not found.', 'capability-manager-enhanced'));
        }
        if (array_key_exists('elements', $input)) {
            $known = (array) get_option('capsman_frontend_features_elements', []);
            foreach ($input['elements'] as $id) {
                if (!isset($known[$id])) {
                    return RoleOperations::error('element_missing', __('A frontend element does not exist.', 'capability-manager-enhanced'));
                }
            }
            update_post_meta($input['post_id'], '_ppc_frontendelements', $input['elements']);
            wp_cache_delete('ppc_ff_page_restriction_cache', 'data');
        }
        return ['post_id' => $input['post_id'], 'elements' => array_values((array) get_post_meta($input['post_id'], '_ppc_frontendelements', true))];
    }

    /** @since 2.54.0 */
    public static function customMenu(array $input)
    {
        $all = (array) get_option('ppc_admin_menus_custom_menus', []);
        $type = $input['type'];
        $collection = $type === 'menu' ? 'menus' : 'submenus';
        $slug = $input['slug'];
        if (trim($slug) === '' || sanitize_text_field($slug) !== $slug) {
            return RoleOperations::error('menu_slug', __('Invalid menu slug.', 'capability-manager-enhanced'));
        }
        if ($input['action'] === 'delete') {
            if (!isset($all[$collection][$slug])) {
                return RoleOperations::error('menu_missing', __('Custom menu not found.', 'capability-manager-enhanced'));
            }
            unset($all[$collection][$slug]);
        } else {
            if (empty($input['title']) || empty($input['capability']) || ($type === 'submenu' && empty($input['parent']))) {
                return RoleOperations::error('menu_fields', __('Title, capability and submenu parent are required.', 'capability-manager-enhanced'));
            }
            if (strpos($slug, ':') !== false && !esc_url_raw($slug, ['http', 'https'])) {
                return RoleOperations::error('menu_url', __('Unsupported menu URL.', 'capability-manager-enhanced'));
            }
            $entry = [
                'form_menu_type' => !empty($input['separator']) ? $type . '-separator' : $type,
                'menu_type' => $type, 'menu_position' => $input['position'] ?? 0, 'menu_position_after' => sanitize_text_field($input['position_after'] ?? ''),
                'menu_data' => [sanitize_text_field($input['title']), sanitize_text_field($input['capability']), $slug,
                    sanitize_text_field($input['title']), '', '', sanitize_text_field($input['icon'] ?? 'dashicons-admin-generic')],
            ];
            if ($type === 'submenu') {
                $entry['menu_parent'] = sanitize_text_field($input['parent']);
            }
            if (!empty($input['separator_color'])) {
                $entry['separator_color'] = sanitize_hex_color($input['separator_color']);
            }
            $all[$collection][$slug] = $entry;
        }
        update_option('ppc_admin_menus_custom_menus', $all, false);
        delete_option('ppc_admin_menus_menu');
        delete_option('ppc_admin_menus_submenu');
        return ['slug' => $slug, 'action' => $input['action']];
    }
}
