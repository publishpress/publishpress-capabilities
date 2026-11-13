<?php

/**
 * Prevent administrator-level grants through MCP without changing wp-admin.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.60.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

\defined('ABSPATH') || exit;

class GrantPolicy
{
    /** @since 2.60.0 */
    public static function isProtected(string $capability): bool
    {
        return strpos($capability, 'manage_capabilities') === 0 || in_array($capability, [
            'administrator', 'manage_options', 'edit_users', 'promote_users', 'create_users',
            'delete_users', 'remove_users', 'activate_plugins', 'install_plugins', 'update_plugins',
            'delete_plugins', 'edit_plugins', 'resume_plugins', 'switch_themes', 'edit_themes',
            'install_themes', 'update_themes', 'delete_themes', 'resume_themes', 'edit_files',
            'update_core', 'unfiltered_html', 'unfiltered_upload', 'manage_network', 'manage_sites',
            'create_sites', 'delete_sites', 'upgrade_network', 'setup_network', 'manage_network_users',
            'manage_network_plugins', 'manage_network_themes', 'manage_network_options',
            'add_users', 'promote_user', 'edit_user', 'delete_user', 'deactivate_plugins',
            'activate_plugin', 'deactivate_plugin', 'resume_plugin', 'resume_theme', 'upload_plugins',
            'upload_themes', 'install_languages', 'update_languages', 'edit_css', 'customize',
            'delete_site', 'update_php', 'update_https', 'manage_privacy_options', 'export_others_personal_data',
            'erase_others_personal_data',
        ], true);
    }

    /** @since 2.60.0 */
    public static function restrictedRole(string $slug): bool
    {
        if (self::isProtected($slug)) {
            return true;
        }
        $role = get_role($slug);
        foreach ($role ? $role->capabilities : [] as $capability => $granted) {
            if ($granted && self::isProtected((string) $capability)) {
                return true;
            }
        }
        return false;
    }

    /** Existing grants may remain; only a false/missing to true transition is refused. @since 2.60.0 */
    public static function validateCapabilities(array $before, array $after)
    {
        foreach ($after as $capability => $granted) {
            if ($granted && empty($before[$capability]) && self::isProtected((string) $capability)) {
                return self::error();
            }
        }
        return true;
    }

    /** @since 2.60.0 */
    public static function error(): \WP_Error
    {
        return new \WP_Error('publishpress_mcp_privileged_grant', __('MCP tools cannot grant administrator-level access. Use the WordPress admin screens for this change.', 'capability-manager-enhanced'), ['status' => 403]);
    }
}
