<?php

function check_admin_referer($action) {}
function sanitize_key($value) { return strtolower($value); }
function sanitize_text_field($value) { return $value; }
function wp_unslash($value) { return $value; }
function __($message, $domain) { return $message; }
function ak_get_roles() { return ['author']; }
function get_role($slug) { return $GLOBALS['role']; }
function pp_capabilities_is_editable_role($slug) { return $slug === 'author'; }
function current_user_can($capability) { return $capability === 'manage_capabilities' || ($capability === 'administrator' && $GLOBALS['administrator']); }
function ak_admin_error($message) { $GLOBALS['error'] = $message; }
function get_option($key) { throw new RuntimeException('Reached post-grant processing.'); }

require dirname(__DIR__) . '/includes/handler.php';

$role = new class {
    public $name = 'author';
    public $capabilities = [];
    public function add_cap($capability) { $this->capabilities[$capability] = true; }
};
$handler = (new ReflectionClass('CapsmanHandler'))->newInstanceWithoutConstructor();
$handler->cm = (object) ['capabilities' => ['manage_capabilities' => 'Manage Capabilities']];
$administrator = false;
$_POST = ['AddCap' => '1', 'current' => 'author', 'capability-name' => 'manage_capabilities_admin_menus'];
$handler->processAdminGeneral();
if ($role->capabilities || empty($error)) {
    throw new RuntimeException('A delegated capability manager must not grant an unheld capability.');
}

$administrator = true;
$_POST['capability-name'] = 'fixture_custom_capability';
try {
    $handler->processAdminGeneral();
} catch (RuntimeException $exception) {
    if ('Reached post-grant processing.' !== $exception->getMessage()) {
        throw $exception;
    }
}
if (empty($role->capabilities['fixture_custom_capability'])) {
    throw new RuntimeException('Administrators must still be able to add a custom capability.');
}
echo "Add Capability grant checks passed.\n";
