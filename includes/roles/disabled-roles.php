<?php

namespace PublishPress\Capabilities;

/**
 * Hides disabled roles from role assignment controls without removing the role.
 */
class PP_Capabilities_Disabled_Roles
{
    private static $instance;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_filter('editable_roles', [$this, 'filterEditableRoles'], PHP_INT_MAX, 2);
        add_filter('alkivia_roles_translate', [$this, 'filterCapabilitiesRoleNames'], PHP_INT_MAX);
        add_action('update_option_default_role', [$this, 'enableNewDefaultRole'], 10, 2);
    }

    /**
     * Returns disabled role slugs.
     *
     * @return array
     */
    public static function getDisabledRoles()
    {
        $disabled_roles = [];

        foreach (array_keys(wp_roles()->roles) as $role) {
            $role_option = get_option("pp_capabilities_{$role}_role_option", []);

            if (is_array($role_option) && !empty($role_option['disable_role'])) {
                $disabled_roles[] = $role;
            }
        }

        $disabled_roles = apply_filters(
            'pp_capabilities_disabled_roles',
            $disabled_roles
        );
        $disabled_roles = array_map('sanitize_key', (array) $disabled_roles);
        $disabled_roles = array_diff($disabled_roles, [sanitize_key(get_option('default_role'))]);

        return array_values(array_unique(array_filter($disabled_roles)));
    }

    /**
     * Checks whether a role is disabled.
     *
     * @param string $role Role slug.
     * @return boolean
     */
    public static function isRoleDisabled($role)
    {
        return in_array(sanitize_key($role), self::getDisabledRoles(), true);
    }

    /**
     * Updates the disabled state for a role.
     *
     * @param string  $role     Role slug.
     * @param boolean $disabled Whether the role should be disabled.
     * @return boolean Whether the requested state was saved.
     */
    public static function setRoleDisabled($role, $disabled)
    {
        $role = sanitize_key($role);

        if ('' === $role || !wp_roles()->is_role($role)) {
            return false;
        }

        if ($disabled && $role === get_option('default_role')) {
            return false;
        }

        $role_option = get_option("pp_capabilities_{$role}_role_option", []);
        $role_option = is_array($role_option) ? $role_option : [];
        $role_option['disable_role'] = $disabled ? 1 : 0;
        update_option("pp_capabilities_{$role}_role_option", $role_option);

        return true;
    }

    /**
     * Makes sure the site's default role remains assignable.
     *
     * @param string $old_role Previous default role.
     * @param string $new_role New default role.
     * @return void
     */
    public function enableNewDefaultRole($old_role, $new_role)
    {
        self::setRoleDisabled($new_role, false);
    }

    /**
     * Removes disabled roles from standard WordPress role assignment controls.
     *
     * Roles already assigned to the user being edited are preserved so saving
     * that user does not accidentally remove their role.
     *
     * @param array $roles Editable roles.
     * @param array $args  Optional filter arguments.
     * @return array
     */
    public function filterEditableRoles($roles, $args = [])
    {
        if (!is_array($roles) || $this->isRoleDataManagementRequest()) {
            return $roles;
        }

        $disabled_roles = self::getDisabledRoles();
        if (empty($disabled_roles)) {
            return $roles;
        }

        $preserved_roles = $this->getEditedUserRoles();
        $preserved_roles = (array) apply_filters(
            'pp_capabilities_disabled_roles_preserved_roles',
            $preserved_roles,
            $roles,
            $args
        );

        foreach ($disabled_roles as $role) {
            if (!in_array($role, $preserved_roles, true)) {
                unset($roles[$role]);
            }
        }

        return $roles;
    }

    /**
     * Removes disabled roles from the plugin's role selectors.
     *
     * @param array $roles Role names keyed by slug.
     * @return array
     */
    public function filterCapabilitiesRoleNames($roles)
    {
        if (!is_array($roles) || $this->isRoleDataManagementRequest()) {
            return $roles;
        }

        $preserved_roles = $this->getEditedUserRoles();

        foreach (self::getDisabledRoles() as $role) {
            if (!in_array($role, $preserved_roles, true)) {
                unset($roles[$role]);
            }
        }

        return $roles;
    }

    /**
     * Returns roles assigned to the user currently being edited.
     *
     * @return array
     */
    private function getEditedUserRoles()
    {
        global $pagenow;

        $user_id = 0;

        if ('profile.php' === $pagenow) {
            $user_id = get_current_user_id();
        } elseif ('user-edit.php' === $pagenow && !empty($_REQUEST['user_id'])) {
            $user_id = absint($_REQUEST['user_id']);
        }

        if (!$user_id) {
            return [];
        }

        $user = get_userdata($user_id);

        return is_object($user) && isset($user->roles) ? (array) $user->roles : [];
    }

    /**
     * Keeps every role available on screens that manage or back up role data.
     *
     * @return boolean
     */
    private function isRoleDataManagementRequest()
    {
        $page = !empty($_REQUEST['page']) ? sanitize_key($_REQUEST['page']) : '';
        $action = !empty($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';

        return in_array($page, ['pp-capabilities-roles', 'pp-capabilities-backup'], true)
            || 0 === strpos($action, 'pp-roles-');
    }
}
