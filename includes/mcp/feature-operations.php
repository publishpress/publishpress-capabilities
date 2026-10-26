<?php

/**
 * Typed configuration operations for the Capabilities feature menus.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class FeatureOperations
{
    /** @since 2.54.0 */
    public static function definitions(): array
    {
        $strings = ['type' => 'array', 'items' => ['type' => 'string', 'maxLength' => 500], 'maxItems' => 1000, 'uniqueItems' => true];
        $boolean = ['type' => 'boolean'];
        $text = ['type' => 'string', 'maxLength' => 2000];
        $noticeTypes = ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['success', 'error', 'warning', 'info']], 'uniqueItems' => true];
        return [
            'editor-features' => ['option' => 'capsman_feature_restrict_', 'fields' => ['disabled' => $strings]],
            'admin-features' => ['option' => 'capsman_disabled_admin_features', 'fields' => ['disabled' => $strings]],
            'frontend-features' => ['option' => 'capsman_disabled_frontend_features', 'fields' => ['disabled' => $strings]],
            'profile-features' => ['option' => 'capsman_disabled_profile_features', 'fields' => ['disabled' => $strings, 'order' => $strings]],
            'nav-menus' => ['option' => 'capsman_nav_item_menus', 'fields' => ['disabled' => $strings]],
            'admin-menus' => ['option' => 'capsman_admin_menus', 'fields' => [
                'disabled_menus' => $strings, 'disabled_submenus' => $strings,
            ]],
            'admin-notices' => ['option' => 'cme_admin_notice_options', 'fields' => [
                'enable_toolbar_access' => $boolean, 'notice_type_remove' => $noticeTypes, 'notice_type_display' => $noticeTypes,
            ]],
            'redirects' => ['option' => 'capsman_role_redirects', 'fields' => [
                'custom_redirect' => $boolean, 'referer_redirect' => $boolean,
                'login_redirect' => $text, 'logout_redirect' => $text,
                'registration_redirect' => $text, 'first_login_redirect' => $text,
            ]],
            'admin-styles' => ['option' => 'pp_capabilities_admin_styles_roles', 'fields' => self::styleFields()],
        ];
    }

    /** @since 2.54.0 */
    public static function styleFields(): array
    {
        $text = ['type' => 'string', 'maxLength' => 5000];
        $fields = [];
        foreach (
            ['admin_color_scheme', 'admin_font_family', 'admin_font_size', 'admin_font_size_unit',
            'admin_custom_footer_text', 'admin_replace_howdy', 'admin_logo', 'admin_favicon',
            'custom_scheme_base', 'custom_scheme_text', 'custom_scheme_highlight',
            'custom_scheme_notification', 'custom_scheme_background', 'custom_scheme_name'] as $name
        ) {
            $fields[$name] = $text;
        }
        $fields['hide_color_scheme_ui'] = ['type' => 'boolean'];
        $fields['force_role_settings'] = ['type' => 'boolean'];
        $fields['admin_typography'] = ['type' => 'object'];
        $fields['element_colors'] = ['type' => 'object'];
        return $fields;
    }

    /** @since 2.54.0 */
    public static function canAccess(string $feature): bool
    {
        return isset(self::definitions()[$feature])
            && pp_capabilities_feature_enabled($feature)
            && RoleOperations::canManage('manage_capabilities_' . str_replace('-', '_', $feature));
    }

    /** @since 2.54.0 */
    public static function option(array $input)
    {
        $feature = $input['feature'];
        $definition = self::definitions()[$feature] ?? null;
        if (!$definition) {
            return RoleOperations::error('unknown_feature', __('Unknown feature.', 'capability-manager-enhanced'));
        }
        if ($feature !== 'editor-features') {
            return $definition['option'];
        }
        $postType = $input['post_type'] ?? 'post';
        $types = array_unique(array_merge(['post', 'page'], apply_filters('pp_capabilities_feature_post_types', [])));
        if (!post_type_exists($postType) || !in_array($postType, $types, true)) {
            return RoleOperations::error('post_type', __('This post type is not available in Editor Features.', 'capability-manager-enhanced'));
        }
        return $definition['option'] . (($input['editor'] ?? 'block') === 'classic' ? 'classic_' : '') . $postType;
    }

    /** @since 2.54.0 */
    public static function read(array $input)
    {
        $option = self::option($input);
        if (is_wp_error($option)) {
            return $option;
        }
        $role = $input['role'];
        if (!get_role($role) && !(in_array($input['feature'], ['nav-menus', 'frontend-features'], true) && in_array($role, ['ppc_guest', 'ppc_users'], true))) {
            return RoleOperations::error('role_not_found', __('Role not found.', 'capability-manager-enhanced'));
        }
        $values = (array) get_option($option, []);
        $settings = $values[$role] ?? [];
        if (isset(self::definitions()[$input['feature']]['fields']['disabled'])) {
            $settings = ['disabled' => array_values((array) $settings)];
            if ($input['feature'] === 'profile-features') {
                $elements = (array) get_option('capsman_profile_features_elements', []);
                $settings['order'] = array_keys((array) ($elements[$role] ?? []));
            }
        } elseif ($input['feature'] === 'admin-menus') {
            $submenus = (array) get_option('capsman_admin_child_menus', []);
            $settings = ['disabled_menus' => array_values((array) $settings), 'disabled_submenus' => array_values($submenus[$role] ?? [])];
        }
        return ['feature' => $input['feature'], 'role' => $role, 'settings' => (object) $settings];
    }

    /** @since 2.54.0 */
    public static function update(array $input)
    {
        $old = self::read($input);
        if (is_wp_error($old)) {
            return $old;
        }
        $role = $input['role'];
        if (!RoleOperations::editable($role) && !(in_array($input['feature'], ['nav-menus', 'frontend-features'], true) && in_array($role, ['ppc_guest', 'ppc_users'], true))) {
            return RoleOperations::error('role_forbidden', __('The role is not editable.', 'capability-manager-enhanced'));
        }
        $fields = self::definitions()[$input['feature']]['fields'];
        $schema = ['type' => 'object', 'properties' => $fields, 'additionalProperties' => false, 'minProperties' => 1];
        $valid = rest_validate_value_from_schema($input['settings'], $schema, 'settings');
        if (is_wp_error($valid)) {
            return $valid;
        }
        $settings = array_merge((array) $old['settings'], $input['settings']);
        if ($input['feature'] === 'profile-features' && isset($input['settings']['order'])) {
            $elements = (array) get_option('capsman_profile_features_elements', []);
            if (array_diff($input['settings']['order'], array_keys((array) ($elements[$role] ?? [])))) {
                return RoleOperations::error('profile_order', __('A profile element in the order does not exist.', 'capability-manager-enhanced'));
            }
        }
        if ($input['feature'] === 'redirects') {
            foreach (['login_redirect', 'logout_redirect', 'registration_redirect', 'first_login_redirect'] as $key) {
                if (!empty($settings[$key])) {
                    $url = $settings[$key];
                    if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                        $url = home_url($url);
                    }
                    if (!wp_validate_redirect($url, '') || wp_parse_url($url, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST)) {
                        return RoleOperations::error('redirect_url', __('Redirects must be valid URLs on this site.', 'capability-manager-enhanced'));
                    }
                    $settings[$key] = esc_url_raw($url);
                }
            }
        }
        if ($input['feature'] === 'admin-menus' && in_array($role, wp_get_current_user()->roles, true)) {
            $settings['disabled_menus'] = array_values(array_diff($settings['disabled_menus'], ['pp-capabilities-roles']));
            $settings['disabled_submenus'] = array_values(array_filter($settings['disabled_submenus'], static function ($id) {
                $parts = explode('|', $id);
                return end($parts) !== 'pp-capabilities-admin-menus';
            }));
        }
        if ($input['feature'] === 'admin-menus') {
            $valid = MenuDiscovery::validate($settings, (array) $old['settings']);
            if (is_wp_error($valid)) {
                return $valid;
            }
        }
        if (!empty($input['dry_run'])) {
            return ['feature' => $input['feature'], 'role' => $role, 'settings' => (object) $settings];
        }
        if ($input['feature'] === 'admin-styles') {
            $styles = self::styles();
            return $styles->saveRoleSettings($role, $settings);
        }
        $option = self::option($input);
        $values = (array) get_option($option, []);
        if (isset($fields['disabled'])) {
            $values[$role] = array_values(array_unique(array_map('sanitize_text_field', $settings['disabled'])));
        } elseif ($input['feature'] === 'admin-menus') {
            $values[$role] = array_map('sanitize_text_field', $settings['disabled_menus']);
            $children = (array) get_option('capsman_admin_child_menus', []);
            $children[$role] = array_map('sanitize_text_field', $settings['disabled_submenus']);
            update_option('capsman_admin_child_menus', $children, false);
        } else {
            $values[$role] = $settings;
        }
        update_option($option, $values, false);
        if ($input['feature'] === 'profile-features' && isset($input['settings']['order'])) {
            $elements = (array) get_option('capsman_profile_features_elements', []);
            $ordered = [];
            foreach ($input['settings']['order'] as $id) {
                $ordered[$id] = $elements[$role][$id];
            }
            $elements[$role] = $ordered + ($elements[$role] ?? []);
            update_option('capsman_profile_features_elements', $elements, false);
        }
        wp_cache_delete('ppc_ff_page_restriction_cache', 'data');
        return self::read($input);
    }

    /** @since 2.54.0 */
    public static function bulkUpdate(array $input)
    {
        foreach ($input['roles'] as $role) {
            $result = self::update(array_merge($input, ['role' => $role, 'dry_run' => true]));
            if (is_wp_error($result)) {
                return $result;
            }
        }
        $results = [];
        foreach ($input['roles'] as $role) {
            $result = self::update(array_merge($input, ['role' => $role]));
            if (is_wp_error($result)) {
                return $result;
            }
            $results[] = $result;
        }
        return ['roles' => $results];
    }

    /** @since 2.54.0 */
    public static function styles()
    {
        require_once dirname(__DIR__) . '/features/admin-styles/admin-styles.php';
        $styles = \PublishPress\Capabilities\PP_Capabilities_Admin_Styles::instance();
        if (!$styles->defaults) {
            $styles->init();
        }
        return $styles;
    }

    /** @since 2.54.0 */
    public static function catalog(array $input): array
    {
        require_once dirname(__DIR__) . '/functions-admin.php';
        $features = [];
        foreach (pp_capabilities_sub_menu_lists(true) as $slug => $menu) {
            $features[] = [
                'feature' => $slug, 'title' => $menu['title'], 'enabled' => pp_capabilities_feature_enabled($slug),
                'capability' => $menu['capabilities'], 'page' => admin_url('admin.php?page=' . $menu['page']),
                'fields' => (object) (self::definitions()[$slug]['fields'] ?? []),
            ];
        }
        return ['features' => $features];
    }

    /** @since 2.54.0 */
    public static function toggle(array $input)
    {
        if (!empty($input['network_sync']) && (!is_multisite() || !is_super_admin() || !is_main_site())) {
            return RoleOperations::error('network_forbidden', __('Network synchronization requires the main-site network administrator.', 'capability-manager-enhanced'));
        }
        require_once dirname(__DIR__) . '/functions-admin.php';
        $menus = pp_capabilities_sub_menu_lists(true);
        if (!isset($menus[$input['feature']]) || !$menus[$input['feature']]['dashboard_control']) {
            return RoleOperations::error('feature_unavailable', __('This feature cannot be toggled.', 'capability-manager-enhanced'));
        }
        $values = (array) get_option('capsman_dashboard_features_status', []);
        $status = $input['enabled'] ? 'on' : 'off';
        $values[$input['feature']]['status'] = $status;
        update_option('capsman_dashboard_features_status', $values, false);
        $GLOBALS['capsman_dashboard_features_status'] = $values;
        do_action('pp_capabilities_dashboard_feature_updated', $input['feature'], $status);
        if (!empty($input['network_sync'])) {
            require_once dirname(__DIR__) . '/network.php';
            pp_capabilities_queue_dashboard_feature_sync($input['feature'], $status);
        }
        return ['feature' => $input['feature'], 'enabled' => $input['enabled']];
    }

    /** @since 2.54.0 */
    public static function choices(array $input)
    {
        $feature = $input['feature'];
        $choices = [];
        if ($feature === 'editor-features') {
            $option = self::option($input);
            if (is_wp_error($option)) {
                return $option;
            }
            require_once dirname(__DIR__) . '/features/restrict-editor-features.php';
            $choices = ($input['editor'] ?? 'block') === 'classic'
                ? \PP_Capabilities_Post_Features::elementsLayoutClassic()
                : \PP_Capabilities_Post_Features::elementsLayout();
        } elseif ($feature === 'admin-features') {
            require_once dirname(__DIR__) . '/features/restrict-admin-features.php';
            $GLOBALS['ppcAdminBar'] = $GLOBALS['ppcAdminBar'] ?? get_option('publishpress_caps_mcp_toolbar_snapshot', []);
            $choices = [
                __('Admin Toolbar', 'capability-manager-enhanced') => \PP_Capabilities_Admin_Features::formatAdminToolbar(),
                __('Header and Footer', 'capability-manager-enhanced') => \PP_Capabilities_Admin_Features::formatHeaderFooter(),
                __('Dashboard widgets', 'capability-manager-enhanced') => get_option('publishpress_caps_mcp_dashboard_snapshot', [
                    'dashboard_welcome_panel' => ['label' => __('Welcome panel', 'capability-manager-enhanced'), 'context' => 'normal', 'action' => 'ppc_dashboard_widget'],
                ]),
            ];
        } elseif ($feature === 'profile-features') {
            $role = $input['role'] ?? '';
            if (!get_role($role)) {
                return RoleOperations::error('role_required', __('Choose an existing role to discover Profile Features.', 'capability-manager-enhanced'));
            }
            require_once dirname(__DIR__) . '/features/restrict-profile-features.php';
            $elements = \PublishPress\Capabilities\PP_Capabilities_Profile_Features::elementsLayout();
            $choices = $elements[$role] ?? [];
            foreach ($choices as &$item) {
                $item['restriction_value'] = $item['elements'] ?? '';
            }
            unset($item);
        } elseif ($feature === 'frontend-features') {
            $choices = (array) get_option('capsman_frontend_features_elements', []);
            foreach ($choices as $id => &$item) {
                $item['restriction_value'] = (string) $id;
            }
            unset($item);
        } elseif ($feature === 'admin-menus') {
            $discovery = MenuDiscovery::choices();
            $choices = [
                'discovery' => (object) $discovery,
                'menus' => $discovery['menus'],
                'submenus' => $discovery['submenus'],
                'custom' => get_option('ppc_admin_menus_custom_menus', []),
                'names' => get_option('ppc_admin_menus_names', []),
                'order' => get_option('ppc_admin_menus_order', []),
            ];
        } elseif ($feature === 'nav-menus') {
            $choices = wp_get_nav_menus();
            foreach ($choices as $menu) {
                $menu->items = wp_get_nav_menu_items($menu->term_id);
            }
        } elseif ($feature === 'admin-styles') {
            $styles = self::styles();
            $choices = ['templates' => $styles->get_style_templates(), 'custom_styles' => $styles->get_custom_styles(), 'color_schemes' => $styles->get_color_schemes()];
        }
        if ($feature === 'admin-features') {
            foreach ($choices as &$section) {
                foreach ($section as $id => &$item) {
                    $item['restriction_value'] = ($item['action'] ?? '') . '||' . $id;
                    if (($item['action'] ?? '') === 'ppc_dashboard_widget') {
                        $item['restriction_value'] .= '||' . ($item['context'] ?? 'normal');
                    }
                }
                unset($item);
            }
            unset($section);
        }
        if ($feature === 'editor-features') {
            foreach ($choices as &$section) {
                foreach ($section as $id => &$item) {
                    $item['restriction_value'] = (string) $id;
                }
                unset($item);
            }
            unset($section);
        }
        if ($feature === 'admin-features' && defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            $choices['custom_rules'] = [
                'css' => CustomRuleOperations::listing(['kind' => 'admin-css'])['rules'],
                'urls' => CustomRuleOperations::listing(['kind' => 'admin-url'])['rules'],
            ];
        }
        if ($feature === 'editor-features' && defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            $kind = ($input['editor'] ?? 'block') === 'classic' ? 'editor-classic' : 'editor-block';
            $choices['custom_rules'] = CustomRuleOperations::listing(['kind' => $kind])['rules'];
            $metaboxes = (array) get_option('ppc_feature_post_metaboxes_data', []);
            $choices['captured_metaboxes'] = (object) ($metaboxes[$input['post_type'] ?? 'post'] ?? []);
        }
        if ($feature === 'admin-features' || $feature === 'admin-menus') {
            $option = $feature === 'admin-features' ? 'ppc_admin_features_settings' : 'ppc_admin_menus_settings';
            $choices['ui_settings'] = (object) get_option($option, []);
        }
        $choices['discovery_context'] = self::discoveryContext($input, $choices);
        return ['feature' => $feature, 'choices' => (object) $choices];
    }

    /** @since 2.54.0 */
    private static function discoveryContext(array $input, array $choices): array
    {
        $feature = $input['feature'];
        $messages = [
            'editor-features' => __('Use restriction_value from the selected editor layout. Custom selectors and metaboxes require Pro and previously saved or captured data.', 'capability-manager-enhanced'),
            'admin-features' => __('Use restriction_value for toolbar, header/footer and dashboard items. Visit the WordPress dashboard as an authorized administrator to refresh captured widgets and toolbar nodes.', 'capability-manager-enhanced'),
            'profile-features' => __('Profile choices belong to the selected role. If empty or incomplete, use prepare-profile-capture for that role and complete capture in the browser. Use each element restriction_value when saving disabled items.', 'capability-manager-enhanced'),
            'frontend-features' => __('These are saved frontend selectors, styles and body-class elements with their page and post-type scopes. Use restriction_value to select them. Use save-interface-element to create a missing element. Reading choices does not install demo elements.', 'capability-manager-enhanced'),
            'admin-notices' => __('Notice types and toolbar access are configured with the fields below. Read actual per-role settings with get-feature-settings.', 'capability-manager-enhanced'),
            'redirects' => __('Read actual per-role redirect targets with get-feature-settings. Use WordPress content lookup to resolve a destination rather than guessing a post ID.', 'capability-manager-enhanced'),
            'nav-menus' => __('These are existing navigation menus and their items. Use their actual menu-item IDs when saving restrictions.', 'capability-manager-enhanced'),
            'admin-styles' => __('These are available templates, saved custom styles and color schemes. Read actual per-role style settings with get-feature-settings.', 'capability-manager-enhanced'),
        ];
        $context = [
            'required_capability' => 'manage_capabilities_' . str_replace('-', '_', $feature),
            'settings_fields' => (object) self::definitions()[$feature]['fields'],
            'instructions' => $messages[$feature] ?? __('Use the menu discovery restriction values and refresh instructions.', 'capability-manager-enhanced'),
        ];
        if (in_array($feature, ['profile-features', 'frontend-features', 'nav-menus'], true)) {
            $context['has_saved_choices'] = (bool) $choices;
        }
        return $context;
    }

    /** @since 2.54.0 */
    public static function settings(array $input): array
    {
        $settings = [];
        foreach (self::settingFields() as $name => $schema) {
            $defaultEnabled = in_array($name, ['cme_test_user_admin_bar', 'cme_test_user_admin_bar_search',
                'cme_test_user_footer_notice', 'cme_admin_menus_restriction_priority', 'cme_display_branding'], true);
            $settings[$name] = $name === 'cme_test_user_excluded_roles'
                ? (array) get_option($name, []) : (bool) get_option($name, $defaultEnabled);
        }
        return ['settings' => (object) $settings, 'schema' => (object) self::settingFields()];
    }

    /** @since 2.54.0 */
    public static function settingFields(): array
    {
        $fields = [];
        foreach (
            ['cme_editor_features_private_post_type', 'cme_capabilities_show_private_taxonomies',
            'cme_capabilities_show_private_post_types', 'cme_capabilities_application_password_capabilities',
            'cme_capabilities_add_user_multi_roles', 'cme_capabilities_edit_user_multi_roles',
            'cme_editor_features_classic_editor_tab', 'cme_test_user_admin_bar', 'cme_test_user_admin_bar_search',
            'cme_test_user_footer_notice', 'cme_admin_menus_restriction_priority',
            'cme_profile_features_auto_redirect', 'cme_role_same_page_redirect_cookie'] as $name
        ) {
            $fields[$name] = ['type' => 'boolean'];
        }
        $fields['cme_test_user_excluded_roles'] = ['type' => 'array', 'items' => ['type' => 'string'], 'uniqueItems' => true];
        $fields = array_merge($fields, ProSettingsOperations::fields());
        $descriptions = [
            'cme_editor_features_private_post_type' => __('Editor Features > Show private post types. Display non-public post types in Editor Features. This does not change the Capabilities screen visibility setting.', 'capability-manager-enhanced'),
            'cme_capabilities_show_private_taxonomies' => __('Capabilities > Show private taxonomies. Include non-public taxonomies when editing role capabilities.', 'capability-manager-enhanced'),
            'cme_capabilities_show_private_post_types' => __('Capabilities > Show private post types. Include non-public post types when editing role capabilities, not the Editor Features setting.', 'capability-manager-enhanced'),
            'cme_capabilities_application_password_capabilities' => __('Capabilities > Application password capabilities. Enable per-application-password capability restrictions.', 'capability-manager-enhanced'),
            'cme_capabilities_add_user_multi_roles' => __('Roles > Multiples roles on "Add New User" screen. Allow assigning multiple roles when creating a new user. This is separate from editing existing users.', 'capability-manager-enhanced'),
            'cme_capabilities_edit_user_multi_roles' => __('Roles > Multiples roles on "User Edit" screen. Enable multiple-role editing for existing users on Edit User. This is separate from Add New User.', 'capability-manager-enhanced'),
            'cme_editor_features_classic_editor_tab' => __('Editor Features > Classic Editor tab. Show controls for Classic Editor restrictions.', 'capability-manager-enhanced'),
            'cme_test_user_admin_bar' => __('User Testing > Admin Bar. Add the user-testing link to the admin toolbar.', 'capability-manager-enhanced'),
            'cme_test_user_admin_bar_search' => __('User Testing > Admin Bar search. Allow searching for a user to test from the toolbar.', 'capability-manager-enhanced'),
            'cme_test_user_footer_notice' => __('User Testing > Front End footer notice. Show a return link while testing a user.', 'capability-manager-enhanced'),
            'cme_admin_menus_restriction_priority' => __('Admin Menus > Treatment of multiple roles. True selects most restrictive, false selects most permissive.', 'capability-manager-enhanced'),
            'cme_profile_features_auto_redirect' => __('Profile Features > Automatic redirection. Control automatic redirection during profile feature capture.', 'capability-manager-enhanced'),
            'cme_role_same_page_redirect_cookie' => __('Redirects > Return to the same page. Track the last visited page for applicable login redirects.', 'capability-manager-enhanced'),
            'cme_test_user_excluded_roles' => __('User Testing > Exclude role from User Testing. List role slugs whose users cannot be tested.', 'capability-manager-enhanced'),
            'cme_display_branding' => __('Pro > Display PublishPress branding. Show or hide plugin branding.', 'capability-manager-enhanced'),
            'cme_custom_status_control' => __('Pro > Custom status control. Enable supported custom status capability handling when the status integration is available.', 'capability-manager-enhanced'),
            'cme_custom_status_postmeta_caps' => __('Pro > Custom status post permissions. Enable post-specific status capabilities. Requires custom status control.', 'capability-manager-enhanced'),
            'presspermit_privacy_statuses_enabled' => __('Pro > Privacy statuses. Enable privacy status integration when its dependencies are available.', 'capability-manager-enhanced'),
            'presspermit_custom_privacy_edit_caps' => __('Pro > Custom privacy editing capabilities. Use the available privacy integration editing rules.', 'capability-manager-enhanced'),
        ];
        foreach ($fields as $name => &$schema) {
            $schema['description'] = $descriptions[$name];
        }
        unset($schema);
        return $fields;
    }

    /** @since 2.54.0 */
    public static function updateSettings(array $input)
    {
        $valid = rest_validate_value_from_schema($input['settings'], [
            'type' => 'object', 'properties' => self::settingFields(), 'additionalProperties' => false,
        ], 'settings');
        if (is_wp_error($valid)) {
            return $valid;
        }
        if (isset($input['settings']['cme_custom_status_control']) && !$input['settings']['cme_custom_status_control']) {
            $input['settings']['cme_custom_status_postmeta_caps'] = false;
        }
        $proValidation = ProSettingsOperations::validate($input['settings']);
        if (is_wp_error($proValidation)) {
            return $proValidation;
        }
        foreach ($input['settings'] as $name => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '';
            } else {
                foreach ($value as $role) {
                    if (!get_role($role)) {
                        return RoleOperations::error('unknown_role', __('An excluded role does not exist.', 'capability-manager-enhanced'));
                    }
                }
            }
        }
        foreach ($input['settings'] as $name => $value) {
            update_option($name, is_bool($value) ? ($value ? '1' : '') : $value, false);
        }
        return self::settings([]);
    }
}
