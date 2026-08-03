<?php

class Pp_Roles_Manager
{

    /**
     * Pp_Roles_Manager constructor.
     */
    public function __construct()
    {

    }

    /**
     * Returns an array of all the available roles.
     * This method is used to show the roles list table.
     * 
     * @param $view string
     * @param $capabilities bool whether to add capabilities to result or not
     * @param $include_features bool whether to include all features count in result
     *
     * @return array[]
     */
    public function get_roles_for_list_table($view = 'all', $capabilities = false, $include_features = false)
    {
        global $wp_roles;

        $roles = wp_roles()->roles;
        $current_user = wp_get_current_user();
        $editable = function_exists('get_editable_roles') ? 
                        array_keys(get_editable_roles()) : 
                        array_keys(apply_filters('editable_roles', $roles));
        $disabled_roles = \PublishPress\Capabilities\PP_Capabilities_Disabled_Roles::getDisabledRoles();

        $count = $this->ppc_role_count_users();

        $res = [];

        foreach ($roles as $role => $detail) {

            //mine role filter
            if ($view === 'mine' && !in_array($role, $current_user->roles)) {
                continue;
                //active role filter
            } elseif ($view === 'active'
                && (!isset($count['avail_roles'][$role])
                || (isset($count['avail_roles'][$role]) && (int)$count['avail_roles'][$role] === 0))
            ) {
                continue;
                //inactive role filter
            } elseif ($view === 'inactive'
                && (isset($count['avail_roles'][$role])
                && (isset($count['avail_roles'][$role]) && (int)$count['avail_roles'][$role] > 0))
            ) {
                continue;
                //disabled role filter
            } elseif ($view === 'disabled'
                && !in_array($role, $disabled_roles, true)
            ) {
                continue;
                //editable role filter
            } elseif ($view === 'editable' && !in_array($role, $editable)) {
                continue;
                //uneditable role filter
            } elseif ($view === 'uneditable' && in_array($role, $editable)) {
                continue;
                //system role filter
            } elseif ($view === 'system' && !$this->is_system_role($role)) {
                continue;
            }

            $res[] = [
                'role'            => $role,
                'name'            => $detail['name'],
                'count'           => isset($count['avail_roles'][$role]) ? $count['avail_roles'][$role] : 0,
                'editor_features' => pp_capabilities_roles_editor_features($role, $include_features),
                'admin_features'  => pp_capabilities_roles_admin_features($role, $include_features),
                'profile_features'  => pp_capabilities_roles_profile_features($role, $include_features),
                'admin_menus'     => pp_capabilities_roles_admin_menus($role, $include_features),
                'nav_menus'       => pp_capabilities_roles_nav_menus($role, $include_features),
                'is_system'       => $this->is_system_role($role),
                'disabled'        => in_array($role, $disabled_roles, true),
                'capabilities'    => ($capabilities) ? $detail['capabilities'] : [],
            ];
        }

        return $res;
    }

    /**
     * Count role users
     *
     * @return array
     */
    public function ppc_role_count_users() {

        $cache_key = 'ppc_role_count_users_cache';

        $count = wp_cache_get($cache_key, 'count');

        if (!$count) {

            /**
             * The computational strategy to use when counting the users.
             * 
             * Using $strategy = ‘time’ this is CPU-intensive and should handle around 10^7 users.
             * Using $strategy = ‘memory’ this is memory-intensive and should handle around 10^5 users, 
             * but see WP Bug #12257. https://developer.wordpress.org/reference/functions/count_users/
             */
            $time_strategy = (bool) apply_filters('ppc_role_count_users_time_strategy', true);

            if ($time_strategy) {
                $strategy =  'time';
            } else {
                $strategy =  'memory';
            }

            $count = count_users($strategy);

            $expire_days = 7;
            $expire_days = apply_filters('ppc_role_count_users_cache_expire_days', $expire_days);

            $expire = (int)$expire_days * DAY_IN_SECONDS;
            wp_cache_set($cache_key, $count, 'count', $expire);

        }

        return $count;
    }

    /**
     * Array containing all default wordpress roles
     *
     * @return array
     */
    public function get_system_roles()
    {

        $roles = [
            'administrator',
            'editor',
            'author',
            'contributor',
            'subscriber',
            'revisor'
        ];

        $roles = apply_filters('pp-roles-get-system-roles', $roles);

        return $roles;
    }

    /**
     * Checks if the given role is a system role
     *
     * @param $role
     *
     * @return bool
     */
    public function is_system_role($role)
    {

        $is = in_array($role, $this->get_system_roles());

        $is = apply_filters('pp-roles-is-system-role', $is, $role);

        return $is;
    }

    /**
     * Checks if he provided role exist
     *
     * @param $role
     *
     * @return bool
     */
    public function is_role($role)
    {
        return wp_roles()->is_role($role);
    }

    /**
     * Get role object from role
     *
     * @param $role
     *
     * @return WP_Role|null
     */
    public function get_role($role)
    {
        return wp_roles()->get_role($role);
    }

    /**
     * Get role name string form a role
     *
     * @param $role
     *
     * @return string
     */
    public function get_role_name($role)
    {
        if ($this->is_role($role)) {
            return wp_roles()->role_names[$role];
        }

        return $role;
    }

    /**
     * Add role to the system
     *
     * @param $role
     * @param $name
     *
     * @return WP_Role|null
     */
    public function add_role($role, $name)
    {
        $result = add_role($role, $name);

        return $result;
    }

    /**
     * Deletes a role from the system
     *
     * @param $role
     *
     * @return bool
     */
    public function delete_role($role, $args = [])
    {
        global $wpdb, $wp_roles;

        $default = get_option('default_role');

        if ($default == $role) {
            return false;
        }

		$like = '%' . $wpdb->esc_like( $role ) . '%';

		$users = $wpdb->get_results($wpdb->prepare( 
                "SELECT ID FROM $wpdb->usermeta INNER JOIN $wpdb->users "
                . "ON $wpdb->usermeta.user_id = $wpdb->users.ID "
                . "WHERE meta_key='{$wpdb->prefix}capabilities' AND meta_value LIKE %s", 

                $like
            )
        );

		// Array of all roles except the one being deleted, for use below
		$role_names = array_diff_key( $wp_roles->role_names, [$role => true] );
        
		$count = 0;
		foreach ( $users as $u ) {
			$skip_role_set = false;
		
			$user = new WP_User($u->ID);
			if ( $user->has_cap($role) ) {		// Check again the user has the deleting role
				// Role may have been assigned supplementally.  Don't move a user to default role if they still have one or more roles following the deletion.
				foreach( array_keys($role_names) as $_role_name ) {
					if ( $user->has_cap($_role_name) ) {
						$skip_role_set = true;
						break;
					}
				}
				
				if ( ! $skip_role_set ) {
					$user->set_role($default);
					$count++;
				}
			}
		}

		remove_role($role);

		if ( $customized_roles = get_option( 'pp_customized_roles' ) ) {
			if ( isset( $customized_roles[$role] ) ) {
				unset( $customized_roles[$role] );
				update_option( 'pp_customized_roles', $customized_roles );
			}
		}

		return $count;
    }
}
