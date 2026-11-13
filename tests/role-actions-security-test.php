<?php

define('PRESSPERMIT_ACTIVE', true);

$allow_role_management = false;
$valid_nonce = true;
$role_mutations = 0;
$raw_referer = 'http://example.test/wp-admin/admin.php?page=pp-capabilities-roles&role=dummy';
$die_args = [];

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

function admin_url($path = '')
{
    return 'http://example.test/wp-admin/' . $path;
}

function add_query_arg($args, $url)
{
    return $url . '?' . http_build_query($args);
}

function esc_url_raw($url)
{
    return $url;
}

function esc_url($url)
{
    return $url;
}

function wp_get_raw_referer()
{
    global $raw_referer;

    return $raw_referer;
}

function wp_validate_redirect($location, $fallback_url = '')
{
    return preg_match('#^https?://(?!example\.test/)#', (string) $location) ? $fallback_url : (string) $location;
}

function wp_die($message = '', $title = '', $args = [])
{
    global $die_args;

    $die_args = $args;

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

            if ('nonce' === $failed_guard && (!isset($die_args['link_url']) || $raw_referer !== $die_args['link_url'])) {
                fwrite(STDERR, sprintf("%s did not link back to the referring screen after a failed nonce check.\n", $request['method']));
                exit(1);
            }
        }
    }
}

$allow_role_management = true;
$valid_nonce = false;
$_REQUEST = ['_wpnonce' => 'expired'];

foreach ([false, 'http://elsewhere.test/wp-admin/'] as $raw_referer) {
    $link_url = '';

    try {
        $actions->delete_role('dummy');
    } catch (RuntimeException $exception) {
        $link_url = isset($die_args['link_url']) ? $die_args['link_url'] : '';
    }

    if ('http://example.test/wp-admin/admin.php?page=pp-capabilities-roles' !== $link_url) {
        fwrite(STDERR, "A failed nonce check without a usable referer did not link to the Roles screen.\n");
        exit(1);
    }
}

if (0 !== $role_mutations) {
    fwrite(STDERR, "A failed role-action guard allowed an option mutation.\n");
    exit(1);
}

echo "Role action security test passed.\n";
