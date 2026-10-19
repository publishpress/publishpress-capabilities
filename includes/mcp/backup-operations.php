<?php

/**
 * Backup, export, and validated import using Capabilities' existing format.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class BackupOperations
{
    /** @since 2.54.0 */
    private static function handler(): \Capsman_BackupHandler
    {
        require_once dirname(__DIR__) . '/functions-admin.php';
        require_once dirname(__DIR__) . '/backup-handler.php';
        return new \Capsman_BackupHandler((object) ['ID' => 'capsman']);
    }

    /** @since 2.54.0 */
    public static function export(array $input)
    {
        $handler = self::handler();
        $data = [];
        $allowed = $handler->get_allowed_import_options(pp_capabilities_backup_sections(), wp_roles()->role_key);
        $sections = $input['sections'] ?? array_keys($allowed);
        if (array_diff($sections, array_keys($allowed))) {
            return RoleOperations::error('export_section', __('Choose section identifiers returned by export discovery.', 'capability-manager-enhanced'));
        }
        if (isset($input['roles']) && !in_array('user_roles', $sections, true)) {
            return RoleOperations::error('export_roles_section', __('Role selection requires the user_roles export section.', 'capability-manager-enhanced'));
        }
        foreach ($sections as $key) {
            $data[$key] = get_option($allowed[$key]['target'], []);
        }
        if (isset($input['roles'])) {
            $roles = (array) ($data['user_roles'] ?? []);
            if (array_diff($input['roles'], array_keys($roles))) {
                return RoleOperations::error('export_role', __('An export role does not exist.', 'capability-manager-enhanced'));
            }
            $data['user_roles'] = [
                'publishpress_capabilities_export' => ['type' => 'selected_roles', 'version' => 1],
                'roles' => array_intersect_key($roles, array_fill_keys($input['roles'], true)),
            ];
        }
        return ['configuration' => (object) $data, 'available_sections' => array_keys($allowed)];
    }

    /** @since 2.54.0 */
    public static function create(array $input): array
    {
        $data = (array) self::export([])['configuration'];
        if (!get_option('capsman_backup_initial') && get_option('capsman_backup')) {
            update_option('capsman_backup_initial', get_option('capsman_backup'), false);
            update_option('capsman_backup_initial_datestamp', get_option('capsman_backup_datestamp'), false);
        }
        foreach ($data as $key => $value) {
            update_option($key === 'user_roles' ? 'capsman_backup' : $key . '_backup', $value, false);
        }
        update_option('capsman_backup_datestamp', current_time('timestamp'), false);
        update_option('capsman_last_backup', implode(', ', array_keys($data)), false);
        return ['backup' => 'capsman_backup', 'sections' => array_keys($data)];
    }

    /** @since 2.54.0 */
    public static function listing(array $input): array
    {
        $backups = [];
        $automatic = get_option('cme_backup_index', get_option('cme_backup_auto_index', []));
        if (!is_array($automatic) || !$automatic) {
            global $wpdb;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cold legacy-index lookup of backup option names; no option API supports this prefix search and a read must not persist a migration.
            $automatic = $wpdb->get_col($wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('cme_backup_auto_') . '%'
            ));
        }
        $automatic = array_filter((array) $automatic, static function ($name) {
            return is_string($name) && strpos($name, 'cme_backup_auto_') === 0
                && $name !== 'cme_backup_auto_index';
        });
        $names = array_merge(['capsman_backup', 'capsman_backup_initial'], (array) $automatic);
        foreach (array_unique($names) as $name) {
            $value = get_option($name);
            if (is_array($value) && $value) {
                $backups[] = ['id' => $name, 'roles' => array_keys($value)];
            }
        }
        return ['backups' => $backups];
    }

    /** @since 2.54.0 */
    public static function restore(array $input)
    {
        $allowed = array_column(self::listing([])['backups'], 'id');
        if (!in_array($input['backup'], $allowed, true)) {
            return RoleOperations::error('backup_not_found', __('Choose a backup returned by list-backups.', 'capability-manager-enhanced'));
        }
        $data = ['user_roles' => get_option($input['backup'])];
        if ($input['backup'] === 'capsman_backup') {
            foreach ((array) self::export([])['configuration'] as $key => $value) {
                if ($key !== 'user_roles' && false !== get_option($key . '_backup', false)) {
                    $data[$key] = get_option($key . '_backup');
                }
            }
        }
        return self::import(['configuration' => $data, 'dry_run' => $input['dry_run'] ?? false]);
    }

    /** @since 2.54.0 */
    public static function import(array $input)
    {
        $handler = self::handler();
        $allowed = $handler->get_allowed_import_options(pp_capabilities_backup_sections(), wp_roles()->role_key);
        $updates = [];
        foreach ($input['configuration'] as $key => $value) {
            if (!isset($allowed[$key])) {
                return RoleOperations::error('unsupported_import', __('Configuration contains an unsupported setting.', 'capability-manager-enhanced'));
            }
            if ($key === 'user_roles') {
                $selected = $handler->is_selected_roles_import($value);
                $roles = $selected ? $value['roles'] : $value;
                if (!$handler->is_valid_import_role_data($roles)) {
                    return RoleOperations::error('invalid_roles', __('Invalid role definitions.', 'capability-manager-enhanced'));
                }
                $roles = $handler->santize_import_role($roles);
                if ($selected) {
                    $roles = array_merge(wp_roles()->roles, $roles);
                }
                $admin = $roles['administrator']['capabilities'] ?? [];
                if (empty($admin['read']) || empty($admin['manage_options']) || empty($admin['manage_capabilities'])) {
                    return RoleOperations::error('restore_lockout', __('The administrator role must retain access to WordPress and Capabilities.', 'capability-manager-enhanced'));
                }
                if (!isset($roles[get_option('default_role')])) {
                    return RoleOperations::error('default_role', __('Imported roles must include the default role.', 'capability-manager-enhanced'));
                }
                $updates[$allowed[$key]['target']] = $roles;
            } else {
                if (!$handler->is_valid_import_option_value($key, $value, $allowed[$key]['section'])) {
                    return RoleOperations::error('invalid_configuration', __('Invalid configuration value.', 'capability-manager-enhanced'));
                }
                $updates[$allowed[$key]['target']] = $handler->santize_import_data($value);
            }
        }
        if (!$updates) {
            return RoleOperations::error('empty_import', __('No configuration was supplied.', 'capability-manager-enhanced'));
        }
        if (!empty($input['dry_run'])) {
            return ['applied' => false, 'settings' => array_keys($updates)];
        }
        RoleOperations::snapshot();
        foreach ($updates as $key => $value) {
            update_option($key, $value, false);
        }
        wp_roles()->for_site();
        $GLOBALS['capsman_dashboard_features_status'] = get_option('capsman_dashboard_features_status', []);
        RoleOperations::saved('', 'restore');
        return ['applied' => true, 'settings' => array_keys($updates)];
    }
}
