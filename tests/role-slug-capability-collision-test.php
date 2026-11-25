<?php

define('ABSPATH', dirname(__DIR__) . '/');
function wp_roles() {
    return (object) ['roles' => ['author' => ['capabilities' => ['fixture_edit_gallery' => true]]]];
}
function ak_get_roles() { return array_keys(wp_roles()->roles); }
function get_role($slug) { return wp_roles()->roles[$slug] ?? null; }

require dirname(__DIR__) . '/includes/handler.php';

$handler = (new ReflectionClass('CapsmanHandler'))->newInstanceWithoutConstructor();
$handler->cm = (object) ['capabilities' => []];
foreach (['Manage Capabilities Admin Menus', 'Fixture Edit Gallery', 'AUTHOR'] as $name) {
    if ('role_exists' !== ($handler->createNewRoleName($name)['error'] ?? '')) {
        throw new RuntimeException('Reserved role name was accepted: ' . $name);
    }
}
if ('ordinary_fixture_role' !== $handler->createNewRoleName('Ordinary Fixture Role')['name']) {
    throw new RuntimeException('Ordinary role names must still be accepted.');
}
echo "Native role slug collision checks passed.\n";
