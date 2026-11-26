<?php
/**
 * Admin Styles component coverage and legacy template regression checks.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.53.0
 */

define('ABSPATH', __DIR__ . '/');
define('PPC_ADMIN_STYLES_CSS_LIBRARY', true);
$options = [];
function add_action() {}
function do_action() {}
function __($text, $domain = '') { return $text; }
function get_option($key, $default = false) { global $options; return $options[$key] ?? $default; }
function update_option($key, $value) { global $options; $options[$key] = $value; return true; }
function maybe_unserialize($value) { return @unserialize($value, ['allowed_classes' => false]) ?: $value; }

require_once dirname(__DIR__) . '/includes/features/admin-styles/admin-styles.php';
require_once dirname(__DIR__) . '/includes/features/admin-styles/admin-styles-css.php';
class CountingAdminStyles extends PublishPress\Capabilities\PP_Capabilities_Admin_Styles
{
    public $templateReads = 0;

    public function get_style_templates()
    {
        $this->templateReads++;
        return parent::get_style_templates();
    }
}
$reflection = new ReflectionClass(CountingAdminStyles::class);
$styles = $reflection->newInstanceWithoutConstructor();
$templates = $styles->get_style_templates();
$legacy = [];
foreach ($templates as $id => $template) {
    $palette = $template['palette'];
    $style = ['element_colors' => [
        'tables' => ['table_row_color' => $palette['text'], 'table_alt_row_color' => $palette['text']],
        'dashboard_widgets' => ['widget_bg' => '', 'widget_body_text' => ''],
    ]];
    foreach (['base', 'text', 'highlight', 'notification', 'background'] as $key) {
        $style['custom_scheme_' . $key] = $palette[$key];
    }
    $legacy[$id] = $style;
}
$options['pp_capabilities_custom_admin_styles'] = $legacy;
$styles->migrateTemplateColors();
$repaired = $styles->get_custom_styles();
foreach ($repaired as $id => $style) {
    $colors = $style['element_colors'];
    foreach (['tables', 'forms', 'buttons', 'dashboard_widgets'] as $tab) {
        if (empty($colors[$tab])) {
            throw new RuntimeException('Missing template coverage: ' . $id . '/' . $tab);
        }
    }
    if ($colors['tables']['table_row_color'] !== '#111827'
        || $colors['tables']['table_alt_row_color'] !== '#111827') {
        throw new RuntimeException('Unreadable table text in ' . $id);
    }
    $css = ppc_generate_element_colors_css($colors);
    foreach (['.components-button.is-primary', '.components-button.is-primary:hover',
        '.components-button.is-primary:focus', '.components-button.is-primary:active',
        '.components-button.is-secondary:not(.is-primary)', ':not(.wp-picker-container *)',
        ':not(:disabled)', ':not([aria-disabled=true])', 'table tbody tr:nth-child(odd):hover', 'box-shadow:'] as $selector) {
        if (strpos($css, $selector) === false) {
            throw new RuntimeException('Missing CSS coverage: ' . $selector);
        }
    }
}
$templateReads = $styles->templateReads;
if ($styles->get_custom_styles() !== $repaired || $styles->templateReads !== $templateReads) {
    throw new RuntimeException('Template repair must run only once.');
}
// The persisted marker must also prevent scanning on subsequent requests.
$nextRequest = $reflection->newInstanceWithoutConstructor();
$nextRequest->get_custom_styles();
if ($nextRequest->templateReads !== 0) {
    throw new RuntimeException('Completed migration must skip the repair scan on later requests.');
}
$customized = $legacy['mint-breeze'];
$customized['element_colors']['tables']['table_row_color'] = '#123456';
$customized['element_colors']['buttons']['button_primary_text'] = '#ff00aa';
$customized['element_colors']['dashboard_widgets']['widget_bg'] = '#333333';
$options['pp_capabilities_custom_admin_styles'] = ['customized' => $customized];
$styles->migrateTemplateColors();
$saved = $styles->get_custom_styles()['customized']['element_colors'];
if ($saved['tables']['table_row_color'] !== '#123456'
    || $saved['buttons']['button_primary_text'] !== '#ff00aa'
    || $saved['dashboard_widgets']['widget_bg'] !== '#333333') {
    throw new RuntimeException('Explicitly customized colors must survive repair.');
}
$customized['custom_scheme_base'] = '#123456';
$options['pp_capabilities_custom_admin_styles'] = ['custom' => $customized];
$styles->migrateTemplateColors();
if ($styles->get_custom_styles()['custom'] !== $customized) {
    throw new RuntimeException('Styles that do not match a built-in palette must remain unchanged.');
}
require_once dirname(__DIR__) . '/classes/pp-capabilities-installer.php';
$options['pp_capabilities_custom_admin_styles'] = $legacy;
PublishPress\Capabilities\Classes\PP_Capabilities_Installer::runUpgradeTasks('2.53.0');
if ($options['pp_capabilities_custom_admin_styles'] !== $legacy) {
    throw new RuntimeException('Upgrades from 2.53.0 must not rerun the template migration.');
}
PublishPress\Capabilities\Classes\PP_Capabilities_Installer::runUpgradeTasks('2.52.0');
if ($options['pp_capabilities_custom_admin_styles'] !== $repaired) {
    throw new RuntimeException('Upgrades from before 2.53.0 must repair existing template styles.');
}
// Empty color-picker fields must not prevent the installer from completing an upgrade.
$emptyStyles = $legacy;
foreach ($emptyStyles as &$style) {
    foreach ([
        'tables' => ['table_header_bg', 'table_row_bg', 'table_alt_row_bg'],
        'buttons' => ['button_primary_bg', 'button_primary_hover_bg', 'button_secondary_bg', 'button_secondary_hover_bg'],
        'forms' => ['input_background'],
        'dashboard_widgets' => ['widget_bg', 'widget_header_bg'],
    ] as $tab => $keys) {
        foreach ($keys as $key) {
            $style['element_colors'][$tab][$key] = '';
        }
    }
}
unset($style);
$options['pp_capabilities_custom_admin_styles'] = $emptyStyles;
PublishPress\Capabilities\Classes\PP_Capabilities_Installer::runUpgradeTasks('2.52.0');
foreach ($styles->get_custom_styles() as $style) {
    if (empty($style['template_color_version'])
        || $style['element_colors']['tables']['table_row_bg'] !== '') {
        throw new RuntimeException('Empty saved backgrounds must survive a successful template upgrade.');
    }
}
$readableText = new ReflectionMethod(PublishPress\Capabilities\PP_Capabilities_Admin_Styles::class, 'getReadableTemplateText');
$readableText->setAccessible(true);
foreach ([['', ''], ['invalid', ''], ['', '#ffffff']] as [$background, $alternate]) {
    if ($readableText->invoke($styles, $background, $alternate) !== '#111827') {
        throw new RuntimeException('Empty or invalid backgrounds must use readable text for the light admin canvas.');
    }
}
// Toolbar foreground colors belong to menu items, not labels inside form buttons.
$toolbarCss = ppc_generate_element_colors_css(['admin_bar' => ['adminbar_text' => '#f9fafb']]);
if (strpos($toolbarCss, '#wpadminbar > #wp-toolbar span') !== false
    || strpos($toolbarCss, '#wpadminbar > #wp-toolbar a') !== false
    || strpos($toolbarCss, '.ab-item:not(button):not(.button):not(.components-button)') === false) {
    throw new RuntimeException('Toolbar text rules must not repaint button labels.');
}
if (in_array('--json', $argv, true)) {
    $css = [];
    foreach ($repaired as $id => $style) {
        $css[$id] = ppc_generate_custom_scheme_css([
            'base' => $style['custom_scheme_base'],
            'text' => $style['custom_scheme_text'],
            'highlight' => $style['custom_scheme_highlight'],
            'notification' => $style['custom_scheme_notification'],
            'background' => $style['custom_scheme_background'],
            'element_colors' => $style['element_colors'],
        ]);
    }
    echo json_encode(['templates' => $templates, 'repaired' => $repaired, 'css' => $css]);
} else {
    echo 'Admin Styles regression checks passed for ' . count($templates) . " built-in palettes.\n";
}
