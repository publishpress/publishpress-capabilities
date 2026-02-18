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

function ppc_get_custom_colors() {

    $colors = [
        'base' => '',
        'text' => '',
        'highlight' => '',
        'notification' => '',
        'background' => '',
        'element_colors' => []
    ];

    $element_colors = [];

    if (isset($_GET['ppc_custom_style'])) {
        $custom_style_slug = sanitize_key($_GET['ppc_custom_style']);

        $custom_styles = get_option('pp_capabilities_custom_admin_styles', []);

        if (isset($custom_styles[$custom_style_slug])) {
            $style = $custom_styles[$custom_style_slug];

            $colors['base'] = $style['custom_scheme_base'] ?? '';
            $colors['text'] = $style['custom_scheme_text'] ?? '';
            $colors['highlight'] = $style['custom_scheme_highlight'] ?? '';
            $colors['notification'] = $style['custom_scheme_notification'] ?? '';
            $colors['background'] = $style['custom_scheme_background'] ?? '';
            $element_colors = $style['element_colors'] ?? [];
        }
    }

    $colors['element_colors'] = $element_colors;
    return $colors;
}

// Function to generate CSS for element-specific colors
function ppc_generate_element_colors_css($element_colors) {
    if (empty($element_colors) || !is_array($element_colors)) {
        return '';
    }

  $ppc_is_light_color = function ($hex) {
    if (empty($hex) || !is_string($hex)) {
      return false;
    }

    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
      $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6) {
      return false;
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

    return $luminance > 0.6;
  };

    $css = "\n/* Element-specific colors */\n";
    $table_scope = 'body:not(.capabilities_page_pp-capabilities-admin-styles)';

    // Links styling
    if (!empty($element_colors['links'])) {
        $links = $element_colors['links'];

        if (!empty($links['link_default'])) {
            $css .= "a { color: {$links['link_default']} !important; }\n";
        }
        if (!empty($links['link_hover'])) {
            $css .= "a:hover, a:focus { color: {$links['link_hover']} !important; }\n";
        }
        if (!empty($links['link_delete'])) {
            $css .= ".wp-core-ui .button-link-delete { color: {$links['link_delete']} !important; }\n";
        }
        if (!empty($links['link_trash'])) {
            $css .= "a.submitdelete { color: {$links['link_trash']} !important; }\n";
        }
        if (!empty($links['link_spam'])) {
            $css .= "a.submitspam { color: {$links['link_spam']} !important; }\n";
        }
        if (!empty($links['link_inactive'])) {
            $css .= "a.inactive { color: {$links['link_inactive']} !important; }\n";
        }
    }

    // Forms styling
    if (!empty($element_colors['forms'])) {
        $forms = $element_colors['forms'];

        if (!empty($forms['input_border'])) {
            $css .= "input[type=text], input[type=password], input[type=email], input[type=number], input[type=url], input[type=tel], input[type=search], input[type=date], input[type=time], input[type=datetime-local], input[type=month], input[type=week], input[type=file], textarea, select, .wp-core-ui select { border-color: {$forms['input_border']}; }\n";
        }
        if (!empty($forms['input_focus_border'])) {
            $css .= "input[type=text]:focus, input[type=password]:focus, input[type=email]:focus, input[type=number]:focus, input[type=url]:focus, input[type=tel]:focus, input[type=search]:focus, input[type=date]:focus, input[type=time]:focus, input[type=datetime-local]:focus, input[type=month]:focus, input[type=week]:focus, input[type=file]:focus, textarea:focus, select:focus, .wp-core-ui select:focus { border-color: {$forms['input_focus_border']}; }\n";
        }
        if (!empty($forms['input_background'])) {
            $css .= "input[type=text], input[type=password], input[type=email], input[type=number], input[type=url], input[type=tel], input[type=search], input[type=date], input[type=time], input[type=datetime-local], input[type=month], input[type=week], input[type=file], textarea, select, .wp-core-ui select { background-color: {$forms['input_background']}; }\n";
        }
        if (!empty($forms['input_text'])) {
            $css .= "input[type=text], input[type=password], input[type=email], input[type=number], input[type=url], input[type=tel], input[type=search], input[type=date], input[type=time], input[type=datetime-local], input[type=month], input[type=week], input[type=file], textarea, select, .wp-core-ui select { color: {$forms['input_text']}; }\n";
        }
        if (!empty($forms['input_placeholder'])) {
            $css .= "input::placeholder, textarea::placeholder { color: {$forms['input_placeholder']}; }\n";
        }
    }

    // Tables styling
    if (!empty($element_colors['tables'])) {
        $tables = $element_colors['tables'];

        if (!empty($tables['table_header_bg'])) {
            $css .= "{$table_scope} table thead th { background-color: {$tables['table_header_bg']} !important; }\n";
        }
        if (!empty($tables['table_header_text'])) {
            $css .= "{$table_scope} table thead th { color: {$tables['table_header_text']} !important; }\n";
        }
        if (!empty($tables['table_row_bg'])) {
            $css .= "{$table_scope} table tbody tr { background-color: {$tables['table_row_bg']} !important; }\n";
        }
        if (!empty($tables['table_row_color'])) {
            $css .= "{$table_scope} table tbody tr td { color: {$tables['table_row_color']} !important; }\n";
        }
        if (!empty($tables['table_row_hover_bg'])) {
            $css .= "{$table_scope} table tbody tr:hover { background-color: {$tables['table_row_hover_bg']} !important; }\n";
        }
        if (!empty($tables['table_border'])) {
            $css .= "{$table_scope} table, {$table_scope} table td, {$table_scope} table th { border-color: {$tables['table_border']} !important; }\n";
        }
        if (!empty($tables['table_alt_row_bg'])) {
            $css .= "{$table_scope} table tbody tr:nth-child(odd) { background-color: {$tables['table_alt_row_bg']} !important; }\n";
        }
        if (!empty($tables['table_alt_row_color'])) {
            $css .= "{$table_scope} table tbody tr:nth-child(odd) td { color: {$tables['table_alt_row_color']} !important; }\n";
        }
    }

    // Buttons styling
    if (!empty($element_colors['buttons'])) {
        $buttons = $element_colors['buttons'];

        if (!empty($buttons['button_primary_bg'])) {
            $css .= ".wp-core-ui .button-primary:not(.wp-picker-container .button-primary) { background-color: {$buttons['button_primary_bg']}; }\n";
        }
        if (!empty($buttons['button_primary_text'])) {
            $css .= ".wp-core-ui .button-primary:not(.wp-picker-container .button-primary) { color: {$buttons['button_primary_text']}; }\n";
        }
        if (!empty($buttons['button_primary_hover_bg'])) {
            $css .= ".wp-core-ui .button-primary:hover:not(.wp-picker-container .button-primary), .wp-core-ui .button-primary:focus:not(.wp-picker-container .button-primary) { background-color: {$buttons['button_primary_hover_bg']}; }\n";
        }
        if (!empty($buttons['button_secondary_bg'])) {
            $css .= ".wp-core-ui .button:not(.wp-picker-container .button) { background-color: {$buttons['button_secondary_bg']}; }\n";
        }
        if (!empty($buttons['button_secondary_text'])) {
            $css .= ".wp-core-ui .button:not(.wp-picker-container .button) { color: {$buttons['button_secondary_text']}; }\n";
        }
        if (!empty($buttons['button_secondary_hover_bg'])) {
            $css .= ".wp-core-ui .button:hover:not(.wp-picker-container .button), .wp-core-ui .button:focus:not(.wp-picker-container .button) { background-color: {$buttons['button_secondary_hover_bg']}; }\n";
        }
    }

    // Admin Menu styling
    if (!empty($element_colors['admin_menu'])) {
        $menu = $element_colors['admin_menu'];

        if (!empty($menu['menu_bg'])) {
            $css .= "#adminmenu, #adminmenuback, #adminmenuwrap { background-color: {$menu['menu_bg']} !important; }\n";
        }
        if (!empty($menu['menu_text'])) {
            $css .= "#adminmenu a { color: {$menu['menu_text']} !important; }\n";
        }
        if (!empty($menu['menu_icon'])) {
            $css .= "#adminmenu .dashicons, #adminmenu .dashicons-before:before { color: {$menu['menu_icon']} !important; }\n";
        }

        if (!empty($menu['menu_hover_bg'])) {
            $css .= "#adminmenu li:hover, #adminmenu li.opensub > a { background-color: {$menu['menu_hover_bg']} !important; }\n";
        }
        if (!empty($menu['menu_hover_text'])) {
            $css .= "#adminmenu li:hover a, #adminmenu li.opensub > a { color: {$menu['menu_hover_text']} !important; }\n";
        }
        if (!empty($menu['menu_current_bg'])) {
            $css .= "#adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu > a.wp-has-current-submenu { background-color: {$menu['menu_current_bg']} !important; }\n";
        }
        if (!empty($menu['menu_current_text'])) {
            $css .= "#adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu > a.wp-has-current-submenu { color: {$menu['menu_current_text']} !important; }\n";
        }
        if (!empty($menu['menu_submenu_bg'])) {
          $css .= "#adminmenu .wp-submenu, #adminmenu .wp-has-submenu:hover .wp-submenu, #adminmenu .wp-has-submenu:focus-within .wp-submenu, #adminmenu .wp-has-current-submenu .wp-submenu, #adminmenu .wp-has-current-submenu.opensub .wp-submenu { background-color: {$menu['menu_submenu_bg']} !important; }\n";
        }
        if (!empty($menu['menu_submenu_text'])) {
            $css .= "#adminmenu .wp-submenu a, #adminmenu .wp-has-current-submenu .wp-submenu a, #adminmenu .wp-has-current-submenu.opensub .wp-submenu a, #adminmenu .wp-submenu a:hover, #adminmenu .wp-submenu a:focus { color: {$menu['menu_submenu_text']} !important; }\n";
        }
    }

    // Admin Bar styling
    if (!empty($element_colors['admin_bar'])) {
        $adminbar = $element_colors['admin_bar'];

        if (!empty($adminbar['adminbar_bg'])) {
            $css .= "#wpadminbar { background-color: {$adminbar['adminbar_bg']}; }\n";
        }
        if (!empty($adminbar['adminbar_text'])) {
          $css .= "#wpadminbar .ab-item, #wpadminbar a.ab-item, #wpadminbar > #wp-toolbar a, #wpadminbar > #wp-toolbar span, #wpadminbar > #wp-toolbar span.ab-label, #wpadminbar .ab-submenu .ab-item, #wpadminbar .quicklinks .ab-submenu a, #wpadminbar .quicklinks .menupop ul li a { color: {$adminbar['adminbar_text']} !important; }\n";
        }
        if (!empty($adminbar['adminbar_icon'])) {
            $css .= "#wpadminbar .ab-icon:before, #wpadminbar .ab-item:before, #wpadminbar .dashicons { color: {$adminbar['adminbar_icon']} !important; }\n";
        }
        if (!empty($adminbar['adminbar_hover_bg'])) {
            $css .= "#wpadminbar li:hover > .ab-item, #wpadminbar li.hover > .ab-item { background-color: {$adminbar['adminbar_hover_bg']}; }\n";
        }
    }

    // Dashboard widgets styling
    if (!empty($element_colors['dashboard_widgets'])) {
        $widgets = $element_colors['dashboard_widgets'];

        if (!empty($widgets['widget_bg'])) {
            $css .= "#dashboard-widgets .postbox { background-color: {$widgets['widget_bg']} !important; }\n";
        }
        if (!empty($widgets['widget_border'])) {
            $css .= "#dashboard-widgets .postbox { border-color: {$widgets['widget_border']} !important; }\n";
        }
        if (!empty($widgets['widget_header_bg'])) {
            $css .= "#dashboard-widgets .postbox-header, #dashboard-widgets .postbox .hndle { background-color: {$widgets['widget_header_bg']} !important; }\n";
        }
        if (!empty($widgets['widget_title_text'])) {
            $css .= "#dashboard-widgets .postbox-header h2, #dashboard-widgets .postbox .hndle { color: {$widgets['widget_title_text']} !important; }\n";
        }
        if (!empty($widgets['widget_body_text'])) {
            $css .= "#dashboard-widgets .postbox .inside, #dashboard-widgets .postbox .inside p, #dashboard-widgets .postbox .inside li { color: {$widgets['widget_body_text']} !important; }\n";
        }
        if (!empty($widgets['widget_link'])) {
            $css .= "#dashboard-widgets .postbox .inside a { color: {$widgets['widget_link']} !important; }\n";
        }
        if (!empty($widgets['widget_link_hover'])) {
            $css .= "#dashboard-widgets .postbox .inside a:hover, #dashboard-widgets .postbox .inside a:focus { color: {$widgets['widget_link_hover']} !important; }\n";
        }
    }

    return $css;
}

// Function to generate CSS
function ppc_generate_custom_scheme_css($colors) {

    // Convert hex to RGB
    function ppc_hex_to_rgb($hex) {
        if (empty($hex) || !is_string($hex)) {
            return null;
        }

        $hex = str_replace('#', '', $hex);

        if(strlen($hex) == 3) {
            $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            $g = hexdec(substr($hex,1,1).substr($hex,1,1));
            $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else if (strlen($hex) == 6) {
            $r = hexdec(substr($hex,0,2));
            $g = hexdec(substr($hex,2,2));
            $b = hexdec(substr($hex,4,2));
        } else {
            return null;
        }

        return "$r, $g, $b";
    }

    function ppc_is_light_color($hex) {
        if (empty($hex) || !is_string($hex)) {
            return false;
        }

        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6) {
            return false;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.6;
    }

    $text_rgb = !empty($colors['text']) ? ppc_hex_to_rgb($colors['text']) : null;

    // Generate shade variations
    function ppc_adjust_brightness($hex, $steps) {
        if (empty($hex) || !is_string($hex)) {
            return null;
        }

        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex,0,1),2).str_repeat(substr($hex,1,1),2).str_repeat(substr($hex,2,1),2);
        }

        if (strlen($hex) != 6) {
            return null;
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

    $base_darker = !empty($colors['base']) ? ppc_adjust_brightness($colors['base'], -20) : null;
    $base_lighter = !empty($colors['base']) ? ppc_adjust_brightness($colors['base'], 20) : null;

    // Generate element colors CSS
    $element_colors_css = ppc_generate_element_colors_css($colors['element_colors']);

    // Build CSS conditionally based on available colors
    $css = "/*! This file is auto-generated */\n/* PublishPress Custom Color Scheme */\n";

    // Only add CSS rules if colors are defined
    if (!empty($colors['background'])) {
        $css .= "\nbody {\n  background: {$colors['background']};\n}\n";
    }

    $css .= <<<CSS

#post-body .misc-pub-post-status:before,
#post-body #visibility:before,
.curtime #timestamp:before,
#post-body .misc-pub-revisions:before,
span.wp-media-buttons-icon:before {
  color: currentColor;
}
CSS;

    $css .= <<<CSS

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
CSS;

    if (!empty($colors['highlight'])) {
        $css .= <<<CSS

.wp-core-ui input[type=reset]:hover,
.wp-core-ui input[type=reset]:active {
  color: {$colors['highlight']};
}
CSS;
    }

    if (!empty($colors['base'])) {
        $css .= <<<CSS

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
CSS;
    }

    $css .= <<<CSS

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
CSS;

    if (!empty($colors['highlight'])) {
        $css .= <<<CSS

.wp-core-ui .button.active,
.wp-core-ui .button.active:focus,
.wp-core-ui .button.active:hover {
  border-color: {$colors['highlight']};
  color: rgb(38.4090909091, 42.25, 46.0909090909);
  box-shadow: inset 0 2px 5px -3px {$colors['highlight']};
}
CSS;
    }

    $css .= <<<CSS

.wp-core-ui .button.active:focus {
  box-shadow: 0 0 0 1px #32373c;
}
CSS;

    if (!empty($colors['base'])) {
        $css .= <<<CSS

.wp-core-ui .button,
.wp-core-ui .button-secondary {
  color: {$colors['base']};
  border-color: {$colors['base']};
}
CSS;
    }

    if (!empty($colors['highlight']) || !empty($colors['base'])) {
        $css .= ".wp-core-ui .button.hover,\n.wp-core-ui .button:hover,\n.wp-core-ui .button-secondary:hover {\n";
        if (!empty($colors['highlight'])) {
            $css .= "  border-color: {$colors['highlight']};\n  color: {$colors['highlight']};\n";
        }
        $css .= "}\n";

        $css .= ".wp-core-ui .button.focus,\n.wp-core-ui .button:focus,\n.wp-core-ui .button-secondary:focus {\n";
        if (!empty($colors['highlight'])) {
            $css .= "  border-color: {$colors['highlight']};\n";
        }
        if (!empty($colors['base'])) {
            $css .= "  color: {$colors['base']};\n";
        }
        if (!empty($colors['highlight'])) {
            $css .= "  box-shadow: 0 0 0 1px {$colors['highlight']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['text'])) {
        $css .= ".wp-core-ui .button-primary:hover {\n  color: {$colors['text']};\n}\n";
    }

    if (!empty($colors['base']) || !empty($colors['text'])) {
        $css .= ".wp-core-ui .button-primary {\n";
        if (!empty($colors['base'])) {
            $css .= "  background: {$colors['base']};\n";
            if ($base_darker) {
                $css .= "  border-color: {$base_darker};\n";
            }
        }
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['highlight']) || !empty($colors['text'])) {
        $css .= ".wp-core-ui .button-primary:hover,\n.wp-core-ui .button-primary:focus {\n";
        if (!empty($colors['highlight'])) {
            $css .= "  background: {$colors['highlight']};\n  border-color: {$colors['highlight']};\n";
        }
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['text']) && !empty($colors['base'])) {
        $css .= ".wp-core-ui .button-primary:focus {\n  box-shadow: 0 0 0 1px {$colors['text']}, 0 0 0 3px {$colors['base']};\n}\n";
    }

    if (!empty($colors['highlight']) || !empty($colors['text'])) {
        $css .= ".wp-core-ui .button-primary:active {\n";
        if (!empty($colors['highlight'])) {
            $css .= "  background: {$colors['highlight']};\n  border-color: {$colors['highlight']};\n";
        }
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['base']) || !empty($colors['text'])) {
        $css .= ".wp-core-ui .button-primary.active,\n.wp-core-ui .button-primary.active:focus,\n.wp-core-ui .button-primary.active:hover {\n";
        if (!empty($colors['base'])) {
            $css .= "  background: {$colors['base']};\n";
            if ($base_darker) {
                $css .= "  border-color: {$base_darker};\n";
            }
            $css .= "  box-shadow: inset 0 2px 5px -3px {$colors['base']};\n";
        }
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['base'])) {
        $css .= ".wp-core-ui .button-group > .button.active {\n  border-color: {$colors['base']};\n}\n";
    }

    $css .= "\n/* List tables */\n";

    if (!empty($colors['base']) || !empty($colors['highlight'])) {
        $css .= ".wrap .page-title-action,\n.wrap .page-title-action:active {\n";
        if (!empty($colors['base'])) {
            $css .= "  border: 1px solid {$colors['base']};\n  color: {$colors['base']};\n";
        }
        $css .= "}\n";

        if (!empty($colors['highlight'])) {
            $css .= ".wrap .page-title-action:hover {\n  color: {$colors['highlight']};\n  border-color: {$colors['highlight']};\n}\n";
        }

        if (!empty($colors['highlight']) || !empty($colors['base'])) {
            $css .= ".wrap .page-title-action:focus {\n";
            if (!empty($colors['highlight'])) {
                $css .= "  border-color: {$colors['highlight']};\n  box-shadow: 0 0 0 1px {$colors['highlight']};\n";
            }
            if (!empty($colors['base'])) {
                $css .= "  color: {$colors['base']};\n";
            }
            $css .= "}\n";
        }
    }

    if (!empty($colors['base']) || !empty($colors['highlight'])) {
        if (!empty($colors['base'])) {
            $css .= ".view-switch a.current:before {\n  color: {$colors['base']};\n}\n";
        }
        if (!empty($colors['highlight'])) {
            $css .= ".view-switch a:hover:before {\n  color: {$colors['highlight']};\n}\n";
        }
    }

    if (!empty($colors['background'])) {
        $css .= <<<CSS

/* Active tabs */
.about-wrap .nav-tab-active,
.nav-tab-active,
.nav-tab-active:hover {
  background-color: {$colors['background']};
  border-bottom-color: {$colors['background']};
}
CSS;
    }

    $css .= "\n/* Admin Menu */\n";

    if (!empty($colors['base'])) {
        $css .= <<<CSS

#adminmenuback,
#adminmenuwrap,
#adminmenu {
  background: {$colors['base']};
}
CSS;
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

#adminmenu a {
  color: {$colors['text']};
}
CSS;
    }

    if ($text_rgb) {
        $css .= <<<CSS

#adminmenu div.wp-menu-image:before {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    if (!empty($colors['text']) || !empty($colors['highlight'])) {
        $css .= <<<CSS

#adminmenu a:hover,
#adminmenu li.menu-top:hover,
#adminmenu li.opensub > a.menu-top,
#adminmenu li > a.menu-top:focus {
CSS;
        if (!empty($colors['text'])) {
            $css .= "\n  color: {$colors['text']};";
        }
        if (!empty($colors['highlight'])) {
            $css .= "\n  background-color: {$colors['highlight']};";
        }
        $css .= "\n}\n";
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

#adminmenu li.menu-top:hover div.wp-menu-image:before,
#adminmenu li.opensub > a.menu-top div.wp-menu-image:before {
  color: {$colors['text']};
}
CSS;
    }

    $css .= "\n/* Admin Menu: submenu */\n";

    if ($base_lighter) {
        $css .= <<<CSS

#adminmenu .wp-submenu,
#adminmenu .wp-has-current-submenu .wp-submenu,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu {
  background: {$base_lighter};
}

#adminmenu li.wp-has-submenu.wp-not-current-submenu.opensub:hover:after,
#adminmenu li.wp-has-submenu.wp-not-current-submenu:focus-within:after {
  border-right-color: {$base_lighter};
}
CSS;
    }

    if ($text_rgb) {
        $css .= <<<CSS

#adminmenu .wp-submenu .wp-submenu-head {
  color: rgba({$text_rgb}, 0.9);
}

#adminmenu .wp-submenu a,
#adminmenu .wp-has-current-submenu .wp-submenu a,
#adminmenu .wp-has-current-submenu.opensub .wp-submenu a {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

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
CSS;
    }

    if (!empty($colors['background'])) {
        $css .= <<<CSS

ul#adminmenu a.wp-has-current-submenu:after,
ul#adminmenu > li.current > a.current:after {
  border-right-color: {$colors['background']};
}
CSS;
    }

    if (!empty($colors['text']) || !empty($colors['highlight'])) {
        $css .= <<<CSS

#adminmenu li.current a.menu-top,
#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
#adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head,
.folded #adminmenu li.current.menu-top {
CSS;
        if (!empty($colors['text'])) {
            $css .= "\n  color: {$colors['text']};";
        }
        if (!empty($colors['highlight'])) {
            $css .= "\n  background: {$colors['highlight']};";
        }
        $css .= "\n}\n";
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

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
CSS;
    }

    $css .= "\n/* Admin Menu: bubble */\n";

    if (!empty($colors['text']) || !empty($colors['notification'])) {
        $css .= "#adminmenu .menu-counter,\n#adminmenu .awaiting-mod,\n#adminmenu .update-plugins {\n";
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        if (!empty($colors['notification'])) {
            $css .= "  background: {$colors['notification']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['text']) || !empty($colors['highlight'])) {
        $css .= <<<CSS

#adminmenu li.current a .awaiting-mod,
#adminmenu li a.wp-has-current-submenu .update-plugins,
#adminmenu li:hover a .awaiting-mod,
#adminmenu li.menu-top:hover > a .update-plugins {
CSS;
        if (!empty($colors['text'])) {
            $css .= "\n  color: {$colors['text']};";
        }
        if (!empty($colors['highlight'])) {
            $css .= "\n  background: {$colors['highlight']};";
        }
        $css .= "\n}\n";
    }

    $css .= "\n/* Admin Menu: collapse button */\n";

    if ($text_rgb) {
        $css .= <<<CSS

#collapse-button {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

#collapse-button:hover,
#collapse-button:focus {
  color: {$colors['text']};
}
CSS;
    }

    $css .= "\n/* Admin Bar */\n";

    if (!empty($colors['text']) || !empty($colors['base'])) {
        $css .= "#wpadminbar {\n";
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        if (!empty($colors['base'])) {
            $css .= "  background: {$colors['base']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

#wpadminbar .ab-item,
#wpadminbar a.ab-item,
#wpadminbar > #wp-toolbar span.ab-label {
  color: {$colors['text']};
}
CSS;
    }

    if ($text_rgb) {
        $css .= <<<CSS

#wpadminbar .ab-icon,
#wpadminbar .ab-icon:before,
#wpadminbar .ab-item:before,
#wpadminbar .ab-item:after {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    if (!empty($colors['highlight']) || !empty($colors['text'])) {
        $css .= <<<CSS

#wpadminbar:not(.mobile) .ab-top-menu > li:hover > .ab-item,
#wpadminbar:not(.mobile) .ab-top-menu > li > .ab-item:focus {
CSS;
        if (!empty($colors['highlight'])) {
            $css .= "\n  background: {$colors['highlight']};";
        }
        if (!empty($colors['text'])) {
            $css .= "\n  color: {$colors['text']};";
        }
        $css .= "\n}\n";
    }

   // Continue with remaining admin bar styles
    if (!empty($colors['text'])) {
        $css .= <<<CSS

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
CSS;
    }

    $css .= "\n/* Admin Bar: submenu */\n";

    if ($base_lighter) {
        $css .= <<<CSS

#wpadminbar .menupop .ab-sub-wrapper {
  background: {$base_lighter};
}

#wpadminbar .quicklinks .menupop ul.ab-sub-secondary,
#wpadminbar .quicklinks .menupop ul.ab-sub-secondary .ab-submenu {
  background: {$base_lighter};
}
CSS;
    }

    if ($text_rgb) {
        $css .= <<<CSS

#wpadminbar .ab-submenu .ab-item,
#wpadminbar .quicklinks .menupop ul li a,
#wpadminbar .quicklinks .menupop.hover ul li a {
  color: rgba({$text_rgb}, 0.8);
}

#wpadminbar .quicklinks li .blavatar,
#wpadminbar .menupop .menupop > .ab-item:before {
  color: rgba({$text_rgb}, 0.7);
}
CSS;
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

#wpadminbar .quicklinks .menupop ul li a:hover,
#wpadminbar .quicklinks .menupop ul li a:focus,
#wpadminbar .quicklinks .menupop ul li a:hover strong,
#wpadminbar .quicklinks .menupop ul li a:focus strong,
#wpadminbar .quicklinks .ab-sub-wrapper .menupop.hover > a,
#wpadminbar .quicklinks .menupop.hover ul li a:hover,
#wpadminbar .quicklinks .menupop.hover ul li a:focus {
  color: {$colors['text']};
}
CSS;
    }

    $css .= "\n/* Admin Bar: search */\n";

    if ($text_rgb) {
        $css .= <<<CSS

#wpadminbar #adminbarsearch:before {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    if (!empty($colors['text']) && !empty($colors['highlight'])) {
        $css .= <<<CSS

#wpadminbar > #wp-toolbar > #wp-admin-bar-top-secondary > #wp-admin-bar-search #adminbarsearch input.adminbar-input:focus {
  color: {$colors['text']};
  background: {$colors['highlight']};
}
CSS;
    }

    $css .= "\n/* Admin Bar: my account */\n";

    if (!empty($colors['highlight'])) {
        $css .= <<<CSS

#wpadminbar .quicklinks li#wp-admin-bar-my-account.with-avatar > a img {
  border-color: {$colors['highlight']};
  background-color: {$colors['highlight']};
}
CSS;
    }

    if (!empty($colors['text'])) {
        $css .= <<<CSS

#wpadminbar #wp-admin-bar-user-info .display-name {
  color: {$colors['text']};
}
#wpadminbar #wp-admin-bar-user-info a:hover .display-name {
  color: {$colors['text']};
}
CSS;
    }

    if ($text_rgb) {
        $css .= <<<CSS

#wpadminbar #wp-admin-bar-user-info .username {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    $css .= "\n/* Pointers */\n";

    if (!empty($colors['base'])) {
        $css .= ".wp-pointer .wp-pointer-content h3 {\n  background-color: {$colors['base']};\n";
        if ($base_darker) {
            $css .= "  border-color: {$base_darker};\n";
        }
        $css .= "}\n";

        $css .= <<<CSS

.wp-pointer .wp-pointer-content h3:before {
  color: {$colors['base']};
}
.wp-pointer.wp-pointer-top .wp-pointer-arrow,
.wp-pointer.wp-pointer-top .wp-pointer-arrow-inner {
  border-bottom-color: {$colors['base']};
}
CSS;
    }

    $css .= "\n/* Responsive Component */\n";

    if ($text_rgb) {
        $css .= <<<CSS

div#wp-responsive-toggle a:before {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    if (!empty($colors['highlight'])) {
        $css .= <<<CSS

.wp-responsive-open div#wp-responsive-toggle a {
  border-color: transparent;
  background: {$colors['highlight']};
}
.wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle a {
  background: {$colors['highlight']};
}
CSS;
    }

    if ($text_rgb) {
        $css .= <<<CSS

.wp-responsive-open #wpadminbar #wp-admin-bar-menu-toggle .ab-icon:before {
  color: rgba({$text_rgb}, 0.8);
}
CSS;
    }

    $css .= "\n/* UI Colors */\n";

    if (!empty($colors['text']) || !empty($colors['base'])) {
        $css .= ".wp-core-ui .wp-ui-primary {\n";
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        if (!empty($colors['base'])) {
            $css .= "  background-color: {$colors['base']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['base'])) {
        $css .= ".wp-core-ui .wp-ui-text-primary {\n  color: {$colors['base']};\n}\n";
    }

    if (!empty($colors['text']) || !empty($colors['highlight'])) {
        $css .= ".wp-core-ui .wp-ui-highlight {\n";
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        if (!empty($colors['highlight'])) {
            $css .= "  background-color: {$colors['highlight']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['highlight'])) {
        $css .= ".wp-core-ui .wp-ui-text-highlight {\n  color: {$colors['highlight']};\n}\n";
    }

    if (!empty($colors['text']) || !empty($colors['notification'])) {
        $css .= ".wp-core-ui .wp-ui-notification {\n";
        if (!empty($colors['text'])) {
            $css .= "  color: {$colors['text']};\n";
        }
        if (!empty($colors['notification'])) {
            $css .= "  background-color: {$colors['notification']};\n";
        }
        $css .= "}\n";
    }

    if (!empty($colors['notification'])) {
        $css .= ".wp-core-ui .wp-ui-text-notification {\n  color: {$colors['notification']};\n}\n";
    }

    if ($text_rgb) {
        $css .= <<<CSS

.wp-core-ui .wp-ui-text-icon {
  color: rgba({$text_rgb}, 0.7);
}
CSS;
    }

    // Add element-specific colors CSS
    $css .= "\n" . $element_colors_css;

    return $css;
}

// Get colors and output CSS
$colors = ppc_get_custom_colors();
echo ppc_generate_custom_scheme_css($colors);
exit;