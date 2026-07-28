<?php

$test_options = [
    'default_role'                           => 'subscriber',
    'pp_capabilities_editor_role_option'     => [
        'disable_role'        => 1,
        'disable_code_editor' => 1,
    ],
    'pp_capabilities_subscriber_role_option' => [
        'disable_role' => 1,
    ],
];
$test_users = [
    7 => (object) ['roles' => ['editor']],
];
$pagenow = 'users.php';

function add_filter()
{
}

function add_action()
{
}

function apply_filters($hook, $value)
{
    return $value;
}

function sanitize_key($value)
{
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
}

function get_option($option, $default = false)
{
    global $test_options;

    return array_key_exists($option, $test_options) ? $test_options[$option] : $default;
}

function update_option($option, $value)
{
    global $test_options;

    $test_options[$option] = $value;

    return true;
}

function wp_roles()
{
    return new class {
        public $roles = [
            'administrator' => ['name' => 'Administrator'],
            'editor'        => ['name' => 'Editor'],
            'subscriber'    => ['name' => 'Subscriber'],
        ];

        public function is_role($role)
        {
            return isset($this->roles[$role]);
        }
    };
}

function absint($value)
{
    return abs((int) $value);
}

function get_current_user_id()
{
    return 7;
}

function get_userdata($user_id)
{
    global $test_users;

    return isset($test_users[$user_id]) ? $test_users[$user_id] : false;
}

require_once dirname(__DIR__) . '/includes/roles/disabled-roles.php';

use PublishPress\Capabilities\PP_Capabilities_Disabled_Roles;

$disabled_roles = PP_Capabilities_Disabled_Roles::instance();
$roles = wp_roles()->roles;

$filtered_roles = $disabled_roles->filterEditableRoles($roles);
if (isset($filtered_roles['editor'])) {
    fwrite(STDERR, "Expected a disabled role to be removed from role assignment controls.\n");
    exit(1);
}

if (!isset($filtered_roles['subscriber'])) {
    fwrite(STDERR, "Expected the WordPress default role to remain available.\n");
    exit(1);
}

$_REQUEST['page'] = 'pp-capabilities-roles';
if ($disabled_roles->filterEditableRoles($roles) !== $roles) {
    fwrite(STDERR, "Expected disabled roles to remain manageable on the Roles screen.\n");
    exit(1);
}

unset($_REQUEST['page']);
$_REQUEST['page'] = 'pp-capabilities-backup';
if ($disabled_roles->filterCapabilitiesRoleNames($roles) !== $roles) {
    fwrite(STDERR, "Expected disabled roles to remain available to backup and restore operations.\n");
    exit(1);
}

unset($_REQUEST['page']);
$_REQUEST['user_id'] = 7;
$pagenow = 'user-edit.php';
$filtered_roles = $disabled_roles->filterEditableRoles($roles);

if (!isset($filtered_roles['editor'])) {
    fwrite(STDERR, "Expected an existing user's disabled role to be preserved while editing them.\n");
    exit(1);
}

if (PP_Capabilities_Disabled_Roles::setRoleDisabled('subscriber', true)) {
    fwrite(STDERR, "Expected the WordPress default role to be protected from disabling.\n");
    exit(1);
}

PP_Capabilities_Disabled_Roles::setRoleDisabled('editor', false);
if (PP_Capabilities_Disabled_Roles::isRoleDisabled('editor')) {
    fwrite(STDERR, "Expected the role to be enabled again without changing its definition.\n");
    exit(1);
}

if (empty($test_options['pp_capabilities_editor_role_option']['disable_code_editor'])) {
    fwrite(STDERR, "Expected disabling a role to preserve its other role settings.\n");
    exit(1);
}

echo "Disabled roles test passed.\n";
