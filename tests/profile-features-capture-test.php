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
    function current_user_can($capability, $id = 0) { global $canEdit; return $capability !== 'edit_user' || ($canEdit && $id !== 60); }
    function pp_capabilities_feature_enabled($feature) { global $testingEnabled; return $feature !== 'user-testing' || $testingEnabled; }
    function get_current_user_id() { return 1; }
    function get_current_blog_id() { return 2; }
    function wp_get_current_user() { global $currentRoles; return (object) ['roles' => $currentRoles]; }
    function is_user_member_of_blog($id) { return true; }
    $wpdb = new class {
        public $users = 'wp_users';
        public function get_blog_prefix($id) { return 'wp_' . $id . '_'; }
        public function prepare($sql, $id) { return sprintf($sql, $id); }
    };
    function get_super_admins() { return []; }
    function add_action($hook, $callback) { $GLOBALS['user_query_hook'] = $callback; }
    function remove_action($hook, $callback) { unset($GLOBALS['user_query_hook']); }
    function get_users($args)
    {
        global $users, $query, $user_query_hook, $candidate_queries;
        $query = $args;
        $after_id = 0;
        if (isset($args['ppc_profile_capture_after_id'])) {
            $sql = (object) ['query_vars' => $args, 'query_where' => 'WHERE 1=1'];
            $user_query_hook($sql);
            if (!preg_match('/ AND wp_users\\.ID > (\\d+)$/', $sql->query_where, $matches)) {
                throw new RuntimeException('Expected the user ID cursor in SQL.');
            }
            $after_id = (int) $matches[1];
        }

        $found = [];
        foreach ($users as $user) {
            if ($user->ID <= $after_id || in_array($user->ID, $args['exclude'], true)) {
                continue;
            }
            if (isset($args['capability'])
                && (!($user->storedRead ?? $user->canRead)
                    || array_intersect($user->roles, $args['role__not_in']))) {
                continue;
            }
            $user->allcaps ??= [];
            $found[] = $user;
        }
        $batch = array_slice($found, 0, $args['number']);
        if ($args['fields'] === 'ID') {
            return array_column($batch, 'ID');
        }
        $candidate_queries[] = [$after_id, $args['number'], isset($args['capability'])];
        return $batch;
    }
    function get_option($key, $default = false) { global $options; return $options[$key] ?? $default; }
    function update_option($key, $value, $autoload = null) { global $options; $options[$key] = $value; }
    function delete_option($key) { global $options; unset($options[$key]); }
    function sanitize_key($value) { return $value; }
    function user_can($user, $capability)
    {
        return $capability === 'manage_options' ? !empty($user->canManage) : $user->canRead;
    }
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
    $_REQUEST = ['page' => 'pp-capabilities-profile-features', 'role' => 'subscriber'];
    foreach ([[], [$user]] as $users) {
        foreach ([[], ['cme_profile_features_auto_redirect' => 1, 'capsman_profile_features_updated' => ['subscriber' => 1]]] as $options) {
            $controller->profileFeaturesCaptureRedirect();
            if (!empty($profile_capture_error)) {
                throw new RuntimeException('A visit that does not start capture must not show a warning.');
            }
        }
    }

    $blockedUser = (object) ['ID' => 6, 'roles' => ['contributor'], 'canRead' => true];
    $users = [$blockedUser];
    $testingEnabled = true;
    $canEdit = true;
    $options = ['pp_capabilities_contributor_role_option' => ['block_dashboard_access' => 1]];
    $_REQUEST = ['page' => 'pp-capabilities-profile-features', 'role' => 'contributor', 'role_refresh' => 1];
    $controller->profileFeaturesCaptureRedirect();
    if (empty($profile_capture_error) || isset($options['capsman_profile_features_elements_testing_role'])) {
        throw new RuntimeException('A role that blocks dashboard access must warn without starting capture.');
    }
    $users = [];
    for ($id = 10; $id < 60; $id++) {
        $users[] = (object) [
            'ID' => $id,
            'roles' => ['subscriber', $id % 2 ? 'author' : 'contributor'],
            'canRead' => true,
        ];
    }
    $users[] = (object) ['ID' => 60, 'roles' => ['subscriber'], 'canRead' => true];
    $users[] = (object) ['ID' => 61, 'roles' => ['subscriber'], 'canRead' => true];
    $candidate_queries = [];
    $options = [
        'cme_test_user_excluded_roles' => ['author'],
        'pp_capabilities_contributor_role_option' => ['block_dashboard_access' => 1],
    ];
    $_REQUEST = ['page' => 'pp-capabilities-profile-features', 'role' => 'subscriber', 'role_refresh' => 1];
    $redirect = '';
    try {
        $controller->profileFeaturesCaptureRedirect();
    } catch (RuntimeException $error) {
        $redirect = $error->getMessage();
    }
    parse_str((string) parse_url($redirect, PHP_URL_QUERY), $redirect_args);
    if (($redirect_args['ppc_test_user'] ?? '') !== base64_encode('61')
        || $candidate_queries !== [[0, 1, true], [10, 50, true]]
        || $profile_capture_error !== ''
        || isset($user_query_hook)) {
        throw new RuntimeException('Capture must skip ineligible users and continue with an ID cursor on the filtered query.');
    }

    $users = [(object) ['ID' => 70, 'roles' => ['subscriber'], 'canRead' => true, 'storedRead' => false]];
    $candidate_queries = [];
    $redirect = '';
    try {
        $controller->profileFeaturesCaptureRedirect();
    } catch (RuntimeException $error) {
        $redirect = $error->getMessage();
    }
    parse_str((string) parse_url($redirect, PHP_URL_QUERY), $redirect_args);
    if (($redirect_args['ppc_test_user'] ?? '') !== base64_encode('70')
        || $candidate_queries !== [[0, 1, true], [0, 1, false]]) {
        throw new RuntimeException('Capture must also find a user whose read permission is granted at runtime.');
    }
    echo "Profile Features capture regression checks passed.\n";
}
