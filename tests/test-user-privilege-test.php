<?php

$held = ['manage_capabilities_user_testing', 'edit_user', 'read', 'edit_posts'];
$multisite = false;
$site_member = true;
function current_user_can($capability, ...$arguments) { return in_array($capability, $GLOBALS['held'], true); }
function get_current_user_id() { return 1; }
function get_option($key, $default) { return $default; }
function is_multisite() { return $GLOBALS['multisite']; }
function is_super_admin() { return false; }
function is_user_member_of_blog($user_id) { return $GLOBALS['site_member']; }

require dirname(__DIR__) . '/includes/test-user.php';

$target = (object) ['ID' => 2, 'roles' => ['author'], 'allcaps' => ['author' => true, 'read' => true, 'edit_posts' => true]];
$check = static function ($expected) use ($target) {
    if ($expected !== PP_Capabilities_Test_User::canTestUser($target)) {
        throw new RuntimeException('Unexpected User Testing privilege decision.');
    }
};
$check(true);
$target->allcaps['manage_capabilities_admin_menus'] = true;
$check(false);
$target->allcaps['manage_capabilities_admin_menus'] = false;
$check(true);
$target->roles = ['administrator'];
$target->allcaps = ['administrator' => true, 'read' => true];
$check(false);
$target->roles = ['author'];
$target->allcaps = ['read' => true];
$multisite = true;
$site_member = false;
$check(false);
echo "User Testing privilege checks passed.\n";
