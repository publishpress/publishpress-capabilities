<?php

/**
 * Shared role assignment rules for profile forms and programmatic actions.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities;

defined('ABSPATH') || exit;

class UserRoleAssignment
{
    /**
     * Validate role assignment and preserve forum roles when replacing site roles.
     *
     * @param int $userId Target site user.
     * @param array $roles Requested role slugs.
     * @param bool $dryRun Validate without saving.
     * @return array|\WP_Error
     * @since 2.54.0
     */
    public static function update(int $userId, array $roles, bool $dryRun = false)
    {
        if (
            !current_user_can('promote_users')
            || !current_user_can('edit_user', $userId)
            || !current_user_can('promote_user', $userId)
        ) {
            return new \WP_Error('publishpress_user_roles_forbidden', __('You cannot change roles for this user.', 'capability-manager-enhanced'));
        }

        $user = get_user_by('id', $userId);
        if (!$user || !is_user_member_of_blog($userId)) {
            return new \WP_Error('publishpress_user_not_found', __('User not found on this site.', 'capability-manager-enhanced'));
        }

        require_once ABSPATH . 'wp-admin/includes/user.php';
        require_once __DIR__ . '/roles/disabled-roles.php';
        PP_Capabilities_Disabled_Roles::instance();
        $roles = array_values(array_unique(array_filter(array_map('sanitize_key', $roles))));
        $editableRoles = array_keys(get_editable_roles());
        if (!$roles || array_diff($roles, $editableRoles)) {
            return new \WP_Error('publishpress_user_roles_invalid', __('Choose existing roles that you are allowed to assign.', 'capability-manager-enhanced'));
        }
        foreach ($roles as $role) {
            $options = (array) get_option('pp_capabilities_' . $role . '_role_option', []);
            if (!empty($options['disable_role']) && !in_array($role, $user->roles, true)) {
                return new \WP_Error('publishpress_user_role_disabled', __('A disabled role cannot be assigned.', 'capability-manager-enhanced'));
            }
        }
        if ($userId === get_current_user_id() && array_diff($user->roles, $roles)) {
            return new \WP_Error('publishpress_user_role_lockout', __('You cannot remove your own roles through this action.', 'capability-manager-enhanced'));
        }

        $forumRoles = array_values(array_filter($user->roles, static function ($role) {
            return strpos($role, 'bbp_') === 0;
        }));
        $after = array_values(array_unique(array_merge($forumRoles, $roles)));
        $before = array_values($user->roles);
        if (!$dryRun) {
            foreach (array_diff($before, $forumRoles) as $role) {
                $user->remove_role($role);
            }
            foreach ($roles as $role) {
                $user->add_role($role);
            }
        }

        return ['user_id' => $userId, 'before' => $before, 'after' => $after, 'applied' => !$dryRun];
    }
}
