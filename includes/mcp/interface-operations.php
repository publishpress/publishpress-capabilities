<?php

/**
 * Manage captured interface elements and admin menu presentation.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class InterfaceOperations
{
    /** @since 2.54.0 */
    public static function saveElement(array $input)
    {
        $feature = $input['feature'];
        $key = $feature === 'profile-features' ? 'capsman_profile_features_elements' : 'capsman_frontend_features_elements';
        $all = (array) get_option($key, []);
        $id = $input['id'] ?? sanitize_key(wp_generate_uuid4());
        if (!preg_match('/^[A-Za-z0-9_.-]+$/D', $id)) {
            return RoleOperations::error('element_id', __('Use the element identifier returned by discovery.', 'capability-manager-enhanced'));
        }
        $label = sanitize_text_field($input['label']);
        if ($label === '') {
            return RoleOperations::error('element_label', __('An element label is required.', 'capability-manager-enhanced'));
        }
        if ($feature === 'profile-features') {
            if (!RoleOperations::editable($input['role'] ?? '')) {
                return RoleOperations::error('role_forbidden', __('The role is not editable.', 'capability-manager-enhanced'));
            }
            if (empty($input['selector'])) {
                return RoleOperations::error('selector_required', __('A selector is required.', 'capability-manager-enhanced'));
            }
            $all[$input['role']][$id] = [
                'label' => $label, 'elements' => sanitize_text_field($input['selector']),
                'element_type' => $input['element_type'] ?? 'custom',
            ];
        } else {
            if (empty($input['selector']) && empty($input['styles']) && empty($input['bodyclass'])) {
                return RoleOperations::error('element_empty', __('A selector, stylesheet, or body class is required.', 'capability-manager-enhanced'));
            }
            if (empty($input['pages']) && empty($input['post_types'])) {
                return RoleOperations::error('element_scope', __('Specify page types or post types.', 'capability-manager-enhanced'));
            }
            foreach ($input['post_types'] ?? [] as $postType) {
                if (!post_type_exists($postType)) {
                    return RoleOperations::error('post_type', __('A post type does not exist.', 'capability-manager-enhanced'));
                }
            }
            $all[$id] = [
                'element_id' => $id, 'label' => $label,
                'elements' => [
                    'selector' => sanitize_textarea_field($input['selector'] ?? ''),
                    'styles' => sanitize_textarea_field($input['styles'] ?? ''),
                    'bodyclass' => sanitize_html_class($input['bodyclass'] ?? ''),
                ],
                'pages' => $input['pages'] ?? [], 'post_types' => $input['post_types'] ?? [],
            ];
        }
        update_option($key, $all, false);
        return ['id' => $id, 'feature' => $feature];
    }

    /** @since 2.54.0 */
    public static function deleteElement(array $input)
    {
        $profile = $input['feature'] === 'profile-features';
        $key = $profile ? 'capsman_profile_features_elements' : 'capsman_frontend_features_elements';
        $all = (array) get_option($key, []);
        $items = $profile ? ($all[$input['role'] ?? ''] ?? []) : $all;
        if (!isset($items[$input['id']])) {
            return RoleOperations::error('element_missing', __('Element not found.', 'capability-manager-enhanced'));
        }
        if ($profile && !RoleOperations::editable($input['role'] ?? '')) {
            return RoleOperations::error('role_forbidden', __('The role is not editable.', 'capability-manager-enhanced'));
        }
        $disabledValue = $profile ? $items[$input['id']]['elements'] : $input['id'];
        unset($items[$input['id']]);
        if ($profile) {
            $all[$input['role']] = $items;
        } else {
            $all = $items;
        }
        update_option($key, $all, false);
        $restrictionKey = $profile ? 'capsman_disabled_profile_features' : 'capsman_disabled_frontend_features';
        $restrictions = (array) get_option($restrictionKey, []);
        foreach ($restrictions as $role => $values) {
            if (!$profile || $role === $input['role']) {
                $restrictions[$role] = array_values(array_diff((array) $values, [$disabledValue]));
            }
        }
        update_option($restrictionKey, $restrictions, false);
        return ['deleted' => $input['id']];
    }

    /** @since 2.54.0 */
    public static function menuPresentation(array $input)
    {
        $action = $input['action'];
        if (
            ($action === 'rename' && (empty($input['role']) || empty($input['menu']) || !isset($input['title'])))
            || ($action === 'reorder' && empty($input['groups']))
        ) {
            return RoleOperations::error('menu_fields', __('The menu action is missing required fields.', 'capability-manager-enhanced'));
        }
        if ($action === 'rename') {
            $role = $input['role'] ?? '';
            if (!RoleOperations::editable($role)) {
                return RoleOperations::error('role_forbidden', __('The role is not editable.', 'capability-manager-enhanced'));
            }
            $all = (array) get_option('ppc_admin_menus_names', []);
            $type = $input['type'] ?? 'menu';
            $all[$role][$type][$input['menu']] = [
                'title' => sanitize_text_field($input['title'] ?? ''),
                'icon' => sanitize_text_field($input['icon'] ?? ''),
            ];
            update_option('ppc_admin_menus_names', $all, false);
        } elseif ($action === 'reorder') {
            $groups = [];
            foreach ($input['groups'] ?? [] as $group) {
                $groups[sanitize_text_field($group['menu'])] = array_map('sanitize_text_field', $group['submenus']);
            }
            update_option('ppc_admin_menus_order', $groups, false);
        } elseif ($action === 'settings') {
            $settings = (array) get_option('ppc_admin_menus_settings', []);
            foreach (['show_menu_slug', 'hide_submenu'] as $key) {
                if (isset($input[$key])) {
                    $settings[$key] = (int) $input[$key];
                }
            }
            update_option('ppc_admin_menus_settings', $settings, false);
        } elseif ($action === 'reset') {
            foreach (['ppc_admin_menus_order', 'ppc_admin_menus_names', 'ppc_admin_menus_custom_menus'] as $key) {
                update_option($key, [], false);
            }
        } else {
            return RoleOperations::error('menu_action', __('Unsupported menu action.', 'capability-manager-enhanced'));
        }
        return ['updated' => true, 'action' => $action];
    }

    /** @since 2.54.0 */
    public static function passwordSubjects(array $input): array
    {
        $subjects = [];
        foreach (pp_capabilities_get_application_password_subjects() as $id => $subject) {
            $subjects[] = [
                'subject' => $id, 'name' => $subject['name'], 'user_id' => (int) $subject['user_id'],
                'denied' => array_keys(pp_capabilities_get_application_password_denied_capabilities($id)),
            ];
        }
        return ['subjects' => $subjects];
    }

    /** @since 2.54.0 */
    public static function passwordRestrictions(array $input)
    {
        if (!pp_capabilities_can_manage_application_password_subject($input['subject'])) {
            return RoleOperations::error('password_subject', __('You cannot manage this application password.', 'capability-manager-enhanced'));
        }
        $known = pp_capabilities_normalize_capability_names(
            pp_capabilities_get_application_password_managed_capabilities(RoleOperations::catalog([])['capabilities'])
        );
        $posted = [];
        foreach (array_keys($known) as $name) {
            $posted[$name] = !in_array($name, $input['denied'], true);
        }
        foreach ($input['denied'] as $name) {
            if (!isset($known[$name])) {
                return RoleOperations::error('unknown_capability', __('A capability is not recognized.', 'capability-manager-enhanced'));
            }
        }
        pp_capabilities_save_application_password_capabilities($input['subject'], $posted, $known);
        return ['subject' => $input['subject'], 'denied' => array_keys(pp_capabilities_get_application_password_denied_capabilities($input['subject']))];
    }
}
