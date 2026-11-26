<?php
/**
 * Group-assigned permissions are displayed separately from direct assignments.
 * @since 2.53.0
 */
function absint($value) { return abs((int) $value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function sanitize_text_field($value) { return trim((string) $value); }
function __($value, $domain = '') { return $value; }
function wp_strip_all_tags($value) { return strip_tags($value); }
function wp_specialchars_decode($value, $flags) { return html_entity_decode($value, $flags); }
function current_user_can($cap, $id = 0) { return false; }
function get_userdata($id) { return (object) ['ID' => $id, 'roles' => ['author']]; }
function translate_user_role($name) { return $name; }
function wp_roles() { return new class { public function get_names() { return ['author' => 'Author']; } }; }
function presspermit() { return $GLOBALS['pp']; }
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
require dirname(__DIR__) . '/includes/admin-load.php';
$reflection = new ReflectionClass('PP_Capabilities_Admin_UI');
$ui = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('getPublishPressPermissionsUserData');
$method->setAccessible(true);
check($method->invoke($ui, 7)['groups'] === [], 'Inactive Permissions must have no groups');
define('PRESSPERMIT_ACTIVE', true);
$group_api = new class {
    public $empty = false;
    public function getGroupsForUser($user, $type, $args) {
        check($user === 7 && $type === 'pp_group' && $args['status'] === 'active', 'Read only active groups for the selected user');
        if ($this->empty) { return []; }
        return [
            10 => (object) ['group_name' => 'Editorial'],
            11 => (object) ['group_name' => 'Stale Editor', 'metagroup_type' => 'wp_role', 'metagroup_id' => 'editor'],
            12 => (object) ['group_name' => 'Logged In', 'metagroup_type' => 'wp_role', 'metagroup_id' => 'wp_auth'],
        ];
    }
    public function getMetagroup($type, $slug) {
        check($type === 'wp_role' && $slug === 'author', 'Resolve current role group');
        return $this->empty ? null : (object) ['ID' => 20, 'metagroup_type' => 'wp_role', 'metagroup_id' => 'author'];
    }
};
$pp = new class($group_api) {
    private $api;
    public function __construct($api) { $this->api = $api; }
    public function groups() { return $this->api; }
    public function getRoles($id, $type) {
        if ($type === 'user') { return ['direct_role' => 1]; }
        check($type === 'pp_group' && $id !== 11, 'Stale groups must not be queried');
        return $id === 20 ? ['page_subscriber:private' => 2] : [];
    }
    public function getExceptions($args) {
        if ($args['agent_type'] === 'user') { return []; }
        check($args['agent_id'] !== 11, 'Stale group permissions excluded');
        if ($args['agent_id'] === 10) {
            return array_fill(0, 55, (object) ['operation' => 'edit', 'mod_type' => 'additional']);
        }
        return $args['agent_id'] === 20 ? [(object) ['operation' => 'read', 'mod_type' => 'exclude']] : [];
    }
};
$data = $method->invoke($ui, 7);
check($data['roles_total'] === 1 && $data['roles'][0]['slug'] === 'direct_role', 'Direct roles remain distinct');
check($data['exceptions_total'] === 0, 'Group exceptions must not inflate direct count');
check(count($data['groups']) === 2 && $data['groups'][0]['name'] === 'Author', 'Current role and custom group appear, sorted by name');
check($data['groups'][0]['roles'][0]['slug'] === 'page_subscriber:private', 'Inherited extra role shown');
check($data['groups'][0]['exceptions'][0]['modification'] === 'Blocked', 'Inherited denial is labelled correctly');
check(count($data['groups'][1]['exceptions']) === 50 && $data['groups'][1]['exceptions_total'] === 55, 'Limit with accurate total');
$group_api->empty = true;
check($method->invoke($ui, 7)['groups'] === [], 'No active assignments returns empty groups');
echo "User capabilities group-permissions checks passed.\n";
