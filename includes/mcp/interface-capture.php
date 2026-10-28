<?php

/**
 * Capture safe interface metadata during real admin visits for MCP discovery.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class InterfaceCapture
{
    /** @since 2.54.0 */
    public static function dashboard(): void
    {
        if (!current_user_can('manage_capabilities_admin_features')) {
            return;
        }
        $widgets = [];
        foreach ($GLOBALS['wp_meta_boxes']['dashboard'] ?? [] as $context => $priorities) {
            foreach ($priorities as $items) {
                foreach ($items as $id => $widget) {
                    if ($widget) {
                        $widgets[$id] = ['label' => wp_strip_all_tags($widget['title'] ?? $id),
                            'context' => $context, 'action' => 'ppc_dashboard_widget'];
                    }
                }
            }
        }
        update_option('publishpress_caps_mcp_dashboard_snapshot', $widgets, false);
    }

    /** @since 2.54.0 */
    public static function toolbar($bar): void
    {
        if (!is_admin() || !current_user_can('manage_capabilities_admin_features')) {
            return;
        }
        $nodes = [];
        foreach ((array) $bar->get_nodes() as $node) {
            $nodes[$node->id] = ['id' => $node->id, 'title' => wp_strip_all_tags($node->title), 'parent' => $node->parent];
        }
        update_option('publishpress_caps_mcp_toolbar_snapshot', $nodes, false);
    }
}
