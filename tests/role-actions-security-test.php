<?php

define('PRESSPERMIT_ACTIVE', true);

$allow_role_management = false;
$valid_nonce = true;
$role_mutations = 0;

function current_user_can($capability)
{
    global $allow_role_management;

    return $allow_role_management;
}

function wp_verify_nonce($nonce, $action)
{
    global $valid_nonce;

    return $valid_nonce;
}

function wp_unslash($value)
{
    return $value;
}

function sanitize_key($value)
{
    return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value));
}

function esc_html__($text)
{
    return $text;
}

function wp_die($message = '', $title = '', $args = [])
{
    throw new RuntimeException($message, isset($args['response']) ? (int) $args['response'] : 0);
}

function pp_capabilities_get_permissions_option()
{
    return [];
}

function pp_capabilities_update_permissions_option()
{
    global $role_mutations;
    $role_mutations++;
}

require_once dirname(__DIR__) . '/includes/roles/class/class-pp-roles-actions.php';

$reflection = new ReflectionClass('Pp_Roles_Actions');
$actions = $reflection->newInstanceWithoutConstructor();

$requests = [
    ['method' => 'add_role', 'arguments' => [], 'request' => ['_wpnonce' => 'valid', 'role_name' => 'Dummy']],
    ['method' => 'edit_role', 'arguments' => [], 'request' => ['_wpnonce' => 'valid', 'current_role' => 'dummy', 'role_name' => 'Dummy']],
    ['method' => 'delete_role', 'arguments' => ['dummy'], 'request' => ['_wpnonce' => 'valid']],
    ['method' => 'hide_role', 'arguments' => ['dummy'], 'request' => ['_wpnonce' => 'valid']],
    ['method' => 'unhide_role', 'arguments' => ['dummy'], 'request' => ['_wpnonce' => 'valid']],
];

foreach ($requests as $request) {
    foreach (['capability', 'nonce'] as $failed_guard) {
        $allow_role_management = 'capability' !== $failed_guard;
        $valid_nonce = 'nonce' !== $failed_guard;
        $_REQUEST = $request['request'];

        try {
            call_user_func_array([$actions, $request['method']], $request['arguments']);
            fwrite(STDERR, sprintf("%s did not terminate after a failed %s check.\n", $request['method'], $failed_guard));
            exit(1);
        } catch (RuntimeException $exception) {
            if (403 !== $exception->getCode()) {
                fwrite(STDERR, sprintf("%s did not return HTTP 403 after a failed %s check.\n", $request['method'], $failed_guard));
                exit(1);
            }
        }
    }
}

if (0 !== $role_mutations) {
    fwrite(STDERR, "A failed role-action guard allowed an option mutation.\n");
    exit(1);
}

echo "Role action security test passed.\n";
