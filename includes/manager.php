<?php
/**
 * PublishPress Capabilities [Free]
 *
 * Plugin to create and manage roles and capabilities.
 *
 * This is the plugin's original controller module, which is due for some refactoring.
 * It registers and handles menus, loads javascript, and processes or routes update operations from the Capabilities screen.
 *
 * Note: for lower overhead, this module is only loaded for Capabilities Pro URLs.
 * For all other wp-admin URLs, menus are registered by a separate skeleton module.
 *
 * @author		Jordi Canals, Kevin Behrens
 * @copyright   Copyright (C) 2009, 2010 Jordi Canals, (C) 2020 PublishPress
 * @license		GNU General Public License version 2
 * @link		https://publishpress.com/
 *
 *
 *	Copyright 2009, 2010 Jordi Canals <devel@jcanals.cat>
 *
 *	Modifications Copyright 2020, PublishPress <help@publishpress.com>
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	version 2 as published by the Free Software Foundation.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

add_action( 'init', 'cme_update_pp_usage' );  // update early so resulting post type cap changes are applied for this request's UI construction

function cme_update_pp_usage() {
	if ( ! empty($_REQUEST['update_filtered_types']) || ! empty($_REQUEST['update_filtered_taxonomies']) || ! empty($_REQUEST['update_detailed_taxonomies']) || ! empty($_REQUEST['SaveRole']) ) {
		check_admin_referer('capsman-general-manager');

		require_once( dirname(__FILE__).'/pp-handler.php' );
		return _cme_update_pp_usage();
	}
}

// Core WP roles to apply safeguard preventing accidental lockout from dashboard
function _cme_core_roles() {
	return apply_filters( 'pp_caps_core_roles', array( 'administrator', 'editor', 'revisor', 'author', 'contributor', 'subscriber' ) );
}

function _cme_core_caps() {
	$core_caps = array_fill_keys( array( 'switch_themes', 'edit_themes', 'activate_plugins', 'edit_plugins', 'edit_users', 'edit_files', 'manage_options', 'moderate_comments',
	'manage_links', 'upload_files', 'import', 'unfiltered_html', 'read', 'delete_users', 'create_users', 'unfiltered_upload', 'edit_dashboard',
	'update_plugins', 'delete_plugins', 'install_plugins', 'update_themes', 'install_themes',
	'update_core', 'list_users', 'remove_users', 'promote_users', 'edit_theme_options', 'delete_themes', 'export' ), true );

	ksort( $core_caps );
	return $core_caps;
}

function _cme_is_read_removal_blocked( $role_name ) {
	$role = get_role($role_name);
	$rcaps = $role->capabilities;

	$core_caps = array_diff_key( _cme_core_caps(), array_fill_keys( array( 'unfiltered_html', 'unfiltered_upload', 'upload_files', 'edit_files', 'read' ), true ) );

	if ( empty( $rcaps['dashboard_lockout_ok'] ) ) {
		$edit_caps = array();
		foreach ( get_post_types( array( 'public' => true ), 'object' ) as $type_obj ) {
			$edit_caps = array_merge( $edit_caps, array_values( array_diff_key( (array) $type_obj->cap, array( 'read_private_posts' => true ) ) ) );
		}

		$edit_caps = array_fill_keys( $edit_caps, true );
		unset( $edit_caps['read'] );
		unset( $edit_caps['upload_files'] );
		unset( $edit_caps['edit_files'] );

		if ( $role_has_admin_caps = in_array( $role_name, _cme_core_roles() ) && ( array_intersect_key( $rcaps, array_diff_key( $core_caps, array( 'read' => true ) ) ) || array_intersect_key( $rcaps, $edit_caps ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Class CapabilityManager.
 * Sets the main environment for all Capability Manager components.
 *
 * @author		Jordi Canals, Kevin Behrens
 * @link		https://publishpress.com/
 */
class CapabilityManager
{
	/**
	 * Array with all capabilities to be managed. (Depends on user caps).
	 * The array keys are the capability, the value is its screen name.
	 * @var array
	 */
	var $capabilities = array();

	/**
	 * Array with roles that can be managed. (Depends on user roles).
	 * The array keys are the role name, the value is its translated name.
	 * @var array
	 */
	var $roles = array();

	/**
	 * Current role we are managing
	 * @var string
	 */
	var $current;

	/**
	 * Maximum level current manager can assign to a user.
	 * @var int
	 */
	private $max_level;

	private $log_db_role_objects = array();

	var $message;

	/**
	 * Module ID. Is the module internal short name.
	 *
	 * @var string
	 */
	public $ID;

	/**
	 * Module URL.
	 *
	 * @var string
	 */
	public $mod_url;

	public function __construct()
	{
		$this->ID = 'capsman';
		$this->mod_url = plugins_url( '', CME_FILE );

		if (is_admin() && !empty($_REQUEST['page']) && ('pp-capabilities-settings' == $_REQUEST['page']) && !empty($_POST['all_options'])) {
			add_action('init', function() {
				if (isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'pp-capabilities-settings') && current_user_can('manage_capabilities_settings')) {
					require_once (dirname(CME_FILE) . '/includes/settings-handler.php');
				}
			}, 1);
		}

		$this->moduleLoad();

		add_action('admin_menu', array($this, 'adminMenus'), 5);  // execute prior to PP, to use menu hook

		// Load styles
		add_action('admin_print_styles', array($this, 'adminStyles'));

		if ( isset($_REQUEST['page']) && ( 'pp-capabilities' == $_REQUEST['page'] ) ) {
			add_action('admin_enqueue_scripts', array($this, 'adminScriptsPP'));
		}

		add_action('init', [$this, 'initRolesAdmin']);

		add_action('wp_ajax_pp-roles-add-role', [$this, 'handleRolesAjax']);
		add_action('wp_ajax_pp-roles-delete-role', [$this, 'handleRolesAjax']);

		if (defined('PRESSPERMIT_VERSION')) {
			add_action('wp_ajax_pp-roles-hide-role', [$this, 'handleRolesAjax']);
			add_action('wp_ajax_pp-roles-unhide-role', [$this, 'handleRolesAjax']);
		}

        //process export
        add_action( 'admin_init', [$this, 'processExport']);

        //redirect for profile features capturing
        add_action('admin_init', [$this, 'profileFeaturesCaptureRedirect']);

        //Initialize plugin capabilities class
        add_action('admin_init', [$this, 'initPluginCapabilities']);
	}

    /**
     * Enqueues administration styles.
     *
     * @hook action 'admin_print_styles'
	 *
     * @return void
     */
    function adminStyles()
    {
		if (empty($_REQUEST['page'])
		|| !in_array(
			$_REQUEST['page'],
			['pp-capabilities', 'pp-capabilities-backup', 'pp-capabilities-roles', 'pp-capabilities-admin-menus', 'pp-capabilities-editor-features', 'pp-capabilities-nav-menus', 'pp-capabilities-settings', 'pp-capabilities-admin-features', 'pp-capabilities-profile-features', 'pp-capabilities-dashboard', 'pp-capabilities-frontend-features', 'pp-capabilities-redirects']
			)
		) {
			return;
		}

		wp_enqueue_style('cme-admin-common', $this->mod_url . '/common/css/pressshack-admin.css', [], PUBLISHPRESS_CAPS_VERSION);

		wp_register_style( $this->ID . 'framework_admin', $this->mod_url . '/framework/styles/admin.css', false, PUBLISHPRESS_CAPS_VERSION);
		wp_enqueue_style( $this->ID . 'framework_admin');

		if ('pp-capabilities' == $_REQUEST['page']) {
			wp_register_style( $this->ID . '_admin', $this->mod_url . '/common/css/admin-caps.css', false, PUBLISHPRESS_CAPS_VERSION);
		} else {
			// @todo: remove Capabilities-specific styles from admin.css
			wp_register_style( $this->ID . '_admin', $this->mod_url . '/common/css/admin.css', false, PUBLISHPRESS_CAPS_VERSION);
		}
		wp_enqueue_style( $this->ID . '_admin');

		wp_enqueue_script('jquery-ui-sortable');

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
		$url = $this->mod_url . "/common/js/admin{$suffix}.js";
		wp_enqueue_script( 'cme_admin', $url, array('jquery', 'wp-i18n', 'jquery-ui-sortable'), PUBLISHPRESS_CAPS_VERSION, true );

		$capNegated = '<span class="tool-tip-text">
		<p>'. __( 'This capability is explicitly negated. Click to add/remove normally.', 'capability-manager-enhanced' ) .'</p>
		<i></i>
		</span>
		X';

		wp_localize_script( 'cme_admin', 'cmeAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pp-capabilities-dashboard-nonce'),
			'negationCaption' => __( 'Explicity negate this capability by storing as disabled', 'capability-manager-enhanced' ),
			'typeCapsNegationCaption' => __( 'Explicitly negate these capabilities by storing as disabled', 'capability-manager-enhanced' ),
			'typeCapUnregistered' => __( 'Post type registration does not define this capability distinctly', 'capability-manager-enhanced' ),
			'capNegated' => $capNegated,
			'chkCaption' => __( 'Add or remove this capability from the WordPress role', 'capability-manager-enhanced' ),
			'switchableCaption' => __( 'Add or remove capability from the role normally', 'capability-manager-enhanced' ),
			'deleteWarning' => __( 'Are you sure you want to delete this item ?', 'capability-manager-enhanced' ),
			'saveWarning'   => __( 'Add or clear custom item entry before saving changes.', 'capability-manager-enhanced' )
			]
		);
    }

	function adminScriptsPP() {
		wp_enqueue_style( 'plugin-install' );
		wp_enqueue_script( 'plugin-install' );
		add_thickbox();
	}

	/**
	 * Creates some filters at module load time.
	 *
	 * @return void
	 */
    protected function moduleLoad ()
    {
		$old_version = get_option($this->ID . '_version');
		if ( version_compare( $old_version, PUBLISHPRESS_CAPS_VERSION, 'ne') ) {
			update_option($this->ID . '_version', PUBLISHPRESS_CAPS_VERSION);
			$this->pluginUpdate();
		}

        // Only roles that a user can administer can be assigned to others.
        add_filter('editable_roles', array($this, 'filterEditRoles'));

        // Users with roles that cannot be managed, are not allowed to be edited.
        add_filter('map_meta_cap', array(&$this, 'filterUserEdit'), 10, 4);

		// ensure storage, retrieval of db-stored customizations to dynamic roles
		if ( isset($_REQUEST['page']) && in_array( $_REQUEST['page'], array( 'pp-capabilities', 'pp-capabilities-backup' ) ) ) {
			global $wpdb;
			$role_key = $wpdb->prefix . 'user_roles';
			$this->log_db_roles();
			add_filter( 'option_' . $role_key, array( &$this, 'reinstate_db_roles' ), PHP_INT_MAX );
		}

		$action = (defined('PP_CAPABILITIES_COMPAT_MODE')) ? 'init' : 'publishpress_capabilities_loaded';
		add_action( $action, array( &$this, 'processRoleUpdate' ) );
    }

	public function set_current_role($role_name) {
		global $current_user;

		if ($role_name && !empty($current_user) && !empty($current_user->ID)) {
			update_option("capsman_last_role_{$current_user->ID}", $role_name);
		}
	}

	public function get_last_role() {
		global $current_user;

		$role_name = get_option("capsman_last_role_{$current_user->ID}");

		if (!$role_name || !get_role($role_name)) {
			$role_name = get_option('default_role');
		}

		return $role_name;
	}

	// Direct query of stored role definitions
	function log_db_roles( $legacy_arg = '' ) {
		global $wpdb;

		$results = (array) maybe_unserialize( $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = '{$wpdb->prefix}user_roles' LIMIT 1") );
		foreach( $results as $_role_name => $_role ) {
			$this->log_db_role_objects[$_role_name] = (object) $_role;
		}

		return $legacy_arg;
	}

	// note: this is only applied when accessing the cme role edit form
	function reinstate_db_roles( $passthru_roles = array() ) {
		global $wp_roles;

		if ( isset($wp_roles) && $this->log_db_role_objects ) {
			$intersect = array_intersect_key( $wp_roles->role_objects, $this->log_db_role_objects );
			foreach( array_keys( $intersect ) as $key ) {
				if ( ! empty( $this->log_db_role_objects[$key]->capabilities ) )
					$wp_roles->role_objects[$key]->capabilities = $this->log_db_role_objects[$key]->capabilities;
			}
		}

		return $passthru_roles;
	}

	/**
	 * Updates Capability Manager to a new version
	 *
	 * @return void
	 */
	protected function pluginUpdate ()
	{
		global $wpdb;

		$backup = get_option($this->ID . '_backup');
		if ( false === $backup ) {		// No previous backup found. Save it!
			$roles = get_option($wpdb->prefix . 'user_roles');
			update_option( $this->ID . '_backup', $roles, false );
			update_option( $this->ID . '_backup_datestamp', current_time( 'timestamp' ), false );
		}

		if (!$wpdb->get_var("SELECT COUNT(option_id) FROM $wpdb->options WHERE option_name LIKE 'cme_backup_auto_%'")) {
			pp_capabilities_autobackup();
		}
	}

	/**
	 * Adds admin panel menus.
	 * User needs to have 'manage_capabilities' to access this menus.
	 * This is set as an action in the parent class constructor.
	 *
	 * @hook action admin_menu
	 * @return void
	 */
	public function adminMenus ()
	{
		add_action( 'admin_menu', array( &$this, 'cme_menu' ), 18 );
	}

	public function cme_menu() {

        global $menu, $submenu, $capabilities_toplevel_page;

        //we need to set primary menu capability to the first menu user has access to
        $sub_menu_pages = pp_capabilities_sub_menu_lists();
        $user_menu_caps = pp_capabilities_user_can_caps();
        $menu_cap       = false;
        $cap_callback   = false;
        $cap_page_slug  = false;
        $cap_title      = 'Capabilities'; // Pass title into add_menu_page() untranslated so hook name, body class and current_screen are not translated
		$cap_title_i8n = __('Capabilities', 'capability-manager-enhanced');
        $cap_name       = false;

        //remove caps that doesn't have menu
        if (in_array('manage_capabilities_user_testing', $user_menu_caps)) {
            $cap_key = array_search('manage_capabilities_user_testing', $user_menu_caps);
            if ($cap_key !== false) {
                unset($user_menu_caps[$cap_key]);
                $user_menu_caps = array_filter($user_menu_caps);
            }
        }

        if (is_multisite() && is_super_admin()) {
            $cap_name      = 'read';
            $cap_callback  = [$this, 'dashboardPage'];
            $cap_page_slug = 'pp-capabilities-dashboard';
        } elseif (count($user_menu_caps) > 0) {
            $cap_name      = $user_menu_caps[0];
            $cap_index     = str_replace(['manage_capabilities_', 'manage_', '_'], ['', '', '-'], $cap_name);
            if (($cap_index !== 'capabilities') && (count($user_menu_caps) === 1)) {
                $cap_title = $sub_menu_pages[$cap_index]['title'];
				$using_submenu_title = true;
            }
            $cap_page_slug = $sub_menu_pages[$cap_index]['page'];
            $cap_callback  = $sub_menu_pages[$cap_index]['callback'];
        }

        $capabilities_toplevel_page = $cap_page_slug;

        if (!$cap_name) {
            return;
        }

		$menu_order = 72;

		if (defined('PUBLISHPRESS_PERMISSIONS_MENU_GROUPING')) {
			foreach ((array)get_option('active_plugins') as $plugin_file) {
				if ( false !== strpos($plugin_file, 'publishpress.php') ) {
					$menu_order = 27;
				}
			}
		}

		add_menu_page(
            $cap_title,
            $cap_title,
            $cap_name,
            $cap_page_slug,
            $cap_callback,
            'dashicons-admin-network',
            $menu_order
        );

		// Translate plugin menu title if needed. Title was passed untranslated to avoid translating hook name, body class, current screen
		if (($cap_title != $cap_title_i8n) && empty($using_submenu_title)) {
			if (!empty($menu) && is_array($menu) && !defined('PP_CAPABILITIES_DISABLE_MENU_TRANSLATION_SUPPORT')) {
				foreach ($menu as $k => $m) {
					if (is_array($m) && isset($m[5]) && ('toplevel_page_pp-capabilities-dashboard' == $m[5])) {
						$menu[$k][0] = $cap_title_i8n;
					}
				}
			}
		}

        $dashboard_screen = (isset($_GET['page']) && $_GET['page'] === $cap_page_slug) ? true : false;
        $submenu_slugs              = [];
        $submenu_slugs_conditions   = [];

        foreach ($sub_menu_pages as $feature => $subpage_option) {
            if ($subpage_option['dashboard_control'] === false
                || pp_capabilities_feature_enabled($feature)
                //we'll be using css to hide menu on dashboard control screen to enable dynamic menu control
                || $dashboard_screen
            ) {
                //register the menu if enabled
                $hook = add_submenu_page($cap_page_slug, $subpage_option['title'], $subpage_option['title'], $subpage_option['capabilities'], $subpage_option['page'], $subpage_option['callback']);
                if ($feature === 'roles' && !empty($hook)) {
                    add_action(
                        "load-$hook",
                        function () {
                        require_once(dirname(CME_FILE) . '/includes/roles/roles-functions.php');
                        admin_roles_page_load();
                    }
                    );
                }
            }
            if ($dashboard_screen) {
                $submenu_slugs[] = $subpage_option['page'];
                $submenu_slugs_conditions[] = [ $subpage_option['page'], pp_capabilities_feature_enabled($feature)];
            }
        }

        if ($dashboard_screen) {
            /**
             * Add CSS classes to these submenus to dynamically show/hide them
             * through dashboard page enable/disable features.
             * Copied from PublishPress Blocks
             */
            foreach ($submenu[$cap_page_slug] as $key => $value) {
                if (in_array($submenu[$cap_page_slug][$key][2], $submenu_slugs)) {
                    $slug_ = $submenu[$cap_page_slug][$key][2];

                    // Add a class to hide menu if feature is disabled on Dashboard
                    foreach ($submenu_slugs_conditions as $item) {
                        if ($item[0] === $slug_) {
                            $showHide = $item[1] === false ? ' ppc-hide-menu-item' : '';
                            break;
                        }
                    }

                    $submenu[$cap_page_slug][$key][4] = $slug_ . '-menu-item' . $showHide;
                }
            }
        }

	}

    function initRolesAdmin() {
        // @todo: solve order of execution issue so this column headers definition is not duplicated
        if (!empty($_REQUEST['page']) && ('pp-capabilities-roles' == $_REQUEST['page'])) {
            add_filter(
                "manage_capabilities_page_pp-capabilities-roles_columns",

                function($arr) {
                    return [
                        'cb' 			  => '<input type="checkbox"/>',
                        'name'            => esc_html__('Role Name', 'capability-manager-enhanced'),
						'count'           => esc_html__('Users'),
						'role_type'       => esc_html__('Role Type', 'capability-manager-enhanced'),
						'default_role'    => esc_html__('Default Role', 'capability-manager-enhanced'),
						'admin_access'    => esc_html__('Admin Access', 'capability-manager-enhanced'),
                    ];
                }
            );
        }
    }

	function handleRolesAjax() {
        require_once (dirname(CME_FILE) . '/includes/roles/roles-functions.php');

        if (!class_exists('PP_Capabilities_Roles')) {
            require_once (dirname(CME_FILE) . '/includes/roles/class/class-pp-roles.php');
        }

        $roles = pp_capabilities_roles()->run();
    }

	/**
	 * Manages roles
	 *
	 * @hook add_management_page
	 * @return void
	 */
	public function ManageRoles ()
	{
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_roles')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage roles.', 'capability-manager-enhanced') . '</strong>');
		}

        require_once (dirname(CME_FILE) . '/includes/roles/roles-functions.php');

        if (!class_exists('PP_Capabilities_Roles')) {
            require_once (dirname(CME_FILE) . '/includes/roles/class/class-pp-roles.php');
        }

        $roles = pp_capabilities_roles()->run();

        require_once ( dirname(CME_FILE) . '/includes/roles/roles.php' );
	}


	/**
	 * Manages Editor Features
	 *
	 * @return void
	 */
	public function ManageEditorFeatures() {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_editor_features')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage editor features.', 'capability-manager-enhanced') . '</strong>');
		}

		$this->generateNames();
		$roles = array_keys($this->roles);

		if (!isset($this->current)) {
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
				$this->set_current_role(sanitize_key($_REQUEST['role']));
			}
		}

		if (!isset($this->current) || !get_role($this->current)) {
			$this->current = get_option('default_role');
		}

		if (!in_array($this->current, $roles)) {
			$this->current = array_shift($roles);
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && isset($_POST['ppc-editor-features-role']) && !empty($_REQUEST['_wpnonce'])) {
			if (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'pp-capabilities-editor-features')) {
				wp_die('<strong>' . esc_html__('You do not have permission to manage editor features.', 'capability-manager-enhanced') . '</strong>');
			} else {
				$this->set_current_role(sanitize_key($_POST['ppc-editor-features-role']));

				$classic_editor = pp_capabilities_is_classic_editor_available();

				$def_post_types = array_unique(apply_filters('pp_capabilities_feature_post_types', ['post', 'page']));

                $active_tab     = isset($_POST['pp_caps_tab']) ? sanitize_key($_POST['pp_caps_tab']) : 'post';

				foreach ($def_post_types as $post_type) {
					if ($classic_editor) {

                        if (isset($_POST['editor-features-all-submit'])){
						    $posted_settings = (isset($_POST["capsman_feature_restrict_classic_{$active_tab}"])) ? array_map('sanitize_text_field', $_POST["capsman_feature_restrict_classic_{$active_tab}"]) : [];
                        } else {
                            $posted_settings = (isset($_POST["capsman_feature_restrict_classic_{$post_type}"])) ? array_map('sanitize_text_field', $_POST["capsman_feature_restrict_classic_{$post_type}"]) : [];
                        }

						$post_features_option = (array)get_option("capsman_feature_restrict_classic_{$post_type}", []);
						$post_features_option[sanitize_key($_POST['ppc-editor-features-role'])] = $posted_settings;
						update_option("capsman_feature_restrict_classic_{$post_type}", $post_features_option, false);
					}

                    if (isset($_POST['editor-features-all-submit'])){
					    $posted_settings = (isset($_POST["capsman_feature_restrict_{$active_tab}"])) ? array_map('sanitize_text_field', $_POST["capsman_feature_restrict_{$active_tab}"]) : [];
                    }else {
					    $posted_settings = (isset($_POST["capsman_feature_restrict_{$post_type}"])) ? array_map('sanitize_text_field', $_POST["capsman_feature_restrict_{$post_type}"]) : [];
                    }

					$post_features_option = (array)get_option("capsman_feature_restrict_{$post_type}", []);
					$post_features_option[sanitize_key($_POST['ppc-editor-features-role'])] = $posted_settings;
					update_option("capsman_feature_restrict_{$post_type}", $post_features_option, false);
				}

				ak_admin_notify(__('Settings updated.', 'capability-manager-enhanced'));
			}
		}

		do_action('pp_capabilities_editor_features');
        include(dirname(CME_FILE) . '/includes/features/editor-features.php');
    }

	/**
	 * Manages Admin Features
	 *
	 * @return void
	 */
	public function ManageAdminFeatures() {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_admin_features')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage admin features.', 'capability-manager-enhanced') . '</strong>');
		}

		$this->generateNames();
		$roles = array_keys($this->roles);

		if (!isset($this->current)) {
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
				$this->set_current_role(sanitize_key($_REQUEST['role']));
			}
		}

		if (!isset($this->current) || !get_role($this->current)) {
			$this->current = get_option('default_role');
		}

		if (!in_array($this->current, $roles)) {
			$this->current = array_shift($roles);
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && isset($_POST['ppc-admin-features-role']) && !empty($_REQUEST['_wpnonce'])) {
			if (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'pp-capabilities-admin-features')) {
				wp_die('<strong>' . esc_html__('You do not have permission to manage admin features.', 'capability-manager-enhanced') . '</strong>');
			} else {
				$features_role = sanitize_key($_POST['ppc-admin-features-role']);

				$this->set_current_role($features_role);

				$disabled_admin_items = !empty(get_option('capsman_disabled_admin_features')) ? (array)get_option('capsman_disabled_admin_features') : [];
				$disabled_admin_items[$features_role] = isset($_POST['capsman_disabled_admin_features']) ? array_map('sanitize_text_field', $_POST['capsman_disabled_admin_features']) : '';

				update_option('capsman_disabled_admin_features', $disabled_admin_items, false);

				//set reload option for instant reflection if user is updating own role
				if (in_array($features_role, wp_get_current_user()->roles)){
					$ppc_page_reload = '1';
				}

	            ak_admin_notify(__('Settings updated.', 'capability-manager-enhanced'));
			}
		}

        include(dirname(CME_FILE) . '/includes/features/admin-features.php');
    }

	/**
	 * Manages Frontend Features
	 *
	 * @return void
	 */
	public function ManageFrontendFeatures() {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_frontend_features')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage frontend features.', 'capability-manager-enhanced') . '</strong>');
		}

		$this->generateNames();
		$roles = array_keys($this->roles);

		if (!isset($this->current)) {
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
				$this->set_current_role(sanitize_key($_REQUEST['role']));
			}
		}

		if (!isset($this->current) || !get_role($this->current)) {
			$this->current = get_option('default_role');
		}

		if (!in_array($this->current, $roles)) {
			$this->current = array_shift($roles);
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && isset($_POST['ppc-frontend-features-role']) && !empty($_REQUEST['_wpnonce'])) {
			if (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'pp-capabilities-frontend-features')) {
				wp_die('<strong>' . esc_html__('You do not have permission to manage frontend features.', 'capability-manager-enhanced') . '</strong>');
			} else {
				$features_role = sanitize_key($_POST['ppc-frontend-features-role']);

				$this->set_current_role($features_role);

				$disabled_frontend_items = !empty(get_option('capsman_disabled_frontend_features')) ? (array)get_option('capsman_disabled_frontend_features') : [];
				$disabled_frontend_items[$features_role] = isset($_POST['capsman_disabled_frontend_features']) ? array_map('sanitize_text_field', $_POST['capsman_disabled_frontend_features']) : '';

				update_option('capsman_disabled_frontend_features', $disabled_frontend_items, false);

	            ak_admin_notify(__('Settings updated.', 'capability-manager-enhanced'));
			}
		}

        include(dirname(CME_FILE) . '/includes/features/frontend-features/frontend-features.php');
    }

	/**
	 * Manage Nave Menus
	 *
	 * @return void
	 */
	public function ManageNavMenus() {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_nav_menus')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage admin features.', 'capability-manager-enhanced') . '</strong>');
		}

		$this->generateNames();
		$roles = array_keys($this->roles);

		if (!isset($this->current)) {
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
				$this->set_current_role(sanitize_key($_REQUEST['role']));
			}
		}

		if (!isset($this->current) || !get_role($this->current)) {
			$this->current = get_option('default_role');
		}

		if (!in_array($this->current, $roles)) {
			$this->current = array_shift($roles);
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && isset($_POST['ppc-nav-menu-role']) && !empty($_REQUEST['_wpnonce'])) {
			if (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'pp-capabilities-nav-menus')) {
				wp_die('<strong>' . esc_html__('You do not have permission to manage navigation menus.', 'capability-manager-enhanced') . '</strong>');
			} else {
				$menu_role = sanitize_key($_POST['ppc-nav-menu-role']);

				$this->set_current_role($menu_role);

                //set role nav child menu
                $nav_item_menu_option = !empty(get_option('capsman_nav_item_menus')) ? get_option('capsman_nav_item_menus') : [];

                $nav_item_menu_option[$menu_role] = isset($_POST['pp_cababilities_restricted_items']) ? array_map('sanitize_text_field', $_POST['pp_cababilities_restricted_items']) : '';

                update_option('capsman_nav_item_menus', $nav_item_menu_option, false);

                ak_admin_notify(__('Settings updated.', 'capability-manager-enhanced'));
			}
		}

        include(dirname(CME_FILE) . '/includes/features/nav-menus.php');
    }


	/**
	 * Manages Profile Features
	 *
	 * @return void
	 */
	public function ManageProfileFeatures() {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_profile_features')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage admin features.', 'capability-manager-enhanced') . '</strong>');
		}

		$this->generateNames();
		$roles = array_keys($this->roles);

		if (!isset($this->current)) {
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
				$this->set_current_role(sanitize_key($_REQUEST['role']));
			}
		}

		if (!isset($this->current) || !get_role($this->current)) {
			$this->current = get_option('default_role');
		}

		if (!in_array($this->current, $roles)) {
			$this->current = array_shift($roles);
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && isset($_POST['ppc-profile-features-role']) && !empty($_REQUEST['_wpnonce'])) {
			if (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'pp-capabilities-profile-features')) {
				wp_die('<strong>' . esc_html__('You do not have permission to manage profile features.', 'capability-manager-enhanced') . '</strong>');
			} else {
				$features_role = sanitize_key($_POST['ppc-profile-features-role']);

				$this->set_current_role($features_role);

                $previous_elements              = !empty(get_option('capsman_profile_features_elements')) ? (array)get_option('capsman_profile_features_elements') : [];
				$previous_disabled_profile_items = !empty(get_option('capsman_disabled_profile_features')) ? (array)get_option('capsman_disabled_profile_features') : [];
                $new_disabled_element           = isset($_POST['capsman_disabled_profile_features']) ? array_map('sanitize_text_field', $_POST['capsman_disabled_profile_features']) : [];
                $previous_role_disabled_element = !empty($previous_disabled_profile_items[$features_role]) ? (array)$previous_disabled_profile_items[$features_role] : [];
                $previous_role_element          = !empty($previous_elements[$features_role]) ? (array)$previous_elements[$features_role] : [];

                if (!empty($previous_role_element)) {
                    $previous_role_element_items = array_column($previous_role_element, 'elements');
                } else {
                    $previous_role_element_items = [];
                }


                $disabled_element_differences   = array_diff($previous_role_disabled_element, $previous_role_element_items);
                $new_disabled_element_items     = array_merge($new_disabled_element, $disabled_element_differences);
                $new_disabled_element_items     = array_filter($new_disabled_element_items);

				$previous_disabled_profile_items[$features_role] = $new_disabled_element_items;

				update_option('capsman_disabled_profile_features', $previous_disabled_profile_items, false);

                //update element sort
				$profile_features_elements_order = !empty($_POST['capsman_profile_features_elements_order']) ? sanitize_text_field($_POST['capsman_profile_features_elements_order']) : false;
                if ($profile_features_elements_order) {
                    $profile_features_elements_order = explode(",", $profile_features_elements_order);
                    $profile_features_elements_order = array_filter($profile_features_elements_order);
                    if (!empty($profile_features_elements_order)) {
                        $new_elements     = [];
                        foreach($profile_features_elements_order as $element_key) {
                            if (isset($previous_role_element[$element_key])) {
                                $new_elements[$element_key] = $previous_role_element[$element_key];
                            }
                        }
                        $previous_elements[$features_role] = $new_elements;
                        update_option('capsman_profile_features_elements', $previous_elements, false);
                    }
                }

	            ak_admin_notify(__('Settings updated.', 'capability-manager-enhanced'));
			}
		}

        include(dirname(CME_FILE) . '/includes/features/profile-features.php');
    }


	/**
	 * Manage Redirect
	 *
	 * @return void
	 */
	public function ManageRedirects() {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_redirects')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage redirect.', 'capability-manager-enhanced') . '</strong>');
		}

		$this->generateNames();
		$roles = array_keys($this->roles);

		if (!isset($this->current)) {
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
				$this->set_current_role(sanitize_key($_REQUEST['role']));
			}
		}

		if (!isset($this->current) || !get_role($this->current)) {
			$this->current = get_option('default_role');
		}

		if (!in_array($this->current, $roles)) {
			$this->current = array_shift($roles);
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && isset($_POST['redirects-features-submit']) && !empty($_REQUEST['_wpnonce'])) {
			if (!wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'pp-capabilities-redirects-features')) {
				wp_die('<strong>' . esc_html__('Invalid form. Reload this page and try again.', 'capability-manager-enhanced') . '</strong>');
			} else {
				$features_role = sanitize_key($_POST['ppc-redirects-features-role']);

				$this->set_current_role($features_role);

				$custom_redirect = !empty($_POST['custom_redirect']) ? (int) $_POST['custom_redirect'] : 0;
				$referer_redirect = !empty($_POST['referer_redirect']) ? (int) $_POST['referer_redirect'] : 0;
				$login_redirect = !empty($_POST['login_redirect']) ? home_url(str_replace(home_url(), '', sanitize_text_field($_POST['login_redirect']))) : '';
				$logout_redirect = !empty($_POST['logout_redirect']) ? home_url(str_replace(home_url(), '', sanitize_text_field($_POST['logout_redirect']))) : '';
				$registration_redirect = !empty($_POST['registration_redirect']) ? home_url(str_replace(home_url(), '', sanitize_text_field($_POST['registration_redirect']))) : '';
				$first_login_redirect = !empty($_POST['first_login_redirect']) ? home_url(str_replace(home_url(), '', sanitize_text_field($_POST['first_login_redirect']))) : '';

				$role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];

				$role_redirects[$features_role] = [
					'custom_redirect' => $custom_redirect,
					'referer_redirect' => $referer_redirect,
					'login_redirect' => $login_redirect,
					'logout_redirect' => $logout_redirect,
					'registration_redirect' => $registration_redirect,
					'first_login_redirect' => $first_login_redirect
				];

				update_option('capsman_role_redirects', $role_redirects);

	            ak_admin_notify(__('Settings updated.', 'capability-manager-enhanced'));
			}
		}

        include(dirname(CME_FILE) . '/includes/redirects/redirects.php');
    }



	/**
	 * Manages Dashboard
	 *
	 * @return void
	 */
	public function dashboardPage() {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_dashboard')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage admin features.', 'capability-manager-enhanced') . '</strong>');
		}

        include(dirname(CME_FILE) . '/includes/dashboard.php');
    }

	/**
	 * Filters roles that can be shown in roles list.
	 * This is mainly used to prevent an user admin to create other users with
	 * higher capabilities.
	 *
	 * @hook 'editable_roles' filter.
	 *
	 * @param $roles List of roles to check.
	 * @return array Restircted roles list
	 */
	function filterEditRoles ( $roles )
	{
		global $current_user;

		if (function_exists('wp_get_current_user') || defined('PP_CAPABILITIES_ROLES_FILTER_EARLY_EXECUTION')) {  // Avoid downstream fatal error from premature current_user_can() call if get_editable_roles() is called too early
			$this->generateNames();
			$valid = array_keys($this->roles);

			foreach ( $roles as $role => $caps ) {
				if ( ! in_array($role, $valid) ) {
					unset($roles[$role]);
				}
			}
		}

        return $roles;
	}

	/**
	 * Checks if a user can be edited or not by current administrator.
	 * Returns array('do_not_allow') if user cannot be edited.
	 *
	 * @hook 'map_meta_cap' filter
	 *
	 * @param array $caps Current user capabilities
	 * @param string $cap Capability to check
	 * @param int $user_id Current user ID
	 * @param array $args For our purpose, we receive edited user id at $args[0]
	 * @return array Allowed capabilities.
	 */
	function filterUserEdit ( $caps, $cap, $user_id, $args )
	{
	    if ( ! in_array( $cap, array( 'edit_user', 'delete_user', 'promote_user', 'remove_user' ) ) || ( ! isset($args[0]) ) || $user_id == (int) $args[0] ) {
	        return $caps;
	    }

		$user = new WP_User( (int) $args[0] );

		$this->generateNames();

		if ( defined( 'CME_LEGACY_USER_EDIT_FILTER' ) && CME_LEGACY_USER_EDIT_FILTER ) {
			$valid = array_keys($this->roles);

			foreach ( $user->roles as $role ) {
				if ( ! in_array($role, $valid) ) {
					$caps = array('do_not_allow');
					break;
				}
			}
		} else {
			global $wp_roles;

			foreach ( $user->roles as $role ) {
				$r = get_role( $role );
    			$level = ak_caps2level($r->capabilities);

				if ( ( ! $level ) && ( 'administrator' == $role ) )
					$level = 10;

	    		if ( $level > $this->max_level ) {
		    		$caps = array('do_not_allow');
					break;
			    }
    		}

		}

		return $caps;
	}

	function processRoleUpdate() {
		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && ( ! empty($_REQUEST['SaveRole']) || ! empty($_REQUEST['AddCap']) ) ) {
			check_admin_referer('capsman-general-manager');

			if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities')) {
				// TODO: Implement exceptions.
				wp_die('<strong>' . esc_html__('You do not have permission to manage capabilities.', 'capability-manager-enhanced') . '</strong>');
			}

			if ( ! empty($_REQUEST['current']) ) { // don't process role update unless form variable is received
				$role = get_role(sanitize_key($_REQUEST['current']));
				$current_level = ($role) ? ak_caps2level($role->capabilities) : 0;

				$this->processAdminGeneral();

				$set_level = (isset($_POST['level'])) ? (int) $_POST['level'] : 0;

				if ($set_level != $current_level) {
					global $wp_roles, $wp_version;

					if ( version_compare($wp_version, '4.9', '>=') ) {
						$wp_roles->for_site();
					} else {
						$wp_roles->reinit();
					}

					foreach( get_users(array('role' => sanitize_key($_REQUEST['current']), 'fields' => 'ID')) as $ID ) {
						$user = new WP_User($ID);
						$user->get_role_caps();
						$user->update_user_level_from_caps();
					}
				}
			}
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && ( ! empty($_REQUEST['RenameRole']) ) ) {
			check_admin_referer('capsman-general-manager');

			if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities')) {
				// TODO: Implement exceptions.
				wp_die('<strong>' . esc_html__('You do not have permission to manage capabilities.', 'capability-manager-enhanced') . '</strong>');
			}

			if ( ! empty($_REQUEST['current']) ) { // don't process role update unless form variable is received
				$this->processAdminGeneral();
			}
		}
	}

	/**
	 * Manages global settings admin.
	 *
	 * @hook add_submenu_page
	 * @return void
	 */
	function generalManager () {
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities')) {
            // TODO: Implement exceptions.
		    wp_die('<strong>' . esc_html__('You do not have permission to manage capabilities.', 'capability-manager-enhanced') . '</strong>');
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD'])) {
			if ( empty($_REQUEST['SaveRole']) && empty($_REQUEST['AddCap']) && empty($_REQUEST['RenameRole']) ) {
				check_admin_referer('capsman-general-manager');
				$this->processAdminGeneral();
			} elseif ( ! empty($_REQUEST['SaveRole']) ) {
				ak_admin_notify( $this->message );  // moved update operation to earlier action to avoid UI refresh issues.  But outputting notification there breaks styling.
			} elseif ( ! empty($_REQUEST['AddCap']) ) {
				ak_admin_notify( $this->message );
			}
		} else {
			if (!empty($_REQUEST['added'])) {
				ak_admin_notify(__('New capability added to role.', 'capability-manager-enhanced'));
			}
		}

		$this->generateNames();
		$roles = array_keys($this->roles);

		if ( ! isset($this->current) ) { // By default, we manage the default role
			if ('POST' !== $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['role'])) {
				$role = sanitize_key($_REQUEST['role']);

				if (!pp_capabilities_is_editable_role($role)) {
					wp_die(esc_html__('The selected role is not editable.', 'capability-manager-enhanced'));
				}

				$this->set_current_role($role);
			}
		}

		if (!isset($this->current) || !get_role($this->current)) {
			$this->current = $this->get_last_role();
		}

		if ( ! in_array($this->current, $roles) ) {    // Current role has been deleted.
			$this->current = array_shift($roles);
		}

		include ( dirname(CME_FILE) . '/includes/admin.php' );
	}

	/**
	 * Processes and saves the changes in the general capabilities form.
	 *
	 * @return void
	 */
	private function processAdminGeneral ()
	{
		check_admin_referer('capsman-general-manager');

		if (! isset($_POST['action']) || 'update' != $_POST['action'] ) {
		    // TODO: Implement exceptions. This must be a fatal error.
			ak_admin_error(__('Bad form Received', 'capability-manager-enhanced'));
			return;
		}

		// Select a new role.
		if ( ! empty($post['LoadRole']) && !empty($_POST['role']) ) {
			$this->set_current_role(sanitize_key($_POST['role']));
		} elseif (!empty($_POST['current'])) {
			$this->set_current_role(sanitize_key($_POST['current']));

			require_once( dirname(__FILE__).'/handler.php' );
			$capsman_modify = new CapsmanHandler( $this );
			$capsman_modify->processAdminGeneral();
		}

        //save user sidebar panel state
        if (!empty($_POST['ppc_metabox_state'])) {
            $metabox_state = map_deep($_POST['ppc_metabox_state'], 'sanitize_text_field');
            update_user_meta(get_current_user_id(), 'ppc_sidebar_metabox_state', $metabox_state);
        }
	}

	/**
	 * Callback function to create names.
	 * Replaces underscores by spaces and uppercases the first letter.
	 *
	 * @access private
	 * @param string $cap Capability name.
	 * @return string	The generated name.
	 */
	function _capNamesCB ( $cap )
	{
		$cap = str_replace('_', ' ', $cap);

		return $cap;
	}

	/**
	 * Generates an array with the system capability names.
	 * The key is the capability and the value the created screen name.
	 *
	 * @uses self::_capNamesCB()
	 * @return void
	 */
	function generateSysNames ()
	{
		$this->max_level = 10;
		$this->roles = ak_get_roles(true);
		$caps = array();

		foreach ( array_keys($this->roles) as $role ) {
			$role_caps = get_role($role);
			$caps = array_merge( $caps, (array) $role_caps->capabilities );  // user reported PHP 5.3.3 error without array cast
		}

		$keys = array_keys($caps);
		$names = array_map(array($this, '_capNamesCB'), $keys);
		$this->capabilities = array_combine($keys, $names);

		asort($this->capabilities);
	}

	/**
	 * Generates an array with the user capability names.
	 * If user has 'administrator' role, system roles are generated.
	 * The key is the capability and the value the created screen name.
	 * A user cannot manage more capabilities that has himself (Except for administrators).
	 *
	 * @uses self::_capNamesCB()
	 * @return void
	 */
	function generateNames ()
	{
		if ( current_user_can('administrator') || ( is_multisite() && is_super_admin() ) ) {
			$this->generateSysNames();
		} else {
		    global $user_ID;
		    $user = new WP_User($user_ID);
		    $this->max_level = ak_caps2level($user->allcaps);

		    $keys = array_keys($user->allcaps);
    		$names = array_map(array($this, '_capNamesCB'), $keys);

	    	$this->capabilities = ( $keys ) ? array_combine($keys, $names) : array();

		    $roles = ak_get_roles(true);
    		unset($roles['administrator']);

			if ( ( defined( 'CME_LEGACY_USER_EDIT_FILTER' ) && CME_LEGACY_USER_EDIT_FILTER ) || ( ! empty( $_REQUEST['page'] ) && 'pp-capabilities' == $_REQUEST['page'] ) ) {
				foreach ( $user->roles as $role ) {			// Unset the roles from capability list.
					unset ( $this->capabilities[$role] );
					unset ( $roles[$role]);					// User cannot manage his roles.
				}
			}

	    	asort($this->capabilities);

		    foreach ( array_keys($roles) as $role ) {
			    $r = get_role($role);
    			$level = ak_caps2level($r->capabilities);

	    		if ( $level > $this->max_level ) {
		    		unset($roles[$role]);
			    }
    		}

	    	$this->roles = $roles;
		}
	}

	/**
	 * Manages backup, restore and resset roles and capabilities
	 *
	 * @hook add_management_page
	 * @return void
	 */
	function backupTool ()
	{
		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_backup')) {
		    // TODO: Implement exceptions.
			wp_die('<strong>' . esc_html__('You do not have permission to restore roles.', 'capability-manager-enhanced') . '</strong>');
		}

		if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD'])) {
			check_admin_referer('pp-capabilities-backup');
			require_once( dirname(__FILE__).'/backup-handler.php' );
			$cme_backup_handler = new Capsman_BackupHandler( $this );
			$cme_backup_handler->processBackupTool();
		}

		if ( isset($_GET['action']) && 'reset-defaults' == $_GET['action']) {
			check_admin_referer('capsman-reset-defaults');
			require_once( dirname(__FILE__).'/backup-handler.php' );
			$cme_backup_handler = new Capsman_BackupHandler( $this );
			$cme_backup_handler->backupToolReset();
		}

		include ( dirname(CME_FILE) . '/includes/backup.php' );
	}


	/**
	 * Processes export.
     *
     * This function need to run in admin init
     * to enable clean download.
	 *
	 * @return void
	 */
	function processExport()
	{
        global $wpdb;

        if ( isset($_POST['export_backup']) && isset($_POST['pp_capabilities_export_section']) && !empty($_POST['pp_capabilities_export_section'])) {
            check_admin_referer('pp-capabilities-backup');

			if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_backup')) {
			    // TODO: Implement exceptions.
				wp_die('<strong>' . esc_html__('You do not have permission to perform this action.', 'capability-manager-enhanced') . '</strong>');
			}

            $export_option   = array_map('sanitize_text_field', $_POST['pp_capabilities_export_section']);
            $backup_sections = pp_capabilities_backup_sections();
            $charset	     = get_option( 'blog_charset' );
            $data		     = [];

            //add role
            if(in_array('user_roles', $export_option)){
                $data['user_roles'] = get_option($wpdb->prefix . 'user_roles');
            }

            //other section
            foreach($backup_sections as $backup_key => $backup_section){

                if(!in_array($backup_key, $export_option)){
                    continue;
                }
                $section_options = $backup_section['options'];
                if(is_array($section_options) && !empty($section_options)){
                    foreach($section_options as $section_option){
                        $active_backup[] = $backup_section['label'];
                        $data[$section_option] = get_option($section_option);
                    }
                }
            }

            // Set the download headers.
            nocache_headers();
            header( 'Content-Type: application/json; charset=' . $charset );
            header( 'Content-Disposition: attachment; filename=capabilities-export-' . current_time('Y-m-d_g-i-s_a') . '.json' );
            header( "Expires: 0" );

            // encode the export data.
            echo json_encode($data);

            // Start the download.
            die();

		}
    }

	function settingsPage() {
		include ( dirname(CME_FILE) . '/includes/settings.php' );
	}

	/**
	 * Initialize plugin capabilities class
	 *
	 * @return void
	 */
	public function initPluginCapabilities() {
		require_once dirname(CME_FILE) . '/includes/plugin-capabilities.php';
		\PublishPress\Capabilities\Plugin_Capabilities::instance();
	}

    /**
     * Redirect for profile features capturing
     *
     * @return void
     */
    function profileFeaturesCaptureRedirect() {

		if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_profile_features')) {
            return;
		}

        if (is_admin() && pp_capabilities_feature_enabled('profile-features') && !empty($_REQUEST['page']) && 'pp-capabilities-profile-features' === $_REQUEST['page']) {
            global $capsman, $role_has_user;
            $default_role = $capsman->get_last_role();

            if (!empty($_REQUEST['role'])) {
				$default_role = sanitize_key($_REQUEST['role']);
                $this->set_current_role($default_role);
			}

            $profile_element_updated = (array) get_option("capsman_profile_features_updated", []);
            $refresh_element = isset($_REQUEST['refresh_element']) ? (int) $_REQUEST['refresh_element'] : 0;
            $role_refresh    = isset($_REQUEST['role_refresh']) ? (int) $_REQUEST['role_refresh'] : 0;

            //get user in current role
            $role_user = get_users(
                [
                    'role'    => $default_role,
                    'exclude' => [get_current_user_id()],
                    'number'  => 1,
                ]
            );

            $role_has_user = true;
            if (empty($role_user) && $default_role !== 'administrator') {
                $role_has_user = false;
            }

            if (
                is_array($profile_element_updated)
                && isset($profile_element_updated[$default_role])
                && (int)$profile_element_updated[$default_role] > 0
            ) {
                if ($refresh_element === 0 && $role_refresh === 0) {
                    return;
                }
            }

            if (!get_option('cme_profile_features_auto_redirect') && !$role_refresh) {
                return;
            }

            if (empty($role_user) && $default_role !== 'administrator') {
                return;
            }

            $can_redirect = true;

            if (!empty($role_user)) {
                $testing_user = $role_user[0];
                if (!user_can($testing_user->ID, 'read')) {
                    $can_redirect = false;
                }

            }

            if ($can_redirect) {
                //redirect user to test link for validation and redirection
                if (empty($role_user)) {
                    $test_link = admin_url('profile.php?ppc_profile_element=1');
                } else {
                    $test_as_user = $role_user[0];
                    $test_link = add_query_arg(
                        [
                        'ppc_test_user'         => base64_encode($test_as_user->ID),
                        'profile_feature_action' => 1,
                        '_wpnonce'              => wp_create_nonce('ppc-test-user')
                    ],
                        admin_url('users.php')
                    );
                }
                if ($refresh_element > 0) {
                    delete_option('capsman_profile_features_updated');
                }
                update_option('capsman_profile_features_elements_testing_role', $default_role, false);
                wp_safe_redirect($test_link);
                exit();
            }
		}
    }
}

function cme_publishpressFooter() {
	?>
	<footer>

	<div class="pp-rating">
	<a href="https://wordpress.org/support/plugin/capability-manager-enhanced/reviews/#new-post" target="_blank" rel="noopener noreferrer">
	<?php printf(
		esc_html__('If you like %s, please leave us a %s rating. Thank you!', 'capability-manager-enhanced'),
		'<strong>PublishPress Capabilities</strong>',
		'<span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span>'
		);
	?>
	</a>
	</div>

	<hr>
	<nav>
	<ul>
	<li><a href="https://publishpress.com/capability-manager/" target="_blank" rel="noopener noreferrer" title="<?php esc_attr_e('About PublishPress Capabilities', 'capability-manager-enhanced');?>"><?php esc_html_e('About', 'capability-manager-enhanced');?>
	</a></li>
	<li><a href="https://publishpress.com/knowledge-base/how-to-use-capability-manager/" target="_blank" rel="noopener noreferrer" title="<?php esc_attr_e('Capabilites Documentation', 'capability-manager-enhanced');?>"><?php esc_html_e('Documentation', 'capability-manager-enhanced');?>
	</a></li>
	<li><a href="https://publishpress.com/contact" target="_blank" rel="noopener noreferrer" title="<?php esc_attr_e('Contact the PublishPress team', 'capability-manager-enhanced');?>"><?php esc_html_e('Contact', 'capability-manager-enhanced');?>
	</a></li>
	</ul>
	</nav>

	<div class="pp-pressshack-logo">
	<a href="https://publishpress.com" target="_blank" rel="noopener noreferrer">

	<img src="<?php echo esc_url_raw(plugins_url('', CME_FILE) . '/common/img/publishpress-logo.png');?>" />
	</a>
	</div>

	</footer>
	<?php
}
