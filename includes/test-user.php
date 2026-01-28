<?php

class PP_Capabilities_Test_User
{
    /**
     * Cookie expiration seconds
     */
    const AUTH_COOKIE_EXPIRATION = DAY_IN_SECONDS;

    /**
     * Cookie hour in seconds
     */
    const AUTH_COOKIE_HOUR_IN_SECONDS = HOUR_IN_SECONDS;

    private static $cookie_name;

    public function __construct()
    {
        self::$cookie_name = defined('PPC_TEST_USER_COOKIE_NAME') ? PPC_TEST_USER_COOKIE_NAME : 'ppc_test_user_tester_' . COOKIEHASH;
        //clear test user cookie on logout and login
        add_action('wp_logout', [$this, 'clearTestUserCookie']);
        add_action('wp_login', [$this, 'clearTestUserCookie']);

        $this->handleUserAction();

        if (is_admin() || self::testerAuth()) {
            require_once (PUBLISHPRESS_CAPS_ABSPATH . '/includes/test-user-ui.php');
            new PP_Capabilities_Test_User_UI();
        }
    }

    /**
     * Initialize test user component
     */
    public static function init()
    {
        $instance = new self;
        return $instance;
    }

    /**
     * Test user process
     */
    public function handleUserAction()
    {
        global $current_user;

        if (!is_user_logged_in() || !isset($_GET['ppc_test_user']) || !isset($_GET['_wpnonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'ppc-test-user')) {
            wp_die(esc_html__('Your link has expired, refresh the page and try again.', 'capability-manager-enhanced'));
        }

        $request_user_id = isset($_GET['ppc_test_user']) ? (int) base64_decode(sanitize_text_field($_GET['ppc_test_user'])) : 0;
        $ppc_return_back = isset($_GET['ppc_return_back']) ? (int) sanitize_text_field($_GET['ppc_return_back']) : 0;
        $current_user_id = get_current_user_id();
        $request_user    = get_userdata($request_user_id);

        if (!$request_user || (is_object($request_user) && !isset($request_user->ID))) {
            wp_die(esc_html__('Unable to retrieve user data.', 'capability-manager-enhanced'));
        } else {
            $profile_feature_action = isset($_GET['profile_feature_action']) ? (int) sanitize_text_field($_GET['profile_feature_action']) : 0;
            if ($ppc_return_back > 0) {
                $user_auth        = wp_unslash(self::testerAuth());
                $original_user_id = wp_validate_auth_cookie($user_auth, 'logged_in');

                if ($profile_feature_action === 1) {
                    $redirect_url = admin_url('admin.php?page=pp-capabilities-profile-features');
                } else {
                    $redirect_url = admin_url();
                }

                if ($original_user_id) {
                    wp_set_auth_cookie($original_user_id, false);

                    // Unset the cookie
                    $this->clearTestUserCookie();

                    /**
                     * Action called before redirecting user back
                     *
                     * @param integer $original_user_id The original user ID. This is the User that's been logged in back.
                     * @param integer $request_user_id The tested account user ID
                     */
                    do_action('pp_capabilities_test_user_restored', $original_user_id, $request_user_id);

                    //redirect back to admin dashboard
                    wp_safe_redirect($redirect_url);
                    exit;
                }
            } elseif (is_admin() && self::canTestUser($request_user)) {

                if ($profile_feature_action === 1) {
                    $redirect_url = admin_url('profile.php?ppc_profile_element=1');
                } elseif (user_can($request_user->ID, 'read')) {
                    $redirect_url = admin_url();
                } else {
                    $redirect_url = home_url();;
                }

                $original_user_id = $current_user_id;

                // Create and set auth cookie for current user before switching
                $token = function_exists('wp_get_session_token') ? wp_get_session_token() : '';
                $orig_auth_cookie = wp_generate_auth_cookie($current_user->ID, time() + self::AUTH_COOKIE_EXPIRATION, 'logged_in', $token);

                // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
                setcookie(self::$cookie_name, $orig_auth_cookie, time() + self::AUTH_COOKIE_EXPIRATION, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);

                // Login as the other user
                wp_set_auth_cookie($request_user_id, false);

                /**
                 * Action called before redirecting user after setting
                 * authentication for testing user.
                 *
                 * @param integer $request_user_id The tested account user ID. This is the User that's been logged in to.
                 * @param integer $original_user_id The original user ID
                 */
                do_action('pp_capabilities_test_user_authenticated', $request_user_id, $original_user_id);

                //redirect user to admin dashboard
                wp_safe_redirect($redirect_url);
                exit;
            }
        }
    }

    /**
     * Clear test user cookie on logout and login
     *
     * @return void
     */
    public function clearTestUserCookie() {
        // Unset the cookie
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
        setcookie(self::$cookie_name, 0, time() - self::AUTH_COOKIE_HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }

    /**
     * Get tester user from cookie
     */
    protected static function testerAuth()
    {
        $auth_key = self::$cookie_name;
        if (isset($_COOKIE[$auth_key]) && !empty($_COOKIE[$auth_key])) {
            // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
            return $_COOKIE[$auth_key];
        } else {
            return false;
        }
    }

    /**
     * Check if current user can test user
     */
    protected static function canTestUser($user)
    {
        $excluded_roles = (array) get_option('cme_test_user_excluded_roles', []);

        $can_test_user  = false;
        if (current_user_can('manage_capabilities_user_testing')
            && current_user_can('edit_user', $user->ID)
            && $user->ID !== get_current_user_id()
            && !array_intersect($excluded_roles, $user->roles)
        ) {
            $can_test_user = true;
        }

        return $can_test_user;
    }
}
