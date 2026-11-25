<?php
/** Behavior checks for shared Admin Menus and the creation boundary. @since 2.53.0 */
$hooks = [];
$options = [];
$can_manage = true;
function is_admin() { return true; }
function pp_capabilities_feature_enabled($feature) { return true; }
function add_action($name, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$name][] = $callback; }
function add_filter($name, $callback, $priority = 10, $args = 1) { add_action($name, $callback, $priority, $args); }
function apply_filters($name, $value) {
    foreach ($GLOBALS['hooks'][$name] ?? [] as $callback) { $value = $callback($value); }
    return $value;
}
function get_option($name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
function update_option($name, $value, $autoload = null) { $GLOBALS['options'][$name] = $value; }
function delete_option($name) { unset($GLOBALS['options'][$name]); }
function __($text, $domain = '') { return $text; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function sanitize_text_field($value) { return strip_tags($value); }
function wp_unslash($value) { return $value; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid' && in_array($action, ['ppc-admin-menu-nonce', 'pp-capabilities-admin-menus'], true); }
function esc_html__($text, $domain) { return $text; }
function wp_die($message, $title, $args) { throw new RuntimeException($message, $args['response']); }
function current_user_can($cap) { return $cap === 'manage_capabilities_admin_menus' && $GLOBALS['can_manage']; }
function is_multisite() { return false; }
function _x($text, $context) { return $text; }
function wp_get_current_user() { return (object) ['roles' => ['delegate'], 'allcaps' => ['level_5' => true]]; }
function get_role($slug) {
    $levels = ['administrator' => 10, 'editor' => 7, 'delegate' => 5, 'author' => 2, 'subscriber' => 0];
    return isset($levels[$slug]) ? (object) ['capabilities' => ['level_' . $levels[$slug] => true]] : null;
}
function wp_roles() {
    return new class {
        public function get_names() {
            return ['administrator' => 'Administrator', 'editor' => 'Editor', 'delegate' => 'Delegate', 'author' => 'Author', 'subscriber' => 'Subscriber'];
        }
    };
}
function pp_capabilities_is_editable_role($slug) { return $slug !== 'subscriber'; }
function wp_send_json($response) { throw new RuntimeException(json_encode($response)); }
function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
require dirname(__DIR__) . '/includes/features/admin-menus/admin-menus-functions.php';
require dirname(__DIR__) . '/includes/features/admin-menus/admin-menus-filters.php';
require dirname(__DIR__) . '/includes/features/admin-menus/admin-menus-controller.php';
$shared = \PublishPress\Capabilities\Admin_Menus_Filters::instance();
check(empty($hooks['wp_ajax_ppc_add_new_admin_menu']), 'Free must not register creation');
foreach (['ppc_update_admin_menu_order_by_ajax', 'ppc_update_admin_menu_title', 'ppc_delete_admin_menu', 'ppc_reset_admin_menu', 'ppc_update_admin_menu_settings'] as $action) {
    check(!empty($hooks['wp_ajax_' . $action]), 'Shared operation unavailable: ' . $action);
}
check(!apply_filters('pp_capabilities_admin_menus_new_menu_enabled', false), 'Free form must be a promo');
$options = [
    'capsman_admin_menus' => ['author' => ['tools.php']],
    'capsman_admin_child_menus' => ['author' => ['edit.php|edit-tags.php']],
    'ppc_admin_menus_names' => ['author' => ['menu' => ['edit.php' => ['title' => 'Content', 'icon' => 'dashicons-book']]]],
];
pp_capabilities_admin_menus_after_role_copied('writer', 'author');
foreach (['capsman_admin_menus', 'capsman_admin_child_menus', 'ppc_admin_menus_names'] as $key) {
    check($options[$key]['writer'] === $options[$key]['author'], 'Role copying must retain menu settings');
}
$menu = [['Posts', 'read', 'edit.php', '', 'menu-top', '', 'dashicons-admin-post']];
$result = \PublishPress\Capabilities\Admin_Menus_Filters::getRoleAdminMenu($menu, [], ['author']);
check($result['menu'][0][0] === 'Content' && $result['menu'][0][6] === 'dashicons-book', 'Free must retain renaming and icons');
$controller = (new ReflectionClass(\PublishPress\Capabilities\AdminMenusController::class))->newInstanceWithoutConstructor();
$normalize = new ReflectionMethod($controller, 'normalizeOwnRoleAdminMenuRestrictions');
$normalize->setAccessible(true);
$result = $normalize->invoke($controller, ['pp-capabilities-roles', 'tools.php'], ['admin.php|pp-capabilities-admin-menus', 'edit.php|edit-tags.php'], 'author', ['author']);
check($result === [['tools.php'], ['edit.php|edit-tags.php']], 'Own-role lockout safeguard');
$_POST = ['nonce' => 'invalid'];
try { $shared->ajaxUpdateAdminMenuSettings(); } catch (RuntimeException $error) { $response = json_decode($error->getMessage(), true); }
check($response['status'] === 'error' && !isset($options['ppc_admin_menus_settings']), 'Nonce rejection must not write');
$can_manage = false; $_POST = ['nonce' => 'valid'];
try { $shared->ajaxUpdateAdminMenuSettings(); } catch (RuntimeException $error) { $response = json_decode($error->getMessage(), true); }
check($response['status'] === 'error' && !isset($options['ppc_admin_menus_settings']), 'Unauthorized request must not write');
$can_manage = true; $_POST = ['nonce' => 'valid', 'hide_submenu' => 1, 'show_menu_slug' => 1];
try { $shared->ajaxUpdateAdminMenuSettings(); } catch (RuntimeException $error) { $response = json_decode($error->getMessage(), true); }
check($response['status'] === 'success' && $options['ppc_admin_menus_settings']['hide_submenu'] === 1, 'Free settings save');

$editable_roles = \PublishPress\Capabilities\AdminMenusController::getEditableRoles();
check(array_keys($editable_roles) === ['author'], 'Role scope must respect role level, own-role protections, and editable role filters');
$capsman = new class {
    public $roles = ['administrator' => 'Administrator', 'author' => 'Author'];
    public function generateNames() {}
};
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['_wpnonce' => 'valid', 'ppc-admin-menu-role' => 'administrator'];
$denied = false;
try {
    $controller->validateFormSubmission();
} catch (RuntimeException $error) {
    $denied = $error->getCode() === 403;
}
check($denied, 'Protected role form must be denied with HTTP 403 on screen load');
$_POST['ppc-admin-menu-role'] = 'author';
$controller->validateFormSubmission();

$_POST = ['nonce' => 'valid', 'menu_id' => 'edit.php', 'menu_title' => 'Articles', 'menu_type' => 'menu', 'current_role' => 'administrator'];
$previous_names = $options['ppc_admin_menus_names'];
try { $shared->ajaxUpdateAdminMenuTitle(); } catch (RuntimeException $error) { $response = json_decode($error->getMessage(), true); }
check($response['status'] === 'error' && $options['ppc_admin_menus_names'] === $previous_names, 'Out-of-scope rename must not write');
$_POST['scope'] = 'all';
$_POST['current_role'] = 'author';
try { $shared->ajaxUpdateAdminMenuTitle(); } catch (RuntimeException $error) { $response = json_decode($error->getMessage(), true); }
check($response['status'] === 'success' && $options['ppc_admin_menus_names']['author']['menu']['edit.php']['title'] === 'Articles', 'Bulk rename updates editable roles');
check(array_diff_key($options['ppc_admin_menus_names'], ['author' => true]) === array_diff_key($previous_names, ['author' => true]), 'Bulk rename preserves out-of-scope roles');
echo "Free Admin Menus behavior checks passed.\n";
