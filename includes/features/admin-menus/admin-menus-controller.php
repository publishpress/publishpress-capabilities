<?php
namespace PublishPress\Capabilities;

/** Shared Admin Menus screen controller. @since 2.53.0 */
class AdminMenusController
{
    private static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueGlobalStyles']);
    }

    public function enqueueGlobalStyles()
    {
        wp_enqueue_style('pp-capabilities-admin-menu-global-css',
            plugin_dir_url(CME_FILE) . 'includes/features/admin-menus/assets/css/admin-menus-global.css',
            [], PUBLISHPRESS_CAPS_VERSION);
    }

    private function getSubmittedAdminMenuRestrictions($field_name)
    {
        if (empty($_POST[$field_name]) || !is_array($_POST[$field_name])) {
            return [];
        }

        return array_values(array_filter(array_map('sanitize_text_field', wp_unslash($_POST[$field_name])), 'strlen'));
    }

    public static function getEditableRoles()
    {
        require_once dirname(__DIR__, 3) . '/framework/lib/users.php';

        $roles = ak_get_roles(true);
        if (!current_user_can('administrator') && !(is_multisite() && is_super_admin())) {
            $user = wp_get_current_user();
            $roles = array_diff_key($roles, array_flip(array_merge(['administrator'], (array) $user->roles)));
            $max_level = ak_caps2level($user->allcaps);
            $roles = array_filter($roles, static function ($role_name) use ($max_level) {
                $role = get_role($role_name);
                return $role && ak_caps2level($role->capabilities) <= $max_level;
            }, ARRAY_FILTER_USE_KEY);
        }

        return array_filter($roles, 'pp_capabilities_is_editable_role', ARRAY_FILTER_USE_KEY);
    }

    private function getScreenRoles($capsman)
    {
        $capsman->generateNames();
        return array_intersect_key($capsman->roles, self::getEditableRoles());
    }

    private function isFormSubmission()
    {
        return 'POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && isset($_POST['ppc-admin-menu-role']);
    }

    public function validateFormSubmission()
    {
        global $capsman;

        if ($this->isFormSubmission()) {
            $this->requireEditableSubmittedRole(array_keys($this->getScreenRoles($capsman)));
        }
    }

    private function requireEditableSubmittedRole(array $roles)
    {
        $nonce = $_POST['_wpnonce'] ?? '';
        if (!is_string($nonce) || !wp_verify_nonce(sanitize_key(wp_unslash($nonce)), 'pp-capabilities-admin-menus')) {
            wp_die(esc_html__('You do not have permission to manage menu restrictions.', 'capability-manager-enhanced'), '', ['response' => 403]);
        }

        $menu_role = sanitize_key(wp_unslash($_POST['ppc-admin-menu-role']));
        if (!in_array($menu_role, $roles, true)) {
            wp_die(esc_html__('You do not have permission to manage menu restrictions for this role.', 'capability-manager-enhanced'), '', ['response' => 403]);
        }

        return $menu_role;
    }

    private function normalizeOwnRoleAdminMenuRestrictions(array $disabled_menus, array $disabled_child_menus, $role_name, array $current_user_roles)
    {
        if (!in_array($role_name, $current_user_roles, true)) {
            return [$disabled_menus, $disabled_child_menus];
        }

        $disabled_menus = array_values(array_diff($disabled_menus, ['pp-capabilities-roles']));

        $disabled_child_menus = array_values(array_filter($disabled_child_menus, function ($menu_id) {
            if (strpos($menu_id, '|') !== false) {
                list(, $submenu_slug) = explode('|', $menu_id, 2);
            } else {
                $submenu_slug = $menu_id;
            }

            return 'pp-capabilities-admin-menus' !== $submenu_slug;
        }));

        return [$disabled_menus, $disabled_child_menus];
    }

    /**
	 * Manages admin menu permission
	 *
	 * @hook add_management_page
	 * @return void
	 */
	function ManageAdminMenus ()
	{
        global $capsman;

		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_admin_menus')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage menu restrictions.', 'capability-manager-enhanced') . '</strong>');
		}

		$capsman->roles = $this->getScreenRoles($capsman);
		$roles = array_keys($capsman->roles);
        if (!$roles) {
            wp_die(esc_html__('You do not have permission to manage menu restrictions for any role.', 'capability-manager-enhanced'), '', ['response' => 403]);
        }

		if ( ! isset($capsman->current) ) {
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
                $capsman->set_current_role(sanitize_key($_REQUEST['role']));
			}
		}

		if (!isset($capsman->current) || !get_role($capsman->current)) {
			$capsman->current = $capsman->get_last_role();
		}

		if ( ! in_array($capsman->current, $roles) ) {
			$capsman->current = reset($roles);
		}

		$ppc_admin_menu_reload = '0';

		if ($this->isFormSubmission()) {
                $menu_role = $this->requireEditableSubmittedRole($roles);
                $save_for_all_roles = !empty($_POST['admin-menu-all-submit']);
                $current_user_roles = (array) wp_get_current_user()->roles;

                $capsman->set_current_role($menu_role);

                //set role admin menu
                $admin_menu_option = !empty(get_option('capsman_admin_menus')) ? get_option('capsman_admin_menus') : [];
                $requested_admin_menus = $this->getSubmittedAdminMenuRestrictions('pp_cababilities_disabled_menu');

                //set role admin child menu
                $admin_child_menu_option = !empty(get_option('capsman_admin_child_menus')) ? get_option('capsman_admin_child_menus') : [];
                $requested_admin_child_menus = $this->getSubmittedAdminMenuRestrictions('pp_cababilities_disabled_child_menu');

                if ($save_for_all_roles) {
                    foreach ($roles as $role_name) {
                        list($role_admin_menus, $role_admin_child_menus) = $this->normalizeOwnRoleAdminMenuRestrictions(
                            $requested_admin_menus,
                            $requested_admin_child_menus,
                            $role_name,
                            $current_user_roles
                        );

                        $admin_menu_option[$role_name] = $role_admin_menus;
                        $admin_child_menu_option[$role_name] = $role_admin_child_menus;
                    }
                } else {
                    list($requested_admin_menus, $requested_admin_child_menus) = $this->normalizeOwnRoleAdminMenuRestrictions(
                        $requested_admin_menus,
                        $requested_admin_child_menus,
                        $menu_role,
                        $current_user_roles
                    );

                    $admin_menu_option[$menu_role] = $requested_admin_menus;
                    $admin_child_menu_option[$menu_role] = $requested_admin_child_menus;
                }

                update_option('capsman_admin_menus', $admin_menu_option, false);
                update_option('capsman_admin_child_menus', $admin_child_menu_option, false);

                //set reload option for menu reflection if user is updating own role
                if ($save_for_all_roles) {
                    if (!empty(array_intersect($current_user_roles, $roles))) {
                        $ppc_admin_menu_reload = '1';
                    }

                    ak_admin_notify(__('Settings updated for all editable roles.', 'capability-manager-enhanced'));
                } elseif (in_array($menu_role, $current_user_roles, true)) {
                	$ppc_admin_menu_reload = '1';
                }

                if (!$save_for_all_roles) {
                    ak_admin_notify(__('Settings updated.', 'capability-manager-enhanced'));
                }
		}

        $url = plugin_dir_url(CME_FILE);

        // enqueue css
        wp_enqueue_style('pp-capabilities-pro-admin-menu-css', $url . 'includes/features/admin-menus/assets/css/admin-menus.css', false, PUBLISHPRESS_CAPS_VERSION);
        // enqueue js
        wp_enqueue_script( 'pp-capabilities-pro-admin-menu-js', $url . 'includes/features/admin-menus/assets/js/admin-menus.js', array('jquery', 'jquery-ui-sortable', 'wp-i18n'), PUBLISHPRESS_CAPS_VERSION, true );

        if (!apply_filters('pp_capabilities_admin_menus_new_menu_enabled', false)) {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_style('pp-capabilities-admin-core', $url . "includes-core/admin-core{$suffix}.css", [], PUBLISHPRESS_CAPS_VERSION);
        }

        // localize js
        $dashicons = json_decode(file_get_contents(__DIR__ . '/assets/icons/dashicons-icons.json'));
        wp_localize_script('pp-capabilities-pro-admin-menu-js', 'ppCapabilitiesProAdminMenus', [
            'saving_menu_order' => __('Saving Menu Order...', 'capability-manager-enhanced'),
            'saving_menu_title' => __('Saving Menu Name...', 'capability-manager-enhanced'),
            'menu_title_empty' => __('Menu Name can not be empty.', 'capability-manager-enhanced'),
            'changeIcon' => __('Change Icon', 'capability-manager-enhanced'),
            'searchIcons' => __('Search icons...', 'capability-manager-enhanced'),
            'iconUpdated' => __('Menu icon updated', 'capability-manager-enhanced'),
            'iconUpdateError' => __('Error updating menu icon', 'capability-manager-enhanced'),
            'duplicateMenuError' => __('Duplicate menu with this Menu URL already exists', 'capability-manager-enhanced'),
            'duplicateSubMenuError' => __('Duplicate submenu with the menu url already exist', 'capability-manager-enhanced'),
            'dashicons' => $dashicons,
            'nonce' => wp_create_nonce('ppc-admin-menu-nonce')
        ]);

        do_action('pp_capabilities_admin_menus_enqueue_scripts');

		include ( __DIR__ . '/admin-menus.php' );
	}

}
