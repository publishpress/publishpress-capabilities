<?php
/** Regression checks for submenu discovery and plugin lifecycle invalidation. */
require __DIR__ . "/admin-menus-free-test.php";
$_GET = ['page' => 'pp-capabilities-admin-menus'];
$menu = [['Hub', 'read', 'publishpress-hub', '', '', '', '']];
$admin_global_menu = array_merge($menu, [['Restricted parent', 'read', 'restricted-parent', '', '', '', '']]);
$admin_global_submenu = [
    'publishpress-hub' => [
        ['Plugins', 'read', 'hub-plugins'],
        ['Settings', 'read', 'hub-settings'],
    ],
    'restricted-parent' => [['Restricted', 'read', 'restricted-item']],
];
$submenu = [
    'publishpress-hub' => [
        ['Welcome', 'read', 'hub-welcome'],
        ['Plugins', 'read', 'hub-plugins'],
        ['AI Connections', 'manage_options', 'hub-mcp'],
        ['Settings', 'read', 'hub-settings'],
    ],
];
$shared->setCapabilitiesAdminMenu();
check(in_array('hub-mcp', array_column($options['ppc_admin_menus_submenu']['publishpress-hub'], 2), true),
    'Cache refresh must retain new submenu entries under an existing parent');
check(count($options['ppc_admin_menus_submenu']['publishpress-hub']) === 4,
    'Snapshot merge must not duplicate known submenu entries');
check(array_column($options['ppc_admin_menus_submenu']['publishpress-hub'], 2) === ['hub-welcome', 'hub-plugins', 'hub-mcp', 'hub-settings'],
    'Cached editor discovery must retain current registered child order');
$merged = \PublishPress\Capabilities\Admin_Menus_Filters::mergeSubmenuSnapshots(
    ['parent' => [5 => ['Old name', 'read', 'existing'], 10 => ['Restricted', 'read', 'restricted']]],
    ['parent' => [5 => ['New name', 'manage_options', 'existing'], 10 => ['New item', 'read', 'new-item']]]
);
check($merged['parent'][5][0] === 'New name' && $merged['parent'][5][1] === 'manage_options',
    'Current titles and capabilities must replace cached metadata');
check(array_column($merged['parent'], 2) === ['existing', 'new-item', 'restricted'],
    'Numeric position collisions must not overwrite restricted or new items');
$options['ppc_admin_menus_order'] = ['publishpress-hub' => ['hub-settings', 'hub-plugins']];
$ordered = \PublishPress\Capabilities\Admin_Menus_Filters::getRoleAdminMenu($menu, $submenu, ['administrator']);
check(array_column($ordered['submenu']['publishpress-hub'], 2) === ['hub-welcome', 'hub-plugins', 'hub-mcp', 'hub-settings'],
    'A parent with new submenu entries must follow current registration order');
$options['capsman_admin_child_menus'] = ['administrator' => ['publishpress-hub|restricted']];
$preferences = array_intersect_key($options, array_flip(['ppc_admin_menus_order', 'ppc_admin_menus_names', 'capsman_admin_child_menus']));
foreach (['activated_plugin', 'deactivate_plugin'] as $hook) {
    check(!empty($hooks[$hook]), 'Plugin lifecycle invalidation must be registered');
    $options['ppc_admin_menus_menu'] = $menu;
    $options['ppc_admin_menus_submenu'] = $submenu;
    foreach ($hooks[$hook] as $callback) { $callback('example/example.php'); }
    check(!isset($options['ppc_admin_menus_menu'], $options['ppc_admin_menus_submenu']),
        'Plugin lifecycle changes must clear discovery snapshots');
}
$options['ppc_admin_menus_submenu'] = $submenu;
$shared->clearStoredDataAfterPluginUpgrade(new stdClass(), ['type' => 'theme', 'action' => 'update']);
check(isset($options['ppc_admin_menus_submenu']), 'Theme updates must not invalidate plugin menu discovery');
$shared->clearStoredDataAfterPluginUpgrade(new stdClass(), ['type' => 'plugin', 'action' => 'update']);
check(!isset($options['ppc_admin_menus_submenu']), 'Plugin updates must invalidate discovery');
check(array_intersect_key($options, $preferences) === $preferences, 'Cache invalidation must preserve order, names and restrictions');
echo "Admin menu cache and sidebar regression checks passed.\n";

$fresh = \PublishPress\Capabilities\Admin_Menus_Filters::mergeSubmenuSnapshots(
    (array) false,
    ['invalid' => false, 'empty' => [], 'valid' => [false, ['Broken'], ['Settings', 'read', 'settings']]]
);
check(!isset($fresh[0], $fresh['invalid']), 'False cached options must not become submenu groups');
$grouped = array_map(function ($children) { return array_reduce($children, function ($carry, $item) { $carry[] = $item[2]; return $carry; }, []); }, $fresh);
check($grouped['valid'] === ['settings'] && $grouped['empty'] === [], 'Fresh-site submenu grouping must accept only valid arrays');
echo "Fresh-site false-cache regression checks passed.\n";

$options['ppc_admin_menus_order'] = [
    'publishpress-hub' => ['hub-settings', 'hub-mcp', 'hub-plugins', 'hub-welcome'],
    'other' => ['other-second', 'other-first'],
];
$test_menu = array_merge($menu, [['Other', 'read', 'other', '', '', '', '']]);
$test_submenu = $submenu + ['other' => [['First', 'read', 'other-first'], ['Second', 'read', 'other-second']]];
$ordered = \PublishPress\Capabilities\Admin_Menus_Filters::getRoleAdminMenu($test_menu, $test_submenu, ['administrator']);
check(array_column($ordered['submenu']['publishpress-hub'], 2) === ['hub-settings', 'hub-mcp', 'hub-plugins', 'hub-welcome'],
    'Custom order remains authoritative when there are no new children');
$options['ppc_admin_menus_order']['publishpress-hub'] = ['hub-settings', 'hub-plugins', 'hub-welcome'];
$ordered = \PublishPress\Capabilities\Admin_Menus_Filters::getRoleAdminMenu($test_menu, $test_submenu, ['administrator']);
check(array_column($ordered['submenu']['publishpress-hub'], 2) === ['hub-welcome', 'hub-plugins', 'hub-mcp', 'hub-settings'],
    'New child invalidates only its parent submenu ordering');
check(array_column($ordered['submenu']['other'], 2) === ['other-second', 'other-first'],
    'Unrelated parent custom order is preserved');
echo "Per-parent submenu order checks passed.\n";
