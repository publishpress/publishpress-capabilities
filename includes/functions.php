<?php
/*
 * PublishPress Capabilities [Free]
 *
 * Functions available for any URL, which are not contained within a class
 *
 * For performance and code separation, do not include functions that are only needed for wp-admin requests
 *
 */

//frontend features restrict instance
require_once (dirname(__FILE__) . '/features/frontend-features/frontend-features-restrict.php');
\PublishPress\Capabilities\PP_Capabilities_Frontend_Features_Restrict::instance();
/**
 * Sanitizes a string entry
 *
 * Keys are used as internal identifiers. Uppercase or lowercase alphanumeric characters,
 * spaces, periods, commas, plusses, asterisks, colons, pipes, parentheses, dashes and underscores are allowed.
 *
 * @param string $entry String entry
 * @return string Sanitized entry
 */
function pp_capabilities_sanitize_entry( $entry ) {
    $entry = preg_replace( '/[^a-zA-Z0-9 \.\,\+\*\:\|\(\)_\-\=]/', '', $entry );
    return $entry;
}

function pp_capabilities_is_editable_role($role_name, $args = []) {
    static $editable_roles;

    if (!function_exists('wp_roles')) {
        return false;
    }

    if (!isset($editable_roles) || !empty($args['force_refresh'])) {
        $all_roles = wp_roles()->roles;
        $editable_roles = apply_filters('editable_roles', $all_roles, $args);
    }

    return apply_filters('pp_capabilities_editable_role', isset($editable_roles[$role_name]), $role_name);
}

function _cme_act_pp_active()
{
    if (defined('PRESSPERMIT_VERSION') || (defined('PPC_VERSION') && function_exists('pp_init_cap_caster'))) {
        define('PRESSPERMIT_ACTIVE', true);
    } else {
        if (defined('SCOPER_VERSION') || (defined('PP_VERSION') && function_exists('pp_init_users_interceptor'))) {
            define('OLD_PRESSPERMIT_ACTIVE', true);
        }
    }
}

function _cme_cap_helper()
{
    global $cme_cap_helper;

    require_once(dirname(__FILE__) . '/cap-helper.php');
    $cme_cap_helper = new CME_Cap_Helper();

    add_action('registered_post_type', '_cme_post_type_late_reg', 5, 2);
    add_action('registered_taxonomy', '_cme_taxonomy_late_reg', 5, 2);
}

function _cme_post_type_late_reg($post_type, $type_obj)
{
    global $cme_cap_helper;

    if (!empty($type_obj->public) || !empty($type_obj->show_ui)) {
        $cme_cap_helper->refresh();
    }
}

function _cme_taxonomy_late_reg($taxonomy, $tx_obj)
{
    global $cme_cap_helper;

    if (!empty($tx_obj->public)) {
        $cme_cap_helper->refresh();
    }
}

function _cme_init()
{
    require_once(dirname(__FILE__) . '/filters.php');

    load_plugin_textdomain('capability-manager-enhanced', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

function cme_is_plugin_active($check_plugin_file)
{
    if (!$check_plugin_file)
        return false;

    $plugins = (array)get_option('active_plugins');

    foreach ($plugins as $plugin_file) {
        if (false !== strpos($plugin_file, $check_plugin_file))
            return $plugin_file;
    }
}

// if a role is marked as hidden, also default it for use by Press Permit as a Pattern Role (when PP Collaborative Editing is activated and Advanced Settings enabled)
function _cme_pp_default_pattern_role($role)
{
    if (!$pp_role_usage = get_option('pp_role_usage'))
        $pp_role_usage = array();

    if (empty($pp_role_usage[$role])) {
        $pp_role_usage[$role] = 'pattern';
        update_option('pp_role_usage', $pp_role_usage);
    }
}

// deprecated
function capsman_get_pp_option($option_basename)
{
    return pp_capabilities_get_permissions_option($option_basename);
}

function pp_capabilities_autobackup()
{
    global $wpdb;

    $roles = get_option($wpdb->prefix . 'user_roles');
    update_option('cme_backup_auto_' . current_time('Y-m-d_g-i-s_a'), $roles, false);

    $max_auto_backups = (defined('CME_AUTOBACKUPS')) ? (int) CME_AUTOBACKUPS : 20;

    $current_options = $wpdb->get_col("SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'cme_backup_auto_%' ORDER BY option_id DESC");

    if (count($current_options) >= $max_auto_backups) {
        $i = 0;

        foreach($current_options as $option_name) {
            $i++;

            if ($i > $max_auto_backups) {
        		$wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM $wpdb->options WHERE option_name = %s",
                        $option_name
                    )
        		);

                wp_cache_delete($option_name, 'options');
            }
        }
    }
}

function pp_capabilities_get_permissions_option($option_basename)
{
    return (function_exists('presspermit')) ? presspermit()->getOption($option_basename) : pp_get_option($option_basename);
}

function pp_capabilities_update_permissions_option($option_basename, $option_val)
{
    function_exists('presspermit') ? presspermit()->updateOption($option_basename, $option_val) : pp_update_option($option_basename, $option_val);
}

/**
 * Get post type.
 *
 * @return null|string String of the post type.
 */
function pp_capabilities_get_post_type()
{
    global $post, $typenow, $current_screen;

    // Check the global $current_screen object - set in screen.php
    if ($current_screen && $current_screen->post_type) {
        return $current_screen->post_type;
    }

    // We have a post so we can just get the post type from that.
    if ($post && $post->post_type) {
        return $post->post_type;
    }

    // Check the global $typenow - set in admin.php
    if ($typenow) {
        return $typenow;
    }

    if (isset($_GET['post']) && !is_array($_GET['post'])) {
        $post_id = (int) $_GET['post'];

    } elseif (isset($_POST['post_ID'])) {
        $post_id = (int) $_POST['post_ID'];
    }

    if (!empty($post_id)) {
        return get_post_type($post_id);
    }

    // lastly check the post_type querystring
    if (isset($_REQUEST['post_type'])) {
        return sanitize_key($_REQUEST['post_type']);
    }

    return 'post';
}

/**
 * Check if Classic Editor plugin is available.
 *
 * @return bool
 */
function pp_capabilities_is_classic_editor_available()
{
    global $wp_version;

    return class_exists('Classic_Editor')
        || function_exists( 'the_gutenberg_project' )
        || class_exists('Gutenberg_Ramp')
        || version_compare($wp_version, '5.0', '<')
        || class_exists('WooCommerce')
        || (defined('PP_CAPABILITIES_CONFIGURE_CLASSIC_EDITOR') && PP_CAPABILITIES_CONFIGURE_CLASSIC_EDITOR)
        || !empty(get_option('cme_editor_features_classic_editor_tab'))
        || (function_exists('et_get_option') && 'on' === et_get_option('et_enable_classic_editor', 'off'));
}

/**
 * Get admin bar node and set as global for our usage.
 * Due to admin toolbar, this function need to run in frontend as well
 *
 * @return array||object $wp_admin_bar nodes.
 */
function ppc_features_get_admin_bar_nodes($wp_admin_bar){

    $adminBarNode = is_object($wp_admin_bar) ? $wp_admin_bar->get_nodes() : '';
    $ppcAdminBar = [];

    if (is_array($adminBarNode) || is_object($adminBarNode)) {
        foreach ($adminBarNode as $adminBarnode) {
            $id = $adminBarnode->id;
            $title = $adminBarnode->title;
            $parent = $adminBarnode->parent;
            $ppcAdminBar[$id] = array('id' => $id, 'title' => $title, 'parent' => $parent);
        }
    }

    $GLOBALS['ppcAdminBar'] = $ppcAdminBar;
}
add_action('admin_bar_menu', 'ppc_features_get_admin_bar_nodes', 999);

/**
 * Implement admin features restriction.
 * Due to admin toolbar, this function need to run in frontend as well
 *
 */
function ppc_admin_feature_restrictions() {
    if (pp_capabilities_feature_enabled('admin-features')) {
        require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes/features/restrict-admin-features.php');
        PP_Capabilities_Admin_Features::adminFeaturedRestriction();
    }
}
add_action('init', 'ppc_admin_feature_restrictions', 999);


/**
 * Implement test user feature
 *
 * @return void
 */
function ppc_test_user_init () {
    if (pp_capabilities_feature_enabled('user-testing')) {
        require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes/test-user.php');
        PP_Capabilities_Test_User::init();
    }
}
add_action('init', 'ppc_test_user_init');

/**
 * Implement admin notices feature
 *
 * @return void
 */
function ppc_admin_notices_init () {
    if (pp_capabilities_feature_enabled('admin-notices')) {
        require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes/admin-notices/admin-notices.php');
        PP_Capabilities_Admin_Notices::init();
    }
}
add_action('init', 'ppc_admin_notices_init');


/**
* Redirect user after registration.
*
* @param int $user_id The user ID.
*/
function ppc_role_redirect_after_registration($user_id) {
    // Skip redirection if the request is from the admin area.
    if (is_admin()) {
        return;
    }

    $user = get_user_by('ID', $user_id);

    if (pp_capabilities_feature_enabled('redirects') && is_object($user) && isset($user->roles) && is_array($user->roles)) {

        $bypass = apply_filters('ppc_registration_redirect_bypass', false, '', $user);
        if ($bypass) {
            return;
        }

        $role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];

        foreach ($user->roles as $user_role) {
            //get role option
            $role_option = array_key_exists($user_role, $role_redirects) ? (array)$role_redirects[$user_role] : [];
            if (is_array($role_option) && !empty($role_option) && !empty($role_option['registration_redirect'])) {
                $redirect_to = esc_url_raw($role_option['registration_redirect']);
                // Login the user automatically.
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);

                wp_safe_redirect($redirect_to);
                exit;
            }
        }
    }
}
add_action('user_register', 'ppc_role_redirect_after_registration', PHP_INT_MAX);

/**
 * Redirect after WooCommerce registration.
 *
 * @param string $redirect_to URL to redirect to.
 * @return string
 */
function woocommerce_registration_redirect($redirect_to) {
    $user_id = get_current_user_id();
    $user = get_user_by('ID', $user_id);

    if (pp_capabilities_feature_enabled('redirects') && is_object($user) && isset($user->roles) && is_array($user->roles)) {
        $role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];

        foreach ($user->roles as $user_role) {
            //get role option
            $role_option = array_key_exists($user_role, $role_redirects) ? (array)$role_redirects[$user_role] : [];
            if (is_array($role_option) && !empty($role_option) && !empty($role_option['registration_redirect'])) {
                $redirect_to = esc_url_raw($role_option['registration_redirect']);
                break;
            }
        }
    }

    return $redirect_to;
}
add_filter('woocommerce_registration_redirect', 'woocommerce_registration_redirect', 11);


/**
 * Redirect user to configured role login redirect
 *
 * @param string $redirect_to URL to redirect to.
 * @param string $request URL the user is coming from.
 * @param object $user Logged user's data.
 * @return string
 */
function ppc_roles_login_redirect($redirect_to, $request, $user) {

    if (pp_capabilities_feature_enabled('redirects') && isset($user->roles) && is_array($user->roles)) {

        $bypass = apply_filters('ppc_login_redirect_bypass', false, $redirect_to, $user);
        if ($bypass) {
            return $redirect_to;
        }

        $role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];
        $is_first_login = get_user_meta($user->ID, '_ppc_first_login', true);
        foreach ($user->roles as $user_role) {
            //get role option
            $role_option = array_key_exists($user_role, $role_redirects) ? (array)$role_redirects[$user_role] : [];

            if ($is_first_login === '' && is_array($role_option) && !empty($role_option) && !empty($role_option['first_login_redirect'])) {
                // first login redirect
                $redirect_to = esc_url_raw($role_option['first_login_redirect']);
                update_user_meta($user->ID, '_ppc_first_login', current_time('mysql'));
                break;
            } elseif (is_array($role_option) && !empty($role_option)
                && !empty($role_option['custom_redirect']) && (int)$role_option['custom_redirect'] > 0
                && !empty($role_option['login_redirect'])
            ) {
                //custom url redirect
                $redirect_to = esc_url_raw($role_option['login_redirect']);
                break;
            } else if (is_array($role_option) && !empty($role_option)
                && !empty($role_option['referer_redirect']) && (int)$role_option['referer_redirect'] > 0
            ) {
                // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
                $redirect_url = (!empty(get_option('cme_role_same_page_redirect_cookie')) && !empty($_COOKIE['ppc_last_visited_page'])) ? $_COOKIE['ppc_last_visited_page'] : wp_get_referer();
                if (!empty($redirect_url)) {
                    //referer url redirect
                    $redirect_to = esc_url_raw($redirect_url);
                }
                break;
            }
        }
    }

    return $redirect_to;
}
add_filter('login_redirect', 'ppc_roles_login_redirect', 10, 3);


/**
* Redirect after WooCommerce login.
*
* @param string $redirect URL to redirect to.
* @param WP_User $user User object.
* @return string
*/
function ppc_roles_woocommerce_login_redirect($redirect_to, $user) {

    if (pp_capabilities_feature_enabled('redirects') && isset($user->roles) && is_array($user->roles)) {

        $bypass = apply_filters('ppc_woocommerce_login_redirect_bypass', false, $redirect_to, $user);
        if ($bypass) {
            return $redirect_to;
        }

        $role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];
        $is_first_login = get_user_meta($user->ID, '_ppc_first_login', true);
        foreach ($user->roles as $user_role) {
            //get role option
            $role_option = array_key_exists($user_role, $role_redirects) ? (array)$role_redirects[$user_role] : [];

            if ($is_first_login === '' && is_array($role_option) && !empty($role_option) && !empty($role_option['first_login_redirect'])) {
                // first login redirect
                $redirect_to = esc_url_raw($role_option['first_login_redirect']);
                update_user_meta($user->ID, '_ppc_first_login', current_time('mysql'));
                break;
            } elseif (is_array($role_option) && !empty($role_option)
                && !empty($role_option['custom_redirect']) && (int)$role_option['custom_redirect'] > 0
                && !empty($role_option['login_redirect'])
            ) {
                //custom url redirect
                $redirect_to = esc_url_raw($role_option['login_redirect']);
                break;
            } else if (is_array($role_option) && !empty($role_option)
                && !empty($role_option['referer_redirect']) && (int)$role_option['referer_redirect'] > 0
            ) {
                // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
                $redirect_url = (!empty(get_option('cme_role_same_page_redirect_cookie')) && !empty($_COOKIE['ppc_last_visited_page'])) ? $_COOKIE['ppc_last_visited_page'] : wp_get_referer();
                if (!empty($redirect_url)) {
                    //referer url redirect
                    $redirect_to = esc_url_raw($redirect_url);
                }
                break;
            }
        }
    }

   return $redirect_to;
}
add_filter('woocommerce_login_redirect', 'ppc_roles_woocommerce_login_redirect', 10, 2);


/**
 * We can no longer relied on wp_get_referer() due to it non
 * reliability and cons. so, we'll be saving last visited page
 * using cookies
 *
 * @return void
 */
function ppc_roles_last_visited_page_cookie() {
    if (!is_user_logged_in() && !empty(get_option('cme_role_same_page_redirect_cookie'))) {
	?>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Get the full current URL
            var currentURL = window.location.href;

            // Check if the path includes /wp-login.php or /wp-admin/
            if (currentURL.indexOf('/wp-login.php') === -1 && currentURL.indexOf('/wp-admin') === -1) {
                // Set expiration time to 1 hour from the current time
                var expirationDate = new Date();
                expirationDate.setTime(expirationDate.getTime() + 60 * 60 * 1000); // 1 hour in milliseconds

                // Get the current domain and set the cookie with domain, path, and expiration time
                var currentDomain = window.location.hostname;
                document.cookie = 'ppc_last_visited_page=' + currentURL + '; path=/; domain=' + currentDomain + '; expires=' + expirationDate.toUTCString();
            }
        });
    </script>
	<?php
    }
}
add_action( 'wp_footer', 'ppc_roles_last_visited_page_cookie' );

/**
 * Redirect user to configured role logout redirect
 *
 * @param string $redirect_to URL to redirect to.
 * @param string $request URL the user is coming from.
 * @param object $user Logged user's data.
 * @return string
 */
function ppc_roles_logout_redirect($redirect_to, $request, $user) {

    if (pp_capabilities_feature_enabled('redirects') && isset($user->roles) && is_array($user->roles)) {
        $bypass = apply_filters('ppc_logout_redirect_bypass', false, $redirect_to, $user);
        if ($bypass) {
            return $redirect_to;
        }

        $role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];
        foreach ($user->roles as $user_role) {
            //get role option
            $role_option = array_key_exists($user_role, $role_redirects) ? (array)$role_redirects[$user_role] : [];
            if (is_array($role_option) && !empty($role_option) && !empty($role_option['logout_redirect'])) {
                $redirect_to = esc_url_raw($role_option['logout_redirect']);
                break;
            }
        }
    }

    return $redirect_to;
}
add_filter('logout_redirect', 'ppc_roles_logout_redirect', 10, 3);

/**
 * Block user role login
 *
 * @param $user (null|WP_User|WP_Error) WP_User if the user is authenticated. WP_Error or null otherwise.
 *
 * @return WP_User object if credentials authenticate the user. WP_Error or null otherwise
*/
function ppc_roles_wp_authenticate_user($user) {

    if (is_wp_error($user)) {
        return $user;
    }

    if (pp_capabilities_feature_enabled('roles') && isset($user->roles) && is_array($user->roles)) {
        foreach ($user->roles as $user_role) {
            //get role option
            $role_option = get_option("pp_capabilities_{$user_role}_role_option", []);
            if (is_array($role_option) && !empty($role_option)
                && !empty($role_option['disable_role_user_login'])
                && (int)$role_option['disable_role_user_login'] > 0
            ) {
                return new WP_Error('ppc_roles_user_banned', __('Login permission denied.', 'capability-manager-enhanced'));
            }
        }
    }

    return $user;
}
add_filter('wp_authenticate_user', 'ppc_roles_wp_authenticate_user', 1);

/**
 * Wocommerce role admin access restriction remove
 */
function ppc_roles_disable_woocommerce_admin_restrictions($restrict_access) {

    if (pp_capabilities_feature_enabled('roles') && $restrict_access && is_user_logged_in()) {
        $user = get_userdata(get_current_user_id());

        if (isset($user->roles) && is_array($user->roles)) {
            foreach ($user->roles as $user_role) {
                //get role option
                $role_option = get_option("pp_capabilities_{$user_role}_role_option", []);
                if (is_array($role_option) && !empty($role_option) && !empty($role_option['disable_woocommerce_admin_restrictions'])) {
                    $restrict_access = false;
                    break;
                }
            }
        }
    }
    return $restrict_access;
}
add_filter('woocommerce_prevent_admin_access', 'ppc_roles_disable_woocommerce_admin_restrictions', 20);
add_filter('woocommerce_disable_admin_bar', 'ppc_roles_disable_woocommerce_admin_restrictions', 20);

/**
 * List of capabilities admin pages
 *
 */
function pp_capabilities_admin_pages(){

    $pp_capabilities_pages = [
        'pp-capabilities',
        'pp-capabilities-dashboard',
        'pp-capabilities-roles',
        'pp-capabilities-admin-menus',
        'pp-capabilities-nav-menus',
        'pp-capabilities-editor-features',
        'pp-capabilities-backup',
        'pp-capabilities-settings',
        'pp-capabilities-admin-features',
        'pp-capabilities-frontend-features',
        'pp-capabilities-redirects',
        'pp-capabilities-profile-features'
    ];

   return apply_filters('pp_capabilities_admin_pages', $pp_capabilities_pages);
}

/**
 * Check if user is in capabilities admin page
 *
 */
function is_pp_capabilities_admin_page(){

    $pp_capabilities_pages = pp_capabilities_admin_pages();

    $is_pp_capabilities_page = false;
	if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $pp_capabilities_pages )) {
        $is_pp_capabilities_page = true;
    }

    return apply_filters('is_pp_capabilities_admin_page', $is_pp_capabilities_page);
}


function pp_capabilities_nav_menu_access_denied()
{
    $forbidden = esc_attr__('You do not have permission to access this page.', 'capability-manager-enhanced');
    wp_die(esc_html($forbidden));
}


 /**
  * Display permission recomendation box
  *
  * @return void
  */
function pp_capabilities_sidebox_banner($banner_title, $banner_messages)
{
    //the banner style only got enqueue when banner display
    //funtion is used which will no longer be true after removing the banner.
    wp_enqueue_style(
        'pp-wordpress-banners-style',
        plugin_dir_url(CME_FILE) . 'lib/vendor/publishpress/wordpress-banners/assets/css/style.css',
        false,
        PP_WP_BANNERS_VERSION
    );

    if (!is_array($banner_messages)) {
        $banner_messages = [$banner_messages];
    } ?>
        <div class="pp-sidebar-box advertisement-box-content postbox">
            <div class="postbox-header">
                <h3 class="advertisement-box-header hndle is-non-sortable">
                    <span><?php echo $banner_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?></span>
                </h3>
            </div>
            <div class="inside">
            <ul>
                <?php foreach ($banner_messages as $banner_message) : ?>
                    <?php if (!empty($banner_message)) : ?>
                        <li><?php echo $banner_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped?></li>
                    <?php endif; ?>
                <?php endforeach ?>
            </ul>
            </div>
        </div>
    <?php
}

/**
 * Load pro sidebar
 */
function pp_capabilities_pro_sidebox()
{
    if (defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
        return;
    }

    //the banner style only got enqueue when banner display
    //funtion is used which will no longer be true after removing the banner.
    wp_enqueue_style(
        'pp-wordpress-banners-style',
        plugin_dir_url(CME_FILE) . 'lib/vendor/publishpress/wordpress-banners/assets/css/style.css',
        false,
        PP_WP_BANNERS_VERSION
    );
    ?>
    <div class="ppc-advertisement-promo">
        <div class="advertisement-box-content postbox">
            <div class="postbox-header">
                <h3 class="advertisement-box-header hndle is-non-sortable">
                    <span><?php echo esc_html__('Need PublishPress Capabilities Support?', 'capability-manager-enhanced'); ?></span>
                </h3>
            </div>

            <div class="inside">
                <p><?php echo esc_html__('If you need help or have a new feature request, let us know.', 'capability-manager-enhanced'); ?>
                    <a class="advert-link" href="https://wordpress.org/plugins/capability-manager-enhanced/" target="_blank">
                    <?php echo esc_html__('Request Support', 'capability-manager-enhanced'); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                            <path
                                d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
                            ></path>
                        </svg>
                    </a>
                </p>
                <p>
                <?php echo esc_html__('Detailed documentation is also available on the plugin website.', 'capability-manager-enhanced'); ?>
                    <a class="advert-link" href="https://publishpress.com/docs-category/cme/" target="_blank">
                    <?php echo esc_html__('View Knowledge Base', 'capability-manager-enhanced'); ?>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                            <path
                                d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
                            ></path>
                        </svg>
                    </a>
                </p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Check if current active theme is block
 * theme/support full site editing
 *
 * @return bool
 */
function pp_capabilities_is_block_theme()
{
    $is_block_theme = false;

    if (function_exists('wp_is_block_theme')
        && function_exists('block_template_part')
        && wp_is_block_theme()
    ) {
        $is_block_theme = true;
    }

    return $is_block_theme;
}

/**
 * Get FSE theme nav menus
 *
 * @return array
 */
function pp_capabilities_get_fse_navs()
{
    $args = array(
        'post_type'      => 'wp_navigation',
        'no_found_rows'  => true,
        'order'          => 'DESC',
        'orderby'        => 'date',
        'post_status'    => 'publish',
        'posts_per_page' => -1
    );

    return get_posts($args);
}


/**
 * Get FSE theme nav menus sub items
 *
 * @param integer $nav_id
 * @return array
 */
function pp_capabilities_get_fse_navs_sub_items($nav_id)
{
    $menu_items = [];

    $nav_post = get_post($nav_id);

    if ($nav_post && !is_wp_error($nav_post) && !empty($nav_post->post_content)) {
        $parsed_blocks = parse_blocks($nav_post->post_content);
        $parsed_blocks = block_core_navigation_filter_out_empty_blocks($parsed_blocks);

        foreach ($parsed_blocks as $parsed_block) {
            $menu_items   = pp_capabilities_parse_nav_block($parsed_block, $menu_items);
        }
    }

    return $menu_items;
}

/**
 * Parse nav block attributes to required format
 *
 * @param object $parsed_block
 * @param array $menu_items
 * @param integer $parent
 * @param integer $depth
 * @param string $ancestor_class
 *
 * @return array $menu_items
 */
function pp_capabilities_parse_nav_block($parsed_block, $menu_items, $parent = 0, $depth = 0, $ancestor_class = '') {

    $block_attrs    = $parsed_block['attrs'];
    $inner_blocks   = $parsed_block['innerBlocks'];
    $block_id       = isset($block_attrs['id']) ? $block_attrs['id'] : 0;

    //assign page id to page list block
    if ($block_id === 0 && isset($parsed_block['blockName']) && in_array($parsed_block['blockName'], ['core/page-list'])) {
        $block_id = '+'.abs(crc32(uniqid(true)));
    }

    //add parent id to ancestor class
    if ($parent !== 0) {
        $ancestor_class .= ' ancestor-' . str_replace('+', '', $parent);
    }

    if (isset($block_attrs['kind']) && $block_attrs['kind'] === 'post-type' && wp_get_post_parent_id($block_id) > 0) {
        $parent = wp_get_post_parent_id($block_attrs['id']);
        $post_ancestors = get_post_ancestors($block_attrs['id']);
        $depth  = count($post_ancestors);
        //add post ancestors id to ancestor class
        if (!empty($post_ancestors)) {
            $post_ancesstors_class = ' ancestor-' . join(' ancestor-', $post_ancestors);
            $ancestor_class .= $post_ancesstors_class;
        }
    }

    //we don't want current block id in ancestor class
    $ancestor_class = str_replace('ancestor-' . $block_id . '', '', $ancestor_class);

    if (!empty($block_attrs) && isset($block_attrs['kind']) && isset($block_attrs['label']) && isset($block_attrs['url'])) {
        //This block has attributes
        $menu_items[] = (object) [
            'ID'                => $block_id,
            'title'             =>  ppc_block_menu_icon($parsed_block['blockName']) . ' ' .  $block_attrs['label'],
            'object_id'         => $block_attrs['url'],
            'object'            => isset($block_attrs['type']) ? $block_attrs['type'] : $block_attrs['kind'],
            'menu_item_parent'  => $parent,
            'ancestor_class'    => $ancestor_class,
            'is_parent_page'    => !empty($inner_blocks) || (isset($block_attrs['is_parent_page']) && $block_attrs['is_parent_page'] === 1) ? 1 : 0,
            'depth'             => $depth
        ];

        if (!empty($inner_blocks)) {
            $depth++;
            foreach ($inner_blocks as $inner_block) {
                $menu_items   = pp_capabilities_parse_nav_block($inner_block, $menu_items, max($block_id, 1), $depth, $ancestor_class);
            }
        }
    } elseif (!empty($block_attrs) && isset($block_attrs['label'])) {
        //This block has the needed label attribute (core/search, core/home-link)
        $menu_items[] = (object) [
            'ID'                => $block_id,
            'title'             => ppc_block_menu_icon($parsed_block['blockName']) . ' ' .  $block_attrs['label'],
            'object_id'         => $parsed_block['blockName'],
            'object'            => 'custom_block_' . sanitize_title_with_dashes($block_attrs['label']),
            'menu_item_parent'  => $parent,
            'ancestor_class'    => $ancestor_class,
            'is_parent_page'    => !empty($inner_blocks) ? 1 : 0,
            'depth'             => $depth
        ];

        if (!empty($inner_blocks)) {
            $depth++;
            foreach ($inner_blocks as $inner_block) {
                $menu_items   = pp_capabilities_parse_nav_block($inner_block, $menu_items, max($block_id, 1), $depth, $ancestor_class);
            }
        }
    } elseif (!empty($parsed_block) && isset($parsed_block['blockName']) && in_array($parsed_block['blockName'], ['core/site-logo', 'core/site-title', 'core/social-links', 'core/page-list'])) {
        //This block doesn't have any block attr
        $menu_items[] = (object) [
            'ID'                => $block_id,
            'title'             => ppc_block_menu_icon($parsed_block['blockName']) . ' ' .  ppc_block_friend_name($parsed_block['blockName']),
            'object_id'         => $parsed_block['blockName'],
            'object'            => 'custom_block',
            'menu_item_parent'  => $parent,
            'ancestor_class'    => $ancestor_class,
            'is_parent_page'    => $block_id !== 0 && (!empty($inner_blocks) || $parsed_block['blockName'] === 'core/page-list') ? 1 : 0,
            'depth'             => $depth
        ];

        //add page list inner block
        if ($parsed_block['blockName'] === 'core/page-list') {
            $pages_args = ['sort_column' => 'menu_order,post_title', 'order' => 'asc'];
            if (isset($block_attrs['parentPageID']) && !empty($block_attrs['parentPageID'])) {
                $pages_args['child_of'] = $block_attrs['parentPageID'];
            }
            $all_pages = get_pages($pages_args);
            if (!empty($all_pages)) {
                foreach ( (array) $all_pages as $page ) {
                    $children = get_pages('child_of='.$page->ID);
                    $inner_blocks[] = [
                        'blockName' => 'page_list_link',
                        'attrs'     => [
                            'label' => $page->post_title,
                            'type'  => 'page',
                            'kind'  => 'post-type',
                            'id'    => $page->ID,
                            'is_parent_page' => count($children) > 0 ? 1 : 0,
                            'url'   => get_permalink($page->ID)
                        ],
                        'innerBlocks' => []
                    ];
                }
            }
        }

        if (!empty($inner_blocks)) {
            $depth++;
            foreach ($inner_blocks as $inner_block) {
                $menu_items   = pp_capabilities_parse_nav_block($inner_block, $menu_items, max($block_id, 1), $depth, $ancestor_class);
            }
        }
    }

    return $menu_items;
}

function ppc_block_friend_name($block_name) {

    $friendly_name = $block_name;

    $supported_blocks = [
        'core/site-logo'     => __('Logo'),
        'core/site-title'    => __('Site Title'),
        'core/social-links'  => __('Social Links', 'capability-manager-enhanced'),
        'core/page-list'     => __('Page Lists', 'capability-manager-enhanced'),
        'core/search'        => __('Search'),
        'core/home-link'     => _x( 'Home', 'nav menu home label' ),
    ];

    if (array_key_exists($block_name, $supported_blocks)) {
        $friendly_name = $supported_blocks[$block_name];
    }

    return $friendly_name;
}


function ppc_block_menu_icon($block_name) {

    return '<i class="dashicons dashicons-arrow-right"></i>';

    $menu_icon = '<span class="ppc-menu-block-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M7 13.8h6v-1.5H7v1.5zM18 16V4c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2zM5.5 16V4c0-.3.2-.5.5-.5h10c.3 0 .5.2.5.5v12c0 .3-.2.5-.5.5H6c-.3 0-.5-.2-.5-.5zM7 10.5h8V9H7v1.5zm0-3.3h8V5.8H7v1.4zM20.2 6v13c0 .7-.6 1.2-1.2 1.2H8v1.5h11c1.5 0 2.7-1.2 2.7-2.8V6h-1.5z"></path></svg></span>';

    $supported_blocks = [
        'core/site-logo'     => '<span class="ppc-menu-block-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M12 3c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 1.5c4.1 0 7.5 3.4 7.5 7.5v.1c-1.4-.8-3.3-1.7-3.4-1.8-.2-.1-.5-.1-.8.1l-2.9 2.1L9 11.3c-.2-.1-.4 0-.6.1l-3.7 2.2c-.1-.5-.2-1-.2-1.5 0-4.2 3.4-7.6 7.5-7.6zm0 15c-3.1 0-5.7-1.9-6.9-4.5l3.7-2.2 3.5 1.2c.2.1.5 0 .7-.1l2.9-2.1c.8.4 2.5 1.2 3.5 1.9-.9 3.3-3.9 5.8-7.4 5.8z"></path></svg></span>',
        'core/site-title'    => '<span class="ppc-menu-block-icon"><svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M12 9c-.8 0-1.5.7-1.5 1.5S11.2 12 12 12s1.5-.7 1.5-1.5S12.8 9 12 9zm0-5c-3.6 0-6.5 2.8-6.5 6.2 0 .8.3 1.8.9 3.1.5 1.1 1.2 2.3 2 3.6.7 1 3 3.8 3.2 3.9l.4.5.4-.5c.2-.2 2.6-2.9 3.2-3.9.8-1.2 1.5-2.5 2-3.6.6-1.3.9-2.3.9-3.1C18.5 6.8 15.6 4 12 4zm4.3 8.7c-.5 1-1.1 2.2-1.9 3.4-.5.7-1.7 2.2-2.4 3-.7-.8-1.9-2.3-2.4-3-.8-1.2-1.4-2.3-1.9-3.3-.6-1.4-.7-2.2-.7-2.5 0-2.6 2.2-4.7 5-4.7s5 2.1 5 4.7c0 .2-.1 1-.7 2.4z"></path></svg></span>',
        'core/social-links'  => '<span class="ppc-menu-block-icon"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M9 11.8l6.1-4.5c.1.4.4.7.9.7h2c.6 0 1-.4 1-1V5c0-.6-.4-1-1-1h-2c-.6 0-1 .4-1 1v.4l-6.4 4.8c-.2-.1-.4-.2-.6-.2H6c-.6 0-1 .4-1 1v2c0 .6.4 1 1 1h2c.2 0 .4-.1.6-.2l6.4 4.8v.4c0 .6.4 1 1 1h2c.6 0 1-.4 1-1v-2c0-.6-.4-1-1-1h-2c-.5 0-.8.3-.9.7L9 12.2v-.4z"></path></svg></span>',
        'core/page-list'     => '<span class="ppc-menu-block-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M7 13.8h6v-1.5H7v1.5zM18 16V4c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2zM5.5 16V4c0-.3.2-.5.5-.5h10c.3 0 .5.2.5.5v12c0 .3-.2.5-.5.5H6c-.3 0-.5-.2-.5-.5zM7 10.5h8V9H7v1.5zm0-3.3h8V5.8H7v1.4zM20.2 6v13c0 .7-.6 1.2-1.2 1.2H8v1.5h11c1.5 0 2.7-1.2 2.7-2.8V6h-1.5z"></path></svg></span>',
        'core/search'        => '<span class="ppc-menu-block-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M13.5 6C10.5 6 8 8.5 8 11.5c0 1.1.3 2.1.9 3l-3.4 3 1 1.1 3.4-2.9c1 .9 2.2 1.4 3.6 1.4 3 0 5.5-2.5 5.5-5.5C19 8.5 16.5 6 13.5 6zm0 9.5c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4z"></path></svg></span>',
        'core/home-link'     => '<span class="ppc-menu-block-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M12 4L4 7.9V20h16V7.9L12 4zm6.5 14.5H14V13h-4v5.5H5.5V8.8L12 5.7l6.5 3.1v9.7z"></path></svg></span>',
        'core/navigation-link' => '<span class="ppc-menu-block-icon"><svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" context="list-view" aria-hidden="true" focusable="false"><path d="M12.5 14.5h-1V16h1c2.2 0 4-1.8 4-4s-1.8-4-4-4h-1v1.5h1c1.4 0 2.5 1.1 2.5 2.5s-1.1 2.5-2.5 2.5zm-4 1.5v-1.5h-1C6.1 14.5 5 13.4 5 12s1.1-2.5 2.5-2.5h1V8h-1c-2.2 0-4 1.8-4 4s1.8 4 4 4h1zm-1-3.2h5v-1.5h-5v1.5zM18 4H9c-1.1 0-2 .9-2 2v.5h1.5V6c0-.3.2-.5.5-.5h9c.3 0 .5.2.5.5v12c0 .3-.2.5-.5.5H9c-.3 0-.5-.2-.5-.5v-.5H7v.5c0 1.1.9 2 2 2h9c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"></path></svg></span>',
    ];

    if (array_key_exists($block_name, $supported_blocks)) {
        $menu_icon = $supported_blocks[$block_name];
    }

    return $menu_icon;
}

/**
 * Nav menu restriction
 */
if (!is_admin()) {

    /**
     * Classic menu
     *
     * Checks the menu items for their visibility options and
     * removes menu items that are not visible.
     *
     * @return array
     */
    function pp_capabilities_nav_menu_permission($items, $menu, $args)
    {
        //return if it's admin page
        if (is_admin() || !pp_capabilities_feature_enabled('nav-menus')) {
            return $items;
        }

        $disabled_nav_menu = '';

        $user_roles = (array)wp_get_current_user()->roles;
        $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array)get_option('capsman_nav_item_menus') : [];

        //add loggedin and guest option to role
        if (is_user_logged_in()) {
            $user_roles[] = 'ppc_users';
        } else {
            $user_roles[] = 'ppc_guest';
        }

        /*
         * PublishPress Permissions: Restrict Nav Menus for a Permission Group
         * (Integrate PublishPress Capabilities Pro functionality).
         *
         * Copy into functions.php, modifying $restriction_role and $permission_group_ids to match your usage.
         *
         * note: Restriction_role can be an extra role that you create just for these menu restrictions.
         *       Configure Capabilities > Nav Menus as desired for that role.
         */
        /*
        add_filter('pp_capabilities_nav_menu_apply_role_restrictions',
            function($roles, $menu_object) {
                if (function_exists('presspermit')) {
                    $permission_group_ids = [12, 14, 15];   // group IDs to restrict
                    $restriction_role = 'subscriber';       // role that has restrictions defined by Capabilities > Nav Menus

                    if (array_intersect(
                        array_keys(presspermit()->getUser()->groups['pp_group']),
                        $permission_group_ids
                    )) {
                        $roles []= $restriction_role;
                    }
                }

                return $roles;
            },
            10, 2
        );
        */

        // Support plugin integrations by allowing additional role-based limitations to be applied to user based on external criteria
        $user_roles = apply_filters('pp_capabilities_nav_menu_apply_role_restrictions', $user_roles, compact('menu'));

        //extract disabled menu for roles user belong
        foreach ($user_roles as $role) {
            if (array_key_exists($role, $nav_menu_item_option)) {
                $disabled_nav_menu .= implode(", ", (array)$nav_menu_item_option[$role]) . ', ';
            }
        }

        if ($disabled_nav_menu) {

            //extract only IDS
            $disabled_item_ids = preg_replace('!(0|[1-9][0-9]*)_([a-zA-Z0-9_.-]*),!s', '$1,', $disabled_nav_menu);

            $disabled_nav_menu_array = array_filter(explode(", ", $disabled_item_ids));

            foreach ($items as $key => $item) {

                $item_parent = get_post_meta($item->ID, '_menu_item_menu_item_parent', true);

                if (in_array($item->ID, $disabled_nav_menu_array) || in_array($item_parent, $disabled_nav_menu_array)) {
                    unset($items[$key]);
                }
            }


        }

        return $items;
    }
    add_filter('wp_get_nav_menu_items', 'pp_capabilities_nav_menu_permission', 99, 3);

    /**
     * FSE theme menu
     *
     * Checks the menu items for their visibility options and
     * removes menu items that are not visible.
     *
     * @return array
     */
    function pp_capabilities_fse_nav_menu_permission($inner_blocks) {
        global $ppc_disabled_nav_menu_data;

        //return if it's admin page
        if (is_admin() || !pp_capabilities_feature_enabled('nav-menus')) {
            return $inner_blocks;
        }

        if (!is_array($ppc_disabled_nav_menu_data)) {
            //we want to make sure we're not running disabled data check multiple times for each filter
            $ppc_disabled_nav_menu_data = [];
        }

        if (isset($ppc_disabled_nav_menu_data['nav_menu_item_option'])) {
            $nav_menu_item_option = $ppc_disabled_nav_menu_data['nav_menu_item_option'];
        } else {
            $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array)get_option('capsman_nav_item_menus') : [];
            $ppc_disabled_nav_menu_data['nav_menu_item_option'] = $nav_menu_item_option;
        }

        if (!$nav_menu_item_option || !function_exists('wp_get_current_user')) {
            return $inner_blocks;
        }

        if (isset($ppc_disabled_nav_menu_data['disabled_nav_menu'])) {
            $disabled_nav_menu = $ppc_disabled_nav_menu_data['disabled_nav_menu'];
        } else {
            $user_roles = (array)wp_get_current_user()->roles;
            //add loggedin and guest option to role
            $user_roles[] = (is_user_logged_in()) ? 'ppc_users' : 'ppc_guest';
            // Support plugin integrations by allowing additional role-based limitations to be applied to user based on external criteria
            $user_roles = apply_filters('pp_capabilities_nav_menu_access_role_restrictions', $user_roles);
            $disabled_nav_menu = '';
            //extract disabled menu for roles user belong
            foreach ($user_roles as $role) {
                if (array_key_exists($role, $nav_menu_item_option)) {
                    $disabled_nav_menu .= implode(", ", (array)$nav_menu_item_option[$role]) . ', ';
                }
            }
            $ppc_disabled_nav_menu_data['disabled_nav_menu'] = $disabled_nav_menu;
        }

        if ($disabled_nav_menu) {
            if (isset($ppc_disabled_nav_menu_data['fse_theme'])) {
                $fse_theme               = $ppc_disabled_nav_menu_data['fse_theme'];
                $disabled_object         = $ppc_disabled_nav_menu_data['disabled_object'];
                $fse_blocked_nav_links   = $ppc_disabled_nav_menu_data['fse_blocked_nav_links'];
                $disabled_nav_menu_array = $ppc_disabled_nav_menu_data['disabled_nav_menu_array'];
            } else {
                $fse_theme = pp_capabilities_is_block_theme();
                //we only need object id and object name e.g, 1_category
                if ($fse_theme) {
                    $disabled_object = preg_replace("/(\|)(.*?)(\|)/", '_', $disabled_nav_menu);
                    //get all urls for css, js and direct block implementation
                    preg_match_all("/\|\s*(.*?)\s*\|/", $disabled_nav_menu, $matches);
                    $fse_blocked_nav_links = array_filter($matches[1]);
                } else {
                    //native nav uses _ separator
                    $disabled_object = preg_replace('!(0|[1-9][0-9]*)_([a-zA-Z0-9_.-]*),!s', '$2,', $disabled_nav_menu);
                    $fse_blocked_nav_links        = [];
                }
                $disabled_nav_menu_array = array_filter(explode(", ", $disabled_object));

                $ppc_disabled_nav_menu_data['fse_theme'] = $fse_theme;
                $ppc_disabled_nav_menu_data['disabled_object'] = $disabled_object;
                $ppc_disabled_nav_menu_data['fse_blocked_nav_links'] = $fse_blocked_nav_links;
                $ppc_disabled_nav_menu_data['disabled_nav_menu_array'] = $disabled_nav_menu_array;
            }

            //we're having issues removing item due to key change. So, we should do this in array
            $removeable_keys    = [];
            $new_page_list_items = [];
            foreach ($inner_blocks as $key_offset => $inner_block) {
                if (isset($inner_block->parsed_block) && is_array($inner_block->parsed_block) && !empty($inner_block->parsed_block)) {
                    $block_details = $inner_block->parsed_block;
                    $block_name    = isset($block_details['blockName']) ? $block_details['blockName'] : false;
                    $block_attrs   = isset($block_details['attrs']) ? (array)$block_details['attrs'] : false;
                    $block_label   = (isset($block_attrs['label'])) ? $block_attrs['label'] : false;
                    $block_id      = isset($block_attrs['id']) ? $block_attrs['id'] : 0;
                    if (in_array($block_name, ['core/site-logo', 'core/site-title', 'core/social-links', 'core/search', 'core/home-link']) && in_array($block_name, $fse_blocked_nav_links)) {
                        //unset core nav block
                        $removeable_keys[] = $key_offset;
                    } elseif ($block_label && in_array($block_id . '_custom_block_' . sanitize_title_with_dashes($block_label), $disabled_nav_menu_array)) {
                        //unset custom block that doesn't have ID identifier
                        $removeable_keys[] = $key_offset;
                    } elseif (is_array($block_attrs) && isset($block_attrs['url']) && in_array($block_attrs['url'], $fse_blocked_nav_links)) {
                        //unset custom links
                        $removeable_keys[] = $key_offset;
                    } elseif (in_array($block_name, ['core/page-list'])) {
                        //unset page list nav block
                        $removeable_keys[] = $key_offset;

                        //we need to list all non-editable pagelist pages and add them as block to enable removal
                        $parent_page_id = isset($block_attrs['parentPageID']) ? (int) $block_attrs['parentPageID'] : 0;
                        $all_pages = get_pages(
                            [
                                'sort_column' => 'menu_order,post_title',
                                'order'       => 'asc',
                            ]
                        );

                        if (!empty($all_pages)) {
                            $top_level_pages     = [];
                            $pages_with_children = [];
                            foreach ((array) $all_pages as $page) {
                                $page_link = get_permalink($page->ID);
                                //only add pages not blocked as navigation link
                                if (!in_array($page_link, $fse_blocked_nav_links)) {
                                    if ($page->post_parent) {
                                        $pages_with_children[ $page->post_parent ][ $page->ID ] = [
                                            'blockName' => 'core/navigation-link',
                                            'attrs'     => [
                                                'label' => $page->post_title,
                                                'type'  => 'page',
                                                'kind'  => 'post-type',
                                                'id'    => $page->ID,
                                                'url'   => $page_link,
                                            ],
                                            'innerBlocks'  => [],
                                            'innerHTML'    => '',
                                            'innerContent' => [],
                                        ];
                                    } else {
                                        $top_level_pages[ $page->ID ] = [
                                            'blockName' => 'core/navigation-link',
                                            'attrs'     => [
                                                'label' => $page->post_title,
                                                'type'  => 'page',
                                                'kind'  => 'post-type',
                                                'id'    => $page->ID,
                                                'url'   => $page_link,
                                                'isTopLevelLink' => 1
                                            ],
                                            'innerBlocks'  => [],
                                            'innerHTML'    => '',
                                            'innerContent' => [],
                                        ];
                                    }
                                }
                            }

                            $new_page_list_items = ppc_block_core_page_list_nest_pages( $top_level_pages, $pages_with_children );
                            if ( 0 !== $parent_page_id ) {
                                if (array_key_exists($parent_page_id, $pages_with_children)) {
                                    $new_page_list_items = ppc_block_core_page_list_nest_pages(
                                        $pages_with_children[$parent_page_id],
                                        $pages_with_children
                                    );
                                } else {
                                    // If the parent page has no child pages, there is nothing to show.
                                    $new_page_list_items = [];
                                }
                            }
                        }
                    } else {
                        //this is probably a block we currently not supporting
                    }
                }
            }

            //unset using block function
            foreach (array_values($removeable_keys) as $removeable_keys) {
                $inner_blocks->offsetUnset($removeable_keys);
            }
            //add new navigation links that wasn't removed from page list to block
            if (!empty($new_page_list_items)) {
                foreach ($new_page_list_items as $new_page_list_item) {
                    $inner_blocks->offsetSet(null, $new_page_list_item);
                }
            }
        }

        return $inner_blocks;
    }
    add_filter('block_core_navigation_render_inner_blocks', 'pp_capabilities_fse_nav_menu_permission', 999);

    /**
     * Outputs nested array of pages  inner blocks
     *
     * @param array $current_level The level being iterated through.
     * @param array $children The children grouped by parent post ID.
     *
     * @return array The nested array of pages.
     */
    function ppc_block_core_page_list_nest_pages( $current_level, $children ) {
        if ( empty( $current_level ) ) {
            return;
        }
        foreach ( (array) $current_level as $key => $current ) {
            if ( isset( $children[ $key ] ) ) {
                $current_level[ $key ]['innerBlocks'] = ppc_block_core_page_list_nest_pages($children[ $key ], $children);
            }
        }
        return $current_level;
    }

    /**
     * Checks the menu items for their privacy and remove
     * if user do not have permission to item
     *
     */
    function pp_capabilities_nav_menu_access($query)
    {
        global $ppc_nav_menu_restricted, $ppc_disabled_nav_menu_data;

        //this function is getting called many times. So, it's needed
        if ($ppc_nav_menu_restricted || !pp_capabilities_feature_enabled('nav-menus')) {
            return;
        }

        if (!function_exists('wp_get_current_user')) {
            return;
        }

        if (!is_array($ppc_disabled_nav_menu_data)) {
            //we want to make sure we're not running disabled data check multiple times for each filter
            $ppc_disabled_nav_menu_data = [];
        }

        if (isset($ppc_disabled_nav_menu_data['nav_menu_item_option'])) {
            $nav_menu_item_option = $ppc_disabled_nav_menu_data['nav_menu_item_option'];
        } else {
            $nav_menu_item_option = !empty(get_option('capsman_nav_item_menus')) ? (array)get_option('capsman_nav_item_menus') : [];
            $ppc_disabled_nav_menu_data['nav_menu_item_option'] = $nav_menu_item_option;
        }

        if (!$nav_menu_item_option || !function_exists('wp_get_current_user')) {
            return;
        }

        if (isset($ppc_disabled_nav_menu_data['disabled_nav_menu'])) {
            $disabled_nav_menu = $ppc_disabled_nav_menu_data['disabled_nav_menu'];
        } else {
            $user_roles = (array)wp_get_current_user()->roles;
            //add loggedin and guest option to role
            $user_roles[] = (is_user_logged_in()) ? 'ppc_users' : 'ppc_guest';
            // Support plugin integrations by allowing additional role-based limitations to be applied to user based on external criteria
            $user_roles = apply_filters('pp_capabilities_nav_menu_access_role_restrictions', $user_roles);
            $disabled_nav_menu = '';
            //extract disabled menu for roles user belong
            foreach ($user_roles as $role) {
                if (array_key_exists($role, $nav_menu_item_option)) {
                    $disabled_nav_menu .= implode(", ", (array)$nav_menu_item_option[$role]) . ', ';
                }
            }
            $ppc_disabled_nav_menu_data['disabled_nav_menu'] = $disabled_nav_menu;
        }

        if ($disabled_nav_menu) {
            if (isset($ppc_disabled_nav_menu_data['fse_theme'])) {
                $fse_theme               = $ppc_disabled_nav_menu_data['fse_theme'];
                $disabled_object         = $ppc_disabled_nav_menu_data['disabled_object'];
                $fse_blocked_nav_links   = $ppc_disabled_nav_menu_data['fse_blocked_nav_links'];
                $disabled_nav_menu_array = $ppc_disabled_nav_menu_data['disabled_nav_menu_array'];
            } else {
                $fse_theme = pp_capabilities_is_block_theme();
                //we only need object id and object name e.g, 1_category
                if ($fse_theme) {
                    $disabled_object = preg_replace("/(\|)(.*?)(\|)/", '_', $disabled_nav_menu);
                    //get all urls for css, js and direct block implementation
                    preg_match_all("/\|\s*(.*?)\s*\|/", $disabled_nav_menu, $matches);
                    $fse_blocked_nav_links = array_filter($matches[1]);
                } else {
                    //native nav uses _ separator
                    $disabled_object = preg_replace('!(0|[1-9][0-9]*)_([a-zA-Z0-9_.-]*),!s', '$2,', $disabled_nav_menu);
                    $fse_blocked_nav_links        = [];
                }
                $disabled_nav_menu_array = array_filter(explode(", ", $disabled_object));

                $ppc_disabled_nav_menu_data['fse_theme'] = $fse_theme;
                $ppc_disabled_nav_menu_data['disabled_object'] = $disabled_object;
                $ppc_disabled_nav_menu_data['fse_blocked_nav_links'] = $fse_blocked_nav_links;
                $ppc_disabled_nav_menu_data['disabled_nav_menu_array'] = $disabled_nav_menu_array;
            }

            //category tags and taxonomy page check
            if (is_category() || is_tag() || is_tax()) {
                $taxonomy_id = get_queried_object()->term_id;
                $taxonnomy_type = get_queried_object()->taxonomy;
                $option_object = $taxonomy_id . '_' . $taxonnomy_type;
                if (in_array($option_object, $disabled_nav_menu_array)) {
                    $ppc_nav_menu_restricted = true;
                    pp_capabilities_nav_menu_access_denied();
                }
            }

            //post, page, cpt check
            if (is_singular()) {
                $post_type = get_post_type();
                $post_id = get_the_ID();
                $option_object = $post_id . '_' . $post_type;
                if (in_array($option_object, $disabled_nav_menu_array)) {
                    $ppc_nav_menu_restricted = true;
                    pp_capabilities_nav_menu_access_denied();
                }
            }

            $ppc_nav_menu_restricted = true;

            if (!empty($fse_blocked_nav_links)) {
                //restrict access to url
                if (in_array(pp_capabilities_current_url(), $fse_blocked_nav_links)) {
                    $ppc_nav_menu_restricted = true;
                    pp_capabilities_nav_menu_access_denied();
                }
                //hide menu item immediately, remove menu item from li after page load
                add_action('wp_head', function() use ($fse_blocked_nav_links) {
                    $menu_item_selectors = array_map('pp_capabilities_nav_link_selector', $fse_blocked_nav_links);
                    ?>
                    <style>
                        <?php echo join(', ', $menu_item_selectors); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        {
                            display: none !important;
                        }
                    </style>

                    <script>
                        jQuery(document).ready( function($) {
                            $('<?php echo join(', ', $menu_item_selectors); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>').each( function() {
                                $(this).closest('li').remove();
                                $(this).remove();
                            });
                        });
                    </script>
                    <?php
                });
            }

        }
    }
    add_action('parse_query', 'pp_capabilities_nav_menu_access');
}

/**
 * Generate style link selector for a nav link
 *
 * @param string $url
 * @return string
 */
function pp_capabilities_nav_link_selector($url) {

    $link_selector = 'li.wp-block-navigation-item a[href*="'. $url .'"]';
    if ($url === 'core/search') {
        $link_selector = '.wp-block-navigation .wp-block-search';
    } elseif ($url === 'core/site-logo') {
        $link_selector = 'li.wp-block-navigation-item .wp-block-site-logo';
    } elseif ($url === 'core/site-title') {
        $link_selector = '.wp-block-navigation .wp-block-site-title';
    } elseif ($url === 'core/social-links') {
        $link_selector = '.wp-block-navigation .wp-block-social-links';
    } elseif ($url === 'core/home-link') {
        $link_selector = '.wp-block-navigation .wp-block-home-link';
    }

    return $link_selector;
}

/**
 * Get current page URL
 *
 * @return string
 */
function pp_capabilities_current_url()
{
    if (!empty($_SERVER['HTTP_HOST']) && !empty($_SERVER['REQUEST_URI'])) {
        return esc_url_raw((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    } else {
        return home_url('');
    }
}

/**
 * Check if a feature is enabled
 *
 * @param integer $feature
 *
 * @return bool
 */
function pp_capabilities_feature_enabled($feature) {
    global $capsman_dashboard_features_status;

    //let use global settings incase this request is made more than once in a page load
    if (!is_array($capsman_dashboard_features_status)) {
        $capsman_dashboard_features_status = !empty(get_option('capsman_dashboard_features_status')) ? (array)get_option('capsman_dashboard_features_status') : [];
    }

    //let enable all feature by default
    $feature_enabled = true;
    if (isset($capsman_dashboard_features_status[$feature])
        && isset($capsman_dashboard_features_status[$feature]['status'])
        && $capsman_dashboard_features_status[$feature]['status'] === 'off'
    ) {
        $feature_enabled = false;
    }

    return $feature_enabled;
}
