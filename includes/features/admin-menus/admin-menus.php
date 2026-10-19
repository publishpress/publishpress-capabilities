<?php
/**
 * Capability Manager Admin Menu Permissions.
 * Hide and block selected Admin Menus per-role.
 *
 *    Copyright 2020, PublishPress <help@publishpress.com>
 *
 *    This program is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU General Public License
 *    version 2 as published by the Free Software Foundation.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

global $capsman, $menu, $submenu, $admin_global_menu, $admin_global_submenu;

$can_add_menu = (bool) apply_filters('pp_capabilities_admin_menus_new_menu_enabled', false);

$ppc_global_menu     = get_option('ppc_admin_menus_menu', []);
$ppc_global_menu     = is_array($ppc_global_menu) ? $ppc_global_menu : [];
$ppc_global_submenu  = get_option('ppc_admin_menus_submenu', []);
$ppc_global_submenu  = is_array($ppc_global_submenu) ? $ppc_global_submenu : [];
$custom_menus        = (array)get_option('ppc_admin_menus_custom_menus', []);
$all_custom_menus    = !empty($custom_menus['menus']) ? $custom_menus['menus'] : [];
$all_custom_submenus = !empty($custom_menus['submenus']) ? $custom_menus['submenus'] : [];

$admin_menu_settings = (array) get_option('ppc_admin_menus_settings', []);
$show_menu_slug      = !empty($admin_menu_settings['show_menu_slug']);
$hide_submenu        = !empty($admin_menu_settings['hide_submenu']);


// We're getting menu issue when admin menus are restricted. So, we need to use menu before restriction

if (is_array($admin_global_menu) && count($admin_global_menu) > count($menu)) {
    $menu   = $admin_global_menu;
    $ppc_global_menu   = $admin_global_menu;
}

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore complete submenu discovery for the restrictions editor.
$submenu = PublishPress\Capabilities\Admin_Menus_Filters::mergeSubmenuSnapshots(
    (array) $admin_global_submenu,
    (array) $submenu
);
$ppc_global_submenu = PublishPress\Capabilities\Admin_Menus_Filters::mergeSubmenuSnapshots(
    $ppc_global_submenu,
    $submenu
);

// grouping original menu by their slug to get original position key
$grouped_menu       = [];
$grouped_submenu    = [];
if (is_array($menu)) {
    $grouped_menu = array_reduce($menu, function($carry, $menu_data) use($menu) {
        $carry[$menu_data[2]] = [
            'position' => array_search($menu_data, $menu),
            'menu_data' => $menu_data
        ];
        return $carry;
    }, []);
}
if (is_array($submenu)) {
    $grouped_submenu = array_map(function ($children) {
        return array_reduce($children, function ($carry, $menu_data) use ($children) {
            $position = array_search($menu_data, $children, true);
            $carry[$menu_data[2]] = [
                'position' => $position,
                'menu_data' => $menu_data,
            ];
            return $carry;
        }, []);
    }, $submenu);
}

$roles = $capsman->roles;
$default_role = $capsman->current;

// update menu and submenu for current role
$role_menu = PublishPress\Capabilities\Admin_Menus_Filters::getRoleAdminMenu($ppc_global_menu, $ppc_global_submenu, [$default_role]);
$ppc_global_menu = $role_menu['menu'];
$ppc_global_submenu = $role_menu['submenu'];

$role_caption = translate_user_role($roles[$default_role]);

$admin_menu_option = !empty(get_option('capsman_admin_menus')) ? get_option('capsman_admin_menus') : [];
$admin_menu_option = array_key_exists($default_role, $admin_menu_option) ? (array)$admin_menu_option[$default_role] : [];

$admin_child_menu_option = !empty(get_option('capsman_admin_child_menus')) ? get_option('capsman_admin_child_menus') : [];
$admin_child_menu_option = array_key_exists($default_role, $admin_child_menu_option) ? (array)$admin_child_menu_option[$default_role] : [];
$admin_child_menu_option = array_map(
    ['\\PublishPress\\Capabilities\\Admin_Menus_Filters', 'normalize_menu_identifier'],
    $admin_child_menu_option
);
?>

<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper admin-menus">
    <div id="icon-capsman-admin" class="icon32"></div>
    <h2><?php esc_html_e('Admin Menus', 'capability-manager-enhanced'); ?></h2>

    <form method="post" id="ppc-admin-menu-form" action="admin.php?page=pp-capabilities-admin-menus">
        <?php wp_nonce_field('pp-capabilities-admin-menus'); ?>
        <div class="pp-columns-wrapper pp-enable-sidebar clear">
                <div class="pp-column-left">
                <fieldset>
                    <table id="akmin">
                        <tr>
                            <td class="content">

                                <div class="publishpress-filters">
                                    <select name="ppc-admin-menu-role" class="ppc-admin-menu-role">
                                        <?php
                                        foreach ($roles as $role_name => $name) :
                                            $name = translate_user_role($name);
                                            ?>
                                            <option value="<?php echo esc_attr($role_name);?>" <?php selected($default_role, $role_name);?>><?php echo esc_html($name);?></option>
                                        <?php
                                        endforeach;
                                        ?>
                                    </select> &nbsp;

                                    <img class="loading" src="<?php echo esc_url($capsman->mod_url); ?>/images/wpspin_light.gif" style="display: none">

                                    <input type="submit" name="admin-menu-submit"
                                        value="<?php esc_attr_e('Save Changes') ?>"
                                        class="button-primary ppc-admin-menu-submit" style="float:right" />

                                    <input type="submit" name="admin-menu-all-submit"
                                        value="<?php esc_attr_e('Save for All Roles', 'capability-manager-enhanced') ?>"
                                        class="button-secondary ppc-admin-menu-submit" style="float:right; margin-right: 10px;" />
                                </div>

                                <div id="pp-capability-menu-wrapper" class="postbox">
                                    <div class="pp-capability-menus">

                                        <div class="pp-capability-menus-wrap">
                                            <div id="pp-capability-menus-general"
                                                class="pp-capability-menus-content editable-role" style="display: block;">

                                                <div class="ppc-new-menu-form">
                                                    <div class="admin-menu-response-message"></div>
                                                    <div class="new-menu-fields<?php echo $can_add_menu ? '' : ' ppc-new-menu-promo'; ?>" style="display: none;">
                                                        <?php if (!$can_add_menu): ?>
                                                            <div class="pp-promo-overlay-row ppc-new-menu-promo-overlay">
                                                                <div class="pp-promo-upgrade-notice">
                                                                    <p><?php esc_html_e('Add custom menu links, submenu links, and separators to the WordPress admin menu. This feature is available in PublishPress Capabilities Pro.', 'capability-manager-enhanced'); ?></p>
                                                                    <p><a href="https://publishpress.com/links/capabilities-banner" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Upgrade to Pro', 'capability-manager-enhanced'); ?></a></p>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                        <table class="menu-form-table" <?php echo $can_add_menu ? '' : 'inert'; ?>>
                                                            <tr>
                                                                <td>
                                                                    <label for="menu-type"><?php esc_html_e('Type:', 'capability-manager-enhanced'); ?></label>
                                                                    <select id="menu-type" class="menu-type">
                                                                        <option value="menu"><?php esc_html_e('Menu Link', 'capability-manager-enhanced'); ?></option>
                                                                        <option value="submenu"><?php esc_html_e('Submenu Link', 'capability-manager-enhanced'); ?></option>
                                                                        <option value="menu-separator"><?php esc_html_e('Menu Separator', 'capability-manager-enhanced'); ?></option>
                                                                        <option value="submenu-separator"><?php esc_html_e('Submenu Separator', 'capability-manager-enhanced'); ?></option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <label for="menu-position"><?php esc_html_e('Position:', 'capability-manager-enhanced'); ?></label>
                                                                    <select id="menu-position" class="menu-position">
                                                                        <option value="after"><?php esc_html_e('After', 'capability-manager-enhanced'); ?></option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <label for="add-menu-after">&nbsp;</label>
                                                                    <select id="add-menu-after" class="add-menu-after"></select>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <label for="menu-title">
                                                                        <?php esc_html_e('Menu Title:', 'capability-manager-enhanced'); ?>
                                                                        <span class="required">*</span>
                                                                    </label>
                                                                    <input type="text" id="menu-title" class="menu-title" value="<?php esc_attr_e('My Custom Menu', 'capability-manager-enhanced'); ?>" />
                                                                    <span class="required required-message hidden-element">
                                                                        <?php esc_html_e('Menu Title is required.', 'capability-manager-enhanced'); ?>
                                                                    </span>

                                                                    <label for="separator-style" class="hidden-element"><?php esc_html_e('Separator Style', 'capability-manager-enhanced'); ?></label>
                                                                    <select id="separator-style" class="separator-style hidden-element">
                                                                        <option value="line"><?php esc_html_e('Line (____________________)', 'capability-manager-enhanced'); ?></option>
                                                                        <option value="empty"><?php esc_html_e('Empty Space', 'capability-manager-enhanced'); ?></option>
                                                                        <option value="text"><?php esc_html_e('Text', 'capability-manager-enhanced'); ?></option>

                                                                    </select>

                                                                    <label for="separator-color" class="hidden-element"><?php esc_html_e('Separator Color', 'capability-manager-enhanced'); ?></label>
                                                                    <input type="color" id="separator-color" class="separator-color hidden-element" value="#ffffff" />
                                                                </td>
                                                                <td colspan="2">
                                                                    <label for="menu-url">
                                                                        <?php esc_html_e('Menu URL:', 'capability-manager-enhanced'); ?>
                                                                        <div class="ppc-tool-tip">
                                                                            <span class="dashicons dashicons-editor-help"></span>
                                                                            <div class="tool-tip-text">
                                                                                <p><?php printf(__( 'Enter an internal link such as %1s or an external link such as %2s. Any link can only be used once for a Menu URL.', 'capability-manager-enhanced' ), '<strong>/wp-admin/edit.php</strong>', '<strong>https://wordpress.org</strong>' ); ?></p>
                                                                                <i></i>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                    <input type="text" value="" id="menu-url" class="menu-url" />
                                                                    <span class="required required-message hidden-element">
                                                                        <?php esc_html_e('Menu with this url already exist.', 'capability-manager-enhanced'); ?>
                                                                    </span></tr>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>
                                                                    <label for="menu-capability">
                                                                        <?php esc_html_e('Menu Capability:', 'capability-manager-enhanced'); ?>
                                                                        <span class="required">*</span>
                                                                        <div class="ppc-tool-tip">
                                                                            <span class="dashicons dashicons-editor-help"></span>
                                                                            <div class="tool-tip-text">
                                                                                <p><?php printf(__( 'This is the capability required to see this menu. For example: %1s or %2s If you\'re entering a custom capability, remember to add and assign it to preferred roles on the capabilities page', 'capability-manager-enhanced' ), '<strong>read</strong>', '<strong>manage_options</strong>' ); ?></p>
                                                                                    <i></i>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                    <input type="text" id="menu-capability" class="menu-capability" value="read" />
                                                                    <span class="required required-message hidden-element">
                                                                        <?php esc_html_e('Menu Capability is required.', 'capability-manager-enhanced'); ?>
                                                                    </span>
                                                                </td>
                                                                <td style="vertical-align: bottom;">
                                                                    <button type="button" class="button button-small ppc-icon-selector-button select-icon-button" data-current="admin-generic"><i class="dashicons dashicons-admin-generic"></i> <span><?php esc_html_e('Menu Icon', 'capability-manager-enhanced'); ?></span></button>
                                                                </td>
                                                                <td style="vertical-align: bottom;">
                                                                    <button style="float: right;" type="button" class="save-new-menu button-primary" <?php disabled(!$can_add_menu); ?>>
                                                                        <span class="spinner admin-menu-spinner"></span>
                                                                        <?php esc_html_e('Save Menu Link', 'capability-manager-enhanced'); ?>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>

                                                <table class="wp-list-table widefat fixed striped pp-capability-menus-select">

                                                    <thead>
                                                        <tr class="ppc-menu-row parent-menu">

                                                            <td class="restrict-column ppc-menu-checkbox">
                                                                <input id="check-all-item" class="check-item check-all-menu-item" type="checkbox"/>
                                                            </td>
                                                            <td class="menu-column ppc-menu-item">
                                                                <label for="check-all-item">
                                                                <span class="menu-item-link check-all-menu-link">
                                                                    <strong>
                                                                    <?php esc_html_e('Toggle all', 'capability-manager-enhanced'); ?>
                                                                    </strong>
                                                                </span></label>
                                                            </td>
                                                            <td class="menu-column ppc-menu-action" style="text-align: right;">
                                                                <div class="add-new-menu button-secondary">
                                                                    <?php esc_html_e('New Menu Link', 'capability-manager-enhanced'); ?>
                                                                </div>
                                                            </td>

                                                        </tr>
                                                    </thead>

                                                    <tfoot>
                                                        <tr class="ppc-menu-row parent-menu">

                                                            <td class="restrict-column ppc-menu-checkbox">
                                                                <input id="check-all-item-2" class="check-item check-all-menu-item" type="checkbox"/>
                                                            </td>
                                                            <td class="menu-column ppc-menu-item">
                                                                <label for="check-all-item-2">
                                                                    <span class="menu-item-link check-all-menu-link">
                                                                    <strong>
                                                                        <?php esc_html_e('Toggle all', 'capability-manager-enhanced'); ?>
                                                                    </strong>
                                                                    </span>
                                                                </label>
                                                            </td>
                                                            <td class="menu-column ppc-menu-action">
                                                            </td>

                                                        </tr>
                                                    </tfoot>

                                                    <tbody class="menu-table-body">

                                                    <?php

                                                    if (isset($ppc_global_menu) && '' !== $ppc_global_menu) {

                                                        ksort($ppc_global_menu);

                                                        if (!get_option('link_manager_enabled')) {
                                                            if (isset($ppc_global_menu[15]) && ('edit-tags.php?taxonomy=link_category' == $ppc_global_menu[15][2])) {
                                                                unset($ppc_global_menu[15]);
                                                            }

                                                            unset($ppc_global_submenu['edit-tags.php?taxonomy=link_category']);
                                                        }

                                                        $sn = 0;

                                                        foreach ($ppc_global_menu as $key => $item) {

                                                            $item_menu_slug = $item[2];

                                                            if ('' === $item_menu_slug) {
                                                                continue;
                                                            }

                                                            if (empty($item[0])) {
                                                                // we need to support separator now for proper menu reordering
                                                                $item[0] = $item_menu_slug;
                                                            }

                                                            //disable capmans checkbox if admin is editing own role
                                                            if ($item_menu_slug === 'pp-capabilities-roles' && in_array($default_role, wp_get_current_user()->roles)) {
                                                                $disabled_field = ' disabled';
                                                                $disabled_class = ' disabled';

                                                                $disabled_info = true;

                                                            } else {
                                                                $disabled_field = $disabled_class = $disabled_info = '';
                                                            }

                                                            $menu_title = ppc_process_admin_menu_title($item[0]);

                                                            // Which icon should we use?
                                                            $icon_name = !empty($item[6]) ? $item[6] : null;

                                                            $icon_type = '';
                                                            $icon_style = '';
                                                            if ($icon_name) {
                                                                // Check if the icon_name starts with 'dashicons-'
                                                                if (strpos($icon_name, 'dashicons-') === 0) {
                                                                    $icon_type = 'dashicons';
                                                                } elseif (strpos($icon_name, 'data:image') === 0) {
                                                                    $icon_type = 'data-image';
                                                                    $icon_style = 'background-repeat: no-repeat;background-position: center;color: #0073aa;background-size: 20px auto;background-image: url(' . $icon_name . ');';
                                                                }
                                                            }

                                                            if (empty($icon_name)) {
                                                                switch(wp_strip_all_tags($item[0])) {
                                                                    default:
                                                                        $icon_name = 'dashicons-open-folder';
                                                                    break;
                                                                case 'Dashboard':
                                                                    $icon_name = 'dashicons-dashboard';
                                                                    break;
                                                                case 'Media':
                                                                    $icon_name = 'dashicons-admin-media';
                                                                    break;
                                                                case 'Links':
                                                                    $icon_name = 'dashicons-admin-links';
                                                                    break;
                                                                case 'Posts':
                                                                    $icon_name = 'dashicons-admin-post';
                                                                    break;
                                                                case 'Pages':
                                                                    $icon_name = 'dashicons-admin-page';
                                                                    break;
                                                                case 'Appearance':
                                                                    $icon_name = 'dashicons-admin-appearance';
                                                                    break;
                                                                case 'Plugins':
                                                                    $icon_name = 'dashicons-admin-plugins';
                                                                    break;
                                                                case 'Comments':
                                                                    $icon_name = 'dashicons-admin-comments';
                                                                    break;
                                                                case 'Users':
                                                                    $icon_name = 'dashicons-admin-users';
                                                                    break;
                                                                case 'Tools':
                                                                    $icon_name = 'dashicons-admin-tools';
                                                                    break;
                                                                case 'Settings':
                                                                    $icon_name = 'dashicons-admin-settings';
                                                                    break;
                                                                case 'Capabilities':
                                                                    $icon_name = 'dashicons-admin-network';
                                                                    break;
                                                                case 'PublishPress Blocks':
                                                                    $icon_name = 'dashicons-layout';
                                                                    break;
                                                                case 'Authors':
                                                                    $icon_name = 'dashicons-groups';
                                                                    break;
                                                                case 'Revisions':
                                                                    $icon_name = 'dashicons-backup';
                                                                    break;
                                                                    break;
                                                                case 'PublishPress':
                                                                    $icon_name = 'dashicons-calendar-alt';
                                                                    break;
                                                                case 'Checklists':
                                                                    $icon_name = 'dashicons-yes-alt';
                                                                    break;
                                                                case 'Notifications':
                                                                    $icon_name = 'dashicons-bell';
                                                                    break;

                                                                }
                                                            }

                                                            $editable_menu = true;

                                                            $additional_class = '';
                                                            $separator_menu = false;
                                                            $text_separator = false;
                                                            if (strpos($item[2], 'separator') === 0) {
                                                                $icon_name = 'dashicons-minus';
                                                                $additional_class .= ' separator';
                                                                $separator_menu = true;
                                                                $text_separator = pp_capabilities_admin_menu_has_css_class($item, 'text-separator');

                                                                if ($text_separator) {
                                                                    $additional_class .= ' text-separator';
                                                                } else {
                                                                    $menu_title = 'separator';
                                                                    $editable_menu = false;
                                                                }
                                                            }

                                                            if (empty($menu_title)) {
                                                                $editable_menu = false;
                                                                $menu_title = __( '(Can\'t detect menu name)', 'capability-manager-enhanced' );
                                                            }

                                                            $item_menu_slug = $item[2];
                                                            $menu_position = isset($grouped_menu[$item[2]]['position']) ? $grouped_menu[$item[2]]['position'] : '';

                                                            $custom_menu = array_key_exists($item[2], $all_custom_menus);
                                                            ?>

                                                            <tr class="ppc-menu-row parent-menu <?php echo esc_attr($additional_class);?>" data-main-slug="<?php echo esc_attr($item_menu_slug);?>" data-position="<?php echo esc_attr($menu_position);?>">

                                                                <td class="restrict-column ppc-menu-checkbox">
                                                                <input id="check-item-<?php echo (int) $sn;?>"<?php echo esc_attr($disabled_field);?> class="check-item" type="checkbox"
                                                                    name="pp_cababilities_disabled_menu<?php echo esc_attr($disabled_class);?>[]"
                                                                    value="<?php echo esc_attr($item_menu_slug);?>"<?php checked(in_array($item_menu_slug, $admin_menu_option));?> />
                                                                </td>
                                                                <td class="menu-column ppc-menu-item <?php echo esc_attr($disabled_class);?>">

                                                                    <label for="check-item-<?php echo esc_attr($sn);?>">
                                                                        <span class="menu-item-link<?php echo (in_array($item_menu_slug, $admin_menu_option)) ? ' restricted' : '';?>">
                                                                        <strong>
                                                                            <i class="dashicons <?php echo esc_attr($icon_name) ?>" style="<?php echo esc_attr($icon_style ); ?>"></i>
                                                                            <span class="menu-title" data-menu-slug="<?php echo esc_attr($item_menu_slug); ?>" data-menu-type="menu">
                                                                                <?php echo esc_html($menu_title); ?>
                                                                            </span>
                                                                            <?php if (!$editable_menu && !$separator_menu):?>
                                                                                <span class="ppc-tool-tip"><span class="dashicons dashicons-info-outline"></span><span class="tool-tip-text"><p><?php esc_html_e('This menu name is not available in menu global data or directly registered with the menu', 'capability-manager-enhanced');?>.</p><i></i></span></span>
                                                                            <?php endif;?>
                                                                            <?php if (!$separator_menu) : ?>
                                                                                <small class="admin-menu-slug ppc-other-menu-element" style="<?php echo $show_menu_slug ? '' : 'display: none;'; ?>">
                                                                                    <?php echo esc_html(pp_capabilities_admin_menu_path($item_menu_slug)); ?>
                                                                                </small>
                                                                            <?php endif; ?>
                                                                            <span class="ppc-admin-menu-rename-form" style="display: none;">
                                                                                <input type="text" class="menu-title-input" value="<?php echo esc_attr($menu_title); ?>">

                <select  class="rename-menu-scope">
                    <option value="role"><?php printf(esc_html__('%s Role', 'capability-manager-enhanced'), $role_caption); ?></option>
                    <option value="all"><?php esc_html_e('All Roles', 'capability-manager-enhanced'); ?></option>
                </select>
                                                                <button type="button" class="button button-small save-menu-title" title="<?php esc_attr_e('Save', 'capability-manager-enhanced'); ?>">
                                                                                    <span class="dashicons dashicons-saved"></span>
                                                                                </button>
                                                                                <button type="button" class="button button-small cancel-menu-title" title="<?php esc_attr_e('Cancel', 'capability-manager-enhanced'); ?>">
                                                                                    <span class="dashicons dashicons-no"></span>
                                                                                </button>
                                                                            </span>
                                                                        </strong>
                                                                    </span>
                                                                    </label>

                                                                    <?php if (!empty($disabled_info)):?><span class="ppc-tool-tip"><span class="dashicons dashicons-info"></span><span class="tool-tip-text"><p> <?php esc_html_e('This option is disabled to prevent complete lockout', 'capability-manager-enhanced');?>.</p><i></i></span></span>
                                                                    <?php endif;?>
                                                                </td>
                                                                <td class="menu-column ppc-menu-item">
                                                                    <div class="pp-admin-menu-buttons">
                                                                        <?php if ($custom_menu) : ?>
                                                                            <div class="ppc-tool-tip click-tooltip">
                                                                                <span class="dashicons dashicons-trash delete"></span>
                                                                                <div class="tool-tip-text">
                                                                                    <p><?php printf(__( 'Are you sure you want to delete %1s? %2s %3s', 'capability-manager-enhanced' ), '<strong>' . $menu_title . '</strong>', '<a class="delete-admin-menu" href="#">'. esc_html__('Delete', 'capability-manager-enhanced') .'</a>', ' | <a class="cancel-click-tooltip" href="#">'. esc_html__('Cancel', 'capability-manager-enhanced') .'</a>' ); ?></p>
                                                                                    <i></i>
                                                                                </div>
                                                                            </div>
                                                                        <?php else : ?>
                                                                            <a style="visibility: hidden;"><span class="dashicons dashicons-edit"></span></a>
                                                                        <?php endif; ?>
                                                                        <div class="ppc-tool-tip">
                                                                            <span class="dashicons dashicons-editor-help"></span>
                                                                            <div class="tool-tip-text">
                                                                                <p><?php printf(__( 'The capability required to access this menu is %s', 'capability-manager-enhanced' ), '<strong>' . esc_html($item[1]) . '</strong>' ); ?></p>
                                                                                <i></i>
                                                                            </div>
                                                                        </div>
                                                                        <?php if ($editable_menu):?>
                                                                            <a class="rename-admin-menu" href="#" title="<?php esc_html_e('Rename Menu', 'capability-manager-enhanced');?>">
                                                                                <span class="dashicons dashicons-edit"></span>
                                                                            </a>
                                                                        <?php else : ?>
                                                                            <a style="visibility: hidden;"><span class="dashicons dashicons-edit"></span></a>
                                                                        <?php endif; ?>
                                                                        <a class="move-admin-menu up" href="#" title="<?php esc_html_e('Move Up', 'capability-manager-enhanced');?>">
                                                                            <span class="dashicons dashicons-arrow-up-alt2"></span>
                                                                        </a>
                                                                        <a class="move-admin-menu down" href="#" title="<?php esc_html_e('Move Down', 'capability-manager-enhanced');?>">
                                                                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                                                                        </a>
                                                                    </div>
                                                                </td>

                                                            </tr>

                                                            <?php
                                                            if (!isset($ppc_global_submenu[$item_menu_slug])) {
                                                                continue;
                                                            }


                                                            $last_subitem = false;
                                                            $last_sn = 0;
                                                            $legacy_item_menu_slug = $item_menu_slug;
                                                            foreach ($ppc_global_submenu[$item_menu_slug] as $subkey => $subitem) {
                                                                $sn++;
                                                                $submenu_slug = $subitem[2];
                                                                $parent_menu_slug = $item_menu_slug;

                                                                //disable pp-capabilities-admin-menus checkbox if admin is editing own role
                                                                if ( $submenu_slug === 'pp-capabilities-admin-menus' && in_array($default_role, wp_get_current_user()->roles)) {
                                                                    $disabled_field = ' disabled';
                                                                    $disabled_class = ' disabled';

                                                                    $disabled_info = true;

                                                                } else {
                                                                    $disabled_field = $disabled_class = $disabled_info = '';
                                                                }

                                                                $sub_menu_value = $parent_menu_slug . '|' . htmlspecialchars_decode($submenu_slug);

                                                                // support for legacy option with bugs that reset parent slug
                                                                $sub_menu_value_old = $legacy_item_menu_slug . $subkey;
                                                                $legacy_item_menu_slug = $subitem[2];

                                                                $is_checked = in_array(
                                                                    \PublishPress\Capabilities\Admin_Menus_Filters::normalize_menu_identifier($sub_menu_value),
                                                                    $admin_child_menu_option,
                                                                    true
                                                                ) || in_array(
                                                                    \PublishPress\Capabilities\Admin_Menus_Filters::normalize_menu_identifier($sub_menu_value_old),
                                                                    $admin_child_menu_option,
                                                                    true
                                                                );

                                                                $sub_menu_title = ppc_process_admin_menu_title($subitem[0]);

                                                                $menu_position = isset($grouped_submenu[$item[2]][$subitem[2]]['position']) ? $grouped_submenu[$item[2]][$subitem[2]]['position'] : '';

                                                                $custom_menu = array_key_exists($subitem[2], $all_custom_submenus);

                                                                $editable_menu = true;

                                                                $icon_name = 'dashicons-arrow-right';
                                                                $additional_class = '';
                                                                $separator_menu = false;
                                                                $text_separator = false;
                                                                if (strpos($subitem[2], 'separator') === 0 || strpos($subitem[2], 'simple_tagsst_separator') === 0) {
                                                                    $icon_name = 'dashicons-minus';
                                                                    $additional_class .= ' separator';
                                                                    $separator_menu = true;
                                                                    $text_separator = pp_capabilities_admin_menu_has_css_class($subitem, 'text-separator');

                                                                    if ($text_separator) {
                                                                        $additional_class .= ' text-separator';
                                                                    } else {
                                                                        $sub_menu_title = 'separator';
                                                                        $editable_menu = false;
                                                                    }
                                                                }

                                                                if (empty($sub_menu_title)) {
                                                                    $editable_menu = false;
                                                                    $sub_menu_title = __( '(Can\'t detect menu name)', 'capability-manager-enhanced' );
                                                                }
                                                                ?>
                                                                <tr class="ppc-menu-row child-menu <?php echo esc_attr($additional_class);?>" data-main-slug="<?php echo esc_attr($submenu_slug);?>"  data-position="<?php echo esc_attr($menu_position);?>" data-parent-slug="<?php echo esc_attr($parent_menu_slug);?>" style="<?php echo $hide_submenu ? 'display: none;' : ''; ?>">

                                                                    <td class="restrict-column ppc-menu-checkbox">
                                                                        <input id="check-item-<?php echo esc_attr($sn);?>"<?php echo esc_attr($disabled_field);?> class="check-item" type="checkbox"
                                                                            name="pp_cababilities_disabled_child_menu<?php echo esc_attr($disabled_class);?>[]"
                                                                            value="<?php echo esc_attr($sub_menu_value);?>"
                                                                            data-legacy-value="<?php echo esc_attr($sub_menu_value_old); ?>"
                                                                            <?php checked($is_checked); ?>
                                                                            data-val="<?php echo esc_attr($sub_menu_value);?>" />
                                                                    </td>
                                                                    <td class="menu-column ppc-menu-item'<?php echo esc_attr($disabled_class);?>">

                                                                        <label for="check-item-<?php echo esc_attr($sn);?>">
                                                                            <span class="menu-item-link<?php echo ($is_checked) ? ' restricted' : '';?>">
                                                                            <strong>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <i class="dashicons <?php echo esc_attr($icon_name); ?>"></i>
                                                                            <span class="menu-title" data-menu-slug="<?php echo esc_attr($sub_menu_value); ?>" data-menu-type="submenu" data-parent-slug="<?php echo esc_attr($parent_menu_slug); ?>">
                                                                                <?php echo esc_html($sub_menu_title); ?>
                                                                            </span>
                                                                            <?php if (!$editable_menu && !$separator_menu):?>
                                                                                <span class="ppc-tool-tip"><span class="dashicons dashicons-info-outline"></span><span class="tool-tip-text"><p><?php esc_html_e('This menu name is not available in menu global data or directly registered with the menu', 'capability-manager-enhanced');?>.</p><i></i></span></span>
                                                                            <?php endif;?>
                                                                            <?php if (!$separator_menu) : ?>
                                                                                <small class="admin-menu-slug ppc-other-menu-element" style="<?php echo $show_menu_slug ? '' : 'display: none;'; ?>">
                                                                                    <?php echo esc_html(pp_capabilities_admin_menu_path($submenu_slug)); ?>
                                                                                </small>
                                                                            <?php endif; ?>
                                                                            <span class="ppc-admin-menu-rename-form" style="display: none;">
                                                                                <input type="text" class="menu-title-input" value="<?php echo esc_attr($sub_menu_title); ?>">
                                                                                <select  class="rename-menu-scope">
                    <option value="role"><?php printf(esc_html__('%s Role', 'capability-manager-enhanced'), $role_caption); ?></option>
                    <option value="all"><?php esc_html_e('All Roles', 'capability-manager-enhanced'); ?></option>
                </select>

                                                                           <button type="button" class="button button-small save-menu-title" title="<?php esc_attr_e('Save', 'capability-manager-enhanced'); ?>">
                                                                                    <span class="dashicons dashicons-saved"></span>
                                                                                </button>
                                                                                <button type="button" class="button button-small cancel-menu-title" title="<?php esc_attr_e('Cancel', 'capability-manager-enhanced'); ?>">
                                                                                    <span class="dashicons dashicons-no"></span>
                                                                                </button>
                                                                            </span>
                                                                            </strong></span>
                                                                        </label>

                                                                        <?php if (!empty($disabled_info)):?>
                                                                            <span class="ppc-tool-tip"><span class="dashicons dashicons-info"></span><span class="tool-tip-text"><p><?php esc_html_e('This option is disabled to prevent complete lockout', 'capability-manager-enhanced');?>.</p><i></i></span></span>
                                                                        <?php endif;?>
                                                                    </td>
                                                                    <td class="menu-column ppc-menu-item">
                                                                        <div class="pp-admin-menu-buttons">
                                                                            <?php if ($custom_menu) : ?>
                                                                                <div class="ppc-tool-tip click-tooltip">
                                                                                    <span class="dashicons dashicons-trash delete"></span>
                                                                                    <div class="tool-tip-text">
                                                                                        <p><?php printf(__( 'Are you sure you want to delete %1s? %2s %3s', 'capability-manager-enhanced' ), '<strong>' . $sub_menu_title . '</strong>', '<a class="delete-admin-menu" href="#">'. esc_html__('Delete', 'capability-manager-enhanced') .'</a>', ' | <a class="cancel-click-tooltip" href="#">'. esc_html__('Cancel', 'capability-manager-enhanced') .'</a>' ); ?></p>
                                                                                        <i></i>
                                                                                    </div>
                                                                                </div>
                                                                            <?php else : ?>
                                                                                <a style="visibility: hidden;"><span class="dashicons dashicons-edit"></span></a>
                                                                            <?php endif; ?>
                                                                            <div class="ppc-tool-tip">
                                                                                <span class="dashicons dashicons-editor-help"></span>
                                                                                <div class="tool-tip-text">
                                                                                    <p><?php printf(__( 'The capability required to access this menu is %s', 'capability-manager-enhanced' ), '<strong>' . esc_html($subitem[1]) . '</strong>' ); ?></p>
                                                                                    <i></i>
                                                                                </div>
                                                                            </div>
                                                                            <?php if ($editable_menu):?>
                                                                                <a class="rename-admin-menu" href="#" title="<?php esc_html_e('Rename Menu', 'capability-manager-enhanced');?>">
                                                                                <span class="dashicons dashicons-edit"></span>
                                                                                </a>
                                                                            <?php else : ?>
                                                                                <a style="visibility: hidden;"><span class="dashicons dashicons-edit"></span></a>
                                                                            <?php endif; ?>
                                                                            <a class="move-admin-menu up" href="#" title="<?php esc_html_e('Move Up', 'capability-manager-enhanced');?>">
                                                                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                                                                            </a>
                                                                            <a class="move-admin-menu down" href="#" title="<?php esc_html_e('Move Down', 'capability-manager-enhanced');?>">
                                                                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                                                                            </a>
                                                                        </div>
                                                                    </td>

                                                                </tr>
                                                            <?php
                                                                if ($last_subitem && ($subitem[2] == $last_subitem[2])) {
                                                                    // If there is ambiguity due to two consective submenu items with the same caption, hide both
                                                                    // Edit: Hide first one
                                                                    ?>
                                                                    <script type="text/javascript">
                                                                    /* <![CDATA[ */
                                                                    /*var elem = document.getElementById('check-item-<?php echo esc_attr($last_sn);?>');
                                                                    var parent = elem.closest('tr');
                                                                    parent.style.display = 'none';*/

                                                                    elem = document.getElementById('check-item-<?php echo esc_attr($sn);?>');
                                                                    parent = elem.closest('tr');
                                                                    parent.style.display = 'none';
                                                                    /* ]]> */
                                                                    </script>
                                                                    <?php
                                                                }

                                                                $last_subitem = $subitem;
                                                                $last_sn = $sn;

                                                            }  // end foreach ppc_global_submenu

                                                            $sn++;

                                                        } // end foreach ppc_global_menu

                                                    } else {
                                                        ?>
                                                        <tr><td style="color: red;"> <?php esc_html_e('No menu found', 'capability-manager-enhanced');?></td></tr>
                                                        <?php
                                                    }

                                                    ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <input type="submit" name="admin-menu-submit"
                                    value="<?php esc_attr_e('Save Changes') ?>"
                                    class="button-primary ppc-admin-menu-submit" style="float: right; margin-top: 10px;"/>
                                <input type="submit" name="admin-menu-all-submit"
                                    value="<?php esc_attr_e('Save for All Roles', 'capability-manager-enhanced') ?>"
                                    class="button-secondary ppc-admin-menu-submit" style="float: right; margin-top: 10px; margin-right: 10px;"/>
                            </td>
                        </tr>
                    </table>

                </fieldset>
            </div><!-- .pp-column-left -->
            <div class="pp-column-right pp-capabilities-sidebar">
            <?php
            $banner_title  = __('How to use Admin Menus', 'capability-manager-enhanced');
            $banner_messages = ['<p>'];
            $banner_messages[] = esc_html__('Admin Menus allows you to edit the admin menu links and control who has access.', 'capability-manager-enhanced');
            $banner_messages[] = '</p><p>';
            $banner_messages[] = '<input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" disabled> = '
                . esc_html__('No change', 'capability-manager-enhanced') . ' <br />';
            $banner_messages[] = '<input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" checked disabled> = '
                . esc_html__('This feature is denied', 'capability-manager-enhanced') . ' <br />';
            $banner_messages[] = '</p>';
            $banner_messages[] = '<p><a class="button ppc-checkboxes-documentation-link" href="https://publishpress.com/knowledge-base/admin-menus-screen/"target="blank">' . esc_html__('View Documentation', 'capability-manager-enhanced') . '</a></p>';
            pp_capabilities_sidebox_banner($banner_title, $banner_messages);
            ?>
            <?php
            $banner_title  = __('Admin Menus Settings', 'capability-manager-enhanced');
            $banner_messages = ['<p>'];
            $banner_messages[] = '<input type="checkbox" class="admin-menu-setting-field hide-submenu" ' . checked($hide_submenu, true, false) . '> '
                . esc_html__('Hide Submenus', 'capability-manager-enhanced') . ' <br />';
            $banner_messages[] = '<input type="checkbox" class="admin-menu-setting-field show-menu-slug" ' . checked($show_menu_slug, true, false) . '> '
                . esc_html__('Show Menu Slugs', 'capability-manager-enhanced') . ' <br />';
            $banner_messages[] = '</p>';
            pp_capabilities_sidebox_banner($banner_title, $banner_messages);
            ?>
            <?php
            $banner_title  = __('Reset Admin Menus', 'capability-manager-enhanced');
            $banner_messages = ['<p>'];
            $banner_messages[] = esc_html__('You can reset admin menu order and name to their original position and names. You can also reset menu cache to fix issue of missing menu.', 'capability-manager-enhanced');
            $banner_messages[] = '</p><p>';
            $banner_messages[] = '<input type="checkbox" class="reset-admin-menu-order"> '
                . esc_html__('Reset Menu Order', 'capability-manager-enhanced') . ' <br />';
            $banner_messages[] = '<input type="checkbox" class="reset-admin-menu-cache"> '
                . esc_html__('Reset Cached Menu', 'capability-manager-enhanced') . ' <br />';
            $banner_messages[] = '<input type="checkbox" class="reset-admin-menu-names"> '
                . esc_html__('Reset Menu Names', 'capability-manager-enhanced') . ' <br />';
            $banner_messages[] = '</p>';
            $banner_messages[] = '<p><a style="color: red; border-color: red;" type="button" class="button ppc-reset-admin-menu-options">' . esc_html__('Reset Selected Option', 'capability-manager-enhanced') . '</a><div class="clear"></div></p>';
            pp_capabilities_sidebox_banner($banner_title, $banner_messages);
            ?>
            </div><!-- .pp-column-right -->
        </div><!-- .pp-columns-wrapper -->
    </form>

    <script type="text/javascript">
        /* <![CDATA[ */
        jQuery(document).ready(function ($) {

            // -------------------------------------------------------------
            //   reload page for instant reflection if user is updating own role
            // -------------------------------------------------------------
            <?php if((int)$ppc_admin_menu_reload === 1){ ?>
                window.location = '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-admin-menus&role=' . $default_role . '')); ?>';
            <?php } ?>

            // -------------------------------------------------------------
            //   Set form action attribute to include role
            // -------------------------------------------------------------
            $('#ppc-admin-menu-form').attr('action', '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-admin-menus&role=' . $default_role . '')); ?>');

            // -------------------------------------------------------------
            //   Instant restricted item class
            // -------------------------------------------------------------
            $(document).on('change', '.pp-capability-menus-wrapper .ppc-menu-row .check-item', function () {

                if ($(this).is(':checked')) {
                    //add class if value is checked
                    $(this).closest('tr').find('.menu-item-link').addClass('restricted');

                    //toggle all checkbox
                    if ($(this).hasClass('check-all-menu-item')) {
                        $("input[type='checkbox'][name='pp_cababilities_disabled_menu[]']").prop('checked', true);
                        $("input[type='checkbox'][name='pp_cababilities_disabled_child_menu[]']").prop('checked', true);
                        $('.menu-item-link').addClass('restricted');
                    } else {
                        $('.check-all-menu-link').removeClass('restricted');
                        $('.check-all-menu-item').prop('checked', false);
                    }

                } else {
                    //unchecked value
                    $(this).closest('tr').find('.menu-item-link').removeClass('restricted');

                    //toggle all checkbox
                    if ($(this).hasClass('check-all-menu-item')) {
                        $("input[type='checkbox'][name='pp_cababilities_disabled_menu[]']").prop('checked', false);
                        $("input[type='checkbox'][name='pp_cababilities_disabled_child_menu[]']").prop('checked', false);
                        $('.menu-item-link').removeClass('restricted');
                    } else {
                        $('.check-all-menu-link').removeClass('restricted');
                        $('.check-all-menu-item').prop('checked', false);
                    }

                }

            });

            // -------------------------------------------------------------
            //   Load selected roles menu
            // -------------------------------------------------------------
            $(document).on('change', '.pp-capability-menus-wrapper .ppc-admin-menu-role', function () {

                //disable select
                $('.pp-capability-menus-wrapper .ppc-admin-menu-role').attr('disabled', true);

                //hide button
                $('.pp-capability-menus-wrapper .ppc-admin-menu-submit').hide();

                //show loading
                $('#pp-capability-menu-wrapper').hide();
                $('div.publishpress-caps-manage img.loading').show();

                //go to url
                window.location = '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-admin-menus&role=')); ?>' + $(this).val() + '';

            });
        });
        /* ]]> */
    </script>

    <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
        cme_publishpressFooter();
    }
    ?>
</div>
<?php
