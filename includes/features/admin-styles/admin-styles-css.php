<?php
/**
 * Custom color scheme CSS endpoint
 * Direct URL: /wp-content/plugins/capabilities-pro/includes/features/admin-styles/admin-styles-css.php
 */

// Prevent direct access without the parameter
if (!isset($_GET['ppc_custom_scheme']) || $_GET['ppc_custom_scheme'] !== '1') {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// Define WordPress context (minimal)
define('SHORTINIT', true);
$wp_load_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php';

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    // Fallback if path is different
    for ($i = 0; $i < 8; $i++) {
        if (file_exists(str_repeat('../', $i) . 'wp-load.php')) {
            require_once(str_repeat('../', $i) . 'wp-load.php');
            break;
        }
    }
}

// Set headers
header('Content-Type: text/css');
header('Cache-Control: public, max-age=86400'); // 24 hours
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');

// Get the plugin instance
function ppc_get_custom_settings() {
    global $immediate_colors;

    // If immediate colors are provided, use them (for live preview)
    if ($immediate_colors && is_array($immediate_colors)) {
        return [
            'colors' => [
                'base' => $immediate_colors['base'] ?? '#655997',
                'text' => $immediate_colors['text'] ?? '#ffffff',
                'highlight' => $immediate_colors['highlight'] ?? '#8a7bb9',
                'notification' => $immediate_colors['notification'] ?? '#d63638',
                'background' => $immediate_colors['background'] ?? '#f0f2f1'
            ],
            'elements' => []
        ];
    }

    // Check for multiple roles
    $roles = isset($_GET['roles']) ? explode(',', sanitize_text_field($_GET['roles'])) : [];

    if (!empty($roles)) {
        // Get all role settings
        $all_role_settings = get_option('pp_capabilities_admin_styles_roles', []);
        $global_settings = get_option('pp_capabilities_admin_styles', []);

        $final_colors = [
            'base' => $global_settings['custom_scheme_base'] ?? '#655997',
            'text' => $global_settings['custom_scheme_text'] ?? '#ffffff',
            'highlight' => $global_settings['custom_scheme_highlight'] ?? '#8a7bb9',
            'notification' => $global_settings['custom_scheme_notification'] ?? '#d63638',
            'background' => $global_settings['custom_scheme_background'] ?? '#f0f2f1'
        ];

        $final_elements = is_array($global_settings['elements'] ?? null) ? $global_settings['elements'] : [];

        // Check each role in reverse order (last role wins)
        foreach (array_reverse($roles) as $role) {
            if (isset($all_role_settings[$role])) {
                $role_settings = $all_role_settings[$role];

                // Update each color if set for this role
                if (!empty($role_settings['custom_scheme_base'])) {
                    $final_colors['base'] = $role_settings['custom_scheme_base'];
                }
                if (!empty($role_settings['custom_scheme_text'])) {
                    $final_colors['text'] = $role_settings['custom_scheme_text'];
                }
                if (!empty($role_settings['custom_scheme_highlight'])) {
                    $final_colors['highlight'] = $role_settings['custom_scheme_highlight'];
                }
                if (!empty($role_settings['custom_scheme_notification'])) {
                    $final_colors['notification'] = $role_settings['custom_scheme_notification'];
                }
                if (!empty($role_settings['custom_scheme_background'])) {
                    $final_colors['background'] = $role_settings['custom_scheme_background'];
                }

                if (!empty($role_settings['elements']) && is_array($role_settings['elements'])) {
                    foreach ($role_settings['elements'] as $group => $values) {
                        if (!is_array($values)) {
                            continue;
                        }
                        foreach ($values as $key => $value) {
                            if (!empty($value)) {
                                $final_elements[$group][$key] = $value;
                            }
                        }
                    }
                }
            }
        }

        return [
            'colors' => $final_colors,
            'elements' => $final_elements
        ];
    } else {
        // Use global settings

        $global_settings = get_option('pp_capabilities_admin_styles', []);

        return [
            'colors' => [
                'base' => $global_settings['custom_scheme_base'] ?? '#655997',
                'text' => $global_settings['custom_scheme_text'] ?? '#ffffff',
                'highlight' => $global_settings['custom_scheme_highlight'] ?? '#8a7bb9',
                'notification' => $global_settings['custom_scheme_notification'] ?? '#d63638',
                'background' => $global_settings['custom_scheme_background'] ?? '#f0f2f1'
            ],
            'elements' => is_array($global_settings['elements'] ?? null) ? $global_settings['elements'] : []
        ];
    }
}

// Function to generate CSS
function ppc_generate_custom_scheme_css($settings) {
    $colors = $settings['colors'] ?? [];
    $elements = $settings['elements'] ?? [];

    $colors = array_merge([
        'base' => '#655997',
        'text' => '#ffffff',
        'highlight' => '#8a7bb9',
        'notification' => '#d63638',
        'background' => '#f0f2f1'
    ], $colors);

    $get_element_color = function ($group, $key, $fallback) use ($elements) {
        if (!empty($elements[$group][$key])) {
            return $elements[$group][$key];
        }

        return $fallback;
    };

    // Convert hex to RGB
    function ppc_hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);

        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        }

        return "$r, $g, $b";
    }

    $base_rgb = ppc_hex_to_rgb($colors['base']);
    $text_rgb = ppc_hex_to_rgb($colors['text']);

    // Generate shade variations
    function ppc_adjust_brightness($hex, $steps) {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1),2).str_repeat(substr($hex,1,1),2).str_repeat(substr($hex,2,1),2);
        }

        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));

        $r = max(0,min(255,$r + $steps));
        $g = max(0,min(255,$g + $steps));
        $b = max(0,min(255,$b + $steps));

        $r_hex = str_pad(dechex($r), 2, '0', STR_PAD_LEFT);
        $g_hex = str_pad(dechex($g), 2, '0', STR_PAD_LEFT);
        $b_hex = str_pad(dechex($b), 2, '0', STR_PAD_LEFT);

        return '#'.$r_hex.$g_hex.$b_hex;
    }

    $base_darker = ppc_adjust_brightness($colors['base'], -20);
    $base_lighter = ppc_adjust_brightness($colors['base'], 20);
    $base_soft = ppc_adjust_brightness($colors['base'], 10);

    $link_default = $get_element_color('links', 'default', $colors['base']);
    $link_hover = $get_element_color('links', 'hover', $colors['highlight']);
    $link_delete = $get_element_color('links', 'delete', $colors['base']);
    $link_delete_hover = $get_element_color('links', 'delete_hover', $colors['highlight']);
    $link_trash = $get_element_color('links', 'trash', $colors['base']);
    $link_trash_hover = $get_element_color('links', 'trash_hover', $colors['highlight']);
    $link_spam = $get_element_color('links', 'spam', $colors['base']);
    $link_spam_hover = $get_element_color('links', 'spam_hover', $colors['highlight']);
    $link_inactive = $get_element_color('links', 'inactive', $colors['base']);
    $link_inactive_hover = $get_element_color('links', 'inactive_hover', $colors['highlight']);

    $button_primary_bg = $get_element_color('buttons', 'primary_background', $colors['base']);
    $button_primary_hover = $get_element_color('buttons', 'primary_hover', $colors['highlight']);
    $button_primary_text = $get_element_color('buttons', 'primary_text', $colors['text']);
    $button_secondary_text = $get_element_color('buttons', 'secondary_text', $colors['base']);
    $button_secondary_hover = $get_element_color('buttons', 'secondary_hover', $colors['highlight']);
    $button_secondary_border = $get_element_color('buttons', 'secondary_border', $colors['base']);
    $button_secondary_border_hover = $get_element_color('buttons', 'secondary_border_hover', $colors['highlight']);
    $button_primary_border = ppc_adjust_brightness($button_primary_bg, -20);

    $menu_background = $get_element_color('admin_menu', 'background', $colors['base']);
    $menu_text = $get_element_color('admin_menu', 'text', $colors['text']);
    $menu_highlight = $get_element_color('admin_menu', 'highlight', $colors['highlight']);
    $menu_submenu_background = $get_element_color('admin_menu', 'submenu_background', $base_lighter);
    $menu_submenu_text = $get_element_color('admin_menu', 'submenu_text', $colors['text']);

    $admin_bar_background = $get_element_color('admin_bar', 'background', $colors['base']);
    $admin_bar_text = $get_element_color('admin_bar', 'text', $colors['text']);
    $admin_bar_highlight = $get_element_color('admin_bar', 'highlight', $colors['highlight']);
    $admin_bar_submenu_background = $get_element_color('admin_bar', 'submenu_background', $base_lighter);
    $admin_bar_submenu_text = $get_element_color('admin_bar', 'submenu_text', $colors['text']);

    $menu_text_rgb = ppc_hex_to_rgb($menu_text);
    $admin_bar_text_rgb = ppc_hex_to_rgb($admin_bar_text);

    $table_header_background = $get_element_color('tables', 'header_background', $base_soft);
    $table_header_text = $get_element_color('tables', 'header_text', $colors['text']);
    $table_row_stripe = $get_element_color('tables', 'row_stripe', $colors['background']);
    $table_row_hover = $get_element_color('tables', 'row_hover', $base_lighter);

    $form_focus_border = $get_element_color('forms', 'focus_border', $colors['base']);
    $form_focus_shadow = $get_element_color('forms', 'focus_shadow', $colors['base']);
    $form_checkbox_radio = $get_element_color('forms', 'checkbox_radio', $colors['base']);

    $notice_background = $get_element_color('notices', 'background', $colors['notification']);
    $notice_text = $get_element_color('notices', 'text', $colors['text']);
    $notice_border = $get_element_color('notices', 'border', $colors['notification']);

    $checkbox_svg_color = ltrim($form_checkbox_radio, '#');

    // Output CSS
    return <<<CSS
/*! This file is auto-generated */
/* PublishPress Custom Color Scheme */

body {
  background: {$colors['background']};
}

/* Links */
a {
  color: {$link_default};
}
a:hover, a:active, a:focus {
  color: {$link_hover};
}

#post-body .misc-pub-post-status:before,
#post-body #visibility:before,
.curtime #timestamp:before,
#post-body .misc-pub-revisions:before,
span.wp-media-buttons-icon:before {
  color: currentColor;
}

.wp-core-ui .button-link {
  color: {$link_default};
}
.wp-core-ui .button-link:hover, .wp-core-ui .button-link:active, .wp-core-ui .button-link:focus {
  color: {$link_hover};
}

.wp-core-ui .button-link-delete {
  color: {$link_delete};
}
.wp-core-ui .button-link-delete:hover,
.wp-core-ui .button-link-delete:focus {
  color: {$link_delete_hover};
}

.submitdelete,
.submitdelete a,
.submitdelete a:visited {
  color: {$link_delete};
}
.submitdelete a:hover,
.submitdelete a:focus {
  color: {$link_delete_hover};
}

.row-actions .trash a,
.row-actions .trash a:visited {
  color: {$link_trash};
}
.row-actions .trash a:hover,
.row-actions .trash a:focus {
  color: {$link_trash_hover};
}

.row-actions .spam a,
.row-actions .spam a:visited {
  color: {$link_spam};
}
.row-actions .spam a:hover,
.row-actions .spam a:focus {
  color: {$link_spam_hover};
}

.row-actions .inactive a,
.row-actions .inactive a:visited {
  color: {$link_inactive};
}
.row-actions .inactive a:hover,
.row-actions .inactive a:focus {
  color: {$link_inactive_hover};
}

/* Forms */
input[type=checkbox]:checked::before {
  content: url("data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%3E%3Cpath%20d%3D%27M14.83%204.89l1.34.94-5.81%208.38H9.02L5.78%209.67l1.34-1.25%202.57%202.4z%27%20fill%3D%27%23{$checkbox_svg_color}%27%2F%3E%3C%2Fsvg%3E");
}

input[type=radio]:checked::before {
  background: {$form_checkbox_radio};
}

.wp-core-ui input[type=reset]:hover,
.wp-core-ui input[type=reset]:active {
  color: {$colors['highlight']};
}

input[type=text]:focus,
input[type=password]:focus,
input[type=color]:focus,
input[type=date]:focus,
input[type=datetime]:focus,
input[type=datetime-local]:focus,
input[type=email]:focus,
input[type=month]:focus,
input[type=number]:focus,
input[type=search]:focus,
input[type=tel]:focus,
input[type=text]:focus,
input[type=time]:focus,
input[type=url]:focus,
input[type=week]:focus,
input[type=checkbox]:focus,
input[type=radio]:focus,
select:focus,
textarea:focus {
  border-color: {$form_focus_border};
  box-shadow: 0 0 0 1px {$form_focus_shadow};
}

/* Core UI */
.wp-core-ui .button {
  border-color: #7e8993;
  color: #32373c;
}
.wp-core-ui .button.hover,
.wp-core-ui .button:hover,
.wp-core-ui .button.focus,
.wp-core-ui .button:focus {
  border-color: rgb(112.7848101266, 124.2721518987, 134.7151898734);
  color: rgb(38.4090909091, 42.25, 46.0909090909);
}
.wp-core-ui .button.focus,
.wp-core-ui .button:focus {
  border-color: #7e8993;
  color: rgb(38.4090909091, 42.25, 46.0909090909);
  box-shadow: 0 0 0 1px #32373c;
}
.wp-core-ui .button:active {
  border-color: #7e8993;
  color: rgb(38.4090909091, 42.25, 46.0909090909);
  box-shadow: none;
}
.wp-core-ui .button.active,
.wp-core-ui .button.active:focus,
.wp-core-ui .button.active:hover {
  border-color: {$colors['highlight']};
  color: rgb(38.4090909091, 42.25, 46.0909090909);
  box-shadow: inset 0 2px 5px -3px {$colors['highlight']};
}
.wp-core-ui .button.active:focus {
  box-shadow: 0 0 0 1px #32373c;
}
.wp-core-ui .button,
.wp-core-ui .button-secondary {
  color: {$button_secondary_text};
  border-color: {$button_secondary_border};
}
.wp-core-ui .button.hover,
.wp-core-ui .button:hover,
.wp-core-ui .button-secondary:hover {
  border-color: {$button_secondary_border_hover};
  color: {$button_secondary_hover};
}
.wp-core-ui .button.focus,
.wp-core-ui .button:focus,
.wp-core-ui .button-secondary:focus {
  border-color: {$button_secondary_border_hover};
  color: {$button_secondary_text};
  box-shadow: 0 0 0 1px {$button_secondary_border_hover};
}
.wp-core-ui .button-primary:hover {
  color: {$button_primary_text};
}
.wp-core-ui .button-primary {
  background: {$button_primary_bg};
  border-color: {$button_primary_border};
  color: {$button_primary_text};
}
.wp-core-ui .button-primary:hover,
.wp-core-ui .button-primary:focus {
  background: {$button_primary_hover};
  border-color: {$button_primary_hover};
  color: {$button_primary_text};
}
.wp-core-ui .button-primary:focus {
  box-shadow: 0 0 0 1px {$button_primary_text}, 0 0 0 3px {$button_primary_bg};
}
.wp-core-ui .button-primary:active {
  background: {$button_primary_hover};
  border-color: {$button_primary_hover};
  color: {$button_primary_text};
}
.wp-core-ui .button-primary.active,
.wp-core-ui .button-primary.active:focus,
.wp-core-ui .button-primary.active:hover {
  background: {$button_primary_bg};
  color: {$button_primary_text};
  border-color: {$button_primary_border};
  box-shadow: inset 0 2px 5px -3px {$button_primary_bg};
}
.wp-core-ui .button-group > .button.active {
  border-color: {$button_primary_bg};
}

/* List tables */
.wrap .page-title-action,
.wrap .page-title-action:active {
  border: 1px solid {$button_secondary_border};
  color: {$button_secondary_text};
}
.wrap .page-title-action:hover {
  color: {$button_secondary_hover};
  border-color: {$button_secondary_border_hover};
}
.wrap .page-title-action:focus {
  border-color: {$button_secondary_border_hover};
  color: {$button_secondary_text};
  box-shadow: 0 0 0 1px {$button_secondary_border_hover};
}

.view-switch a.current:before {
  color: {$button_secondary_text};
}
.view-switch a:hover:before {
  color: {$button_secondary_hover};
}

/* Table styling */
.wp-list-table thead th,
.wp-list-table tfoot th,
.widefat thead th,
.widefat tfoot th {
  background: {$table_header_background};
  color: {$table_header_text};
}

.wp-list-table.striped tbody tr:nth-child(odd),
.widefat.striped tbody tr:nth-child(odd) {
  background-color: {$table_row_stripe};
}

.wp-list-table tbody tr:hover,
.widefat tbody tr:hover {
  background-color: {$table_row_hover};
}

/* Active tabs */
.about-wrap .nav-tab-active,
.nav-tab-active,
.nav-tab-active:hover {
  background-color: {$colors['background']};
  border-bottom-color: {$colors['background']};
}

/* Admin Menu */
#adminmenuback,
#adminmenuwrap,
#adminmenu {
  background: {$menu_background};
}

#adminmenu a {
  color: {$menu_text};
}

#adminmenu div.wp-menu-image:before {
  color: rgba({$menu_text_rgb}, 0.8);
}

#adminmenu a:hover,
#adminmenu li.menu-top:hover,
#adminmenu li.opensub > a.menu-top,
#adminmenu li > a.menu-top:focus {
  color: {$menu_text};
  background-color: {$menu_highlight};
}

#adminmenu li.menu-top:hover div.wp-menu-image:before,
#adminmenu li.opensub > a.menu-top div.wp-menu-image:before {
  color: {$menu_text};
}

/* Admin Menu: submenu */
#adminmenu .wp-submenu,
#adminmenu .wp-has-current-submenu .wp-submenu,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu {
  background: {$menu_submenu_background};
}

#adminmenu li.wp-has-submenu.wp-not-current-submenu.opensub:hover:after,
#adminmenu li.wp-has-submenu.wp-not-current-submenu:focus-within:after {
  border-right-color: {$menu_submenu_background};
}

#adminmenu .wp-submenu .wp-submenu-head {
  color: {$menu_submenu_text};
}

#adminmenu .wp-submenu a,
#adminmenu .wp-has-current-submenu .wp-submenu a,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu a {
  color: {$menu_submenu_text};
}
#adminmenu .wp-submenu a:focus,
#adminmenu .wp-submenu a:hover,
#adminmenu .wp-has-current-submenu .wp-submenu a:focus,
#adminmenu .wp-has-current-submenu .wp-submenu a:hover,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu a:focus,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu a:hover {
  color: {$menu_text};
}

/* Admin Menu: current */
#adminmenu .wp-submenu li.current a,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a {
  color: {$menu_text};
}
#adminmenu .wp-submenu li.current a:hover,
#adminmenu .wp-submenu li.current a:focus,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:hover,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:focus {
  color: {$menu_text};
}

ul#adminmenu a.wp-has-current-submenu:after,
ul#adminmenu > li.current > a.current:after {
  border-right-color: {$colors['background']};
}

#adminmenu li.current a.menu-top,
#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
#adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head,
.folded #adminmenu li.current.menu-top {
  color: {$menu_text};
  background: {$menu_highlight};
}

#adminmenu li.wp-has-current-submenu div.wp-menu-image:before,
#adminmenu a.current:hover div.wp-menu-image:before,
#adminmenu li.current div.wp-menu-image:before,
#adminmenu li.wp-has-current-submenu a:focus div.wp-menu-image:before,
#adminmenu li.wp-has-current-submenu.opensub div.wp-menu-image:before,
#adminmenu li:hover div.wp-menu-image:before,
#adminmenu li a:focus div.wp-menu-image:before,
#adminmenu li.opensub div.wp-menu-image:before {
  color: {$menu_text};
}

/* Admin Menu: bubble */
#adminmenu .menu-counter,
#adminmenu .awaiting-mod,
#adminmenu .update-plugins {
  color: {$menu_text};
  background: {$colors['notification']};
}

#adminmenu li.current a .awaiting-mod,
#adminmenu li a.wp-has-current-submenu .update-plugins,
#adminmenu li:hover a .awaiting-mod,
#adminmenu li.menu-top:hover > a .update-plugins {
  color: {$menu_text};
  background: {$menu_highlight};
}

/* Admin Menu: collapse button */
#collapse-button {
  color: rgba({$menu_text_rgb}, 0.8);
}
#collapse-button:hover,
#collapse-button:focus {
  color: {$menu_text};
}

/* Admin Bar */
#wpadminbar {
  color: {$admin_bar_text};
  background: {$admin_bar_background};
}

#wpadminbar .ab-item,
#wpadminbar a.ab-item,
#wpadminbar > #wp-toolbar span.ab-label {
  color: {$admin_bar_text};
}

#wpadminbar .ab-icon,
#wpadminbar .ab-icon:before,
#wpadminbar .ab-item:before,
#wpadminbar .ab-item:after {
  color: rgba({$admin_bar_text_rgb}, 0.8);
}

#wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
#wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {
  background: {$admin_bar_highlight};
  color: {$admin_bar_text};
}

#wpadminbar:not(.mobile) > #wp-toolbar li:hover span.ab-label,
#wpadminbar:not(.mobile) > #wp-toolbar li.hover span.ab-label,
#wpadminbar:not(.mobile) > #wp-toolbar a:focus span.ab-label {
  color: {$admin_bar_text};
}

#wpadminbar:not(.mobile) li:hover .ab-icon:before,
#wpadminbar:not(.mobile) li:hover .ab-item:before,
#wpadminbar:not(.mobile) li:hover .ab-item:after,
#wpadminbar:not(.mobile) li:hover #adminbarsearch:before {
  color: {$admin_bar_text};
}

/* Admin Bar: submenu */
#wpadminbar .menupop .ab-sub-wrapper {
  background: {$admin_bar_submenu_background};
}

#wpadminbar .quicklinks .menupop ul.ab-sub-secondary,
#wpadminbar .quicklinks .menupop ul.ab-sub-secondary .ab-submenu {
  background: {$admin_bar_submenu_background};
}

#wpadminbar .ab-submenu .ab-item,
#wpadminbar .quicklinks .menupop ul li a,
#wpadminbar .quicklinks .menupop.hover ul li a {
  color: {$admin_bar_submenu_text};
}

#wpadminbar .quicklinks li .blavatar,
#wpadminbar .menupop .menupop > .ab-item:before {
  color: rgba({$text_rgb}, 0.7);
}

#wpadminbar .quicklinks .menupop ul li a:hover,
#wpadminbar .quicklinks .menupop ul li a:focus,
#wpadminbar .quicklinks .menupop ul li a:hover strong,
#wpadminbar .quicklinks .menupop ul li a:focus strong,
#wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover > a,
#wpadminbar .quicklinks .menupop.hover ul li a:hover,
#wpadminbar .quicklinks .menupop.hover ul li a:focus {
  color: {$colors['text']};
}

/* Admin Bar: search */
#wpadminbar #adminbarsearch:before {
  color: rgba({$text_rgb}, 0.8);
}

#wpadminbar > #wp-toolbar > #wp-admin-bar-top-secondary > #wp-admin-bar-search #adminbarsearch input.adminbar-input:focus {
  color: {$colors['text']};
  background: {$colors['highlight']};
}

/* Admin Bar: my account */
#wpadminbar .quicklinks li#wp-admin-bar-my-account.with-avatar > a img {
  border-color: {$colors['highlight']};
  background-color: {$colors['highlight']};
}

#wpadminbar #wp-admin-bar-user-info .display-name {
  color: {$colors['text']};
}
#wpadminbar #wp-admin-bar-user-info a:hover .display-name {
  color: {$colors['text']};
}
#wpadminbar #wp-admin-bar-user-info .username {
  color: rgba({$text_rgb}, 0.8);
}

/* Pointers */
.wp-pointer .wp-pointer-content h3 {
  background-color: {$colors['base']};
  border-color: {$base_darker};
}
.wp-pointer .wp-pointer-content h3:before {
  color: {$colors['base']};
}
.wp-pointer.wp-pointer-top .wp-pointer-arrow,
.wp-pointer.wp-pointer-top .wp-pointer-arrow-inner {
  border-bottom-color: {$colors['base']};
}

/* Responsive Component */
div#wp-responsive-toggle a:before {
  color: rgba({$text_rgb}, 0.8);
}
.wp-responsive-open div#wp-responsive-toggle a {
  border-color: transparent;
  background: {$colors['highlight']};
}
.wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle a {
  background: {$colors['highlight']};
}
.wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle .ab-icon:before {
  color: rgba({$text_rgb}, 0.8);
}

/* UI Colors */
.wp-core-ui .wp-ui-primary {
  color: {$colors['text']};
  background-color: {$colors['base']};
}
.wp-core-ui .wp-ui-text-primary {
  color: {$colors['base']};
}
.wp-core-ui .wp-ui-highlight {
  color: {$colors['text']};
  background-color: {$colors['highlight']};
}
.wp-core-ui .wp-ui-text-highlight {
  color: {$colors['highlight']};
}
.wp-core-ui .wp-ui-notification {
  color: {$notice_text};
  background-color: {$notice_background};
}
.wp-core-ui .wp-ui-text-notification {
  color: {$colors['notification']};
}
.wp-core-ui .wp-ui-text-icon {
  color: rgba({$text_rgb}, 0.7);
}

.notice,
.update-nag,
.notice-success,
.notice-warning,
.notice-error,
.notice-info {
  border-left-color: {$notice_border};
}

.notice,
.update-nag {
  background-color: {$notice_background};
  color: {$notice_text};
}
CSS;
}

// Get colors and output CSS
$settings = ppc_get_custom_settings();
echo ppc_generate_custom_scheme_css($settings);
exit;
