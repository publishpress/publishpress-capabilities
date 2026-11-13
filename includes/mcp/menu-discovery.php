<?php

/**
 * Discover the actual identifiers used by Admin Menus restrictions.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class MenuDiscovery
{
    /** @since 2.54.0 */
    public static function capture(): void
    {
        if (!is_admin() || is_network_admin() || !RoleOperations::canManage('manage_capabilities_admin_menus')) {
            return;
        }
        $menus = $GLOBALS['menu'] ?? [];
        if (!$menus || !is_array($menus)) {
            return;
        }
        // Capture after custom menu registration and before Capabilities applies restrictions.
        update_option('ppc_admin_menus_menu', $menus, false);
        update_option('ppc_admin_menus_submenu', (array) ($GLOBALS['submenu'] ?? []), false);
    }

    /** @since 2.54.0 */
    public static function choices(): array
    {
        $menus = [];
        $submenus = [];
        foreach ((array) get_option('ppc_admin_menus_menu', []) as $item) {
            if (!is_array($item) || !isset($item[2]) || strpos((string) $item[2], 'separator') === 0) {
                continue;
            }
            $value = self::identifier((string) $item[2]);
            if ($value !== '') {
                $menus[] = self::item($item, $value);
            }
        }
        foreach ((array) get_option('ppc_admin_menus_submenu', []) as $parent => $items) {
            foreach ((array) $items as $item) {
                if (!is_array($item) || !isset($item[2])) {
                    continue;
                }
                $parentValue = self::identifier((string) $parent);
                $childValue = self::identifier((string) $item[2], true);
                if ($parentValue !== '' && $childValue !== '') {
                    $submenus[] = self::item($item, $parentValue . '|' . $childValue) + ['parent' => $parentValue];
                }
            }
        }
        return [
            'menus' => $menus, 'submenus' => $submenus, 'available' => (bool) $menus,
            'refresh_url' => admin_url('admin.php?page=pp-capabilities-admin-menus'),
            'instructions' => __('Use restriction_value exactly as returned. If discovery is empty or a menu is missing, open the Admin Menus page as an authorized administrator, then request choices again. Do not invent identifiers or infer which menus a role can access from this snapshot.', 'capability-manager-enhanced'),
        ];
    }

    /** @since 2.54.0 */
    public static function validate(array $settings, array $previous)
    {
        $choices = self::choices();
        foreach (['disabled_menus' => 'menus', 'disabled_submenus' => 'submenus'] as $field => $group) {
            $added = array_diff($settings[$field] ?? [], $previous[$field] ?? []);
            $known = array_column($choices[$group], 'restriction_value');
            if (array_diff($added, $known)) {
                return RoleOperations::error('unknown_menu', __('A new menu restriction was not found in discovery. Request get-feature-choices for admin-menus and use its restriction_value. If missing, open the Admin Menus page and try again.', 'capability-manager-enhanced'));
            }
        }
        return true;
    }

    /** @since 2.54.0 */
    private static function item(array $item, string $value): array
    {
        return [
            'label' => trim(wp_strip_all_tags((string) ($item[0] ?? ''))),
            'restriction_value' => $value,
            'capability' => (string) ($item[1] ?? ''),
        ];
    }

    /** @since 2.54.0 */
    private static function identifier(string $slug, bool $submenu = false): string
    {
        // Customizer return URLs contain request-specific data, not a restriction identifier.
        if (strpos($slug, 'customize.php?') === 0) {
            return $submenu ? 'customize.php' : '';
        }
        if (preg_match('/(?:[?&]|&amp;)(?:_wpnonce|_ajax_nonce|nonce)=/i', $slug)) {
            return '';
        }
        return $slug;
    }
}
