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

function is_admin()
{
    return false;
}

function __($text)
{
    return $text;
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

$submenus = pp_capabilities_sub_menu_lists(true);
if (
    !isset($submenus['admin-columns'])
    || 'pp-capabilities-admin-columns' !== $submenus['admin-columns']['page']
    || 'manage_capabilities_admin_columns' !== $submenus['admin-columns']['capabilities']
) {
    fwrite(STDERR, "Expected Admin Columns to be registered with the Capabilities menus.\n");
    exit(1);
}

$plugin_capabilities = cme_publishpress_capabilities_capabilities([]);
if (!in_array('manage_capabilities_admin_columns', $plugin_capabilities, true)) {
    fwrite(STDERR, "Expected the Admin Columns management capability to be registered.\n");
    exit(1);
}

$hidden = pp_capabilities_should_display_admin_menu();
if ($hidden !== false) {
    fwrite(STDERR, "Expected multisite subsite menus to be hidden when the constant is enabled.\n");
    exit(1);
}

echo "Menu restriction test passed.\n";
