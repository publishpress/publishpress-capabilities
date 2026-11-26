<?php
/**
 * Behavioral checks for recovery without an admin session.
 * @since 2.53.0
 */
namespace WP_CLI\Utils {
    function format_items($format, $items, $fields) { $GLOBALS['listed'] = $items; }
}
namespace {
    class WP_CLI {
        public static $commands = [];
        public static $confirmations = 0;
        public static $allow = true;
        public static function add_command($name, $callback) { self::$commands[$name] = $callback; }
        public static function error($message) { throw new RuntimeException($message); }
        public static function success($message) {}
        public static function confirm($message, $args) {
            self::$confirmations++;
            if (!self::$allow) { throw new RuntimeException('Cancelled'); }
        }
    }
    $options = [];
    $auto = [];
    $wpdb = (object) ['prefix' => 'wp_2_'];
    $current_user = null;
    $rolesObject = new class {
        public $roles = [];
        public $reloads = [];
        public function for_site($id) { $this->reloads[] = $id; }
    };
    function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
    function update_option($key, $value, $autoload = null) {
        if ($key !== ($GLOBALS['blocked'] ?? null)) { $GLOBALS['options'][$key] = $value; }
    }
    function delete_option($key) { unset($GLOBALS['options'][$key]); }
    function get_current_blog_id() { return 2; }
    function wp_roles() { return $GLOBALS['rolesObject']; }
    function pp_capabilities_get_auto_backup_option_names() { return $GLOBALS['auto']; }
    function pp_capabilities_backup_sections() { return [['options' => ['capsman_disabled_admin_features']]]; }
    function apply_filters($hook, $value) { return ['manage_capabilities_backup']; }
    function remove_role($role) { unset(wp_roles()->roles[$role]); }
    function populate_roles() {
        wp_roles()->roles = ['administrator' => ['name' => 'Administrator', 'capabilities' => ['read' => true, 'manage_options' => true]]];
        update_option($GLOBALS['wpdb']->prefix . 'user_roles', wp_roles()->roles);
    }
    function get_role($slug) {
        return new class($slug) {
            private $slug;
            public function __construct($slug) { $this->slug = $slug; }
            public function add_cap($cap) { wp_roles()->roles[$this->slug]['capabilities'][$cap] = true; }
        };
    }
    function check($condition, $message) { if (!$condition) { throw new RuntimeException($message); } }
    function fails($callback) {
        try { $callback(); } catch (RuntimeException $error) { return; }
        throw new RuntimeException('Expected rejection');
    }
    require dirname(__DIR__) . '/includes/recovery.php';
    require dirname(__DIR__) . '/includes/cli-recovery.php';
    $command = new \PublishPress\Capabilities\RecoveryCommand();
    $roles = ['administrator' => ['name' => 'Administrator', 'capabilities' => ['read' => true]]];
    $id = 'cme_backup_auto_2026-10-13_9-00-00_am';
    $auto = [$id, 'cme_backup_auto_missing'];
    $options = ['capsman_backup' => $roles, 'capsman_backup_datestamp' => strtotime('2026-10-12 UTC'),
        'capsman_backup_initial' => $roles, $id => $roles,
        'capsman_disabled_admin_features_backup' => [], 'capsman_disabled_admin_features' => ['blocked']];
    $command->listBackups([], []);
    check(count($listed) === 3 && $listed[0]['id'] === $id, 'List and latest ordering');
    $command->restore([], ['last' => true, 'yes' => true]);
    check(get_option('wp_2_user_roles') === $roles && get_option('wp_user_roles') === false, 'Site isolation');
    check(get_option('capsman_disabled_admin_features') === ['blocked'], 'Automatic restore only restores roles');
    check(wp_roles()->reloads === [2], 'Role cache reload');
    check(strpos(get_option('capsman_last_recovery')['message'], $id) !== false, 'Recovery audit');
    $command->restore([], ['id' => 'capsman_backup']);
    check(get_option('capsman_disabled_admin_features') === [], 'Manual restore includes empty settings');
    fails(function () use ($command) { $command->restore([], []); });
    fails(function () use ($command) { $command->restore([], ['last' => true, 'id' => 'capsman_backup']); });
    fails(function () use ($command) { $command->restore([], ['id' => 'wp_2_user_roles']); });
    $options[$id] = ['invalid' => 'roles'];
    fails(function () use ($command, $id) { $command->restore([], ['id' => $id]); });
    $options[$id] = $roles;
    $options['wp_2_user_roles'] = ['old'];
    WP_CLI::$allow = false;
    fails(function () use ($command) { $command->restore([], ['last' => true]); });
    check(get_option('wp_2_user_roles') === ['old'], 'Cancellation must not mutate roles');
    WP_CLI::$allow = true;
    $blocked = 'wp_2_user_roles';
    fails(function () use ($command) { $command->restore([], ['last' => true]); });
    unset($blocked);
    $options['capsman_backup_datestamp'] = strtotime('2026-10-14 UTC');
    check(\PublishPress\Capabilities\Recovery::resolveBackup() === 'capsman_backup', 'Newer manual backup selected');
    $fixture = sys_get_temp_dir() . '/cme-recovery-test-' . uniqid();
    mkdir($fixture . '/wp-admin/includes', 0777, true);
    file_put_contents($fixture . '/wp-admin/includes/schema.php', '<?php');
    define('ABSPATH', $fixture . '/');
    try {
        wp_roles()->roles = ['custom' => ['name' => 'Custom', 'capabilities' => []]];
        $command->resetRoles([], ['yes' => true]);
        check(!isset(wp_roles()->roles['custom']), 'Reset removes custom roles');
        check(!empty(wp_roles()->roles['administrator']['capabilities']['manage_capabilities_backup']), 'Reset grants plugin access');
    } finally {
        unlink($fixture . '/wp-admin/includes/schema.php');
        rmdir($fixture . '/wp-admin/includes'); rmdir($fixture . '/wp-admin'); rmdir($fixture);
    }
    $options = []; $auto = [];
    fails(function () use ($command) { $command->restore([], ['last' => true]); });
    check(count(WP_CLI::$commands) === 3, 'Three recovery commands registered');
    echo "Recovery behavior checks passed.\n";
}
