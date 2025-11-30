<?php

/*
 * PublishPress Capabilities [Free]
 *
 * Admin execution controller: menu registration and other filters and actions that need to be loaded for every wp-admin URL
 *
 * This module should not include full functions related to our own plugin screens.
 * Instead, use these filter and action handlers to load other classes when needed.
 *
 */
class PP_Capabilities_Admin_UI {
    function __construct() {
        global $pagenow;

        /**
         * The class responsible for handling notifications
         */
        require_once (dirname(CME_FILE) . '/classes/pp-capabilities-notices.php');

        /**
         * Installer class
         */
        require_once (dirname(CME_FILE) . '/classes/pp-capabilities-installer.php');

        add_action('init', [$this, 'featureRestrictionsGutenberg'], PHP_INT_MAX - 1);

        if (is_admin()) {
            // Redirect on plugin activation
            add_action('admin_init', [$this, 'redirect_on_activate'], 2000);

            add_action('admin_init', [$this, 'featureRestrictionsClassic'], PHP_INT_MAX - 1);
            add_action('wp_ajax_save_dashboard_feature_by_ajax', [$this, 'saveDashboardFeature']);

            // Installation hooks
            add_action(
                'pp_capabilities_install',
                ['PublishPress\\Capabilities\\Classes\\PP_Capabilities_Installer', 'runInstallTasks']
            );
            add_action(
                'pp_capabilities_upgrade',
                ['PublishPress\\Capabilities\\Classes\\PP_Capabilities_Installer', 'runUpgradeTasks']
            );
            add_action('admin_init', [$this, 'manage_installation'], 2000);

            //Add role blocked nav menu indication
            add_action('wp_nav_menu_item_custom_fields', [$this, 'add_nav_menu_indicator'], 20, 5);
        }

        add_filter('cme_publishpress_capabilities_capabilities', 'cme_publishpress_capabilities_capabilities');

        add_action('admin_enqueue_scripts', [$this, 'adminScripts'], 100);
        add_action('admin_print_scripts', [$this, 'adminPrintScripts']);

        add_action('profile_update', [$this, 'action_profile_update'], 10, 2);

        if (is_multisite()) {
            add_action('add_user_to_blog', [$this, 'action_profile_update'], 9);
        } else {
            add_action('user_register', [$this, 'action_profile_update'], 9);
        }
        add_action('init', [$this, 'register_textdomain']);

        if (is_admin() && (isset($_REQUEST['page']) && (in_array($_REQUEST['page'], ['pp-capabilities', 'pp-capabilities-backup', 'pp-capabilities-roles', 'pp-capabilities-admin-menus', 'pp-capabilities-editor-features', 'pp-capabilities-nav-menus', 'pp-capabilities-settings', 'pp-capabilities-admin-features', 'pp-capabilities-profile-features', 'pp-capabilities-dashboard', 'pp-capabilities-frontend-features', 'pp-capabilities-redirects']))

        || (!empty($_REQUEST['action']) && in_array($_REQUEST['action'], ['pp-roles-add-role', 'pp-roles-delete-role', 'pp-roles-hide-role', 'pp-roles-unhide-role']))
        || ( ! empty($_SERVER['SCRIPT_NAME']) && strpos(sanitize_text_field($_SERVER['SCRIPT_NAME']), 'p-admin/plugins.php' ) && ! empty($_REQUEST['action'] ) )
        || ( isset($_GET['action']) && ('reset-defaults' == $_GET['action']) && isset($_REQUEST['_wpnonce']) && wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'capsman-reset-defaults') )
        || in_array( $pagenow, array( 'users.php', 'user-edit.php', 'profile.php', 'user-new.php' ) )
        ) ) {
            global $capsman;

            // Run the plugin
            require_once ( dirname(CME_FILE) . '/framework/lib/formating.php' );
            require_once ( dirname(CME_FILE) . '/framework/lib/users.php' );

            require_once ( dirname(CME_FILE) . '/includes/manager.php' );
            $capsman = new CapabilityManager();
        } else {
            add_action( 'admin_menu', [$this, 'cmeSubmenus'], 18 );
        }

        add_action('init', function() { // late execution avoids clash with autoloaders in other plugins
            global $pagenow;

            if ((($pagenow == 'admin.php') && isset($_GET['page']) && in_array($_GET['page'], ['pp-capabilities', 'pp-capabilities-backup', 'pp-capabilities-roles', 'pp-capabilities-admin-menus', 'pp-capabilities-editor-features', 'pp-capabilities-nav-menus', 'pp-capabilities-settings', 'pp-capabilities-admin-features', 'pp-capabilities-profile-features', 'pp-capabilities-dashboard', 'pp-capabilities-redirects'])) // @todo: CSS for button alignment in Editor Features, Admin Features
            || (defined('DOING_AJAX') && DOING_AJAX && !empty($_REQUEST['action']) && (false !== strpos(sanitize_key($_REQUEST['action']), 'capability-manager-enhanced')))
            ) {
                if (!class_exists('\PublishPress\WordPressReviews\ReviewsController')) {
                    include_once PUBLISHPRESS_CAPS_ABSPATH . '/lib/vendor/publishpress/wordpress-reviews/ReviewsController.php';
                }

                if (class_exists('\PublishPress\WordPressReviews\ReviewsController')) {
                    $reviews = new \PublishPress\WordPressReviews\ReviewsController(
                        'capability-manager-enhanced',
                        'PublishPress Capabilities',
                        plugin_dir_url(CME_FILE) . 'common/img/capabilities-wp-logo.png'
                    );

                    add_filter('publishpress_wp_reviews_display_banner_capability-manager-enhanced', [$this, 'shouldDisplayBanner']);

                    $reviews->init();
                }
            }
        });


        add_filter('pp_capabilities_feature_post_types', [$this, 'fltEditorFeaturesPostTypes'], 5);
        add_filter('block_editor_settings_all', [$this, 'filterCodeEditingStatus'], 999);
        add_filter('classic_editor_enabled_editors_for_post_type', [$this, 'filterRolePostTypeEditor'], 10, 2);
        add_filter('classic_editor_plugin_settings', [$this, 'filterRoleEditorSettings']);

        //profile features integration
        require_once (dirname(CME_FILE) . '/includes/features/restrict-profile-features.php');
        \PublishPress\Capabilities\PP_Capabilities_Profile_Features::instance();

        //frontend features post metabox
        require_once (dirname(__FILE__) . '/features/frontend-features/frontend-features-metaboxes.php');
        \PublishPress\Capabilities\PP_Capabilities_Frontend_Features_Metaboxes::instance();

        //capabilities settings
        add_action('pp-capabilities-settings-ui', [$this, 'settingsUI']);

        //clear the "done" flag on new plugin install
        add_action('activated_plugin', [$this, 'clearProfileFeaturesDoneFlag'], 10, 2);
        //prevent access to admin dashboard
        add_action('admin_init', [$this, 'blockDashboardAccess']);
        // Add plugin list action links
        add_filter('plugin_action_links', [$this, 'addPluginActionLinks'], 10, 2);
        add_filter('plugin_row_meta', [$this, 'addPluginRowMetaLinks'], 10, 2);
    }

	function register_textdomain() {

        $domain       = 'capability-manager-enhanced';
		$mofile_custom = sprintf('%s-%s.mo', $domain, get_user_locale());
		$locations = [
			trailingslashit( WP_LANG_DIR . '/' . $domain ),
			trailingslashit( WP_LANG_DIR . '/loco/plugins/'),
			trailingslashit( WP_LANG_DIR . '/plugins/'),
			trailingslashit( WP_LANG_DIR ),
			trailingslashit( plugin_dir_path(CME_FILE) . 'languages' ),
        ];
		// Try custom locations in WP_LANG_DIR.
		foreach ($locations as $location) {
			if (load_textdomain($domain, $location . $mofile_custom)) {
				return true;
			}
		}

	}

    /**
     * Filters the editors that are enabled for the post type.
     *
     * @param array $editors    Associative array of the editors and whether they are enabled for the post type.
     * @param string $post_type The post type.
     */
    public function filterRolePostTypeEditor($editors, $post_type) {
      $user = wp_get_current_user();

      if (is_object($user) && isset($user->roles)) {
          $current_user_editors = [];
          foreach ($user->roles as $user_role) {
              //get role option
              $role_option = get_option("pp_capabilities_{$user_role}_role_option", []);
              if (is_array($role_option) && !empty($role_option) && !empty($role_option['role_editor'])) {
                  $current_user_editors = array_merge($current_user_editors, $role_option['role_editor']);
              }
          }

          if (!empty($current_user_editors)) {
              $current_user_editors = array_unique($current_user_editors);
              $editors = array(
                  'classic_editor' => in_array('classic_editor', $current_user_editors) ? true : false,
                  'block_editor'   => in_array('block_editor', $current_user_editors) ? true : false,
              );
          }
      }

      return $editors;
  }

  /**
   * Override the classic editor plugin's settings.
   *
   * @param bool $settings
   * @return mixed
   */
  public function filterRoleEditorSettings($settings) {
      $user = wp_get_current_user();

      if (is_object($user) && isset($user->roles)) {
          $current_user_editors = [];
          foreach ($user->roles as $user_role) {
              //get role option
              $role_option = get_option("pp_capabilities_{$user_role}_role_option", []);
              if (is_array($role_option) && !empty($role_option) && !empty($role_option['role_editor'])) {
                  $current_user_editors = array_merge($current_user_editors, $role_option['role_editor']);
              }
          }

          if (!empty($current_user_editors)) {
              $current_user_editors = array_unique($current_user_editors);
              $settings = [];
              $settings['editor'] = ($current_user_editors[0] === 'classic_editor') ? 'classic' : 'block';
              $settings['allow-users'] = count($current_user_editors) > 1 ? true : false;
          }
      }

      return $settings;
  }

    public function filterCodeEditingStatus($settings) {
        $user = wp_get_current_user();

        if (is_object($user) && isset($user->roles)) {
            foreach ($user->roles as $user_role) {
                //get role option
                $role_option = get_option("pp_capabilities_{$user_role}_role_option", []);
                if (is_array($role_option) && !empty($role_option) && !empty($role_option['disable_code_editor']) && (int)$role_option['disable_code_editor'] > 0) {
                    $settings['codeEditingEnabled'] = false;
                    break;
                }
            }
        }

        return $settings;
    }

    public function fltEditorFeaturesPostTypes($def_post_types) {
        if((int)get_option('cme_editor_features_private_post_type') > 0 || defined('PP_CAPABILITIES_PRIVATE_TYPES')){
            $private_cpt = get_post_types(['public' => true, 'show_ui' => true], 'names', 'or');
            $public_cpt  = get_post_types(['public' => true, 'show_ui' => true], 'names', 'or');
            $def_post_types =  array_unique(array_merge($def_post_types, $private_cpt, $public_cpt));
        }else{
            $def_post_types = array_merge($def_post_types, get_post_types(['public' => true], 'names'));
        }

        unset($def_post_types['attachment']);

        return $def_post_types;
    }

    public function shouldDisplayBanner() {
        global $pagenow;

        return ($pagenow == 'admin.php') && isset($_GET['page']) && in_array($_GET['page'], ['pp-capabilities', 'pp-capabilities-backup', 'pp-capabilities-roles', 'pp-capabilities-admin-menus', 'pp-capabilities-editor-features', 'pp-capabilities-nav-menus', 'pp-capabilities-settings', 'pp-capabilities-admin-features', 'pp-capabilities-profile-features', 'pp-capabilities-dashboard', 'pp-capabilities-redirects']);
    }

    private function applyFeatureRestrictions($editor = 'gutenberg') {
        global $pagenow;

        if (is_multisite() && is_super_admin() && !defined('PP_CAPABILITIES_RESTRICT_SUPER_ADMIN')) {
            return;
        }

        if (!pp_capabilities_feature_enabled('editor-features')) {
            return;
        }

        // Return if not a post editor request
        if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
            return;
        }

        static $def_post_types; // avoid redundant filter application

        if (!isset($def_post_types)) {
            $def_post_types = array_unique(apply_filters('pp_capabilities_feature_post_types', ['post', 'page']));
        }

        $post_type = pp_capabilities_get_post_type();

        // Return if not a supported post type
        if (in_array($post_type, apply_filters('pp_capabilities_unsupported_post_types', ['attachment']))) {
            return;
        }

        switch ($editor) {
            case 'gutenberg':
                if (_pp_capabilities_is_block_editor_active()) {
                    require_once ( dirname(CME_FILE) . '/includes/features/restrict-editor-features.php' );
                    PP_Capabilities_Post_Features::applyRestrictions($post_type);
                }

                break;

            case 'classic':
                if (!_pp_capabilities_is_block_editor_active()) {
                    require_once ( dirname(CME_FILE) . '/includes/features/restrict-editor-features.php' );
                    PP_Capabilities_Post_Features::adminInitClassic($post_type);
                }
        }
    }

    function featureRestrictionsGutenberg() {
        $this->applyFeatureRestrictions();
    }

    function featureRestrictionsClassic() {
        $this->applyFeatureRestrictions('classic');
    }

    function adminScripts() {
        global $publishpress;

        // Include global style and script
        wp_enqueue_style('cme-admin-global-css', plugin_dir_url(CME_FILE) . 'common/css/global.css', [], PUBLISHPRESS_CAPS_VERSION);
        wp_enqueue_script('cme-admin-global-js', plugin_dir_url(CME_FILE) . 'common/js/global.js', ['jquery'],  PUBLISHPRESS_CAPS_VERSION);
        wp_localize_script(
            'cme-admin-global-js',
            'ppCapabilitiesGlobalData',
            [
                'nonce' => wp_create_nonce('ppc-test-user-admin-bar-action')
            ]
        );

        if (function_exists('get_current_screen') && (!defined('PUBLISHPRESS_VERSION') || empty($publishpress) || empty($publishpress->modules) || empty($publishpress->modules->roles))) {
            $screen = get_current_screen();

            if ('user-edit' === $screen->base || 'profile' === $screen->base || ('user' === $screen->base && 'add' === $screen->action)) {

				$multi_role = ('user-edit' === $screen->base && get_option('cme_capabilities_edit_user_multi_roles')) || ('user' === $screen->base && 'add' === $screen->action && (defined('PP_CAPABILITIES_ADD_USER_MULTI_ROLES') || get_option('cme_capabilities_add_user_multi_roles'))) ? true : false;

                // Check if we are on the user's profile page
                wp_enqueue_script(
                    'pp-capabilities-chosen-js',
                    plugin_dir_url(CME_FILE) . 'common/libs/chosen-v1.8.7/chosen.jquery.js',
                    ['jquery'],
                    PUBLISHPRESS_CAPS_VERSION
                );

                // Enqueue jQuery UI script from WordPress core
                wp_enqueue_script('jquery-ui-core');

                wp_enqueue_script(
                    'pp-capabilities-roles-profile-js',
                    plugin_dir_url(CME_FILE) . 'common/js/profile.js',
                    ['jquery', 'pp-capabilities-chosen-js'],
                    PUBLISHPRESS_CAPS_VERSION
                );

                wp_enqueue_style(
                    'pp-capabilities-chosen-css',
                    plugin_dir_url(CME_FILE) . 'common/libs/chosen-v1.8.7/chosen.css',
                    false,
                    PUBLISHPRESS_CAPS_VERSION
                );
                wp_enqueue_style(
                    'pp-capabilities-roles-profile-css',
                    plugin_dir_url(CME_FILE) . 'common/css/profile.css',
                    ['pp-capabilities-chosen-css'],
                    PUBLISHPRESS_CAPS_VERSION
                );

                $roles = !empty($_GET['user_id']) ? $this->getUsersRoles((int) $_GET['user_id']) : [];

                if (empty($roles)) {
                    $roles = (array) get_option('default_role');
                }

                wp_localize_script(
                    'pp-capabilities-roles-profile-js',
                    'ppCapabilitiesProfileData',
                    [
                        'role_description'  => esc_html__('Drag multiple roles selection to change order.', 'capability-manager-enhanced'),
                        'selected_roles'    => $roles,
                        'multi_roles'       => $multi_role ? 1 : 0,
                        'profile_page_title' => esc_html__('Page title', 'capability-manager-enhanced'),
                        'rankmath_title'    => esc_html__('Rank Math SEO', 'capability-manager-enhanced'),
                        'nonce'             => wp_create_nonce('ppc-profile-edit-action')
                    ]
                );
            }
        }
    }

    function adminPrintScripts() {

        global $capabilities_toplevel_page;

        if (!empty($capabilities_toplevel_page) && pp_capabilities_feature_enabled('capabilities') && current_user_can('manage_capabilities')) {
            /**
             * Update capabilities top level slug from dashboard/toplevel page to capabilities
             */
            $menu_inline_script = "
            jQuery(document).ready( function($) {
                if (jQuery('li#toplevel_page_{$capabilities_toplevel_page} a.toplevel_page_{$capabilities_toplevel_page}').length > 0) {
                    var toplevel_page = jQuery('li#toplevel_page_{$capabilities_toplevel_page} a.toplevel_page_{$capabilities_toplevel_page}');
                    var toplevel_page_link = toplevel_page.attr('href');
                    if (toplevel_page_link) {
                        toplevel_page.attr('href', toplevel_page_link.replace('{$capabilities_toplevel_page}', 'pp-capabilities'));
                    }
                }
            });";
            ppc_add_inline_script($menu_inline_script);
        }

        // Counteract overzealous menu icon styling in PublishPress <= 3.2.0 :)
        if (defined('PUBLISHPRESS_VERSION') && version_compare(constant('PUBLISHPRESS_VERSION'), '3.2.0', '<=') && defined('PP_CAPABILITIES_FIX_ADMIN_ICON')):?>
        <style type="text/css">
        #toplevel_page_pp-capabilities-dashboard .dashicons-before::before, #toplevel_page_pp-capabilities-dashboard .wp-has-current-submenu .dashicons-before::before {
            background-image: inherit !important;
            content: "\f112" !important;
        }
        </style>
        <?php endif;
    }

    /**
     * Returns a list of roles with name and display name to populate a select field.
     *
     * @param int $userId
     *
     * @return array
     */
    protected function getUsersRoles($userId)
    {
        if (empty($userId)) {
            return [];
        }

        $user = get_user_by('id', $userId);

        if (empty($user)) {
            return [];
        }

        return array_values($user->roles);
    }

    public function action_profile_update($userId, $oldUserData = [])
    {
        // Check if we need to update the user's roles, allowing to set multiple roles.
        if ((!empty($_REQUEST['_wpnonce']) && wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'update-user_' . $userId)
            || !empty($_REQUEST['_wpnonce_create-user']) && wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce_create-user']), 'create-user'))
            && isset($_POST['pp_roles']) && current_user_can('promote_users')) {
            // Remove the user's roles
            $user = get_user_by('ID', $userId);

            $newRoles     = array_map('sanitize_key', $_POST['pp_roles']);
            $currentRoles = $user->roles;

            if (empty($newRoles) || !is_array($newRoles)) {
                return;
            }

            // Remove all roles
            foreach ($currentRoles as $role) {
                // Check if it is a bbPress rule. If so, don't remove it.
                $isBBPressRole = preg_match('/^bbp_/', $role);

                if (!$isBBPressRole) {
                    $user->remove_role($role);
                }
            }

            // Add new roles in order
            foreach ($newRoles as $role) {
                $user->add_role($role);
            }
        }
    }


    // perf enhancement: display submenu links without loading framework and plugin code
    function cmeSubmenus() {
        global $capabilities_toplevel_page, $current_user;

        //make sure admin doesn't lose access to capabilities screen
        if (!current_user_can('manage_capabilities') && current_user_can('administrator')) {
            $pp_capabilities = apply_filters('cme_publishpress_capabilities_capabilities', []);
            $role = get_role('administrator');
            foreach ($pp_capabilities as $cap) {
                if (!$role->has_cap($cap)) {
                    $role->add_cap($cap);
                    $current_user->allcaps[$cap] = true;
                }
            }
        }

        //we need to set primary menu capability to the first menu user has access to
        $sub_menu_pages = pp_capabilities_sub_menu_lists(true);
        $user_menu_caps = pp_capabilities_user_can_caps();
        $menu_cap       = false;
        $cap_callback   = false;
        $cap_page_slug  = false;
        $cap_title      = __('Capabilities', 'capability-manager-enhanced');
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

        foreach ($sub_menu_pages as $feature => $subpage_option) {
            if ($subpage_option['dashboard_control'] === false || pp_capabilities_feature_enabled($feature)) {
                add_submenu_page($cap_page_slug, $subpage_option['title'], $subpage_option['title'], $subpage_option['capabilities'], $subpage_option['page'], $subpage_option['callback']);
            }
        }

    }


    public function settingsUI() {
        wp_enqueue_script('pp-capabilities-chosen-js', plugin_dir_url(CME_FILE) . 'common/libs/chosen-v1.8.7/chosen.jquery.js', ['jquery'], PUBLISHPRESS_CAPS_VERSION);
        wp_enqueue_style('pp-capabilities-chosen-css', plugin_dir_url(CME_FILE) . 'common/libs/chosen-v1.8.7/chosen.css', false, PUBLISHPRESS_CAPS_VERSION);
        require_once(dirname(__FILE__).'/settings-ui.php');
        new Capabilities_Settings_UI();
    }

    /**
     * Clear the "done" flag on new plugin install
     * (forcing another auto-refresh on next Profile Restrictions visit)
     *
     * @param string $plugin       Path to the plugin file relative to the plugins directory.
     * @param bool   $network_wide Whether to enable the plugin for all sites in the network
     * or just the current site. Multisite only. Default false.
     *
     * @return void
     */
    public function clearProfileFeaturesDoneFlag($plugin, $network_wide) {
        delete_option('capsman_profile_features_updated');
    }

    /**
     * Block dasbboard access
     *
     * @return void
     */
    public function blockDashboardAccess() {

        if (current_user_can('manage_options') || wp_doing_ajax()) {
            return;
        }

        $user = wp_get_current_user();
        if (isset($user->roles) && is_array($user->roles)) {
            foreach ($user->roles as $user_role) {
                //get role option
                $role_option = get_option("pp_capabilities_{$user_role}_role_option", []);
                if (is_array($role_option) && !empty($role_option)
                    && !empty($role_option['block_dashboard_access'])
                    && (int)$role_option['block_dashboard_access'] > 0
                ) {
                    wp_safe_redirect(home_url());
                    die();
                }
            }
        }
    }

    /**
     * Ajax for saving a feature from dashboard page
     *
     * Copied from PublishPress Blocks
     *
     * @return boolean,void     Return false if failure, echo json on success
     */
    public function saveDashboardFeature()
    {
        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_dashboard')) {
            wp_send_json( __('No permission!', 'capability-manager-enhanced'), 403 );
            return false;
        }

        if (
            ! wp_verify_nonce(
                sanitize_key( $_POST['nonce'] ),
                'pp-capabilities-dashboard-nonce'
            )
        ) {
            wp_send_json( __('Invalid nonce token!', 'capability-manager-enhanced'), 400 );
        }

        if( empty( $_POST['feature'] ) || ! $_POST['feature'] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            wp_send_json( __('Error: wrong data', 'capability-manager-enhanced'), 400 );
            return false;
        }

        $capsman_dashboard_features_status = !empty(get_option('capsman_dashboard_features_status')) ? (array)get_option('capsman_dashboard_features_status') : [];


        $feature = sanitize_text_field( $_POST['feature'] );

        $capsman_dashboard_features_status[$feature]['status'] = (bool) $_POST['new_state'] ? 'on' : 'off';
        update_option('capsman_dashboard_features_status', $capsman_dashboard_features_status, false);

        wp_send_json( true, 200 );
    }

    /**
     * Manages the installation detecting if this is the first time this plugin runs or is an upgrade.
     * If no version is stored in the options, we treat as a new installation. Otherwise, we check the
     * last version. If different, it is an upgrade or downgrade.
     */
    public function manage_installation()
    {
        $option_name = 'PUBLISHPRESS_CAPS_VERSION';

        $previous_version = get_option($option_name);
        $current_version  = PUBLISHPRESS_CAPS_VERSION;

        if (!apply_filters('pp_capabilities_skip_installation', false, $previous_version, $current_version)) {
            if (empty($previous_version)) {
                /**
                 * Action called when the plugin is installed.
                 *
                 * @param string $current_version
                 */
                do_action('pp_capabilities_install', $current_version);
            } elseif (version_compare($previous_version, $current_version, '>')) {
                /**
                 * Action called when the plugin is downgraded.
                 *
                 * @param string $previous_version
                 */
                do_action('pp_capabilities_downgrade', $previous_version);
            } elseif (version_compare($previous_version, $current_version, '<')) {
                /**
                 * Action called when the plugin is upgraded.
                 *
                 * @param string $previous_version
                 */
                do_action('pp_capabilities_upgrade', $previous_version);
            }
        }

        if ($current_version !== $previous_version) {
            update_option($option_name, $current_version, true);
        }
    }


	/**
	* Fires just before the move buttons of a nav menu item in the menu editor.
	* Add role blocked nav menu indication
	*
	* @param int       $item_id Menu item ID.
	* @param \WP_Post  $item    Menu item data object.
	* @param int       $depth   Depth of menu item. Used for padding.
	* @param \stdClass $args    An object of menu item arguments.
	* @param int       $id      Nav menu ID.
	*/
	public function add_nav_menu_indicator( $item_id, $item, $depth, $args, $id = null ) {
        global $capsman;

        if (!is_admin() || !pp_capabilities_feature_enabled('nav-menus')) {
            return;
        }

        $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array)get_option('capsman_nav_item_menus') : [];
        if (!is_array($nav_menu_item_option)) {
            return;
        }
        $nav_menu_item_option = array_filter($nav_menu_item_option);

        if (empty($nav_menu_item_option)) {
            return;
        }

        $searchPrefix = $item_id . '_';

        $restricted_roles = array_filter(
            array_map(
                function ($subArray) use ($searchPrefix) {
                    return array_filter(
                        $subArray,
                        function ($value) use ($searchPrefix) {
                            return strpos($value, $searchPrefix) === 0;
                        }
                    );
                },
                $nav_menu_item_option
            )
        );

        if (empty($restricted_roles)) {
            return;
        }
        $ppc_other_permissions = [
            "ppc_users" => esc_html__('Logged In Users', 'capability-manager-enhanced'),
            "ppc_guest" => esc_html__('Logged Out Users', 'capability-manager-enhanced')
        ];
        $wp_roles_obj = wp_roles();
	    $roles = $wp_roles_obj->get_names();
        ?>
        <div class="ppc-nav-edit">
            <div class="clear"></div>
            <h4 style="margin-bottom: 0.6em;"><?php esc_html_e( 'PublishPress Capabilities Menu Restriction', 'capability-manager-enhanced' ) ?></h4>
            <p class="description description-wide ppc-nav-mode"><?php esc_html_e( 'This menu is restricted for the following roles', 'capability-manager-enhanced' ) ?></p>
            <ul>
                <?php foreach (array_keys($restricted_roles) as $role) :
                    $role_url = admin_url('admin.php?page=pp-capabilities-nav-menus&role=' . $role . '');
                    if (array_key_exists($role, $ppc_other_permissions)) {
                        $role_caption = $ppc_other_permissions[$role];
                    } else {
                        if (is_array($roles) && !empty($roles[$role])) {
                            $role_caption = $roles[$role];
                        } else {
                            $role_caption = translate_user_role($role);
                        }
                    }
                    ?>
                <li style="margin-bottom: 5px;">
                    <a target="blank" href="<?php echo esc_url($role_url); ?>"><?php echo esc_html($role_caption); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php
	}

    /**
    * Redirect user on plugin activation
    *
    * @return void
    */
    public function redirect_on_activate()
    {
        if (get_option('pp_capabilities_activated')) {
            delete_option('pp_capabilities_activated');
            wp_safe_redirect(admin_url("admin.php?page=pp-capabilities-dashboard"));
            exit;
        }
    }

    public function addPluginActionLinks($links, $file)
    {
        if ($file == plugin_basename(CME_FILE) && ! defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            $upgrade_link = ['<a href="https://publishpress.com/links/capabilities-menu"
            target="_blank" style="font-weight: bold;">
            ' . __('Upgrade to Pro', 'capability-manager-enhanced') . '
            </a>'];

            $links = array_merge($upgrade_link, $links);
        }

        return $links;
    }

    public function addPluginRowMetaLinks($links, $file)
    {
        if ($file == plugin_basename(CME_FILE)) {
            $links[] = '<a href="' . admin_url('admin.php?page=pp-capabilities-dashboard') . '">' . __('Dashboard', 'capability-manager-enhanced') . '</a>';
            $links[] = "<a href='" . admin_url("admin.php?page=pp-capabilities") . "'>" . esc_html__('Capabilities', 'capability-manager-enhanced') . "</a>";
            $links[] = '<a href="' . admin_url('admin.php?page=pp-capabilities-roles') . '">' . __('Roles', 'capability-manager-enhanced') . '</a>';
            $links[] = "<a href='" . admin_url("admin.php?page=pp-capabilities-settings") . "'>" . esc_html__('Settings', 'capability-manager-enhanced') . "</a>";
        }

        return $links;
    }
}
