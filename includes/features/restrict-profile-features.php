<?php
namespace PublishPress\Capabilities;

class PP_Capabilities_Profile_Features
{

    /**
     * Class hooks and filters
     *
     * @return void
     */
    public static function instance() {
        //ajax handler for updating profile features elements
        add_action('wp_ajax_ppc_update_profile_features_element_by_ajax', [__CLASS__, 'profileElementUpdateAjaxHandler']);
        //ajax handler for enabling profile features capture for a role
        add_action('wp_ajax_ppc_set_profile_features_role', [__CLASS__, 'setProfileFeaturesRoleAccess']);
        //implement profile features restriction
        add_action('admin_head', [__CLASS__, 'applyProfileRestriction'], 1);
    }

    /**
     * Ajax handler for updating profile features elements
     *
     * @since 2.7.0
     */
    public static function profileElementUpdateAjaxHandler()
    {
        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';
        $redirect_url = admin_url('admin.php?page=pp-capabilities-profile-features');

        $security       = isset($_POST['security']) ? sanitize_key($_POST['security']) : false;
        $page_elements  = isset($_POST['page_elements']) ? $_POST['page_elements'] : [];// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if (!$security || !wp_verify_nonce($security, 'ppc-profile-edit-action') || !pp_capabilities_feature_enabled('profile-features')) {
            $response['redirect'] = $redirect_url;
        } else {
            // Check if the current role is enabled for profile features editing
            $profile_feature_role = get_option("capsman_profile_features_elements_testing_role", 'subscriber');
            $temporary_role = self::getTemporaryProfileFeaturesRole();

            if (empty($temporary_role) || $temporary_role !== $profile_feature_role) {
                $response['status']  = 'error';
                $response['message'] = __('This role is not enabled for profile feature editing.', 'capability-manager-enhanced');
                $response['redirect'] = $redirect_url;
                wp_send_json($response);
            }

            $response['status']  = 'success';
            $profile_features_elements = self::elementsLayout();
            $profile_element_updated = (array) get_option("capsman_profile_features_updated", []);
            $profile_element_updated[$profile_feature_role] = 1;
            $role_profile_features_elements = [];
            foreach ($page_elements as $key => $data) {
                $new_element_key  = sanitize_key($key);
                $new_element_data = [
                    'label'        => sanitize_text_field($data['label']),
                    'elements'     => sanitize_text_field($data['elements']),
                    'element_type' => sanitize_key($data['element_type'])
                ];
                $role_profile_features_elements[$new_element_key] = $new_element_data;
            }
            $profile_features_elements[$profile_feature_role] = $role_profile_features_elements;
            update_option('capsman_profile_features_elements', $profile_features_elements, false);
            update_option('capsman_profile_features_updated', $profile_element_updated, false);
            delete_option('capsman_profile_features_elements_testing_role');
            delete_option('capsman_profile_features_role');
            $cookie_name = defined('PPC_TEST_USER_COOKIE_NAME') ? PPC_TEST_USER_COOKIE_NAME : 'ppc_test_user_tester_' . COOKIEHASH;
            if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name])) {
                $user = wp_get_current_user();
                $redirect_url = add_query_arg(
                    [
                        'ppc_test_user'   => base64_encode(get_current_user_id()),
                        'profile_feature_action' => 1,
                        'ppc_return_back' => 1,
                        '_wpnonce'        => wp_create_nonce('ppc-test-user')
                    ],
                    home_url()
                );
                $response['redirect'] = $redirect_url;
            } else {
                $response['redirect'] = $redirect_url;
            }
        }
        wp_send_json($response);
    }

    /**
     * Get all admin features layout.
     *
     * @return array Elements layout.
     */
    public static function elementsLayout()
    {
        $elements = !empty(get_option('capsman_profile_features_elements')) ? (array)get_option('capsman_profile_features_elements') : [];
        $elements = array_filter($elements);

        return apply_filters('pp_capabilities_profile_features_elements', $elements);
    }

    /**
     * Enable or disable profile features capture for a role
     *
     * @since 2.7.0
     */
    public static function setProfileFeaturesRoleAccess()
    {
        $response['status']  = 'error';
        $response['message'] = __('An error occurred!', 'capability-manager-enhanced');

        $security = isset($_POST['security']) ? sanitize_key($_POST['security']) : false;
        $role = isset($_POST['role']) ? sanitize_key($_POST['role']) : '';
        $enabled = isset($_POST['enabled']) ? (int) $_POST['enabled'] : 0;

        if (!$security || !wp_verify_nonce($security, 'pp-capabilities-profile-features') || !pp_capabilities_feature_enabled('profile-features') || !current_user_can('manage_capabilities_profile_features')) {
            wp_send_json($response);
        }

        if (!$enabled) {
            delete_option('capsman_profile_features_role');
            $response['status']  = 'success';
            $response['message'] = __('Profile features access disabled.', 'capability-manager-enhanced');
            wp_send_json($response);
        }

        if (empty($role) || !get_role($role)) {
            $response['message'] = __('Invalid role provided.', 'capability-manager-enhanced');
            wp_send_json($response);
        }

        $expires = (int) current_time('timestamp') + (5 * MINUTE_IN_SECONDS);
        update_option('capsman_profile_features_role', ['role' => $role, 'expires' => $expires], false);

        $response['status']  = 'success';
        $response['message'] = __('Profile features access enabled for this role.', 'capability-manager-enhanced');

        wp_send_json($response);
    }

    /**
     * Get temporary enabled role for profile features capture
     *
     * @return string Role slug or empty string
     */
    public static function getTemporaryProfileFeaturesRole()
    {
        $setting = get_option('capsman_profile_features_role', []);
        if (!is_array($setting)) {
            return '';
        }

        $role = isset($setting['role']) ? sanitize_key($setting['role']) : '';
        $expires = isset($setting['expires']) ? (int) $setting['expires'] : 0;

        if (empty($role)) {
            return '';
        }

        if ($expires && current_time('timestamp') > $expires) {
            delete_option('capsman_profile_features_role');
            return '';
        }

        return $role;
    }

    /**
     * Check if a specific role is enabled for profile features editing
     *
     * @param string $role Role slug
     * @return bool True if role is enabled
     */
    public static function isRoleEnabledForProfileFeatures($role = '')
    {
        $temporary_role = self::getTemporaryProfileFeaturesRole();

        if (empty($role)) {
            return !empty($temporary_role);
        }

        return !empty($temporary_role) && $role === $temporary_role;
    }

    /**
     * Implement profile features restriction
     *
     * @return void
     */
    public static function applyProfileRestriction() {

        if (!function_exists('get_current_screen') || !pp_capabilities_feature_enabled('profile-features')) {
            return;
        }

        $screen = get_current_screen();

        if (is_object($screen) && isset($screen->base) && in_array($screen->base, ['user-edit', 'profile'])) {
            if (!is_array(get_option("capsman_disabled_profile_features", []))) {
                return;
            }

            $restrict_elements = [];

            // Only restrictions associated with this user's role(s) will be applied
            $role_restrictions = array_intersect_key(
                get_option("capsman_disabled_profile_features", []),
                array_fill_keys(wp_get_current_user()->roles, true)
            );

            foreach ($role_restrictions as $features) {
                if (is_array($features)) {
                    $restrict_elements = array_merge($restrict_elements, $features);
                }
            }

            // apply the stored restrictions by css
            if ($restrict_elements = array_unique($restrict_elements)) {
                $original_restrict_styles =  implode(',', array_map('esc_attr', $restrict_elements)) . ' {display:none !important;}';;
                /**
                 * Headers are showing for secs before been hidden due
                 * to the fact we're just adding class to them.
                 *
                 * So, we should hide them by default and then re update
                 * the inline styles value
                 */
                $restrict_elements[] = '#profile-page form h1, #profile-page form h2, #profile-page form h3, #profile-page form h4, #profile-page form h5, #profile-page form h6, #profile-page form tr';
                $inline_styles = implode(',', array_map('esc_attr', $restrict_elements)) . ' {display:none !important;}';
                //add inline styles
                ppc_add_inline_style($inline_styles, 'ppc-profile-dummy-css-handle');
                //add inline script to update inline css
                $inline_script = "
                jQuery(document).ready( function($) {
                    $('#ppc-profile-dummy-css-handle-inline-css').html('{$original_restrict_styles}');
                });";
                ppc_add_inline_script($inline_script, 'ppc-profile-dummy-css-handle');
            }
        }

    }

}
