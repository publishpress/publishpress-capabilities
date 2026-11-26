<?php
/**
 * Verify missing legacy CSS files are skipped during Admin Styles cleanup.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.53.0
 */

define('ABSPATH', __DIR__ . '/');
$testDirectory = sys_get_temp_dir() . '/ppc-css-cleanup-' . uniqid('', true);
mkdir($testDirectory);
$deleted = [];
$slug = 'ppc-custom-style-test';
$old = $slug . '-abcdef123456.css';
$active = $slug . '-123456abcdef.css';
$missing = $slug . '-000000000000.css';
$unrelated = 'unrelated.css';
foreach ([$old, $active, $unrelated] as $file) {
    file_put_contents($testDirectory . '/' . $file, '/* fixture */');
}
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($value)); }
function sanitize_file_name($value) { return basename($value); }
function wp_basename($value) { return basename($value); }
function trailingslashit($value) { return rtrim($value, '/\\') . '/'; }
function wp_normalize_path($value) { return str_replace('\\', '/', $value); }
function validate_file($value) { return strpos($value, '..') !== false ? 1 : 0; }
function wp_upload_dir() { return ['basedir' => '/unused', 'baseurl' => 'https://example.test/uploads']; }
function apply_filters($hook, $value, ...$args) {
    global $testDirectory;
    if ($hook === 'pp_capabilities_admin_styles_upload_dir') {
        $value['path'] = $testDirectory;
    }
    return $value;
}
function wp_delete_file($path) {
    global $deleted;
    if (!is_file($path)) {
        throw new RuntimeException('Cleanup must never attempt to unlink a missing file.');
    }
    $deleted[] = basename($path);
    unlink($path);
}
$wp_filesystem = new class {
    public function dirlist($path) {
        global $old, $active, $missing, $unrelated;
        // Also exercise a stale directory listing where a candidate has already disappeared.
        return array_fill_keys([$old, $active, $missing, $unrelated], []);
    }
};
require_once dirname(__DIR__) . '/includes/features/admin-styles/admin-styles.php';
$class = new ReflectionClass(PublishPress\Capabilities\PP_Capabilities_Admin_Styles::class);
$styles = $class->newInstanceWithoutConstructor();
$cleanup = $class->getMethod('delete_custom_style_css_files');
$cleanup->setAccessible(true);
try {
    $cleanup->invoke($styles, $slug, ['css_file' => $missing], $active);
    if ($deleted !== [$old] || !is_file($testDirectory . '/' . $active)
        || !is_file($testDirectory . '/' . $unrelated)) {
        throw new RuntimeException('Cleanup must delete only existing obsolete files for this style.');
    }
    $cleanup->invoke($styles, $slug, ['css_file' => $missing], $active);
    if ($deleted !== [$old]) {
        throw new RuntimeException('Repeated cleanup must skip files already removed.');
    }
    echo "Admin Styles CSS cleanup regression checks passed.\n";
} finally {
    foreach ([$old, $active, $unrelated] as $file) {
        if (is_file($testDirectory . '/' . $file)) {
            unlink($testDirectory . '/' . $file);
        }
    }
    rmdir($testDirectory);
}
