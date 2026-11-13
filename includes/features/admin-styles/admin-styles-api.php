<?php

/**
 * Programmatic entry points reusing the Admin Styles sanitizers and CSS generator.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities;

defined('ABSPATH') || exit;

trait AdminStylesApi
{
    /** @since 2.54.0 */
    public function saveRoleSettings(string $role, array $settings)
    {
        if (!current_user_can('manage_capabilities_admin_styles') || !pp_capabilities_is_editable_role($role, ['force_refresh' => true])) {
            return new \WP_Error('publishpress_style_forbidden', __('You cannot change styles for this role.', 'capability-manager-enhanced'));
        }
        $all = $this->getRoleSettings();
        $settings = array_merge($all[$role] ?? [], $settings);
        $settings['custom_scheme_version'] = time();
        $all[$role] = $this->sanitize_settings($settings);
        update_option('pp_capabilities_admin_styles_roles', $all, false);
        return ['feature' => 'admin-styles', 'role' => $role, 'settings' => (object) $all[$role]];
    }

    /** @since 2.54.0 */
    public function saveStyle(array $input)
    {
        if (!current_user_can('manage_capabilities_admin_styles')) {
            return new \WP_Error('publishpress_style_forbidden', __('You cannot change admin styles.', 'capability-manager-enhanced'));
        }
        $styles = $this->get_custom_styles();
        $slug = $input['slug'] ?? $this->generate_unique_slug($input['name'], $styles);
        if (strpos($slug, 'ppc-custom-style-') !== 0) {
            return new \WP_Error('publishpress_style_slug', __('Custom style slugs must start with ppc-custom-style-.', 'capability-manager-enhanced'));
        }
        if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') && count($styles) > 0 && !isset($styles[$slug])) {
            return new \WP_Error('publishpress_style_limit', __('The Free edition supports one custom admin style.', 'capability-manager-enhanced'));
        }
        $style = $styles[$slug] ?? [];
        $style = array_merge($style, $input['settings']);
        $name = sanitize_text_field($input['name']);
        if ($name === '') {
            return new \WP_Error('publishpress_style_name', __('A style name is required.', 'capability-manager-enhanced'));
        }
        foreach (['custom_scheme_base', 'custom_scheme_text', 'custom_scheme_highlight', 'custom_scheme_notification', 'custom_scheme_background'] as $key) {
            $style[$key] = sanitize_hex_color($style[$key] ?? '');
        }
        $style['name'] = $name;
        $style['element_colors'] = $this->sanitize_element_colors($style['element_colors'] ?? []);
        $style['advanced_rules'] = $this->sanitize_advanced_rules($style['advanced_rules'] ?? []);
        $style['custom_scheme_version'] = time();
        $style['template_color_version'] = 1;
        $style['created'] = $style['created'] ?? current_time('mysql');
        $style = $this->generate_custom_style_css_file($slug, $style);
        $styles[$slug] = $style;
        $this->save_custom_styles($styles);
        return ['slug' => $slug, 'style' => (object) $style];
    }

    /** @since 2.54.0 */
    public function deleteStyle(array $input)
    {
        if (!current_user_can('manage_capabilities_admin_styles')) {
            return new \WP_Error('publishpress_style_forbidden', __('You cannot delete admin styles.', 'capability-manager-enhanced'));
        }
        $styles = $this->get_custom_styles();
        $slug = $input['slug'];
        if (!isset($styles[$slug])) {
            return new \WP_Error('publishpress_style_missing', __('Custom style not found.', 'capability-manager-enhanced'));
        }
        $this->delete_custom_style_css_files($slug, $styles[$slug]);
        unset($styles[$slug]);
        $this->save_custom_styles($styles);
        $roles = $this->getRoleSettings();
        foreach ($roles as &$settings) {
            if (($settings['admin_color_scheme'] ?? '') === $slug) {
                $settings['admin_color_scheme'] = 'fresh';
            }
        }
        unset($settings);
        update_option('pp_capabilities_admin_styles_roles', $roles, false);
        return ['deleted' => $slug];
    }
}
