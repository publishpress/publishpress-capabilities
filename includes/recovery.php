<?php

/**
 * Recover site roles without an authenticated admin session.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.53.0
 */

namespace PublishPress\Capabilities;

class Recovery
{
    /** @since 2.53.0 */
    public static function backups()
    {
        $backups = [];
        foreach (['capsman_backup', 'capsman_backup_initial'] as $id) {
            if (get_option($id, false) !== false) {
                $backups[] = ['id' => $id, 'type' => $id === 'capsman_backup' ? 'manual' : 'initial',
                    'timestamp' => (int) get_option($id . '_datestamp', 0)];
            }
        }
        foreach (pp_capabilities_get_auto_backup_option_names() as $id) {
            if (strpos($id, 'cme_backup_auto_') !== 0 || get_option($id, false) === false) {
                continue;
            }
            $date = \DateTime::createFromFormat('!Y-m-d_g-i-s_a', substr($id, 16), new \DateTimeZone('UTC'));
            $backups[] = ['id' => $id, 'type' => 'automatic', 'timestamp' => $date ? $date->getTimestamp() : 0];
        }
        usort($backups, function ($a, $b) { return $b['timestamp'] <=> $a['timestamp']; });
        return $backups;
    }

    /** @since 2.53.0 */
    public static function resolveBackup($id = null)
    {
        $backups = self::backups();
        if ($id === null && !empty($backups)) {
            $id = $backups[0]['id'];
        }
        if (!$id || !in_array($id, array_column($backups, 'id'), true)) {
            throw new \RuntimeException('No matching backup found. Use wp capabilities backup list.');
        }
        $roles = get_option($id);
        if (!is_array($roles) || !$roles) {
            throw new \RuntimeException('The selected backup does not contain valid roles.');
        }
        foreach ($roles as $role) {
            if (!is_array($role) || !isset($role['name'], $role['capabilities'])
                || !is_string($role['name']) || !is_array($role['capabilities'])) {
                throw new \RuntimeException('The selected backup contains an invalid role definition.');
            }
        }
        return $id;
    }

    /** @since 2.53.0 */
    public static function restore($id)
    {
        global $wpdb;
        $id = self::resolveBackup($id);
        $roles = get_option($id);
        $key = $wpdb->prefix . 'user_roles';
        update_option($key, $roles);
        if (get_option($key) !== $roles) {
            throw new \RuntimeException('Could not write the restored roles.');
        }
        if ($id === 'capsman_backup') {
            foreach (pp_capabilities_backup_sections() as $section) {
                foreach (isset($section['options']) ? (array) $section['options'] : [] as $option) {
                    $value = get_option($option . '_backup', false);
                    if ($value !== false) {
                        update_option($option, $value);
                        if (get_option($option) !== $value) {
                            throw new \RuntimeException('Could not restore option: ' . $option);
                        }
                    }
                }
            }
        }
        wp_roles()->for_site(get_current_blog_id());
        self::log('Restored backup ' . $id);
    }

    /** @since 2.53.0 */
    public static function log($message)
    {
        $message = 'PublishPress Capabilities recovery, site ' . get_current_blog_id() . ': ' . $message;
        update_option('capsman_last_recovery', ['time' => gmdate('c'), 'message' => $message], false);
        error_log($message);
    }

    /** @since 2.53.0 */
    public static function resetRoles()
    {
        global $current_user, $wpdb;
        require_once ABSPATH . 'wp-admin/includes/schema.php';
        foreach (array_keys(wp_roles()->roles) as $role) {
            remove_role($role);
        }
        populate_roles();
        $role = get_role('administrator');
        foreach (apply_filters('cme_publishpress_capabilities_capabilities', []) as $cap) {
            $role->add_cap($cap);
            if ($current_user) {
                $current_user->allcaps[$cap] = true;
            }
        }
        if (function_exists('pp_populate_roles')) {
            pp_populate_roles();
        } else {
            $version = get_option('pp_c_version', true);
            if ($version && is_array($version) && isset($version['version'])) {
                $version['version'] = preg_match('/dev|alpha|beta|rc/i', $version['version']) ? '0.1-beta' : 0.1;
            } else {
                $version = ['version' => '0.1', 'db_version' => '1.0'];
            }
            update_option('pp_c_version', $version);
            delete_option('ppperm_added_role_caps_10beta');
        }
        $stored_roles = get_option($wpdb->prefix . 'user_roles', []);
        if (empty($stored_roles['administrator']['capabilities']['manage_options'])) {
            throw new \RuntimeException('Could not persist the default administrator role.');
        }
        self::log('Reset roles to WordPress defaults');
    }
}
