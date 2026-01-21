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
function ppc_get_custom_colors() {
    global $immediate_colors;

    // If immediate colors are provided, use them (for live preview)
    if ($immediate_colors && is_array($immediate_colors)) {
        return [
            'base' => $immediate_colors['base'] ?? '#655997',
            'text' => $immediate_colors['text'] ?? '#ffffff',
            'highlight' => $immediate_colors['highlight'] ?? '#8a7bb9',
            'notification' => $immediate_colors['notification'] ?? '#d63638',
            'background' => $immediate_colors['background'] ?? '#f0f2f1'
        ];
    }

    // Check for multiple roles
    $roles = isset($_GET['roles']) ? explode(',', sanitize_text_field($_GET['roles'])) : [];

    if (!empty($roles)) {
        // Get all role settings
        $all_role_settings = get_option('pp_capabilities_admin_styles_roles', []);
        $global_settings = get_option('pp_capabilities_admin_styles', []);

        // Default colors
        $final_colors = [
            'base' => $global_settings['custom_scheme_base'] ?? '#655997',
            'text' => $global_settings['custom_scheme_text'] ?? '#ffffff',
            'highlight' => $global_settings['custom_scheme_highlight'] ?? '#8a7bb9',
            'notification' => $global_settings['custom_scheme_notification'] ?? '#d63638',
            'background' => $global_settings['custom_scheme_background'] ?? '#f0f2f1'
        ];

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
            }
        }

        return $final_colors;
    } else {
        // Use global settings

        return [
            'base' => $settings['custom_scheme_base'] ?? '#655997',
            'text' => $settings['custom_scheme_text'] ?? '#ffffff',
            'highlight' => $settings['custom_scheme_highlight'] ?? '#8a7bb9',
            'notification' => $settings['custom_scheme_notification'] ?? '#d63638',
            'background' => $settings['custom_scheme_background'] ?? '#f0f2f1'
        ];
    }
}

// Function to generate CSS
function ppc_generate_custom_scheme_css($colors) {
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

    // Output CSS
    return <<<CSS
/*! This file is auto-generated */
/* PublishPress Custom Color Scheme */

body {
  background: {$colors['background']};
}

/* Links */
a {
  color: {$colors['base']};
}
a:hover, a:active, a:focus {
  color: {$colors['highlight']};
}

#post-body .misc-pub-post-status:before,
#post-body #visibility:before,
.curtime #timestamp:before,
#post-body .misc-pub-revisions:before,
span.wp-media-buttons-icon:before {
  color: currentColor;
}

.wp-core-ui .button-link {
  color: {$colors['base']};
}
.wp-core-ui .button-link:hover, .wp-core-ui .button-link:active, .wp-core-ui .button-link:focus {
  color: {$colors['highlight']};
}

.wp-core-ui .button-link-delete {
  color: #a00;
}
.wp-core-ui .button-link-delete:hover,
.wp-core-ui .button-link-delete:focus {
  color: #dc3232;
}

/* Forms */
input[type=checkbox]:checked::before {
  content: url("data:image/svg+xml;utf8,%3Csvg%20xmlns%3D%27http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%27%20viewBox%3D%270%200%2020%2020%27%3E%3Cpath%20d%3D%27M14.83%204.89l1.34.94-5.81%208.38H9.02L5.78%209.67l1.34-1.25%202.57%202.4z%27%20fill%3D%27%237e8993%27%2F%3E%3C%2Fsvg%3E");
}

input[type=radio]:checked::before {
  background: #7e8993;
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
  border-color: {$colors['base']};
  box-shadow: 0 0 0 1px {$colors['base']};
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
  color: {$colors['base']};
  border-color: {$colors['base']};
}
.wp-core-ui .button.hover,
.wp-core-ui .button:hover,
.wp-core-ui .button-secondary:hover {
  border-color: {$colors['highlight']};
  color: {$colors['highlight']};
}
.wp-core-ui .button.focus,
.wp-core-ui .button:focus,
.wp-core-ui .button-secondary:focus {
  border-color: {$colors['highlight']};
  color: {$colors['base']};
  box-shadow: 0 0 0 1px {$colors['highlight']};
}
.wp-core-ui .button-primary:hover {
  color: {$colors['text']};
}
.wp-core-ui .button-primary {
  background: {$colors['base']};
  border-color: {$base_darker};
  color: {$colors['text']};
}
.wp-core-ui .button-primary:hover,
.wp-core-ui .button-primary:focus {
  background: {$colors['highlight']};
  border-color: {$colors['highlight']};
  color: {$colors['text']};
}
.wp-core-ui .button-primary:focus {
  box-shadow: 0 0 0 1px {$colors['text']}, 0 0 0 3px {$colors['base']};
}
.wp-core-ui .button-primary:active {
  background: {$colors['highlight']};
  border-color: {$colors['highlight']};
  color: {$colors['text']};
}
.wp-core-ui .button-primary.active,
.wp-core-ui .button-primary.active:focus,
.wp-core-ui .button-primary.active:hover {
  background: {$colors['base']};
  color: {$colors['text']};
  border-color: {$base_darker};
  box-shadow: inset 0 2px 5px -3px {$colors['base']};
}
.wp-core-ui .button-group > .button.active {
  border-color: {$colors['base']};
}

/* List tables */
.wrap .page-title-action,
.wrap .page-title-action:active {
  border: 1px solid {$colors['base']};
  color: {$colors['base']};
}
.wrap .page-title-action:hover {
  color: {$colors['highlight']};
  border-color: {$colors['highlight']};
}
.wrap .page-title-action:focus {
  border-color: {$colors['highlight']};
  color: {$colors['base']};
  box-shadow: 0 0 0 1px {$colors['highlight']};
}

.view-switch a.current:before {
  color: {$colors['base']};
}
.view-switch a:hover:before {
  color: {$colors['highlight']};
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
  background: {$colors['base']};
}

#adminmenu a {
  color: {$colors['text']};
}

#adminmenu div.wp-menu-image:before {
  color: rgba({$text_rgb}, 0.8);
}

#adminmenu a:hover,
#adminmenu li.menu-top:hover,
#adminmenu li.opensub > a.menu-top,
#adminmenu li > a.menu-top:focus {
  color: {$colors['text']};
  background-color: {$colors['highlight']};
}

#adminmenu li.menu-top:hover div.wp-menu-image:before,
#adminmenu li.opensub > a.menu-top div.wp-menu-image:before {
  color: {$colors['text']};
}

/* Admin Menu: submenu */
#adminmenu .wp-submenu,
#adminmenu .wp-has-current-submenu .wp-submenu,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu {
  background: {$base_lighter};
}

#adminmenu li.wp-has-submenu.wp-not-current-submenu.opensub:hover:after,
#adminmenu li.wp-has-submenu.wp-not-current-submenu:focus-within:after {
  border-right-color: {$base_lighter};
}

#adminmenu .wp-submenu .wp-submenu-head {
  color: rgba({$text_rgb}, 0.9);
}

#adminmenu .wp-submenu a,
#adminmenu .wp-has-current-submenu .wp-submenu a,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu a {
  color: rgba({$text_rgb}, 0.8);
}
#adminmenu .wp-submenu a:focus,
#adminmenu .wp-submenu a:hover,
#adminmenu .wp-has-current-submenu .wp-submenu a:focus,
#adminmenu .wp-has-current-submenu .wp-submenu a:hover,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu a:focus,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu a:hover {
  color: {$colors['text']};
}

/* Admin Menu: current */
#adminmenu .wp-submenu li.current a,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a {
  color: {$colors['text']};
}
#adminmenu .wp-submenu li.current a:hover,
#adminmenu .wp-submenu li.current a:focus,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:hover,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu li.current a:focus {
  color: {$colors['text']};
}

ul#adminmenu a.wp-has-current-submenu:after,
ul#adminmenu > li.current > a.current:after {
  border-right-color: {$colors['background']};
}

#adminmenu li.current a.menu-top,
#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
#adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head,
.folded #adminmenu li.current.menu-top {
  color: {$colors['text']};
  background: {$colors['highlight']};
}

#adminmenu li.wp-has-current-submenu div.wp-menu-image:before,
#adminmenu a.current:hover div.wp-menu-image:before,
#adminmenu li.current div.wp-menu-image:before,
#adminmenu li.wp-has-current-submenu a:focus div.wp-menu-image:before,
#adminmenu li.wp-has-current-submenu.opensub div.wp-menu-image:before,
#adminmenu li:hover div.wp-menu-image:before,
#adminmenu li a:focus div.wp-menu-image:before,
#adminmenu li.opensub div.wp-menu-image:before {
  color: {$colors['text']};
}

/* Admin Menu: bubble */
#adminmenu .menu-counter,
#adminmenu .awaiting-mod,
#adminmenu .update-plugins {
  color: {$colors['text']};
  background: {$colors['notification']};
}

#adminmenu li.current a .awaiting-mod,
#adminmenu li a.wp-has-current-submenu .update-plugins,
#adminmenu li:hover a .awaiting-mod,
#adminmenu li.menu-top:hover > a .update-plugins {
  color: {$colors['text']};
  background: {$colors['highlight']};
}

/* Admin Menu: collapse button */
#collapse-button {
  color: rgba({$text_rgb}, 0.8);
}
#collapse-button:hover,
#collapse-button:focus {
  color: {$colors['text']};
}

/* Admin Bar */
#wpadminbar {
  color: {$colors['text']};
  background: {$colors['base']};
}

#wpadminbar .ab-item,
#wpadminbar a.ab-item,
#wpadminbar > #wp-toolbar span.ab-label {
  color: {$colors['text']};
}

#wpadminbar .ab-icon,
#wpadminbar .ab-icon:before,
#wpadminbar .ab-item:before,
#wpadminbar .ab-item:after {
  color: rgba({$text_rgb}, 0.8);
}

#wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
#wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {
  background: {$colors['highlight']};
  color: {$colors['text']};
}

#wpadminbar:not(.mobile) > #wp-toolbar li:hover span.ab-label,
#wpadminbar:not(.mobile) > #wp-toolbar li.hover span.ab-label,
#wpadminbar:not(.mobile) > #wp-toolbar a:focus span.ab-label {
  color: {$colors['text']};
}

#wpadminbar:not(.mobile) li:hover .ab-icon:before,
#wpadminbar:not(.mobile) li:hover .ab-item:before,
#wpadminbar:not(.mobile) li:hover .ab-item:after,
#wpadminbar:not(.mobile) li:hover #adminbarsearch:before {
  color: {$colors['text']};
}

/* Admin Bar: submenu */
#wpadminbar .menupop .ab-sub-wrapper {
  background: {$base_lighter};
}

#wpadminbar .quicklinks .menupop ul.ab-sub-secondary,
#wpadminbar .quicklinks .menupop ul.ab-sub-secondary .ab-submenu {
  background: {$base_lighter};
}

#wpadminbar .ab-submenu .ab-item,
#wpadminbar .quicklinks .menupop ul li a,
#wpadminbar .quicklinks .menupop.hover ul li a {
  color: rgba({$text_rgb}, 0.8);
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
  color: {$colors['text']};
  background-color: {$colors['notification']};
}
.wp-core-ui .wp-ui-text-notification {
  color: {$colors['notification']};
}
.wp-core-ui .wp-ui-text-icon {
  color: rgba({$text_rgb}, 0.7);
}
CSS;
}

// Get colors and output CSS
$colors = ppc_get_custom_colors();
echo ppc_generate_custom_scheme_css($colors);
exit;