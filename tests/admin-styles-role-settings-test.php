<?php
/**
 * Regression coverage for invalid Admin Styles role options.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.53.0
 */

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
$testOptions = [];

function add_action() {}
function get_option($key, $default = false) { global $testOptions; return $testOptions[$key] ?? $default; }
function update_option($key, $value) { global $testOptions; $testOptions[$key] = $value; return true; }
function maybe_unserialize($value) { return @unserialize($value, ['allowed_classes' => false]) ?: $value; }
function wp_unslash($value) { return $value; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_title($value) { return strtolower(str_replace(' ', '-', $value)); }
function sanitize_hex_color($value) { return preg_match('/^#[a-f0-9]{6}$/i', $value) ? $value : ''; }
function wp_verify_nonce() { return true; }
function is_multisite() { return false; }
function current_user_can() { return true; }
function current_time() { return '2026-10-09 12:00:00'; }
function get_current_user_id() { return 1; }
function set_transient() {}
function admin_url($path) { return $path; }
function add_query_arg($args, $url) { return $url; }
function wp_safe_redirect($url) { throw new RuntimeException('redirect'); }
function wp_parse_args($args, $defaults) { return array_merge($defaults, $args); }
function wp_roles() { return (object) ['role_names' => ['editor' => 'Editor', 'subscriber' => 'Subscriber']]; }
function wp_upload_dir() { return ['error' => 'Uploads unavailable in unit test']; }
function ppc_generate_custom_scheme_css() { return ''; }
function __($text, $domain = '') { return $text; }

require_once dirname(__DIR__) . '/includes/features/admin-styles/admin-styles.php';

$reflection = new ReflectionClass(PublishPress\Capabilities\PP_Capabilities_Admin_Styles::class);
$styles = $reflection->newInstanceWithoutConstructor();
$styles->defaults = ['admin_color_scheme' => 'fresh'];
$valid = ['subscriber' => ['admin_color_scheme' => 'light']];
$cases = ['', 'invalid', false, 42, serialize($valid), $valid + ['editor' => 'invalid']];

foreach ($cases as $stored) {
    foreach (['custom', 'role', 'all'] as $mode) {
        $testOptions = ['pp_capabilities_admin_styles_roles' => $stored];
        $styles->load_settings_for_role('editor');
        if ($styles->settings !== $styles->defaults) {
            throw new RuntimeException('Invalid role settings must fall back to defaults.');
        }
        $_POST = [
            '_wpnonce' => 'test',
            'page' => 'pp-capabilities-admin-styles',
            'ppc-admin-styles-role' => 'editor',
            'settings' => ['admin_color_scheme' => 'light'],
        ];
        if ($mode === 'custom') {
            $_POST += [
                'custom_style_action' => 'save',
                'custom_style_name' => 'Mint Breeze',
                'custom_style_slug' => 'new',
                'custom_style_custom_scheme_base' => '#134e4a',
                'custom_style_custom_scheme_highlight' => '#14b8a6',
            ];
        } else {
            $_POST[$mode === 'all' ? 'admin-styles-all-submit' : 'admin-styles-submit'] = 'Save';
        }
        $_REQUEST = $_POST;
        try {
            $styles->handle_form_submission();
            throw new RuntimeException('Expected a successful redirect.');
        } catch (RuntimeException $error) {
            if ($error->getMessage() !== 'redirect') {
                throw $error;
            }
        }
        $saved = $testOptions['pp_capabilities_admin_styles_roles'];
        $expected = $mode === 'custom' ? 'ppc-custom-style-mint-breeze' : 'light';
        if ($saved['editor']['admin_color_scheme'] !== $expected) {
            throw new RuntimeException('Save must recover invalid options and select the requested scheme.');
        }
        if ($mode === 'all' && $saved['subscriber']['admin_color_scheme'] !== 'light') {
            throw new RuntimeException('Save for all roles must update every role.');
        }
        if ($mode !== 'all' && (is_array($stored) || $stored === serialize($valid))
            && $saved['subscriber'] !== $valid['subscriber']) {
            throw new RuntimeException('Valid settings for other roles must be preserved.');
        }
        if ($mode === 'custom') {
            $custom = $testOptions['pp_capabilities_custom_admin_styles'][$expected];
            if ($custom['custom_scheme_base'] !== '#134e4a' || $custom['custom_scheme_highlight'] !== '#14b8a6') {
                throw new RuntimeException('Unedited template colors must survive saving.');
            }
        }
    }
}

echo "Admin Styles role settings regression tests passed (18 saves).\n";
