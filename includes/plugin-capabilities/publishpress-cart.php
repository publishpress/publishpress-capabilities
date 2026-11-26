<?php

/**
 * Register PublishPress Cart capability groups for Free and Pro.
 *
 * @since 2.53.0
 */

if (!defined('ABSPATH')) {
    exit('No direct script access allowed');
}

add_filter('cme_plugin_capabilities', function ($pluginCaps) {
    if (!defined('PPCART_VERSION') || !function_exists('ppcart_live_cap')) {
        return $pluginCaps;
    }

    $post_type_caps = function ($post_type) {
        $caps = [];
        foreach (['edit', 'read', 'delete'] as $operation) {
            $caps[] = $operation . '_' . $post_type;
        }
        foreach (['edit', 'edit_others', 'publish', 'read_private', 'delete', 'delete_private',
            'delete_published', 'delete_others', 'edit_private', 'edit_published'] as $operation) {
            $caps[] = $operation . '_' . $post_type . 's';
        }
        return $caps;
    };

    $groups = [
        __('Settings', 'capability-manager-enhanced') => [ppcart_live_cap('manager_option')],
    ];
    $families = [
        'product' => __('Products', 'capability-manager-enhanced'),
        'order' => __('Orders', 'capability-manager-enhanced'),
        'subscription' => __('Subscriptions', 'capability-manager-enhanced'),
    ];
    foreach ($families as $family => $label) {
        $post_type = ppcart_live_post_type($family);
        $caps = $post_type_caps($post_type);
        $groups[$label] = array_values(array_unique($caps));
    }

    $upsell_caps = [];
    foreach (ppcart_query_pro_post_types('us_path') as $post_type) {
        $caps = $post_type_caps($post_type);
        $upsell_caps = array_merge($upsell_caps, array_values($caps));
    }
    $groups[__('Upsell Paths', 'capability-manager-enhanced')] = array_values(array_unique($upsell_caps));
    $pluginCaps['PublishPress Cart'] = apply_filters('cme_publishpress_cart_capabilities', $groups);

    return $pluginCaps;
});
