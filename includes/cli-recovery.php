<?php

/**
 * WP-CLI recovery commands for PublishPress Capabilities.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.53.0
 */

namespace PublishPress\Capabilities;

class RecoveryCommand
{
    /**
     * List backups for the selected site.
     *
     * [--format=<format>]
     * : Output format: table, json, csv, yaml, count, ids.
     * ---
     * default: table
     * ---
     * @since 2.53.0
     */
    public function listBackups($args, $assocArgs)
    {
        \WP_CLI\Utils\format_items(isset($assocArgs['format']) ? $assocArgs['format'] : 'table',
            Recovery::backups(), ['id', 'type', 'timestamp']);
    }

    /**
     * Restore a backup without an admin login.
     *
     * [--last]
     * : Restore the most recent backup.
     * [--id=<id>]
     * : Restore an ID from backup list.
     * [--yes]
     * : Skip confirmation.
     * @since 2.53.0
     */
    public function restore($args, $assocArgs)
    {
        if (isset($assocArgs['last']) === isset($assocArgs['id'])) {
            \WP_CLI::error('Specify exactly one of --last or --id=<id>.');
        }
        try {
            $id = Recovery::resolveBackup(isset($assocArgs['id']) ? $assocArgs['id'] : null);
            \WP_CLI::confirm('Restore ' . $id . ' on site ' . get_current_blog_id() . '?', $assocArgs);
            Recovery::restore($id);
            \WP_CLI::success('Restored backup ' . $id . ' on site ' . get_current_blog_id() . '.');
        } catch (\RuntimeException $error) {
            \WP_CLI::error($error->getMessage());
        }
    }

    /**
     * Reset roles to WordPress defaults, removing custom roles and capabilities.
     *
     * [--yes]
     * : Skip confirmation.
     * @since 2.53.0
     */
    public function resetRoles($args, $assocArgs)
    {
        \WP_CLI::confirm('Delete custom roles and reset roles on site ' . get_current_blog_id() . '?', $assocArgs);
        try {
            Recovery::resetRoles();
            \WP_CLI::success('Reset roles to WordPress defaults on site ' . get_current_blog_id() . '.');
        } catch (\RuntimeException $error) {
            \WP_CLI::error($error->getMessage());
        }
    }
}

if (function_exists('cme_publishpress_capabilities_capabilities')) {
    add_filter('cme_publishpress_capabilities_capabilities', 'cme_publishpress_capabilities_capabilities');
}

$command = new RecoveryCommand();
\WP_CLI::add_command('capabilities backup list', [$command, 'listBackups']);
\WP_CLI::add_command('capabilities backup restore', [$command, 'restore']);
\WP_CLI::add_command('capabilities reset-roles', [$command, 'resetRoles']);
