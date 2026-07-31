<?php

$test_actions = [];
$test_current_site_id = 1;
$test_site_stack = [];
$test_requested_network_id = 0;
$test_network_options = [
    1 => [
        'cme_autocreate_roles' => ['project_manager'],
    ],
];

class TestNetworkRole
{
    public $capabilities;

    public function __construct($capabilities)
    {
        $this->capabilities = $capabilities;
    }

    public function add_cap($capability, $grant = true)
    {
        $this->capabilities[$capability] = $grant;
    }

    public function remove_cap($capability)
    {
        unset($this->capabilities[$capability]);
    }
}

class TestNetworkRoles
{
    public $role_names = [];
    public $sites;
    private $site_id = 1;

    public function __construct()
    {
        $this->sites = [
            1 => [
                'administrator'   => [
                    'name' => 'Administrator',
                    'role' => new TestNetworkRole(['manage_options' => true]),
                ],
                'project_manager' => [
                    'name' => 'Project Manager',
                    'role' => new TestNetworkRole([
                        'read'          => true,
                        'edit_projects' => true,
                    ]),
                ],
            ],
            2 => [
                'administrator' => [
                    'name' => 'Administrator',
                    'role' => new TestNetworkRole(['manage_options' => true]),
                ],
            ],
        ];

        $this->for_site(1);
    }

    public function for_site($site_id = null)
    {
        $this->site_id = (int) $site_id;
        $this->role_names = [];

        foreach ($this->sites[$this->site_id] as $role_name => $role_data) {
            $this->role_names[$role_name] = $role_data['name'];
        }
    }

    public function get_role($role_name)
    {
        return isset($this->sites[$this->site_id][$role_name])
            ? $this->sites[$this->site_id][$role_name]['role']
            : null;
    }

    public function add_role($role_name, $display_name, $capabilities)
    {
        $role = new TestNetworkRole($capabilities);
        $this->sites[$this->site_id][$role_name] = [
            'name' => $display_name,
            'role' => $role,
        ];
        $this->role_names[$role_name] = $display_name;

        return $role;
    }
}

$wp_roles = new TestNetworkRoles();

function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
{
    global $test_actions;

    $test_actions[$hook] = compact('callback', 'priority', 'accepted_args');
}

function get_current_network_id()
{
    return 1;
}

function get_network_option($network_id, $option, $default = false)
{
    global $test_network_options, $test_requested_network_id;

    $test_requested_network_id = (int) $network_id;

    return isset($test_network_options[$network_id][$option])
        ? $test_network_options[$network_id][$option]
        : $default;
}

function get_main_site_id($network_id = null)
{
    return 1;
}

function get_current_blog_id()
{
    global $test_current_site_id;

    return $test_current_site_id;
}

function switch_to_blog($site_id)
{
    global $test_current_site_id, $test_site_stack;

    $test_site_stack[] = $test_current_site_id;
    $test_current_site_id = (int) $site_id;

    return true;
}

function restore_current_blog()
{
    global $test_current_site_id, $test_site_stack;

    if (empty($test_site_stack)) {
        return false;
    }

    $test_current_site_id = array_pop($test_site_stack);

    return true;
}

function wp_roles()
{
    global $wp_roles;

    return $wp_roles;
}

function get_role($role_name)
{
    return wp_roles()->get_role($role_name);
}

function sanitize_key($value)
{
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
}

require_once dirname(__DIR__) . '/includes/network.php';

if (empty($test_actions['wp_initialize_site'])) {
    fwrite(STDERR, "Expected the supported wp_initialize_site hook to be registered.\n");
    exit(1);
}

if (200 !== $test_actions['wp_initialize_site']['priority']
    || 2 !== $test_actions['wp_initialize_site']['accepted_args']
) {
    fwrite(STDERR, "Expected role copying to run after WordPress initializes the new site.\n");
    exit(1);
}

_cme_initialize_site_roles(
    (object) [
        'blog_id'    => 2,
        'network_id' => 1,
    ]
);

$copied_role = $wp_roles->sites[2]['project_manager']['role'];

if (!$copied_role instanceof TestNetworkRole
    || empty($copied_role->capabilities['edit_projects'])
) {
    fwrite(STDERR, "Expected an included role to be copied to a CLI-created site.\n");
    exit(1);
}

if (1 !== $test_requested_network_id) {
    fwrite(STDERR, "Expected role settings to be read from the new site's network.\n");
    exit(1);
}

if (1 !== $test_current_site_id || !empty($test_site_stack)) {
    fwrite(STDERR, "Expected the original site context to be fully restored.\n");
    exit(1);
}

echo "Network new-site test passed.\n";
