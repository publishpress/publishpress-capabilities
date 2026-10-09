<?php
/**
 * Regression checks for Profile Features capture eligibility on multisite.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.53.0
 */

namespace PublishPress\Capabilities {
    class PP_Capabilities_Profile_Features
    {
        public static function isRoleEnabledForProfileFeatures($role) { return true; }
    }
}

namespace {
    define('DAY_IN_SECONDS', 86400);
    define('HOUR_IN_SECONDS', 3600);
    $users = [];
    $currentRoles = ['administrator'];
    $canEdit = true;
    $testingEnabled = true;
    $options = [];
    $query = [];
    function is_multisite() { return true; }
    function is_super_admin() { return false; }
    function is_admin() { return true; }
    function current_user_can($capability, $id = 0) { global $canEdit; return $capability !== 'edit_user' || $canEdit; }
    function pp_capabilities_feature_enabled($feature) { global $testingEnabled; return $feature !== 'user-testing' || $testingEnabled; }
    function get_current_user_id() { return 1; }
    function get_current_blog_id() { return 2; }
    function wp_get_current_user() { global $currentRoles; return (object) ['roles' => $currentRoles]; }
    function get_users($args) { global $users, $query; $query = $args; return $users; }
    function get_option($key, $default = false) { global $options; return $options[$key] ?? $default; }
    function update_option($key, $value, $autoload = null) { global $options; $options[$key] = $value; }
    function delete_option($key) { global $options; unset($options[$key]); }
    function sanitize_key($value) { return $value; }
    function user_can($id, $capability) { global $users; return $users[0]->canRead; }
    function __($text, $domain = '') { return $text; }
    function admin_url($path) { return $path; }
    function wp_create_nonce($action) { return 'nonce'; }
    function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
    function wp_safe_redirect($url) { throw new RuntimeException($url); }

    require_once dirname(__DIR__) . '/includes/test-user.php';
    // Load the actual controller method without bootstrapping the full plugin.
    $source = file_get_contents(dirname(__DIR__) . '/includes/manager.php');
    $start = strpos($source, '    function profileFeaturesCaptureRedirect()');
    $end = strpos($source, "\n}\n", $start);
    eval('class CaptureController { public function set_current_role($role) {} '
        . substr($source, $start, $end - $start) . '}');
    $controller = new CaptureController();
    $capsman = new class {
        public function get_last_role() { return 'subscriber'; }
    };
    $user = (object) ['ID' => 2, 'roles' => ['subscriber'], 'canRead' => true];
    $cases = [
        ['subscriber', [], ['administrator'], true, [], false],
        ['administrator', [], [], true, [], false],
        ['subscriber', [$user], ['administrator'], false, [], false],
        ['subscriber', [$user], ['administrator'], true, ['subscriber'], false],
        ['subscriber', [(object) ['ID' => 2, 'roles' => ['subscriber'], 'canRead' => false]], ['administrator'], true, [], false],
        ['subscriber', [$user], ['administrator'], true, [], true],
        ['administrator', [], ['administrator'], true, [], true],
    ];
    foreach ($cases as [$role, $users, $currentRoles, $canEdit, $excluded, $shouldRedirect]) {
        $_REQUEST = ['page' => 'pp-capabilities-profile-features', 'role' => $role, 'role_refresh' => 1];
        $options = ['cme_test_user_excluded_roles' => $excluded, 'capsman_profile_features_updated' => ['subscriber' => 1]];
        $redirect = '';
        try {
            $controller->profileFeaturesCaptureRedirect();
        } catch (RuntimeException $error) {
            $redirect = $error->getMessage();
        }
        if ($shouldRedirect !== ($redirect !== '')) {
            throw new RuntimeException('Capture redirected with incorrect eligibility.');
        }
        if ($query['blog_id'] !== 2) {
            throw new RuntimeException('Capture must query the current sub-site.');
        }
        if (!$shouldRedirect && (empty($profile_capture_error)
            || isset($options['capsman_profile_features_elements_testing_role']))) {
            throw new RuntimeException('Failed capture must show a message without setting capture state.');
        }
        if ($shouldRedirect && $options['capsman_profile_features_elements_testing_role'] !== $role) {
            throw new RuntimeException('Successful capture must retain the selected role.');
        }
        if (!$shouldRedirect) {
            $_REQUEST = ['page' => 'pp-capabilities-profile-features', 'role' => $role];
            $options['cme_profile_features_auto_redirect'] = 1;
            $controller->profileFeaturesCaptureRedirect();
        }
    }
    $users = [$user];
    $testingEnabled = false;
    $canEdit = true;
    $options = [];
    $_REQUEST = ['page' => 'pp-capabilities-profile-features', 'role' => 'subscriber', 'role_refresh' => 1];
    $controller->profileFeaturesCaptureRedirect();
    if (empty($profile_capture_error) || isset($options['capsman_profile_features_elements_testing_role'])) {
        throw new RuntimeException('Disabled User Testing must show a warning without starting capture.');
    }
    echo "Profile Features capture regression checks passed (7 eligibility scenarios and repeat visits).\n";
}
