<?php

function is_multisite()
{
    return true;
}

function is_super_admin($user_id = false)
{
    return 7 === $user_id;
}

function wp_roles()
{
    return new class {
        public $roles = [
            'administrator' => [
                'name' => 'Administrator',
                'capabilities' => [
                    'read' => true,
                    'edit_posts' => true,
                ],
            ],
        ];

        public function get_names()
        {
            return ['administrator' => 'Administrator'];
        }
    };
}

function sanitize_key($value)
{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}

function sanitize_text_field($value)
{
    return trim((string) $value);
}

function user_can($user, $capability)
{
    return 7 === $user->ID && in_array($capability, ['read', 'edit_posts'], true);
}

function pp_capabilities_is_editable_role()
{
    return false;
}

require_once dirname(__DIR__) . '/includes/admin-load.php';

$user = new class {
    public $ID = 7;
    public $roles = [];
    public $caps = [];
    public $allcaps = [];

    public function get_role_caps()
    {
        $this->allcaps = [];
    }
};

$reflection = new ReflectionClass('PP_Capabilities_Admin_UI');
$admin_ui = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('getUserCapabilitiesPageData');
$method->setAccessible(true);
$page_data = $method->invoke($admin_ui, $user);

if (empty($page_data['is_super_admin'])) {
    fwrite(STDERR, "The capabilities view must identify multisite super admins.\n");
    exit(1);
}

$granted_capabilities = $page_data['effective_granted_caps'];
sort($granted_capabilities);

if (['edit_posts', 'read'] !== $granted_capabilities) {
    fwrite(STDERR, "Super-admin grants must be evaluated from registered capabilities.\n");
    exit(1);
}

echo "User capabilities super-admin test passed.\n";
