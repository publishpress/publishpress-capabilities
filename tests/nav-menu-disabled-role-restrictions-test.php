<?php

$registered_roles = [
    'administrator' => ['name' => 'Administrator'],
    'editor'        => ['name' => 'Editor'],
    'subscriber'    => ['name' => 'Subscriber'],
];

function wp_roles()
{
    global $registered_roles;

    return (object) ['roles' => $registered_roles];
}

function get_editable_roles()
{
    global $registered_roles;

    // Simulate the disabled-role filter omitting an existing registered role.
    return array_intersect_key($registered_roles, array_flip(['administrator', 'editor']));
}

function esc_html__($text)
{
    return $text;
}

function translate_user_role($role_name)
{
    return $role_name;
}

function sanitize_key($key)
{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $key));
}

require_once dirname(__DIR__) . '/includes/admin-load.php';

$reflection = new ReflectionClass('PP_Capabilities_Admin_UI');
$admin_ui = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('getNavMenuRestrictionRoles');
$method->setAccessible(true);
$role_options = $method->invoke($admin_ui);

if (!isset($role_options['subscriber']) || 'Subscriber' !== $role_options['subscriber']) {
    fwrite(STDERR, "Disabled registered roles must remain available to menu restrictions.\n");
    exit(1);
}

echo "Nav menu disabled-role restriction test passed.\n";
