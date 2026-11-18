<?php

/**
 * Inspect effective user permissions without changing the authenticated identity.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class UserOperations
{
    /** @since 2.54.0 */
    public static function inspect(array $input)
    {
        $user = get_user_by('id', $input['user_id']);
        if (!$user || !is_user_member_of_blog($user->ID)) {
            return RoleOperations::error('user_not_found', __('User not found on this site.', 'capability-manager-enhanced'));
        }
        $caps = [];
        foreach ($user->allcaps as $name => $grant) {
            $caps[] = ['name' => (string) $name, 'granted' => user_can($user, $name)];
        }
        return ['user_id' => $user->ID, 'roles' => array_values($user->roles), 'capabilities' => $caps];
    }

    /** @since 2.54.0 */
    public static function check(array $input)
    {
        $user = get_user_by('id', $input['user_id']);
        if (!$user || !is_user_member_of_blog($user->ID)) {
            return RoleOperations::error('user_not_found', __('User not found on this site.', 'capability-manager-enhanced'));
        }
        $args = !empty($input['object_id']) ? [$input['object_id']] : [];
        return [
            'user_id' => $user->ID, 'capability' => $input['capability'],
            'allowed' => user_can($user, $input['capability'], ...$args),
        ];
    }
    /**
     * Update a site's existing user roles through the shared profile rules.
     *
     * @param array $input Validated ability input.
     * @return array|\WP_Error
     * @since 2.54.0
     */
    public static function updateRoles(array $input)
    {
        if (!get_option('cme_capabilities_edit_user_multi_roles')) {
            return RoleOperations::error('multiple_roles_disabled', __('Enable multiple-role editing in the plugin settings first.', 'capability-manager-enhanced'));
        }
        require_once dirname(__DIR__) . '/user-role-assignment.php';
        $preview = \PublishPress\Capabilities\UserRoleAssignment::update($input['user_id'], $input['roles'], true);
        if (is_wp_error($preview)) {
            return $preview;
        }
        foreach (array_diff($preview['after'], $preview['before']) as $role) {
            if (GrantPolicy::restrictedRole($role)) {
                return GrantPolicy::error();
            }
        }
        return !empty($input['dry_run']) ? $preview : \PublishPress\Capabilities\UserRoleAssignment::update($input['user_id'], $input['roles']);
    }
}
