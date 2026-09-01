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

        // Non-destructive role disabling.
        require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes/roles/disabled-roles.php');
        \PublishPress\Capabilities\PP_Capabilities_Disabled_Roles::instance();

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

            // Admin feature settings update ajax callback
            add_action('wp_ajax_ppc_update_admin_feature_settings', [$this, 'ajaxUpdateAdminFeatureSettings']);

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

            // Add inline nav menu restrictions on the native Menus screen.
            add_action('wp_nav_menu_item_custom_fields', [$this, 'add_nav_menu_indicator'], 20, 5);
            add_action('wp_update_nav_menu_item', [$this, 'saveNavMenuRestrictions'], 20, 3);
            add_action('before_delete_post', [$this, 'cleanupDeletedNavMenuRestrictions']);
            add_action('admin_head-nav-menus.php', [$this, 'outputNavMenuRestrictionStyles']);
            add_action('admin_init', [$this, 'blockSubsiteCapabilitiesAccess'], 1000);
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

        if (is_admin() && (isset($_REQUEST['page']) && (in_array($_REQUEST['page'], ['pp-capabilities', 'pp-capabilities-backup', 'pp-capabilities-roles', 'pp-capabilities-admin-menus', 'pp-capabilities-editor-features', 'pp-capabilities-nav-menus', 'pp-capabilities-settings', 'pp-capabilities-admin-features', 'pp-capabilities-profile-features', 'pp-capabilities-dashboard', 'pp-capabilities-frontend-features', 'pp-capabilities-redirects', 'pp-capabilities-admin-styles', 'pp-capabilities-admin-notices']))

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

            if ((($pagenow == 'admin.php') && isset($_GET['page']) && in_array($_GET['page'], ['pp-capabilities', 'pp-capabilities-backup', 'pp-capabilities-roles', 'pp-capabilities-admin-menus', 'pp-capabilities-editor-features', 'pp-capabilities-nav-menus', 'pp-capabilities-settings', 'pp-capabilities-admin-features', 'pp-capabilities-profile-features', 'pp-capabilities-dashboard', 'pp-capabilities-redirects', 'pp-capabilities-admin-styles', 'pp-capabilities-admin-notices'])) // @todo: CSS for button alignment in Editor Features, Admin Features
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
        add_filter('all_plugins', [$this, 'filterPluginsListName']);
    }

	function register_textdomain() {

        $domain       = 'capability-manager-enhanced';
		$mofile_custom = sprintf('%s-%s.mo', $domain, get_user_locale());
		$locations = [
			trailingslashit( WP_LANG_DIR . '/plugins/'),
			trailingslashit( WP_LANG_DIR . '/' . $domain ),
			trailingslashit( WP_LANG_DIR . '/loco/plugins/'),
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

        return ($pagenow == 'admin.php') && isset($_GET['page']) && in_array($_GET['page'], ['pp-capabilities', 'pp-capabilities-backup', 'pp-capabilities-roles', 'pp-capabilities-admin-menus', 'pp-capabilities-editor-features', 'pp-capabilities-nav-menus', 'pp-capabilities-settings', 'pp-capabilities-admin-features', 'pp-capabilities-profile-features', 'pp-capabilities-dashboard', 'pp-capabilities-redirects', 'pp-capabilities-admin-styles', 'pp-capabilities-admin-notices']);
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
                        'role_description'  => esc_html__('Drag multiple roles selection to change roles order.', 'capability-manager-enhanced'),
                        'selected_roles'    => $roles,
                        'multi_roles'       => $multi_role ? 1 : 0,
                        'profile_page_title' => esc_html__('Page title', 'capability-manager-enhanced'),
                        'rankmath_title'    => esc_html__('Rank Math SEO', 'capability-manager-enhanced'),
                        'chosen_no_results_text' => esc_html__('No results match', 'capability-manager-enhanced'),
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
            if (!current_user_can('edit_user', $userId) || !current_user_can('promote_user', $userId)) {
                return;
            }

            $user = get_user_by('ID', $userId);
            if (empty($user)) {
                return;
            }

            $newRoles = array_unique(
                array_filter(
                    array_map('sanitize_key', (array) $_POST['pp_roles'])
                )
            );
            $currentRoles = $user->roles;

            if (empty($newRoles) || !is_array($newRoles)) {
                return;
            }

            $editableRoles = function_exists('get_editable_roles')
                ? array_keys(get_editable_roles())
                : array_keys(apply_filters('editable_roles', wp_roles()->roles));

            // Reject the request if any submitted role is not editable by current user.
            if (array_diff($newRoles, $editableRoles)) {
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

        if (!pp_capabilities_should_display_admin_menu()) {
            return;
        }

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


    public function blockSubsiteCapabilitiesAccess() {
        if (pp_capabilities_should_display_admin_menu()) {
            return;
        }

        $sub_menu_pages = pp_capabilities_sub_menu_lists(true);
        $capability_pages = array_map(function ($page) {
            return $page['page'];
        }, $sub_menu_pages);

        if (!empty($_GET['page']) && in_array(sanitize_key($_GET['page']), $capability_pages, true)) {
            wp_safe_redirect(admin_url());
            exit;
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

        $feature = sanitize_key(wp_unslash($_POST['feature']));
        $dashboard_options = pp_capabilities_dashboard_options();

        if (!isset($dashboard_options[$feature])) {
            wp_send_json(__('Error: wrong data', 'capability-manager-enhanced'), 400);
            return false;
        }

        $capsman_dashboard_features_status = !empty(get_option('capsman_dashboard_features_status')) ? (array)get_option('capsman_dashboard_features_status') : [];

        $feature_status = !empty($_POST['new_state']) ? 'on' : 'off';
        $capsman_dashboard_features_status[$feature]['status'] = $feature_status;
        update_option('capsman_dashboard_features_status', $capsman_dashboard_features_status, false);
        do_action('pp_capabilities_dashboard_feature_updated', $feature, $feature_status);

        $network_sync = !empty($_POST['network_sync'])
            && is_multisite()
            && is_super_admin()
            && is_main_site();

        if ($network_sync && function_exists('pp_capabilities_queue_dashboard_feature_sync')) {
            pp_capabilities_queue_dashboard_feature_sync($feature, $feature_status);

            wp_send_json([
                'message' => __('Changes saved. Feature status synchronization has been queued for all network sites.', 'capability-manager-enhanced'),
                'network_sync' => true,
            ], 200);
        }

        wp_send_json([
            'message' => __('Changes saved!', 'capability-manager-enhanced'),
            'network_sync' => false,
        ], 200);
    }

    /**
     * Ajax handler for updating admin feature settings
     */
    public function ajaxUpdateAdminFeatureSettings() {

        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        // Verify nonce and capabilities
        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'pp-capabilities-admin-features')) {
            $response['message'] =  __('Security check failed', 'capability-manager-enhanced');
        } elseif (!current_user_can('manage_capabilities_admin_features')) {
            $response['message'] =  __('Permission denied', 'capability-manager-enhanced');
        } else {
            $hide_submenu      = !empty($_POST['hide_submenu']) ? (int)($_POST['hide_submenu']) : 0;

            $admin_feature_settings = (array) get_option('ppc_admin_features_settings', []);
            $admin_feature_settings['hide_submenu'] = $hide_submenu;

            update_option('ppc_admin_features_settings', $admin_feature_settings);

            $response['status']  = 'success';
            $response['message'] = __('Settings updated successfully.', 'capability-manager-enhanced');
        }

        wp_send_json($response);
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


    private function canManageNavMenuRestrictions()
    {
        if (!is_admin() || !pp_capabilities_feature_enabled('nav-menus')) {
            return false;
        }

        if (is_multisite() && is_super_admin()) {
            return true;
        }

        return current_user_can('administrator') || current_user_can('manage_capabilities_nav_menus');
    }

    private function getNavMenuRestrictionRoles()
    {
        $role_options = [
            'ppc_users' => esc_html__('Logged In Users', 'capability-manager-enhanced'),
            'ppc_guest' => esc_html__('Logged Out Users', 'capability-manager-enhanced'),
        ];

        $editable_roles = function_exists('get_editable_roles')
            ? get_editable_roles()
            : apply_filters('editable_roles', wp_roles()->roles);

        foreach ((array) $editable_roles as $role_name => $role_details) {
            $role_name = sanitize_key($role_name);

            if ('' === $role_name) {
                continue;
            }

            $role_options[$role_name] = !empty($role_details['name'])
                ? translate_user_role($role_details['name'])
                : translate_user_role($role_name);
        }

        return $role_options;
    }

    private function getNavMenuRestrictionValue($item_id, $item = null)
    {
        if (empty($item)) {
            $item = wp_setup_nav_menu_item(get_post($item_id));
        }

        if (empty($item) || empty($item->ID) || !isset($item->object_id, $item->object)) {
            return '';
        }

        return $item->ID . '_' . sanitize_text_field((string) $item->object_id) . '_' . sanitize_key((string) $item->object);
    }

    private function getRestrictedNavMenuRoles($item_id, $nav_menu_item_option = [])
    {
        $item_value = $this->getNavMenuRestrictionValue($item_id);

        if ('' === $item_value) {
            return [];
        }

        if (empty($nav_menu_item_option) || !is_array($nav_menu_item_option)) {
            $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array) get_option('capsman_nav_item_menus') : [];
        }

        $restricted_roles = [];

        foreach ((array) $nav_menu_item_option as $role_name => $restricted_items) {
            if (in_array($item_value, array_filter((array) $restricted_items), true)) {
                $restricted_roles[] = sanitize_key($role_name);
            }
        }

        return $restricted_roles;
    }

    public function outputNavMenuRestrictionStyles()
    {
        if (!$this->canManageNavMenuRestrictions()) {
            return;
        }
        ?>
        <style id="pp-capabilities-nav-menu-inline-restrictions">
            .field-pp-capabilities-nav-restrictions {
                margin-top: 12px;
                padding-top: 12px;
                border-top: 1px solid #dcdcde;
            }

            .field-pp-capabilities-nav-restrictions .ppc-nav-mode {
                margin-bottom: 8px;
            }

            .field-pp-capabilities-nav-restrictions .ppc-nav-edit-role-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 6px 12px;
                max-height: 240px;
                overflow-y: auto;
                padding: 4px;
                border: 1px solid #dcdcde;
                background: #fff;
            }

            .field-pp-capabilities-nav-restrictions .ppc-nav-edit-role-option {
                display: flex;
                align-items: center;
                gap: 6px;
                margin: 0;
            }

            .field-pp-capabilities-nav-restrictions .ppc-nav-edit-role-search-label {
                display: block;
                margin: 0 0 4px;
                font-weight: 600;
            }

            .field-pp-capabilities-nav-restrictions .ppc-nav-edit-role-search {
                width: 100%;
                max-width: 400px;
                margin-bottom: 8px;
            }

            .field-pp-capabilities-nav-restrictions .ppc-nav-role-filter-status {
                min-height: 18px;
                margin: 4px 0 8px;
            }

            .field-pp-capabilities-nav-restrictions .ppc-nav-manage-link {
                margin-top: 10px;
            }
        </style>
        <script>
            (function() {
                function filterNavMenuRoles(searchField) {
                    var roleList = document.getElementById(searchField.getAttribute('aria-controls'));

                    if (!roleList) {
                        return;
                    }

                    var searchTerm = searchField.value.trim().toLowerCase();
                    var roleOptions = roleList.querySelectorAll('.ppc-nav-edit-role-option');
                    var visibleRoles = 0;

                    Array.prototype.forEach.call(roleOptions, function(roleOption) {
                        var roleName = roleOption.getAttribute('data-role-name') || '';
                        var roleCaption = roleOption.textContent || '';
                        var isVisible = !searchTerm || (roleName + ' ' + roleCaption).toLowerCase().indexOf(searchTerm) !== -1;

                        roleOption.hidden = !isVisible;

                        if (isVisible) {
                            visibleRoles++;
                        }
                    });

                    var status = document.getElementById(searchField.getAttribute('data-status'));

                    if (!status) {
                        return;
                    }

                    if (!searchTerm) {
                        status.textContent = '';
                    } else if (!visibleRoles) {
                        status.textContent = searchField.getAttribute('data-no-results');
                    } else {
                        var roleLabel = visibleRoles === 1
                            ? searchField.getAttribute('data-one-result')
                            : searchField.getAttribute('data-many-results');

                        status.textContent = visibleRoles + ' ' + roleLabel;
                    }
                }

                document.addEventListener('input', function(event) {
                    if (event.target.matches('.ppc-nav-edit-role-search')) {
                        filterNavMenuRoles(event.target);
                    }
                });
            }());
        </script>
        <?php
    }

	/**
	* Fires just before the move buttons of a nav menu item in the menu editor.
	* Add inline role controls for nav menu restrictions.
	*
	* @param int       $item_id Menu item ID.
	* @param \WP_Post  $item    Menu item data object.
	* @param int       $depth   Depth of menu item. Used for padding.
	* @param \stdClass $args    An object of menu item arguments.
	* @param int       $id      Nav menu ID.
	*/
	public function add_nav_menu_indicator( $item_id, $item, $depth, $args, $id = null ) {
        if (!$this->canManageNavMenuRestrictions()) {
            return;
        }

        $role_options = $this->getNavMenuRestrictionRoles();

        if (empty($role_options)) {
            return;
        }

        $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array) get_option('capsman_nav_item_menus') : [];
        $restricted_roles = $this->getRestrictedNavMenuRoles($item_id, $nav_menu_item_option);
        ?>
        <fieldset class="field-pp-capabilities-nav-restrictions description description-wide">
            <div class="ppc-nav-edit">
                <div class="clear"></div>
                <h4 style="margin-bottom: 0.6em;"><?php esc_html_e('PublishPress Capabilities Menu Restriction', 'capability-manager-enhanced'); ?></h4>
                <p class="description description-wide ppc-nav-mode"><?php esc_html_e('Hide this menu item for the selected roles.', 'capability-manager-enhanced'); ?></p>

                <?php
                $role_search_id = 'pp-capabilities-nav-menu-role-search-' . (int) $item_id;
                $role_list_id = 'pp-capabilities-nav-menu-role-list-' . (int) $item_id;
                $role_status_id = 'pp-capabilities-nav-menu-role-status-' . (int) $item_id;
                ?>
                <label class="ppc-nav-edit-role-search-label" for="<?php echo esc_attr($role_search_id); ?>">
                    <?php esc_html_e('Search roles', 'capability-manager-enhanced'); ?>
                </label>
                <input
                    id="<?php echo esc_attr($role_search_id); ?>"
                    class="ppc-nav-edit-role-search"
                    type="search"
                    placeholder="<?php esc_attr_e('Search by role name', 'capability-manager-enhanced'); ?>"
                    autocomplete="off"
                    aria-controls="<?php echo esc_attr($role_list_id); ?>"
                    data-status="<?php echo esc_attr($role_status_id); ?>"
                    data-no-results="<?php esc_attr_e('No roles match your search.', 'capability-manager-enhanced'); ?>"
                    data-one-result="<?php esc_attr_e('role shown', 'capability-manager-enhanced'); ?>"
                    data-many-results="<?php esc_attr_e('roles shown', 'capability-manager-enhanced'); ?>"
                />
                <p id="<?php echo esc_attr($role_status_id); ?>" class="description ppc-nav-role-filter-status" aria-live="polite"></p>

                <div id="<?php echo esc_attr($role_list_id); ?>" class="ppc-nav-edit-role-grid">
                    <?php foreach ($role_options as $role_name => $role_caption) : ?>
                        <label class="ppc-nav-edit-role-option" data-role-name="<?php echo esc_attr($role_name); ?>" for="pp-capabilities-nav-menu-role-<?php echo (int) $item_id; ?>-<?php echo esc_attr($role_name); ?>">
                            <input
                                id="pp-capabilities-nav-menu-role-<?php echo (int) $item_id; ?>-<?php echo esc_attr($role_name); ?>"
                                type="checkbox"
                                name="pp_capabilities_nav_menu_roles[<?php echo (int) $item_id; ?>][]"
                                value="<?php echo esc_attr($role_name); ?>"
                                <?php checked(in_array($role_name, $restricted_roles, true)); ?>
                            />
                            <span><?php echo esc_html($role_caption); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="description ppc-nav-manage-link">
                    <?php
                    printf(
                        wp_kses(
                            __('Need a role-by-role overview? %1$sOpen Navigation Menu Restrictions%2$s.', 'capability-manager-enhanced'),
                            [
                                'a' => [
                                    'href' => [],
                                ],
                            ]
                        ),
                        '<a href="' . esc_url(admin_url('admin.php?page=pp-capabilities-nav-menus')) . '">',
                        '</a>'
                    );
                    ?>
                </p>
            </div>
        </fieldset>

        <?php
	}

    public function saveNavMenuRestrictions($menu_id, $menu_item_db_id, $args)
    {
        if (!$this->canManageNavMenuRestrictions()) {
            return;
        }

        if (
            empty($_POST['update-nav-menu-nonce'])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['update-nav-menu-nonce'])),
                'update-nav_menu'
            )
        ) {
            return;
        }

        $item_value = $this->getNavMenuRestrictionValue($menu_item_db_id);

        if ('' === $item_value) {
            return;
        }

        $role_options = $this->getNavMenuRestrictionRoles();
        $valid_roles = array_keys($role_options);
        $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array) get_option('capsman_nav_item_menus') : [];

        foreach ((array) $nav_menu_item_option as $role_name => $restricted_items) {
            $restricted_items = array_values(
                array_filter(
                    array_map('sanitize_text_field', (array) $restricted_items),
                    function ($value) use ($menu_item_db_id) {
                        return strpos((string) $value, $menu_item_db_id . '_') !== 0;
                    }
                )
            );

            if (empty($restricted_items)) {
                unset($nav_menu_item_option[$role_name]);
            } else {
                $nav_menu_item_option[$role_name] = $restricted_items;
            }
        }

        $submitted_roles = [];

        if (isset($_POST['pp_capabilities_nav_menu_roles'][$menu_item_db_id])) {
            $submitted_roles = array_unique(
                array_filter(
                    array_map(
                        'sanitize_key',
                        (array) wp_unslash($_POST['pp_capabilities_nav_menu_roles'][$menu_item_db_id])
                    )
                )
            );
        }

        foreach ($submitted_roles as $role_name) {
            if (!in_array($role_name, $valid_roles, true)) {
                continue;
            }

            if (empty($nav_menu_item_option[$role_name]) || !is_array($nav_menu_item_option[$role_name])) {
                $nav_menu_item_option[$role_name] = [];
            }

            $nav_menu_item_option[$role_name][] = $item_value;
            $nav_menu_item_option[$role_name] = array_values(array_unique($nav_menu_item_option[$role_name]));
        }

        update_option('capsman_nav_item_menus', $nav_menu_item_option, false);
    }

    public function cleanupDeletedNavMenuRestrictions($post_id)
    {
        if ('nav_menu_item' !== get_post_type($post_id)) {
            return;
        }

        $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array) get_option('capsman_nav_item_menus') : [];

        if (empty($nav_menu_item_option)) {
            return;
        }

        $updated = false;

        foreach ((array) $nav_menu_item_option as $role_name => $restricted_items) {
            $filtered_items = array_values(
                array_filter(
                    array_map('sanitize_text_field', (array) $restricted_items),
                    function ($value) use ($post_id) {
                        return strpos((string) $value, $post_id . '_') !== 0;
                    }
                )
            );

            if ($filtered_items !== array_values((array) $restricted_items)) {
                $updated = true;
            }

            if (empty($filtered_items)) {
                unset($nav_menu_item_option[$role_name]);
            } else {
                $nav_menu_item_option[$role_name] = $filtered_items;
            }
        }

        if ($updated) {
            update_option('capsman_nav_item_menus', $nav_menu_item_option, false);
        }
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

    /**
     * Show "Free" suffix for this plugin only on WordPress plugin list screens.
     *
     * @param array $all_plugins
     *
     * @return array
     */
    public function filterPluginsListName($all_plugins)
    {
        global $pagenow;

        if (!is_admin() || 'plugins.php' !== $pagenow) {
            return $all_plugins;
        }

        $plugin_file = plugin_basename(CME_FILE);

        if (isset($all_plugins[$plugin_file]['Name'])) {
            $all_plugins[$plugin_file]['Name'] = 'PublishPress Capabilities Free';
        }

        return $all_plugins;
    }
}
