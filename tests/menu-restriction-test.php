<?php

if (!defined('CME_FILE')) {
    define('CME_FILE', dirname(__DIR__) . '/capsman-enhanced.php');
}

if (!defined('PP_CAPABILITIES_HIDE_MENU_ON_SUBSITES')) {
    define('PP_CAPABILITIES_HIDE_MENU_ON_SUBSITES', true);
}

function is_multisite()
{
    return true;
}

function is_super_admin()
{
    return false;
}

function apply_filters($hook, $value, $user = null)
{
    if ($hook === 'pp_capabilities_display_admin_menu_on_subsite') {
        return false;
    }

    return $value;
}

function current_user_can($capability)
{
    return false;
}

function wp_get_current_user()
{
    return null;
}

require_once dirname(__DIR__) . '/includes/functions-admin.php';

$hidden = pp_capabilities_should_display_admin_menu();
if ($hidden !== false) {
    fwrite(STDERR, "Expected multisite subsite menus to be hidden when the constant is enabled.\n");
    exit(1);
}

echo "Menu restriction test passed.\n";
