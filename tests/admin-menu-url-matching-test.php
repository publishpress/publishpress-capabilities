<?php

function wp_parse_url($url) { return parse_url($url); }
function wp_parse_str($query, &$arguments) { parse_str($query, $arguments); }
function plugin_basename($path) { return $path; }
function wp_unslash($value) { return $value; }
function is_multisite() { return false; }
function esc_attr__($text, $domain) { return $text; }
function esc_html($text) { return $text; }
function wp_die($message, $title, $args) { throw new RuntimeException($message, $args['response']); }

require __DIR__ . '/../includes/features/admin-menus/admin-menus-functions.php';

$cases = [
    'plain core screen' => ['tools.php', 'tools.php', '', '', '', '', true],
    'core screen with extra arguments' => ['tools.php', 'tools.php', '', '', '', 'view=compact', true],
    'plain plugin slug with extra arguments' => ['demo-settings', 'options-general.php', 'demo-settings', '', '', 'page=demo-settings&view=compact', true],
    'query-qualified screen reordered with extra arguments' => [
        'edit-tags.php?taxonomy=genre&amp;post_type=book', 'edit-tags.php', '', 'book', 'genre', 'post_type=book&view=compact&taxonomy=genre', true,
    ],
    'explicit plugin URL reordered with extra arguments' => [
        'admin.php?page=demo-settings&tab=general', 'admin.php', 'demo-settings', '', '', 'tab=general&view=compact&page=demo-settings', true,
    ],
    'default Posts screen with pagination' => ['edit.php', 'edit.php', '', '', '', 'paged=2', true],
    'distinct post type on shared script' => ['edit.php', 'edit.php', '', 'page', '', 'post_type=page', false],
    'distinct taxonomy on shared script' => [
        'edit-tags.php?taxonomy=genre&post_type=book', 'edit-tags.php', '', 'book', 'topic', 'post_type=book&taxonomy=topic', false,
    ],
    'distinct plugin tab' => [
        'admin.php?page=demo-settings&tab=general', 'admin.php', 'demo-settings', '', '', 'page=demo-settings&tab=advanced', false,
    ],
    'distinct plugin screen on shared script' => ['tools.php', 'tools.php', 'demo-settings', '', '', 'page=demo-settings', false],
    'Customizer with return link' => ['customize.php?return=old', 'customize.php', '', '', '', 'return=new', true],
    'external URL' => ['https://example.com/tools.php', 'tools.php', '', '', '', '', false],
];

foreach ($cases as $name => [$slug, $pagenow, $plugin_page, $typenow, $taxnow, $query, $expected]) {
    wp_parse_str($query, $_GET);
    $blocked = false;
    try {
        pp_capabilities_admin_menu_access($slug);
    } catch (RuntimeException $error) {
        if ($error->getCode() !== 403) {
            throw $error;
        }
        $blocked = true;
    }
    if ($blocked !== $expected) {
        throw new RuntimeException('Unexpected Admin Menus access decision: ' . $name);
    }
}

echo "Admin Menus URL matching checks passed.\n";
