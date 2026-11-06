<?php

namespace PublishPress\Capabilities;
class Admin_Menus_Filters
{
    private static $instance = null;

    private static $menu_mapping_index = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Admin_Menus_Filters();
        }

        return self::$instance;
    }

    public function __construct()
    {
        if (is_admin() && pp_capabilities_feature_enabled('admin-menus')) {
            // Admin menu order update ajax callback
            add_action('wp_ajax_ppc_update_admin_menu_order_by_ajax', [$this, 'ajaxUpdateAdminMenuOrder']);
            // Admin menu title update ajax callback
            add_action('wp_ajax_ppc_update_admin_menu_title', [$this, 'ajaxUpdateAdminMenuTitle']);
            // Admin menu delete update ajax callback
            add_action('wp_ajax_ppc_delete_admin_menu', [$this, 'ajaxDeleteAdminMenu']);
            // Admin menu reset ajax callback
            add_action('wp_ajax_ppc_reset_admin_menu', [$this, 'ajaxResetAdminMenu']);
            // Admin menu settings update ajax callback
            add_action('wp_ajax_ppc_update_admin_menu_settings', [$this, 'ajaxUpdateAdminMenuSettings']);
            // Admin Menu Rename and Re-order
            add_filter('submenu_file', [$this, 'customizeAdminMenu'], PHP_INT_MAX - 2);
            // Add custom menu
            add_action('admin_menu', [$this, 'addCustomMenu'], PHP_INT_MAX - 100);
            // Output dynamic separator colors for custom admin menus
            add_action('admin_head', [$this, 'outputCustomMenuSeparatorStyles'], PHP_INT_MAX - 100);
            // Set admin menu and sub menu in 'adminmenu' to support custom menu and also get menu correct order:
            add_action('adminmenu', [$this, 'setCapabilitiesAdminMenu'], PHP_INT_MAX - 99);
            // Set admin menu restriction
            add_action('admin_menu', [$this, 'adminMenuRestriction'], PHP_INT_MAX - 98);
            // Clear stored menu when plugins are deactivated to remove old plugin menu
            add_action('deactivate_plugin', [$this, 'clearStoredData']);
            add_action('activated_plugin', [$this, 'clearStoredData']);
            add_action('upgrader_process_complete', [$this, 'clearStoredDataAfterPluginUpgrade'], 10, 2);
            // Clear stored menu snapshots when feature toggles can add or remove submenu items.
            add_action('pp_capabilities_dashboard_feature_updated', [$this, 'clearStoredDataForMenuFeature'], 10, 2);
        }
    }

    private static function get_menu_mapping_index() {
        if (self::$menu_mapping_index === null) {
            self::$menu_mapping_index = [];
            $global_submenu = get_option('ppc_admin_menus_submenu', []);

            foreach ($global_submenu as $parent_slug => $submenus) {
                if (is_array($submenus)) {
                    foreach ($submenus as $index => $subitem) {
                        $legacy_parent_slug = $parent_slug;
                        if (isset($subitem[2])) {
                            // Map submenu slug to parent
                            self::$menu_mapping_index[$subitem[2]] = $parent_slug;
                            // Map legacy format to new format
                            self::$menu_mapping_index[$legacy_parent_slug . $index] = $parent_slug . '|' . $subitem[2];
                            // This is intentional to support legacy option with incorrect parent
                            $legacy_parent_slug = $subitem[2];
                        }
                    }
                }
            }
        }
        return self::$menu_mapping_index;
    }

    /**
     * Universal menu resolution for both new option and legacy option
     * @param mixed $identifier
     * @return array
     */
    private static function resolve_menu_identifiers($identifier) {
        $mapping_index = self::get_menu_mapping_index();
        $identifiers = [$identifier];

        $normalized_identifier = self::normalize_menu_identifier($identifier);
        if ($normalized_identifier !== $identifier) {
            $identifiers[] = $normalized_identifier;
        }

        // Check if we have a direct mapping
        if (isset($mapping_index[$identifier])) {
            $identifiers[] = htmlspecialchars($mapping_index[$identifier]);
        }

        // Handle old format values
        if (is_numeric(substr($identifier, -1)) && strpos($identifier, '|') === false) {
            $identifiers[] = htmlspecialchars($identifier);
        }

        // Handle new format values
        if (strpos($identifier, '|') !== false) {
            list($parent, $submenu) = explode('|', $identifier, 2);
            $identifiers[] = htmlspecialchars($submenu);
        }

        return array_unique($identifiers);
    }

    public static function normalize_menu_identifier($identifier) {
        if (!is_string($identifier)) {
            return $identifier;
        }

        if (strpos($identifier, '|') !== false) {
            list($parent, $submenu) = explode('|', $identifier, 2);
            return $parent . '|' . self::normalize_menu_identifier($submenu);
        }

        if (strpos($identifier, 'customize.php?') === 0) {
            return 'customize.php';
        }

        return $identifier;
    }

    private function build_restriction_lookup_table($disabled_child_menu_array) {
        $lookup_table = [];

        foreach ($disabled_child_menu_array as $disabled_identifier) {
            $identifiers = self::resolve_menu_identifiers($disabled_identifier);
            foreach ($identifiers as $id) {
                $lookup_table[$id] = true;
            }
        }

        return $lookup_table;
    }

    /**
     * Set admin menu and sub menu after all menu has been added but before menu restriction to get all menus
     *
     * I initially set global item here but it has the following limitations:
     * - Restricted menu are not showing in 'Admin menu Restrictions' screen if admin restrict his role
     * - On the pp_capabilities_admin_menu_permission() function, custom menu are not available there
     *
     * So, storing the data in adminmenu that has complete data is our best option so far.
     *
     * @since 2.3.1
     */
    public function setCapabilitiesAdminMenu()
    {
        global $menu, $submenu, $admin_global_menu, $admin_global_submenu;

        // we only want to update complete menu and on capablities page where menu is not restricted
        if ( current_user_can('manage_capabilities_admin_menus') && isset($_GET['page']) && $_GET['page'] === 'pp-capabilities-admin-menus')
        {
            // We're getting menu issue when menu are restricted. So, we need to use menu before restriction
            $stored_menu    = $menu;
            $stored_submenu = $submenu;
            if (is_array($admin_global_menu)
                && count($admin_global_menu) > count($menu)
            ) {
                $stored_menu = $admin_global_menu;
            }

            $stored_submenu = self::mergeSubmenuSnapshots((array) $admin_global_submenu, $submenu);

            if ( get_option('ppc_admin_menus_menu') !== $stored_menu) {//save menu
                update_option('ppc_admin_menus_menu', $stored_menu);
            }
            if ( get_option('ppc_admin_menus_submenu') !== $stored_submenu) {//save submenu
                update_option('ppc_admin_menus_submenu', $stored_submenu);
            }
        }
    }

    /**
     * Clear stored menu when plugins are deactivated to remove old plugin menu
     *
     * @param string $plugin The path of the plugin being deactivated, relative to the plugins directory.
     */
    public function clearStoredData($plugin = null) {
        self::$menu_mapping_index = null;
        delete_option('ppc_admin_menus_menu');
        delete_option('ppc_admin_menus_submenu');
    }

    /**
     * Refresh discovery after plugin installs and updates without resetting user settings.
     *
     * @param object $upgrader WordPress upgrader instance.
     * @param array $options Upgrade operation details.
     * @since 2.54.0
     */
    public function clearStoredDataAfterPluginUpgrade($upgrader, $options) {
        if (isset($options['type'], $options['action']) && $options['type'] === 'plugin'
            && in_array($options['action'], ['install', 'update'], true)
        ) {
            $this->clearStoredData();
        }
    }

    /**
     * Merge discovery snapshots by submenu slug, retaining cached restricted items.
     * Current entries replace stale metadata; numeric offsets are not item identities.
     *
     * @param array $cached Previously captured submenu groups.
     * @param array $current Submenu groups registered in the current request.
     * @return array
     * @since 2.54.0
     */
    public static function mergeSubmenuSnapshots($cached, $current) {
        $cached = self::normalizeSubmenuSnapshot($cached);
        $current = self::normalizeSubmenuSnapshot($current);
        foreach ($current as $parent => $items) {
            if (!isset($cached[$parent]) || !is_array($cached[$parent])) {
                $cached[$parent] = $items;
                continue;
            }
            $positions = [];
            foreach ($cached[$parent] as $position => $item) {
                if (isset($item[2])) {
                    $positions[$item[2]] = $position;
                }
            }
            foreach ($items as $position => $item) {
                if (!isset($item[2])) {
                    continue;
                }
                if (isset($positions[$item[2]])) {
                    $cached[$parent][$positions[$item[2]]] = $item;
                } elseif (!isset($cached[$parent][$position])) {
                    $cached[$parent][$position] = $item;
                    $positions[$item[2]] = $position;
                } else {
                    $cached[$parent][] = $item;
                    end($cached[$parent]);
                    $positions[$item[2]] = key($cached[$parent]);
                }
            }
            // Follow current registration order without changing cached legacy offsets.
            $ordered = [];
            foreach ($items as $item) {
                $position = $positions[$item[2]];
                $ordered[$position] = $cached[$parent][$position];
            }
            $cached[$parent] = $ordered + $cached[$parent];
        }
        return $cached;
    }

    /**
     * Missing legacy options can be false; only submenu arrays are renderable.
     *
     * @param array $snapshot Submenu groups from discovery or storage.
     * @return array
     * @since 2.54.0
     */
    private static function normalizeSubmenuSnapshot($snapshot) {
        foreach ($snapshot as $parent => $items) {
            if (!is_array($items)) {
                unset($snapshot[$parent]);
                continue;
            }
            foreach ($items as $position => $item) {
                if (!is_array($item) || !isset($item[2])) {
                    unset($snapshot[$parent][$position]);
                }
            }
        }
        return $snapshot;
    }

    public function clearStoredDataForMenuFeature($feature, $status) {
        $this->clearStoredData();
    }

    /**
     * Customize admin menu order, names, new menu and parent relationships
     *
     * @param string $submenu_file The parent file
     *
     * @return string Modified parent file
     */
    public function customizeAdminMenu($submenu_file = '') {
        global $menu, $submenu;

        $user_roles = wp_get_current_user()->roles;

        // get menus for current role
        $role_menu = self::getRoleAdminMenu($menu, $submenu, $user_roles);

        // set menu and submenu for current role
        $menu       = self::restoreCustomMenuSeparatorClasses($role_menu['menu']);
        $submenu    = $role_menu['submenu'];

        return $submenu_file;
    }
    public static function getRoleAdminMenu($menu, $submenu, $user_roles) {
        $enable_menu_renaming = true;
        $enable_menu_reordering = true;

        // Allow disabling renaming and reordering via URL parameters
        if (isset($_GET['page']) && $_GET['page'] === 'pp-capabilities-admin-menus') {
            if (isset($_GET['disable_menu_renaming']) && $_GET['disable_menu_renaming'] === 'true') {
                $enable_menu_renaming = false;
            }
            if (isset($_GET['disable_menu_reordering']) && $_GET['disable_menu_reordering'] === 'true') {
                $enable_menu_reordering = false;
            }
        }

        // Get stored menu settings
        $menu_order = get_option('ppc_admin_menus_order', []);
        $menu_names = get_option('ppc_admin_menus_names', []);

        // Rename menus and submenus based on stored settings
        foreach ($user_roles as $role) {
            if ($enable_menu_renaming && isset($menu_names[$role])) {
                self::renameMenus($menu, $submenu, $menu_names[$role]);
            }
        }

        // Reorder menus and submenus
        if ($enable_menu_reordering && !empty($menu_order) && is_array($menu_order)) {
           list($menu, $submenu) = self::reorderMenus($menu, $submenu, $menu_order);
        }

        return ['menu' => $menu, 'submenu' => $submenu];
    }

    /**
     * Restore WordPress's separator marker after core has removed adjacent separators.
     *
     * Custom separators omit this class while WordPress normalizes the admin menu so
     * multiple adjacent items survive. The class is required again when menu-header.php
     * renders the final separator markup.
     */
    private static function restoreCustomMenuSeparatorClasses($menu) {
        foreach ($menu as &$menu_item) {
            if (!pp_capabilities_admin_menu_has_css_class($menu_item, 'ppc-admin-menu-separator')
                || pp_capabilities_admin_menu_has_css_class($menu_item, 'text-separator')
                || pp_capabilities_admin_menu_has_css_class($menu_item, 'wp-menu-separator')
            ) {
                continue;
            }

            $menu_item[4] = 'wp-menu-separator ' . trim($menu_item[4]);
        }
        unset($menu_item);

        return $menu;
    }

    /**
     * Rename menu and submenu items based on custom names.
     */
    private static function renameMenus(&$menu, &$submenu, $menu_names) {
        // Rename main menus
        foreach ($menu as $priority => &$menu_item) {
            $menu_id = $menu_item[2] ?? '';
            if ($menu_id && isset($menu_names['menu'][$menu_id])) {
                $menu_item[0] = self::appendHtmlToTitle(
                    $menu_item[0],
                    $menu_names['menu'][$menu_id]['title'] ?? ''
                );
                $menu_item[6] = (!empty($menu_names['menu'][$menu_id]['icon'])) ? $menu_names['menu'][$menu_id]['icon'] : $menu_item[6];
            }
        }

        // Rename submenus
        foreach ($submenu as $parent => &$submenu_items) {
            foreach ($submenu_items as &$submenu_item) {
                $submenu_id = $submenu_item[2] ?? '';
                if ($submenu_id && isset($menu_names['submenu'][$submenu_id])) {
                    $submenu_item[0] = self::appendHtmlToTitle(
                        $submenu_item[0],
                        $menu_names['submenu'][$submenu_id]['title'] ?? ''
                    );
                }
            }
        }
    }

    /**
     * Reorder menu and submenu items based on default order, with custom order taking precedence.
     */
    private static function reorderMenus($menu, $submenu, $menu_order) {

        // Create lookups for quick access
        $menu_lookup = array_column($menu, null, 2); // Main menu by ID
        $menu_lookup_index = array_keys($menu_lookup);
        $menu_order_lookup = array_keys($menu_order);
        $submenu_lookup = [];

        foreach ($submenu as $parent => $items) {
            foreach ($items as $priority => $item) {
                $submenu_lookup[$item[2]] = [
                    'item' => $item,
                    'parent' => $parent,
                    'priority' => $priority
                ];
            }
        }

        //Get new menu items. Items in menu but not in menu order. Probably new menu added by new plugins installed after menu order.
        $new_wordpress_menu = array_diff($menu_lookup_index, $menu_order_lookup);

        // Get the count of both arrays and determine the larger count
        $menu_count         = count($menu_lookup_index);
        $menu_order_count   = count($menu_order_lookup);
        $max_count          = max($menu_count, $menu_order_count);

        // store menu slug to prevent duplicate menu
        $custom_and_new_menus = [];
        // Loop from 0 to the larger array size and order menus based on menu order while adding new menu to their original position.
        for ($i = 0; $i < $max_count; $i++) {
            // Check if an element exists in $menu_order_lookup and $new_wordpress_menu
            $order_menu = isset($menu_order_lookup[$i]) ? $menu_order_lookup[$i] : null;
            $item_new   = isset($new_wordpress_menu[$i]) ? $new_wordpress_menu[$i] : null;

            // check if index exist in menu order and wordpress menu (to make sure we're not adding menu of already deleted plugin that exist in menu order)
            if ($order_menu !== null
                && in_array($order_menu, $menu_lookup_index)
                && !in_array($order_menu, $custom_and_new_menus)) {
                $custom_and_new_menus[] = $order_menu;
            }

            // check if index exist in new menu (not in our stored menu order)
            if ($item_new !== null && !in_array($item_new, $custom_and_new_menus)) {
                // Insert the new item at position 10 (index 10)
                array_splice($custom_and_new_menus, $i, 0, $item_new);
            }
        }

        // build menu based on $custom_and_new_menus order which include both new and old menu and order
        $final_menu = [];
        $final_submenu = [];

        foreach ($custom_and_new_menus as $custom_and_new_menu_key) {
            // add menu
             $final_menu[] = $menu_lookup[$custom_and_new_menu_key];

            // Handle submenus for this menu
            if (isset($submenu[$custom_and_new_menu_key])) {
                $default_submenu = $submenu[$custom_and_new_menu_key];

                // Check if a custom order exists for this menu's submenus
                if (isset($menu_order[$custom_and_new_menu_key]) && is_array($menu_order[$custom_and_new_menu_key])) {
                    $custom_submenu_order = $menu_order[$custom_and_new_menu_key];

                    if (self::hasNewSubmenus($default_submenu, $menu_order)) {
                        $registered_order = array_column($default_submenu, 2);
                        $custom_submenu_order = array_merge(
                            $registered_order,
                            array_diff($custom_submenu_order, $registered_order)
                        );
                    }

                    // Add custom submenu items first, in order
                    $ordered_submenu = [];
                    foreach ($custom_submenu_order as $subcustom_and_new_menu_key) {
                        // add the submenu if it exist in lookup
                        if (isset($submenu_lookup[$subcustom_and_new_menu_key])) {
                            $ordered_submenu[] = $submenu_lookup[$subcustom_and_new_menu_key]['item'];
                        } elseif (isset($submenu_lookup[esc_attr($subcustom_and_new_menu_key)])) {
                            $ordered_submenu[] = $submenu_lookup[esc_attr($subcustom_and_new_menu_key)]['item'];
                        }
                    }
                    // Append any default submenu items not in the custom order or moved submenu
                    foreach ($default_submenu as $default_submenu_item) {
                        if (!in_array($default_submenu_item, $ordered_submenu, true)) {
                            $ordered_submenu[] = $default_submenu_item;
                        }
                    }
                    $final_submenu[$custom_and_new_menu_key] = $ordered_submenu;
                } else {
                    // No custom order for this menu submenus, keep the default submenu
                    $final_submenu[$custom_and_new_menu_key] = $default_submenu;
                }
            }
        }

        return [$final_menu, $final_submenu];
    }

    /**
     * Detect new children without treating deliberately moved submenus as new.
     * The saved order remains authoritative while all registered children are known.
     *
     * @param array $submenu Currently registered children for one parent.
     * @param array $menu_order Saved submenu orders across parents.
     * @return bool
     * @since 2.54.0
     */
    private static function hasNewSubmenus($submenu, $menu_order) {
        $known = [];
        foreach ($menu_order as $children) {
            if (is_array($children)) {
                foreach ($children as $slug) {
                    $known[htmlspecialchars_decode((string) $slug, ENT_QUOTES)] = true;
                }
            }
        }
        foreach ($submenu as $item) {
            if (isset($item[2]) && !isset($known[htmlspecialchars_decode($item[2], ENT_QUOTES)])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Append HTML content (like counters) from old title to new title.
     */
    private static function appendHtmlToTitle($old_title, $new_title) {
        if (preg_match_all('/<[^>]+>.*?<\/[^>]+>/', $old_title, $htmlParts)) {
            return $new_title . ' ' . implode('', $htmlParts[0]);
        }
        return $new_title;
    }

    /**
     * Ajax handler for updating admin menus settings
     */
    public function ajaxUpdateAdminMenuSettings() {

        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        // Verify nonce and capabilities
        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ppc-admin-menu-nonce')) {
            $response['message'] =  __('Security check failed', 'capability-manager-enhanced');
        } elseif (!current_user_can('manage_capabilities_admin_menus')) {
            $response['message'] =  __('Permission denied', 'capability-manager-enhanced');
        } else {
            $show_menu_slug    = !empty($_POST['show_menu_slug']) ? (int)($_POST['show_menu_slug']) : 0;
            $hide_submenu      = !empty($_POST['hide_submenu']) ? (int)($_POST['hide_submenu']) : 0;

            $admin_menu_settings = (array) get_option('ppc_admin_menus_settings', []);
            $admin_menu_settings['show_menu_slug'] = $show_menu_slug;
            $admin_menu_settings['hide_submenu'] = $hide_submenu;

            update_option('ppc_admin_menus_settings', $admin_menu_settings);

            $response['status']  = 'success';
            $response['message'] = __('Settings updated successfully.', 'capability-manager-enhanced');
        }

        wp_send_json($response);
    }

    /**
     * Ajax handler for resetting admin menus
     */
    public function ajaxResetAdminMenu() {

        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        // Verify nonce and capabilities
        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ppc-admin-menu-nonce')) {
            $response['message'] =  __('Security check failed', 'capability-manager-enhanced');
        } elseif (!current_user_can('manage_capabilities_admin_menus')) {
            $response['message'] =  __('Permission denied', 'capability-manager-enhanced');
        } else {
            $reset_order    = !empty($_POST['reset_order']) ? (int)($_POST['reset_order']) : 0;
            $reset_names    = !empty($_POST['reset_names']) ? (int)($_POST['reset_names']) : 0;
            $reset_menu     = !empty($_POST['reset_menu']) ? (int)($_POST['reset_menu']) : 0;

            $menu_action = 0;
            if (!empty($reset_order)) {
                $menu_action++;
                update_option('ppc_admin_menus_order', []);
                $response_message = __('Admin Menu Order reset successfully. Reloading page...', 'capability-manager-enhanced');
            }

            if (!empty($reset_names)) {
                $menu_action++;
                update_option('ppc_admin_menus_names', []);
                $response_message = __('Admin Menu Names reset successfully. Reloading page...', 'capability-manager-enhanced');
            }

            if (!empty($reset_menu)) {
                $menu_action++;
                $this->clearStoredData();
                $response_message = __('Admin Menu Cache reset successfully. Reloading page...', 'capability-manager-enhanced');
            }

            if ($menu_action > 1) {
                $response_message = __('Admin Menu reset successfully. Reloading page...', 'capability-manager-enhanced');
            }

            $response['status']  = 'success';
            $response['redirect']  = admin_url('admin.php?page=pp-capabilities-admin-menus');
            $response['message'] = $response_message;
        }

        wp_send_json($response);
    }

    /**
     * Ajax handler for deleting menu items
     */
    public function ajaxDeleteAdminMenu() {

        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        // Verify nonce and capabilities
        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ppc-admin-menu-nonce')) {
            $response['message'] =  __('Security check failed', 'capability-manager-enhanced');
        } elseif (!current_user_can('manage_capabilities_admin_menus')) {
            $response['message'] =  __('Permission denied', 'capability-manager-enhanced');
        } else {
            $menu_type      = !empty($_POST['menu_type']) ? sanitize_text_field($_POST['menu_type']) : '';
            $menu_slug      = !empty($_POST['menu_slug']) ? sanitize_text_field($_POST['menu_slug']) : '';

            $custom_admin_menus = (array) get_option('ppc_admin_menus_custom_menus', []);

            if ($menu_type == 'menu') {
                if (array_key_exists($menu_slug, $custom_admin_menus['menus'])) {
                    unset($custom_admin_menus['menus'][$menu_slug]);
                }
            } else {
                if (array_key_exists($menu_slug, $custom_admin_menus['submenus'])) {
                    unset($custom_admin_menus['submenus'][$menu_slug]);
                }
            }

            // update new menu
            update_option('ppc_admin_menus_custom_menus', $custom_admin_menus);

            $this->clearStoredData();

            $response['status']  = 'success';
            $response['message'] = __('Menu deleted successfully.', 'capability-manager-enhanced');
        }

        wp_send_json($response);
    }

    /**
     * Ajax handler for renaming menu items
     */
    public function ajaxUpdateAdminMenuTitle() {
        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        // Verify nonce and capabilities
        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ppc-admin-menu-nonce')) {
            $response['message'] =  __('Security check failed', 'capability-manager-enhanced');
        } elseif (!current_user_can('manage_capabilities_admin_menus')) {
            $response['message'] =  __('Permission denied', 'capability-manager-enhanced');
        } else {
            $menu_id      = !empty($_POST['menu_id']) ? sanitize_text_field($_POST['menu_id']) : '';
            $menu_title   = !empty($_POST['menu_title']) ? sanitize_text_field($_POST['menu_title']) : '';
            $current_role = !empty($_POST['current_role']) ? sanitize_text_field($_POST['current_role']) : '';
            $menu_type    = !empty($_POST['menu_type']) ? sanitize_text_field($_POST['menu_type']) : '';
            $menu_icon    = !empty($_POST['menu_icon']) ? sanitize_text_field($_POST['menu_icon']) : '';
            $scope        = !empty($_POST['scope']) ? sanitize_text_field($_POST['scope']) : 'role';

            if (empty($menu_id) || empty($menu_title)) {
                $response['message'] = __('Invalid data', 'capability-manager-enhanced');
            } else {
                $admin_menus_names = !empty(get_option('ppc_admin_menus_names')) ? (array) get_option('ppc_admin_menus_names', []) : [];

                if ($scope === 'all') {
                    // Apply to all roles
                    foreach (wp_roles()->roles as $role_name => $role_info) {
                        self::updateMenuAndSubmenuNames($admin_menus_names, $role_name, $menu_id, $menu_type, $menu_title, $menu_icon);
                    }
                    $response['message'] = __('Menu Name Updated for all roles.', 'capability-manager-enhanced');
                } else {
                    // Apply to current role only
                    self::updateMenuAndSubmenuNames($admin_menus_names, $current_role, $menu_id, $menu_type, $menu_title, $menu_icon);
                    $response['message'] = __('Menu Name Updated.', 'capability-manager-enhanced');
                }

                update_option('ppc_admin_menus_names', $admin_menus_names);

                $response['menu_title'] = $menu_title;
                $response['status']  = 'success';
                $response['message'] = __('Menu Name Updated.', 'capability-manager-enhanced');
            }
        }

        wp_send_json($response);
    }

    /**
     * Ajax callback to update admin menu order
     *
     * @since 3.0.0
     */
    public function ajaxUpdateAdminMenuOrder()
    {
        $response['status']  = 'error';
        $response['message'] = __('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        // Verify nonce
        if (empty($_POST['nonce']) || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'ppc-admin-menu-nonce')) {
            $response['message'] =  __('Security check failed', 'capability-manager-enhanced');
        } elseif (!current_user_can('manage_capabilities_admin_menus')) {
            $response['message'] =  __('Permission denied', 'capability-manager-enhanced');
        } else {
            $menu_groups    = !empty($_POST['menu_groups']) ? map_deep($_POST['menu_groups'], 'sanitize_text_field') : '';

            if (empty($menu_groups)) {
                $response['message'] = __('Invalid data', 'capability-manager-enhanced');
            } else {
                update_option('ppc_admin_menus_order', $menu_groups);
                $response['status']  = 'success';
                $response['message'] = __('Menu Order Updated.', 'capability-manager-enhanced');
            }
        }

        wp_send_json($response);
    }

    private function getCustomMenuSeparatorColorRules($menu_items)
    {
        $css_rules = [];

        foreach ((array) $menu_items as $menu_item) {
            if (empty($menu_item['form_menu_type']) || empty($menu_item['menu_data'][2]) || empty($menu_item['separator_color'])) {
                continue;
            }

            if (!in_array($menu_item['form_menu_type'], ['menu-separator', 'submenu-separator'], true)) {
                continue;
            }

            $separator_color = sanitize_hex_color($menu_item['separator_color']);
            if (empty($separator_color)) {
                continue;
            }

            $separator_class = sanitize_html_class('ppc-admin-menu-item-' . $menu_item['menu_data'][2]);

            $css_rules[] = "#adminmenu li.{$separator_class}.ppc-admin-menu-separator.line-separator .separator:after,"
                . "\n#adminmenu ul.wp-submenu li.{$separator_class}.ppc-admin-menu-separator.line-separator:after {"
                . "\n    border-bottom-color: {$separator_color};"
                . "\n}";
        }

        return $css_rules;
    }

    public function outputCustomMenuSeparatorStyles()
    {
        $custom_menus = (array) get_option('ppc_admin_menus_custom_menus', []);
        $css_rules = [];

        if (!empty($custom_menus['menus'])) {
            $css_rules = array_merge($css_rules, $this->getCustomMenuSeparatorColorRules($custom_menus['menus']));
        }

        if (!empty($custom_menus['submenus'])) {
            $css_rules = array_merge($css_rules, $this->getCustomMenuSeparatorColorRules($custom_menus['submenus']));
        }

        if (empty($css_rules)) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is built from sanitized class names and hex colors.
        echo "<style id='ppc-admin-menu-separator-colors'>\n" . implode("\n", $css_rules) . "\n</style>\n";
    }

    /**
     * Helper function to update both menu and submenu names
     */
    private static function updateMenuAndSubmenuNames(&$admin_menus_names, $role, $menu_id, $menu_type, $menu_title, $menu_icon) {
        // Initialize arrays if they don't exist
        if (!isset($admin_menus_names[$role])) {
            $admin_menus_names[$role] = ['menu' => [], 'submenu' => []];
        }
        if (!isset($admin_menus_names[$role]['menu'])) {
            $admin_menus_names[$role]['menu'] = [];
        }
        if (!isset($admin_menus_names[$role]['submenu'])) {
            $admin_menus_names[$role]['submenu'] = [];
        }

        // Update both menu and submenu entries with the same ID
        if ($menu_type === 'menu') {
            $admin_menus_names[$role]['menu'][$menu_id] = ['title' => $menu_title, 'icon' => $menu_icon];
        } else {
            $admin_menus_names[$role]['submenu'][$menu_id] = ['title' => $menu_title];
        }
    }

    public function addCustomMenu() {
        $custom_menus = get_option('ppc_admin_menus_custom_menus', []);

        if (!empty($custom_menus['menus'])) {
            foreach ($custom_menus['menus'] as $menu_id => $menu_data) {
                $menu_icon = isset($menu_data['menu_data'][6]) ? $menu_data['menu_data'][6] : '';
                $menu_position = !empty($menu_data['menu_position']) ? $menu_data['menu_position'] : 0;

                ppc_add_menu_page(
                    $menu_data['menu_data'][0],
                    $menu_data['menu_data'][0],
                    $menu_data['menu_data'][1],
                    $menu_data['menu_data'][2],
                    '',
                    $menu_icon,
                    $menu_position,
                    $menu_data
                );
            }
        }

        if (!empty($custom_menus['submenus'])) {
            foreach ($custom_menus['submenus'] as $submenu_id => $submenu_data) {
                $submenu_position = !empty($submenu_data['menu_position']) ? $submenu_data['menu_position'] : 0;
                ppc_add_submenu_page(
                    $submenu_data['menu_parent'],
                    $submenu_data['menu_data'][0],
                    $submenu_data['menu_data'][0],
                    $submenu_data['menu_data'][1],
                    $submenu_id,
                    '',
                    $submenu_position,
                    $submenu_data
                );
            }
        }

    }

    public function adminMenuRestriction() {

    global $menu, $submenu, $admin_global_menu, $admin_global_submenu;

    if (is_multisite() && is_super_admin() && !defined('PP_CAPABILITIES_RESTRICT_SUPER_ADMIN')) {
        return;
    }

    if (!pp_capabilities_feature_enabled('admin-menus')) {
        return;
    }

    $ppc_global_menu     = (array)get_option('ppc_admin_menus_menu');
    $ppc_global_submenu  = (array)get_option('ppc_admin_menus_submenu');

    //let add a fallback value just incase
    if(!empty($ppc_global_menu)){
        $admin_global_menu = $ppc_global_menu;
    }else{
        $admin_global_menu 	  = (array)$GLOBALS['menu'];
    }
    if(!empty($ppc_global_submenu)){
        $admin_global_submenu = $ppc_global_submenu;
    }else{
        $admin_global_submenu = (array)$GLOBALS['submenu'];
    }

    /**
     * We need to use global menu when not on capabilities
     * admin menu page to have current role(e.g author) menus
     * instead of saved menu which could be for administrator role.
     */
    if (!isset($_GET['page']) || (isset($_GET['page']) && $_GET['page'] !== 'pp-capabilities-admin-menus')) {
        $admin_global_menu    = (array)$GLOBALS['menu'];
        $admin_global_submenu = (array)$GLOBALS['submenu'];
    }


    if (is_object($admin_global_submenu)) {
        $admin_global_submenu = get_object_vars($admin_global_submenu);
    }

    if (!isset($admin_global_menu) || empty($admin_global_menu)) {
        $admin_global_menu = $menu;
    }

    if (!isset($admin_global_submenu) || empty($admin_global_submenu)) {
        $admin_global_submenu = $submenu;
    }

    //return if not admin page
    if (!is_admin()) {
        return;
    }

    //return if it's ajax request
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    $remove_menu = true;
    //We need to exclude restriction on Admin Menu Restrictions and use css instead due to new ways of getting menus to support custom menus
    if (isset($_GET['page']) && $_GET['page'] === 'pp-capabilities-admin-menus') {
        $remove_menu = false;
    }

    $disabled_menu 		 	 = '';
    $disabled_child_menu 	 = '';
    $user_roles			 	 = wp_get_current_user()->roles;

    // Support plugin integrations by allowing additional role-based limitations to be applied to user based on external criteria
    $user_roles = apply_filters('pp_capabilities_admin_menu_apply_role_restrictions', $user_roles, compact('menu', 'submenu'));

    $admin_menu_option = !empty(get_option('capsman_admin_menus'))
    ? array_intersect_key((array)get_option('capsman_admin_menus'), array_fill_keys($user_roles, true)) : [];

    $admin_child_menu_option = !empty(get_option('capsman_admin_child_menus'))
    ? array_intersect_key((array)get_option('capsman_admin_child_menus'), array_fill_keys($user_roles, true)) : [];

    foreach ($admin_child_menu_option as $role => $disabled_menus) {
        $admin_child_menu_option[$role] = array_values(array_unique(array_map(
            [self::class, 'normalize_menu_identifier'],
            (array) $disabled_menus
        )));
    }

    /*
     * PublishPress Permissions: Restrict Nav Menus for a Permission Group
     * (Integrate PublishPress Capabilities Pro functionality).
     *
     * Copy into functions.php, modifying $restriction_role and $permission_group_ids to match your usage.
     *
     * note: Restriction_role can be an extra role that you create just for these menu restrictions.
     *       Configure Capabilities > Nav Menus as desired for that role.
     */
    /*
    add_filter('pp_capabilities_admin_menu_apply_role_restrictions',
        function($roles) {
            if (function_exists('presspermit')) {
                $permission_group_ids = [12, 14, 15];   // group IDs to restrict
                $restriction_role = 'subscriber';       // role that has restrictions defined by Capabilities > Nav Menus

                if (array_intersect(
                    array_keys(\presspermit()->getUser()->groups['pp_group']),
                    $permission_group_ids
                )) {
                    $roles []= $restriction_role;
                }
            }

            return $roles;
        }
    );
    */

    //extract disabled menu for roles user belong
    $disabled_menu_array = [];
    $disabled_child_menu_array = [];

    foreach ($admin_menu_option as $disabled_menus) {
        $disabled_menu_array = array_merge($disabled_menu_array, (array) $disabled_menus);
    }

    foreach ($admin_child_menu_option as $disabled_menus) {
        $disabled_child_menu_array = array_merge($disabled_child_menu_array, (array) $disabled_menus);
    }

    // Case of multiple user roles: If restriction priority is disabled, don't prevent access if any user role is unrestricted
    if (count($user_roles) > 1 && !get_option('cme_admin_menus_restriction_priority', 1)) {
        foreach ($disabled_menu_array as $disabled_menu) {
            foreach ($user_roles as $role) {
                if (empty($admin_menu_option[$role]) || (!in_array($disabled_menu, $admin_menu_option[$role]))) {
                    $disabled_menu_array = array_diff($disabled_menu_array, (array) $disabled_menu);
                    continue 2;
                }
            }
        }

        foreach ($disabled_child_menu_array as $disabled_menu) {
            foreach ($user_roles as $role) {
                if (empty($admin_child_menu_option[$role]) || (!in_array($disabled_menu, $admin_child_menu_option[$role]))) {
                    $disabled_child_menu_array = array_diff($disabled_child_menu_array, (array) $disabled_menu);
                    continue 2;
                }
            }
        }
    }

    // if users.php menu is disabled, also disable profile.php
    if (in_array('users.php', $disabled_menu_array)) {
        $disabled_menu_array []= 'profile.php';
    }

    // Include yoast seo menu and submenu for all slug
    if (in_array('wpseo_dashboard', $disabled_menu_array)) {
        $disabled_menu_array []= 'wpseo_workouts';
    }

    // deal with discrepancy between users.php > profile.php submenu location stored by Administrator vs. profile.php > profile.php loaded by limited users
    if (in_array('users.php15', $disabled_child_menu_array)) {
        if (empty($admin_global_submenu['users.php']) || empty($admin_global_submenu['users.php'][15])) {
            $disabled_child_menu_array[]= 'profile.php5';
        }
    }

    global $removed_menu_items, $removed_submenu_items;
    $removed_menu_items      = [];
    $removed_submenu_items   = [];
    foreach ($admin_global_menu as $key => $item) {
        if (isset($item[2])) {
            $menu_slug = $item[2];
            $manage_capabilities_menu = (in_array($menu_slug, ['pp-capabilities-roles', 'pp-capabilities-dashboard']) && (current_user_can('manage_capabilities') || current_user_can('manage_capabilities_admin_menus'))) ? true : false;

            //remove menu and prevent page access if set
            if (!$manage_capabilities_menu && in_array($menu_slug, $disabled_menu_array)) {
                $removed_menu_items []= $menu_slug;
                if($remove_menu){
                    remove_menu_page($menu_slug);
                    pp_capabilities_admin_menu_access($menu_slug);
                    unset($submenu[$menu_slug]);
                }
            }

            $check_slugs = [$menu_slug];
            if ('users.php' == $menu_slug) {
                $check_slugs []= 'profile.php';
            }

            $restriction_lookup = $this->build_restriction_lookup_table($disabled_child_menu_array);

            foreach($check_slugs as $menu_slug) {
                //remove menu and prevent page access if set
                if (isset($admin_global_submenu) && !empty($admin_global_submenu[$menu_slug])) {

                    foreach ($admin_global_submenu[$menu_slug] as $subindex => $subitem) {
                        if (isset($subitem[2])) {
                            $submenu_slug = $subitem[2];
                            $new_format = $menu_slug . '|' . $submenu_slug;
                            $old_format = $menu_slug . $subindex;

                            // Get all possible identifiers for this submenu
                            $current_identifiers = array_unique([
                                $new_format,
                                $old_format,
                                $submenu_slug,
                                self::normalize_menu_identifier($new_format),
                                self::normalize_menu_identifier($old_format),
                                self::normalize_menu_identifier($submenu_slug)
                            ]);

                            $should_remove = false;
                            foreach ($current_identifiers as $current_identifier) {
                                if (isset($restriction_lookup[$current_identifier]) || isset($restriction_lookup[htmlspecialchars($current_identifier, ENT_QUOTES)])) {
                                    $should_remove = true;
                                    break;
                                }
                            }

                            if ($should_remove) {
                                if (isset($subitem[2])) {
                                    $removed_submenu_items[$menu_slug] = $subitem[2];
                                    if($remove_menu){
                                        remove_submenu_page($menu_slug, $subitem[2]);

                                        if (in_array(strtolower($subitem[1]), ['customize', 'customizer']) && !empty($_SERVER['REQUEST_URI'])) {
                                            /**
                                             * To use remove_submenu_page to remove theme customizer,
                                             * the url must match what is being exactly linked in the
                                             * admin for that function to work.
                                             *
                                             * See related issue: https://github.com/publishpress/PublishPress-Capabilities/issues/290
                                             */
                                            remove_submenu_page( $menu_slug, 'customize.php?return=' . urlencode(esc_url_raw($_SERVER['REQUEST_URI'])));
                                        }
                                        pp_capabilities_admin_menu_access(self::normalize_menu_identifier($subitem[2]));
                                    }
                                }
                                unset($menu[$menu_slug]);
                            }
                        }
                    }
                }
            }
        }

        /**
         * We need to force JavaScript removal of all menus for
         * some custom menu coming from Menu Editor plugin that's not
         * available in global
         */
        $removed_menu_items = $disabled_menu_array;
    }

    /**
     * due to conflict with custom menu which makes this function run earlier,
     * we need to provide an additional measure to remove custom menus just
     * incase they're added late. This is only UI related as
     * pp_capabilities_admin_menu_access() will always block access to the
     * page irrespective of then they added the menu
     *
     * @since 2.3.1
     */
     add_action('admin_footer', function () {
        global $removed_menu_items, $removed_submenu_items;

        $removed_menu_js = '';

        // Handle menu items
        foreach ($removed_menu_items as $menu_slug) {
            $original_slug = $menu_slug; // Original copy
            $normalized_slug = $menu_slug; // Modified/normalized copy

            // Check if the slug is a query string or direct link
            if (strpos($menu_slug, '?') === false && strpos($menu_slug, '/') === false) {
                // Normalize slugs that are plain (e.g., "roles-manager")
                $normalized_slug = 'admin.php?page=' . $menu_slug;
            }

            // Generate JS selectors for both original and normalized slugs
            $removed_menu_js .= "$(\"#adminmenu li a[href='" . esc_js($original_slug) . "']\").closest('li').remove(); ";
            if ($normalized_slug !== $original_slug) {
                $removed_menu_js .= "$(\"#adminmenu li a[href='" . esc_js($normalized_slug) . "']\").closest('li').remove(); ";
            }
        }

        // Handle submenu items
        foreach ($removed_submenu_items as $menu_slug => $submenu_url) {
            $original_menu_slug = $menu_slug;
            $normalized_menu_slug = $menu_slug;

            // Normalize main menu slug if needed
            if (strpos($menu_slug, '?') === false && strpos($menu_slug, '/') === false) {
                $normalized_menu_slug = 'admin.php?page=' . $menu_slug;
            }

            // Generate JS selectors for both original and normalized slugs
            $removed_menu_js .= "$(\"#adminmenu li a[href='" . esc_js($original_menu_slug) . "']\").closest('li').find(\"ul li a[href='" . esc_js($submenu_url) . "']\").closest('li').remove(); ";
            if ($normalized_menu_slug !== $original_menu_slug) {
                $removed_menu_js .= "$(\"#adminmenu li a[href='" . esc_js($normalized_menu_slug) . "']\").closest('li').find(\"ul li a[href='" . esc_js($submenu_url) . "']\").closest('li').remove(); ";
            }
        }
        // the esc function is turning & to &amp;
        $removed_menu_js = str_replace('&amp;', '&', $removed_menu_js);
        ?>
        <script type="text/javascript">
        (function ($) {
            $(document).ready(function () {
                <?php echo $removed_menu_js; ?>
            });
        })(jQuery);
        </script>
        <?php
        });
    }
}

\PublishPress\Capabilities\Admin_Menus_Filters::instance();
