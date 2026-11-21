<?php

define('ABSPATH', dirname(__DIR__) . '/');
define('PUBLISHPRESS_CAPS_ABSPATH', dirname(__DIR__));
define('CME_FILE', PUBLISHPRESS_CAPS_ABSPATH . '/capsman-enhanced.php');

function add_action($hook, $callback, ...$arguments) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_filter($hook, $callback, ...$arguments) {}
function apply_filters($hook, $value, ...$arguments) { return $value; }
function is_admin() { return false; }
function wp_is_block_theme() { return true; }
function block_template_part($slug) {}
function wp_get_current_user() { return (object) ['roles' => ['subscriber']]; }
function is_user_logged_in() { return true; }
function is_category() { return false; }
function is_tag() { return false; }
function is_tax() { return false; }
function is_singular() { return false; }
function is_front_page() { return $GLOBALS['front_page']; }
function wp_unslash($value) { return $value; }
function esc_url_raw($url) { return $url; }
function esc_attr__($text, $domain) { return $text; }
function esc_html($text) { return $text; }
function home_url($path) { return 'https://example.test' . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function wp_parse_str($query, &$arguments) { parse_str($query, $arguments); }
function get_option($key, $default = false) {
    return 'capsman_nav_item_menus' === $key ? ['subscriber' => ['0|' . $GLOBALS['blocked_url'] . '|custom']] : $default;
}
function wp_die($message) { throw new RuntimeException($message); }

require PUBLISHPRESS_CAPS_ABSPATH . '/includes/functions.php';

$handler = $hooks['parse_query'][0];
$cases = [
    'extra query on a link without configured parameters' => ['/library/', '/library/?source=menu', true],
    'extra query on a link with configured parameters' => ['/library/?section=members', '/library/?section=members&source=menu', true],
    'reordered arguments' => ['/library/?section=members&view=list', '/library/?view=list&section=members', true],
    'reordered and added arguments on an escaped link' => ['/library/?section=members&amp;view=list', '/library/?view=list&source=menu&section=members', true],
    'different configured value' => ['/library/?section=members', '/library/?section=public&source=menu', false],
    'missing configured value' => ['/library/?section=members', '/library/?source=menu', false],
    'different path' => ['/library/', '/catalog/?source=menu', false],
    'home with extra query' => ['/', '/?source=menu', true, true],
    'distinct plain-permalink page' => ['/', '/?page_id=12', false],
    'different host' => ['https://elsewhere.test/library/', '/library/?source=menu', false],
];

$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'example.test';

foreach ($cases as $label => $case) {
    [$link, $request, $expected] = $case;
    $blocked_url = str_starts_with($link, '/') ? home_url($link) : $link;
    $_SERVER['REQUEST_URI'] = $request;
    $front_page = $case[3] ?? false;
    $ppc_disabled_nav_menu_data = [];
    $ppc_nav_menu_restricted = false;

    $denied = false;
    try {
        $handler(new stdClass());
    } catch (RuntimeException $error) {
        if ('You do not have permission to access this page.' !== $error->getMessage()) {
            throw $error;
        }
        $denied = true;
    }

    if ($denied !== $expected) {
        throw new RuntimeException('Unexpected custom link access decision: ' . $label);
    }
}

echo "Block Navigation custom link URL checks passed.\n";
