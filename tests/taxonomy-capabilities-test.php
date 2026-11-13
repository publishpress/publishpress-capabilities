<?php

define('ABSPATH', __DIR__ . '/../');

function cme_get_assisted_taxonomies()
{
    return ['agile', 'public_tax', 'hidden'];
}

function cme_get_detailed_taxonomies()
{
    global $detailed;
    return $detailed ? ['agile', 'public_tax', 'hidden'] : [];
}

function _cme_get_plural($taxonomy, $taxonomyObject)
{
    return $taxonomy . 's';
}

function is_multisite()
{
    return false;
}

function current_user_can($capability)
{
    return false;
}

require_once __DIR__ . '/../includes/cap-helper.php';

function taxonomyFixture($public, $showUi)
{
    return (object) [
        'public' => $public,
        'show_ui' => $showUi,
        'cap' => (object) [
            'manage_terms' => 'manage_categories',
            'edit_terms' => 'manage_categories',
            'delete_terms' => 'manage_categories',
            'assign_terms' => 'edit_posts',
        ],
    ];
}

foreach ([false, true] as $detailed) {
    $wp_roles = [];
    $wp_taxonomies = [
        'category' => taxonomyFixture(true, true),
        'nav_menu' => taxonomyFixture(false, false),
        'agile' => taxonomyFixture(false, true),
        'public_tax' => taxonomyFixture(true, false),
        'hidden' => taxonomyFixture(false, false),
        'unselected' => taxonomyFixture(false, true),
    ];
    $originalCaps = (array) $wp_taxonomies['hidden']->cap;
    $helper = (new ReflectionClass(CME_Cap_Helper::class))->newInstanceWithoutConstructor();
    $helper->force_distinct_taxonomy_caps();

    foreach (['agile', 'public_tax'] as $taxonomy) {
        $plural = $taxonomy . 's';
        $expected = [
            'manage_terms' => "manage_$plural",
            'edit_terms' => ($detailed ? 'edit_' : 'manage_') . $plural,
            'delete_terms' => ($detailed ? 'delete_' : 'manage_') . $plural,
            'assign_terms' => $detailed ? "assign_$plural" : 'edit_posts',
        ];
        if ($expected !== (array) $wp_taxonomies[$taxonomy]->cap) {
            throw new RuntimeException("Incorrect capabilities for $taxonomy (detailed: " . (int) $detailed . ').');
        }
        foreach (array_values($expected) as $capability) {
            if (!isset($helper->all_taxonomy_caps[$capability])) {
                throw new RuntimeException("Missing capability from role editor capability collection: $capability");
            }
        }
    }

    foreach (['hidden', 'unselected'] as $taxonomy) {
        if ($originalCaps !== (array) $wp_taxonomies[$taxonomy]->cap) {
            throw new RuntimeException("Capabilities should remain unchanged for $taxonomy.");
        }
    }
    if ('manage_nav_menus' !== $wp_taxonomies['nav_menu']->cap->assign_terms) {
        throw new RuntimeException('Nav menu assignment capability must be preserved.');
    }
}

echo "Taxonomy capabilities test passed.\n";
