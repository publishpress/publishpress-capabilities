<?php

$test_feature_enabled = true;
$test_options = [];
$test_user = (object) ['roles' => []];

function is_admin()
{
    return true;
}

function add_action()
{
}

function add_filter()
{
}

function pp_capabilities_feature_enabled()
{
    global $test_feature_enabled;

    return $test_feature_enabled;
}

function is_multisite()
{
    return false;
}

function is_super_admin()
{
    return false;
}

function wp_get_current_user()
{
    global $test_user;

    return $test_user;
}

function apply_filters($hook, $value)
{
    if ('pp_capabilities_admin_columns_apply_role_restrictions' === $hook) {
        return array_merge((array) $value, ['group_restriction']);
    }

    return $value;
}

function get_option($option, $default = false)
{
    global $test_options;

    return isset($test_options[$option]) ? $test_options[$option] : $default;
}

function sanitize_key($value)
{
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
}

function sanitize_text_field($value)
{
    return trim(strip_tags((string) $value));
}

require_once dirname(__DIR__) . '/includes/features/admin-columns/admin-columns.php';

use PublishPress\Capabilities\PP_Capabilities_Admin_Columns;

$admin_columns = PP_Capabilities_Admin_Columns::instance();
$columns = [
    'cb'         => 'Select',
    'title'      => 'Title',
    'author'     => 'Author',
    'categories' => 'Categories',
    'seo_score'  => 'SEO Score',
    'date'       => 'Date',
];

$test_user->roles = ['editor', 'seo_editor'];
$test_options[PP_Capabilities_Admin_Columns::OPTION_NAME] = [
    'editor' => [
        'post' => ['author', 'cb'],
    ],
    'seo_editor' => [
        'post' => ['seo_score', 'title'],
    ],
    'group_restriction' => [
        'post' => ['categories'],
    ],
];

$filtered = $admin_columns->filterColumns($columns, 'post');

foreach (['author', 'categories', 'seo_score'] as $hidden_column) {
    if (isset($filtered[$hidden_column])) {
        fwrite(STDERR, "Expected {$hidden_column} to be hidden.\n");
        exit(1);
    }
}

foreach (['cb', 'title', 'date'] as $visible_column) {
    if (!isset($filtered[$visible_column])) {
        fwrite(STDERR, "Expected {$visible_column} to remain visible.\n");
        exit(1);
    }
}

$test_feature_enabled = false;
if ($admin_columns->filterColumns($columns, 'post') !== $columns) {
    fwrite(STDERR, "Expected disabled Admin Columns feature to leave columns unchanged.\n");
    exit(1);
}

$sanitized = PP_Capabilities_Admin_Columns::sanitizeColumnKeys(
    ['author', ' author ', '', ['invalid'], 'seo_score', 'author']
);

if ($sanitized !== ['author', 'seo_score']) {
    fwrite(STDERR, "Expected submitted column keys to be sanitized and deduplicated.\n");
    exit(1);
}

echo "Admin columns test passed.\n";
