<?php

function add_filter()
{
}

function add_action()
{
}

function is_admin()
{
    return false;
}

function plugin_basename($slug)
{
    return $slug;
}

function sanitize_title($title)
{
    return $title;
}

function get_plugin_page_hookname($slug)
{
    return $slug;
}

function current_user_can()
{
    return true;
}

function set_url_scheme($url)
{
    return $url;
}

function absint($value)
{
    return abs((int) $value);
}

require_once dirname(__DIR__) . '/includes/features/admin-menus/admin-menus-functions.php';

$menu = [
    10 => ['Existing menu', 'read', 'existing-menu', '', 'menu-top'],
];
$admin_page_hooks = [];
$_registered_pages = [];
$_parent_pages = [];

foreach (['separator-first', 'separator-second'] as $slug) {
    ppc_add_menu_page('', '', 'read', $slug, '', '', 10, [
        'form_menu_type' => 'menu-separator',
        'menu_data' => ['', 'read', $slug, '', 'wp-menu-separator ppc-admin-menu-separator line-separator'],
    ]);
}

foreach ($menu as $menu_item) {
    if (strpos($menu_item[2], 'separator-') !== 0) {
        continue;
    }

    if (strpos($menu_item[4], 'wp-menu-separator') !== false) {
        throw new RuntimeException('Custom separator still triggers WordPress adjacent-separator removal.');
    }
}

$separator_slugs = array_values(array_filter(array_column($menu, 2), static function ($slug) {
    return strpos($slug, 'separator-') === 0;
}));

if ($separator_slugs !== ['separator-first', 'separator-second']) {
    throw new RuntimeException('Multiple top-level separators were not retained.');
}

require_once dirname(__DIR__) . '/includes/features/admin-menus/admin-menus-filters.php';

$restore_method = new ReflectionMethod(
    \PublishPress\Capabilities\Admin_Menus_Filters::class,
    'restoreCustomMenuSeparatorClasses'
);
$restore_method->setAccessible(true);
$menu = $restore_method->invoke(null, $menu);

foreach ($menu as $menu_item) {
    if (strpos($menu_item[2], 'separator-') === 0
        && strpos($menu_item[4], 'wp-menu-separator') === false
    ) {
        throw new RuntimeException('WordPress separator rendering class was not restored after cleanup.');
    }
}

$submenu = [
    'existing-menu' => [
        ['Existing submenu', 'read', 'existing-submenu'],
    ],
];
$_wp_real_parent_file = [];
$_wp_submenu_nopriv = [];

foreach (['submenu-separator-first', 'submenu-separator-second'] as $slug) {
    ppc_add_submenu_page('existing-menu', '', '', 'read', $slug, '', 1, [
        'form_menu_type' => 'submenu-separator',
        'menu_data' => ['', 'read', $slug, '', 'ppc-admin-menu-separator line-separator'],
    ]);
}

$submenu_separator_slugs = array_values(array_filter(array_column($submenu['existing-menu'], 2), static function ($slug) {
    return strpos($slug, 'submenu-separator-') === 0;
}));

sort($submenu_separator_slugs);
if ($submenu_separator_slugs !== ['submenu-separator-first', 'submenu-separator-second']) {
    throw new RuntimeException('Multiple submenu separators were not retained.');
}

echo "Admin menu separator rendering test passed.\n";
