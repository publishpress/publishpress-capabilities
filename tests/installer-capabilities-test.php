<?php

$test_plugin_capabilities = [
    'manage_capabilities_dashboard',
    'manage_capabilities_roles',
    'manage_capabilities',
    'manage_capabilities_editor_features',
    'manage_capabilities_admin_features',
    'manage_capabilities_admin_styles',
    'manage_capabilities_admin_notices',
    'manage_capabilities_admin_menus',
    'manage_capabilities_frontend_features',
    'manage_capabilities_profile_features',
    'manage_capabilities_redirects',
    'manage_capabilities_nav_menus',
    'manage_capabilities_user_testing',
    'manage_capabilities_backup',
    'manage_capabilities_settings',
];
$test_options = [];

class TestInstallerRole
{
    public $capabilities;

    public function __construct($capabilities = [])
    {
        $this->capabilities = $capabilities;
    }

    public function has_cap($capability)
    {
        return !empty($this->capabilities[$capability]);
    }

    public function add_cap($capability)
    {
        $this->capabilities[$capability] = true;
    }

    public function remove_cap($capability)
    {
        unset($this->capabilities[$capability]);
    }
}

class TestInstallerRoles
{
    public $roles = [];
    public $role_objects = [];

    public function __construct($roles)
    {
        foreach ($roles as $role_name => $capabilities) {
            $this->roles[$role_name] = ['capabilities' => $capabilities];
            $this->role_objects[$role_name] = new TestInstallerRole($capabilities);
        }
    }
}

$test_roles = new TestInstallerRoles([
    'administrator' => ['read' => true],
    'editor'        => ['read' => true],
]);

function wp_roles()
{
    global $test_roles;

    return $test_roles;
}

function get_role($role_name)
{
    global $test_roles;

    return isset($test_roles->role_objects[$role_name])
        ? $test_roles->role_objects[$role_name]
        : null;
}

function apply_filters($hook, $value)
{
    global $test_plugin_capabilities;

    if ('cme_publishpress_capabilities_capabilities' === $hook) {
        return $test_plugin_capabilities;
    }

    return $value;
}

function do_action()
{
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

require_once dirname(__DIR__) . '/classes/pp-capabilities-installer.php';

use PublishPress\Capabilities\Classes\PP_Capabilities_Installer;

PP_Capabilities_Installer::runInstallTasks('2.50.1');

foreach ($test_plugin_capabilities as $capability) {
    if (!get_role('administrator')->has_cap($capability)) {
        throw new RuntimeException("Expected fresh installations to grant {$capability} to Administrators.");
    }

    if (get_role('editor')->has_cap($capability)) {
        throw new RuntimeException("Fresh installations must not grant {$capability} to Editors.");
    }
}

$test_roles = new TestInstallerRoles([
    'administrator' => ['read' => true],
    'editor'        => ['read' => true],
]);

PP_Capabilities_Installer::runUpgradeTasks('2.7.0');

foreach ([
    'manage_capabilities_frontend_features',
    'manage_capabilities_redirects',
    'manage_capabilities_admin_styles',
] as $capability) {
    if (!get_role('administrator')->has_cap($capability)) {
        throw new RuntimeException("Expected historical upgrades to grant {$capability} to Administrators.");
    }

    if (get_role('editor')->has_cap($capability)) {
        throw new RuntimeException("Historical upgrades must not grant {$capability} to Editors.");
    }
}

$test_roles = new TestInstallerRoles([
    'administrator'      => ['read' => true],
    'capability_manager' => ['read' => true, 'manage_capabilities' => true],
]);

PP_Capabilities_Installer::runInstallTasks('2.50.1');

foreach ($test_plugin_capabilities as $capability) {
    if (!get_role('capability_manager')->has_cap($capability)) {
        throw new RuntimeException("Expected explicitly delegated roles to receive {$capability}.");
    }
}

$test_roles = new TestInstallerRoles([
    'administrator' => array_fill_keys($test_plugin_capabilities, true),
    'editor'        => array_fill_keys($test_plugin_capabilities, true),
]);

PP_Capabilities_Installer::runUpgradeTasks('2.50.0');

foreach ($test_plugin_capabilities as $capability) {
    if (get_role('editor')->has_cap($capability)) {
        throw new RuntimeException("Expected upgrades to revoke legacy Editor capability {$capability}.");
    }
}

echo "Installer capabilities test passed.\n";
