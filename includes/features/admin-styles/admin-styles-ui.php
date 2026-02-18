<?php
/**
 * Capability Manager Admin Styles.
 *  Customize the WordPress admin area per-role.
 *
 *    Copyright 2026, PublishPress <help@publishpress.com>
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

use PublishPress\Capabilities\PP_Capabilities_Admin_Styles;

global $capsman;

$admin_styles = PP_Capabilities_Admin_Styles::instance();

// Get current role from URL or default
$current_role = isset($_GET['role']) ? sanitize_key($_GET['role']) : '';
if (empty($current_role)) {
    $current_role = $capsman->get_last_role();
}

// Force reload of settings for the current role after form submission
if (!empty($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'pp-capabilities-admin-styles')) {
    $admin_styles->load_settings_for_role($current_role);
}

$settings = $admin_styles->settings;
$color_schemes = $admin_styles->get_color_schemes();

// Get roles and default role
$roles = $capsman->roles;
$default_role = $current_role;
$current_role_name = isset($roles[$default_role]) ? translate_user_role($roles[$default_role]) : '';

// Get current user's color scheme for preview
$current_user_color = get_user_option('admin_color', get_current_user_id());
if (empty($current_user_color)) {
    $current_user_color = 'fresh';
}

$role_caption = translate_user_role($roles[$default_role]);

// Display custom style success/error messages from transients
$user_id = get_current_user_id();

// Check for saved custom style
$saved_style_name = get_transient('ppc_custom_style_saved_' . $user_id);
if ($saved_style_name !== false) {
    echo '<div class="notice notice-success is-dismissible"><p>' .
         sprintf(esc_html__('Custom style "%s" saved successfully.', 'capsman-enhanced'), esc_html($saved_style_name)) .
         '</p></div>';
    delete_transient('ppc_custom_style_saved_' . $user_id);
}

// Check for deleted custom style
$deleted_style_name = get_transient('ppc_custom_style_deleted_' . $user_id);
if ($deleted_style_name !== false) {
    echo '<div class="notice notice-success is-dismissible"><p>' .
         sprintf(esc_html__('Custom style "%s" deleted successfully.', 'capsman-enhanced'), esc_html($deleted_style_name)) .
         '</p></div>';
    delete_transient('ppc_custom_style_deleted_' . $user_id);
}

// Check for error
$error_type = get_transient('ppc_custom_style_error_' . $user_id);
if ($error_type === 'empty_style_name') {
    echo '<div class="notice notice-error is-dismissible"><p>' .
         esc_html__('Custom style name cannot be empty.', 'capsman-enhanced') .
         '</p></div>';
    delete_transient('ppc_custom_style_error_' . $user_id);
}

// Check for admin styles saved
$admin_styles_saved = get_transient('ppc_admin_styles_saved_' . $user_id);
if ($admin_styles_saved !== false) {
    echo '<div class="notice notice-success is-dismissible"><p>' .
         esc_html__('Admin styles saved successfully.', 'capsman-enhanced') .
         '</p></div>';
    delete_transient('ppc_admin_styles_saved_' . $user_id);
}
?>
<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper admin-styles">
    <div id="icon-capsman-admin" class="icon32"></div>
    <h2><?php esc_html_e('Admin Styles', 'capability-manager-enhanced'); ?></h2>

    <form method="post" id="ppc-admin-styles-form"
        action="<?php echo esc_url(admin_url('admin.php?page=pp-capabilities-admin-styles')); ?>">
        <?php wp_nonce_field('pp-capabilities-admin-styles', '_wpnonce'); ?>
        <input type="hidden" name="current_user_color" id="current_user_color"
            value="<?php echo esc_attr($current_user_color); ?>" />
        <input type="hidden" name="page" value="pp-capabilities-admin-styles">

        <div id="ppc-admin-styles-overlay" class="ppc-form-overlay" aria-hidden="true">
            <span class="spinner is-active" aria-hidden="true"></span>
            <span class="ppc-form-overlay-text"></span>
        </div>

        <div class="pp-columns-wrapper pp-enable-sidebar">
            <div class="pp-column-left">
                <table id="akmin">
                    <tr>
                        <td class="content">

                            <div class="publishpress-filters">
                                <select name="ppc-admin-styles-role" class="ppc-admin-styles-role">
                                    <?php
                                    foreach ($roles as $role_name => $name):
                                        $name = translate_user_role($name);
                                        ?>
                                        <option value="<?php echo esc_attr($role_name); ?>" <?php selected($default_role, $role_name); ?>><?php echo esc_html($name); ?></option>
                                        <?php
                                    endforeach;
                                    ?>
                                </select> &nbsp;

                                <img class="loading"
                                    src="<?php echo esc_url_raw($capsman->mod_url); ?>/images/wpspin_light.gif"
                                    style="display: none">
                            </div>

                            <div>
                                <div class="pp-capabilities-submit-top" style="display: flex;gap: 10px;float:right; margin-bottom: 20px;">
                                    <input type="submit" name="admin-styles-all-submit"
                                        value="<?php esc_attr_e('Save for all Roles', 'capability-manager-enhanced') ?>"
                                        class="button-secondary ppc-admin-styles-submit" style="float:right" />

                                    <input type="submit" name="admin-styles-submit"
                                        value="<?php echo esc_attr(sprintf(esc_html__('Save for %s', 'capability-manager-enhanced'), esc_html($current_role_name))); ?>"
                                        class="button-primary ppc-admin-styles-submit" style="float:right" />
                                </div>

                            </div>

                            <div id="pp-capability-menu-wrapper">
                                <div class="pp-capability-menus">

                                    <div class="pp-capability-menus-wrap">
                                        <div id="pp-capability-menus-general"
                                            class="pp-capability-menus-content editable-role" style="display: block;">

                                                <table
                                                    class="wp-list-table widefat fixed striped pp-capability-menus-select">
                                                    <thead>
                                                        <tr>
                                                            <th class="menu-column" style="width: 250px;">
                                                                <?php esc_html_e('Setting', 'capability-manager-enhanced'); ?>
                                                            </th>
                                                            <th class="value-column">
                                                                <?php esc_html_e('Value', 'capability-manager-enhanced'); ?>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>

                                                        <tr class="ppc-menu-row parent-menu">
                                                            <td class="menu-column ppc-menu-item">
                                                                <label>
                                                                    <strong><?php esc_html_e('Admin Color Scheme', 'capability-manager-enhanced'); ?></strong>
                                                                </label>
                                                                <p class="cme-subtext">
                                                                    <?php esc_html_e('Sets the admin color scheme for all users. Click a scheme to preview it instantly.', 'capability-manager-enhanced'); ?>
                                                                </p>
                                                                <div class="add-new-button-area">
                                                                    <label for="ppc-custom-style-template" class="custom-style-template-label">
                                                                        <?php esc_html_e('Start from template', 'capsman-enhanced'); ?>
                                                                    </label>
                                                                    <select id="ppc-custom-style-template" class="custom-style-template-select">
                                                                        <option value="blank">
                                                                            <?php esc_html_e('Blank Template', 'capsman-enhanced'); ?>
                                                                        </option>
                                                                        <?php foreach ($admin_styles->get_style_templates() as $template_id => $template): ?>
                                                                            <option value="<?php echo esc_attr($template_id); ?>">
                                                                                <?php echo esc_html($template['name']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <button type="button"
                                                                        class="button button-secondary custom-styles-button">
                                                                        <?php esc_html_e('Add Custom Style', 'capsman-enhanced'); ?>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                            <td class="value-column ppc-menu-checkbox">
                                                                <fieldset class="ppc-admin-color-schemes">
                                                                    <input type="hidden"
                                                                        name="settings[admin_color_scheme]"
                                                                        id="admin_color_scheme"
                                                                        value="<?php echo esc_attr($settings['admin_color_scheme']); ?>" />

                                                                    <div class="color-options">
                                                                        <?php foreach ($color_schemes as $key => $name):
                                                                            $scheme_data = PP_Capabilities_Admin_Styles::instance()->get_color_scheme_data($key);

                                                                            $is_selected = ($settings['admin_color_scheme'] === $key);
                                                                            $is_current_user = ($current_user_color === $key);
                                                                            ?>
                                                                            <div class="color-option <?php echo (strpos($key, 'ppc-custom-style-') === 0) ? 'ppc-user-custom-scheme' : ''; ?> <?php echo $is_selected ? 'selected' : ''; ?>">
                                                                                <input type="radio"
                                                                                    name="admin_color_scheme_radio"
                                                                                    id="color-<?php echo esc_attr($key); ?>"
                                                                                    value="<?php echo esc_attr($key); ?>"
                                                                                    data-scheme="<?php echo esc_attr($key); ?>"
                                                                                    <?php checked($is_selected); ?>
                                                                                    class="tog" />
                                                                                <label for="color-<?php echo esc_attr($key); ?>">
                                                                                    <?php echo esc_html($name); ?>
                                                                                    <?php if (strpos($key, 'ppc-custom-style-') === 0): ?>
                                                                                        <span class="custom-style-edit-icon"
                                                                                            title="<?php esc_attr_e('Edit custom style', 'capsman-enhanced'); ?>"
                                                                                            data-style="<?php echo esc_attr($key); ?>"
                                                                                            data-name="<?php echo esc_attr($name); ?>">
                                                                                            <span class="dashicons dashicons-edit"></span>
                                                                                        </span>
                                                                                    <?php endif; ?>
                                                                                </label>

                                                                                <div class="color-palette">
                                                                                    <?php if (!empty($scheme_data['colors'])): ?>
                                                                                        <?php foreach ($scheme_data['colors'] as $color): ?>
                                                                                            <div class="color-palette-shade"
                                                                                                style="background-color: <?php echo esc_attr($color); ?>">
                                                                                            </div>
                                                                                        <?php endforeach; ?>
                                                                                    <?php else: ?>
                                                                                        <div class="color-palette-shade"
                                                                                            style="background-color: #2271b1">
                                                                                        </div>
                                                                                        <div class="color-palette-shade"
                                                                                            style="background-color: #72aee6">
                                                                                        </div>
                                                                                        <div class="color-palette-shade"
                                                                                            style="background-color: #ffffff">
                                                                                        </div>
                                                                                        <div class="color-palette-shade"
                                                                                            style="background-color: #d63638">
                                                                                        </div>
                                                                                        <div class="color-palette-shade"
                                                                                            style="background-color: #f0f0f1">
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </div>

                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    </div>

                                                                </fieldset>
                                                            </td>
                                                        </tr>

                                                        <tr id="custom-style-form" style="display: none;">
                                                            <td colspan="2" class="custom-form-td value-column ppc-menu-checkbox">
                                                                <div>
                                                                    <div class="color-editor-card">
                                                                        <h4 class="editor-title form-promo-blur">
                                                                            <span class="dashicons dashicons-admin-customizer"></span>
                                                                            <span class="custom-form-title"><?php esc_html_e('Edit Color Style', 'capsman-enhanced'); ?></span>
                                                                        </h4>
                                                                        <p class="editor-description form-promo-blur">
                                                                            <?php esc_html_e('Customize colors for different admin elements. Changes are previewed instantly.', 'capsman-enhanced'); ?>
                                                                        </p>

                                                                        <input type="hidden" name="custom_style_action" value="">
                                                                        <input type="hidden" name="custom_style_slug" id="custom_style_slug" value="new">
                                                                        <input type="hidden" name="custom_style_is_builtin" id="custom_style_is_builtin" value="0">

                                                                        <div class="custom-style-tabs editor-tabs">
                                                                            <nav class="nav-tab-wrapper editor-nav-tabs form-promo-blur">
                                                                            <?php
                                                                            $color_tabs = $admin_styles->get_element_color_tabs();
                                                                            $tab_index = 0;
                                                                            foreach ($color_tabs as $tab_key => $tab_data):
                                                                                $is_active = ($tab_index === 0) ? 'nav-tab-active' : '';
                                                                                ?>
                                                                                <a href="#custom-style-tab-<?php echo esc_attr($tab_key); ?>"
                                                                                   class="nav-tab  editor-tab-link custom-style-tab <?php echo esc_attr($is_active); ?>"
                                                                                   data-tab="custom-style-tab-<?php echo esc_attr($tab_key); ?>">
                                                                                    <?php echo esc_html($tab_data['label']); ?>
                                                                                </a>
                                                                                <?php
                                                                                $tab_index++;
                                                                            endforeach;
                                                                            ?>
                                                                            </nav>

                                                                            <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')) : ?>
                                                                                <div class="ppc-menu-row parent-menu pp-promo-overlay-row" style="display: none;">
                                                                                    <div class="pp-promo-upgrade-notice"
                                                                                    style="margin-top: 0;left: 15%;">
                                                                                        <p>
                                                                                            <?php esc_html_e('Add more than one custom admin styles. This feature is available in PublishPress Capabilities Pro.',
                                                                                                'capability-manager-enhanced'); ?>
                                                                                        </p>
                                                                                        <p>
                                                                                            <a href="https://publishpress.com/links/capabilities-banner" target="_blank">
                                                                                                <?php esc_html_e('Upgrade to Pro', 'capability-manager-enhanced'); ?>
                                                                                            </a>
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            <?php endif; ?>

                                                                            <div class="editor-tab-panels form-promo-blur">
                                                                                <?php
                                                                                $tab_index = 0;
                                                                                foreach ($color_tabs as $tab_key => $tab_data):
                                                                                    $is_active = ($tab_index === 0) ? 'active' : '';
                                                                                    ?>
                                                                                    <div id="custom-style-tab-<?php echo esc_attr($tab_key); ?>"
                                                                                         class="editor-tab-pane custom-style-tab-content <?php echo esc_attr($is_active); ?>">

                                                                                        <table class="color-rows">
                                                                                    <tbody>
                                                                                    <?php
                                                                                    $global_field_index = 0;
                                                                                    foreach ($tab_data['colors'] as $color_key => $color_config): ?>
                                                                                        <?php if ($tab_index === 0 && $global_field_index === 0) : ?>
                                                                                        <tr class="color-row" id="custom-style-name-row">
                                                                                            <td class="color-label">
                                                                                                <label for="custom_style_name" class="color-label-text">
                                                                                                    <span id="style-name-label"><?php esc_html_e('Custom Style Name', 'capsman-enhanced'); ?></span> <span class="required" id="style-name-required">*</span>
                                                                                                </label>
                                                                                            </td>
                                                                                            <td class="color-input-cell">
                                                                                                <input type="text"
                                                                                                    name="custom_style_name"
                                                                                                    id="custom_style_name" value=""
                                                                                                    class="regular-text"
                                                                                                    placeholder="<?php esc_attr_e('e.g., Company Branding, Dark Mode', 'capsman-enhanced'); ?>">
                                                                                            </td>
                                                                                        </tr>
                                                                                        <?php endif; ?>
                                                                                        <tr class="color-row">
                                                                                            <td class="color-label">
                                                                                                <label for="custom_style_<?php echo esc_attr($color_key); ?>" class="color-label-text">
                                                                                                    <?php echo esc_html($color_config['label']); ?>
                                                                                                </label>
                                                                                            </td>
                                                                                            <td class="color-input-cell">
                                                                                                <input type="text"
                                                                                                    name="custom_style_<?php echo esc_attr($color_key); ?>"
                                                                                                    id="custom_style_<?php echo esc_attr($color_key); ?>"
                                                                                                    value=""
                                                                                                    class="pp-capabilities-color-picker custom-style-color color-input"
                                                                                                    data-category="<?php echo $tab_key === 'general' ? 'general' : 'element_colors'; ?>"
                                                                                                    data-tab="<?php echo esc_attr($tab_key); ?>"
                                                                                                    data-color-key="<?php echo esc_attr($color_key); ?>">
                                                                                            </td>
                                                                                        </tr>
                                                                                    <?php $global_field_index++; endforeach; ?>
</tbody>
                                                                                </table>
                                                                            </div>
                                                                            <?php
                                                                            $tab_index++;
                                                                        endforeach;
                                                                        ?>
                                                                    </div>

                                                                    <div class="editor-actions form-promo-blur">
                                                                        <div style="display: flex; gap: 10px;">
                                                                            <button type="button"
                                                                                class="button cancel-custom-style">
                                                                                <?php esc_html_e('Cancel', 'capsman-enhanced'); ?>
                                                                            </button>
                                                                            <div class="custom-link-delete ppc-tool-tip click-tooltip" id="custom-style-delete-button" style="display: none; margin-left: auto;">
                                                                                <button type="button"
                                                                                    class="button button-secondary ppc-button-delete"
                                                                                    style="border-color: #d63638 !important;color: #d63638 !important;">
                                                                                    <?php esc_attr_e('Delete Custom Style', 'capsman-enhanced'); ?>
                                                                                </button>

                                                                                <div class="tool-tip-text">
                                                                                    <p><?php printf(__( 'Are you sure you want to delete this %1s? %2s %3s', 'capsman-enhanced' ), '<strong>' . esc_html__('Custom Style', 'capsman-enhanced') . '</strong>', '<br /><input type="submit" name="delete_custom_style" value="'. esc_attr__('Delete Custom Style', 'capsman-enhanced') .'" class="button-link-delete" style="background: none !important;color: #d63638 !important;">', ' | <a class="cancel-click-tooltip" href="#">'. esc_html__('Cancel', 'capsman-enhanced') .'</a>' ); ?></p>
                                                                                        <i></i>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <input type="submit" name="save_custom_style"
                                                                            value="<?php esc_attr_e('Save Custom Style', 'capsman-enhanced'); ?>"
                                                                            class="button-primary">
                                                                    </div>

                                                                    <div id="custom-style-error" class="editor-error">
                                                                    </div>
                                                                        </div>
                                                                    </div>
</tr>


                                                        <tr class="ppc-menu-row parent-menu">
                                                            <td class="menu-column ppc-menu-item">
                                                                <label for="admin_logo">
                                                                    <strong><?php esc_html_e('Custom Admin Logo', 'capability-manager-enhanced'); ?></strong>
                                                                </label>
                                                                <p class="cme-subtext">
                                                                    <?php esc_html_e('Upload a custom logo to replace the WordPress icon in the admin bar. Recommended: 20px by 20px in SVG or PNG format.', 'capability-manager-enhanced'); ?>
                                                                </p>
                                                            </td>
                                                            <td class="value-column ppc-menu-checkbox">
                                                                <div class="pp-capabilities-image-upload">
                                                                    <input type="text" name="settings[admin_logo]"
                                                                        id="admin_logo"
                                                                        value="<?php echo esc_url($settings['admin_logo']); ?>"
                                                                        class="regular-text pp-capabilities-image-url">
                                                                    <span class="logo-preview">
                                                                        <?php if (!empty($settings['admin_logo'])): ?>
                                                                            <img src="<?php echo esc_url($settings['admin_logo']); ?>" style="max-width: 20px; max-height: 20px; vertical-align: middle; margin-right: 5px;">
                                                                        <?php endif; ?>
                                                                    </span>
                                                                    <button type="button"
                                                                        class="button pp-capabilities-upload-button"
                                                                        data-target="admin_logo">
                                                                        <?php esc_html_e('Select Image', 'capability-manager-enhanced'); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="button button-link pp-capabilities-remove-button"
                                                                        data-target="admin_logo">
                                                                        <?php esc_html_e('Remove', 'capability-manager-enhanced'); ?>
                                                                    </button>
                                                                </div>

                                                            </td>
                                                        </tr>

                                                        <tr class="ppc-menu-row parent-menu">
                                                            <td class="menu-column ppc-menu-item">
                                                                <label for="admin_favicon">
                                                                    <strong><?php esc_html_e('Custom Favicon', 'capability-manager-enhanced'); ?></strong>
                                                                </label>
                                                                <p class="cme-subtext">
                                                                    <?php esc_html_e('Upload a custom favicon for the admin area. Recommended: 16px by 16px or 32px by 32px in SVG or PNG format.', 'capability-manager-enhanced'); ?>
                                                                </p>
                                                            </td>
                                                            <td class="value-column ppc-menu-checkbox">
                                                                <div class="pp-capabilities-image-upload">
                                                                    <input type="text" name="settings[admin_favicon]"
                                                                        id="admin_favicon"
                                                                        value="<?php echo esc_url($settings['admin_favicon']); ?>"
                                                                        class="regular-text pp-capabilities-image-url">
                                                                    <span class="favicon-preview">
                                                                        <?php if (!empty($settings['admin_favicon'])): ?>
                                                                            <img src="<?php echo esc_url($settings['admin_favicon']); ?>"
                                                                                style="max-width: 20px; max-height: 20px; vertical-align: middle; margin-right: 5px;">
                                                                        <?php endif; ?>
                                                                    </span>
                                                                    <button type="button"
                                                                        class="button pp-capabilities-upload-button"
                                                                        data-target="admin_favicon">
                                                                        <?php esc_html_e('Select Image', 'capsman-enhanced'); ?>
                                                                    </button>
                                                                    <button type="button"
                                                                        class="button button-link pp-capabilities-remove-button"
                                                                        data-target="admin_favicon">
                                                                        <?php esc_html_e('Remove', 'capsman-enhanced'); ?>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>

                                                        <tr class="ppc-menu-row parent-menu">
                                                            <td class="menu-column ppc-menu-item">
                                                                <label for="admin_custom_footer_text">
                                                                    <strong><?php esc_html_e('Admin Footer Text', 'capability-manager-enhanced'); ?></strong>
                                                                </label>
                                                                <p class="cme-subtext">
                                                                    <?php esc_html_e('Replace the default "Thanks for creating with WordPress" message in the admin footer.', 'capability-manager-enhanced'); ?>
                                                                </p>
                                                            </td>
                                                            <td class="value-column ppc-menu-checkbox">
                                                                <textarea name="settings[admin_custom_footer_text]"
                                                                    id="admin_custom_footer_text" rows="3"
                                                                    style="resize: none;"
                                                                    class="large-text"><?php echo esc_textarea($settings['admin_custom_footer_text']); ?></textarea>
                                                            </td>
                                                        </tr>

                                                        <tr class="ppc-menu-row parent-menu">
                                                            <td class="menu-column ppc-menu-item">
                                                                <label for="admin_replace_howdy">
                                                                    <strong><?php esc_html_e('Replace "Howdy"', 'capability-manager-enhanced'); ?></strong>
                                                                </label>
                                                                <p class="cme-subtext">
                                                                    <?php esc_html_e('Replace "Howdy" in the admin bar.', 'capability-manager-enhanced'); ?>
                                                                </p>
                                                            </td>
                                                            <td class="value-column ppc-menu-checkbox">
                                                                <input type="text" name="settings[admin_replace_howdy]"
                                                                    id="admin_replace_howdy"
                                                                    value="<?php echo esc_attr($settings['admin_replace_howdy']); ?>"
                                                                    style="width: 99%" class="regular-text">
                                                            </td>
                                                        </tr>

                                                        <tr class="ppc-menu-row parent-menu">
                                                            <td class="menu-column ppc-menu-item">
                                                                <strong><?php esc_html_e('Control Settings', 'capability-manager-enhanced'); ?></strong>
                                                                <p class="cme-subtext">
                                                                    <?php esc_html_e('Control how admin styles are applied to users.', 'capability-manager-enhanced'); ?>
                                                                </p>
                                                            </td>
                                                            <td class="value-column ppc-menu-checkbox">
                                                                <fieldset>
                                                                    <label style="display: block; margin-bottom: 15px;">
                                                                        <input type="checkbox"
                                                                            name="settings[hide_color_scheme_ui]"
                                                                            value="1" <?php checked(!empty($settings['hide_color_scheme_ui'])); ?>>
                                                                        <strong><?php esc_html_e('Hide Admin Color Scheme settings in user profile', 'capability-manager-enhanced'); ?></strong>
                                                                        <p class="cme-subtext">
                                                                            <?php esc_html_e('When enabled, users cannot see or change color scheme settings in their profile. Role-based settings will be applied.', 'capability-manager-enhanced'); ?>
                                                                        </p>
                                                                    </label>

                                                                    <label style="display: block;">
                                                                        <input type="checkbox"
                                                                            name="settings[force_role_settings]"
                                                                            value="1" <?php checked(!empty($settings['force_role_settings'])); ?>>
                                                                        <strong><?php esc_html_e('Force role settings (override user selection)', 'capability-manager-enhanced'); ?></strong>
                                                                        <p class="cme-subtext">
                                                                            <?php esc_html_e('When enabled, role settings will always apply, overriding any user selection even if the color scheme UI is visible to them.', 'capability-manager-enhanced'); ?>
                                                                        </p>
                                                                    </label>
                                                                </fieldset>
                                                            </td>
                                                        </tr>

                                                    </tbody>
                                                </table>

                                        </div>
                                    </div>

                                </div>
                            </div>


                            <div class="editor-features-footer-meta">
                                <div style="display: flex;gap: 10px;float:right;margin-top: 20px;">

                                    <input type="submit" name="admin-styles-all-submit"
                                        value="<?php esc_attr_e('Save for all Roles', 'capability-manager-enhanced') ?>"
                                        class="button-secondary ppc-admin-styles-submit" style="float:right" />

                                    <input type="submit" name="admin-styles-submit"
                                        value="<?php echo esc_attr(sprintf(esc_html__('Save for %s', 'capability-manager-enhanced'), esc_html($current_role_name))); ?>"
                                        class="button-primary ppc-admin-styles-submit" style="float:right" />

                                </div>
                            </div>

                        </td>
                    </tr>
                </table>
            </div><!-- .pp-column-left -->
            <div class="pp-column-right pp-capabilities-sidebar">
                <?php
                $banner_messages = ['<p>'];
                $banner_messages[] = esc_html__('Admin Styles allows you to customize the WordPress admin area with your own branding.', 'capability-manager-enhanced');
                $banner_messages[] = '</p><p>';
                $banner_messages[] = '<strong>' . esc_html__('Features include:', 'capability-manager-enhanced') . '</strong>';
                $banner_messages[] = '<ul class="pp-features-list">';
                $banner_messages[] = '<li class="pp-features-list-item">' . esc_html__('Custom admin color schemes', 'capability-manager-enhanced') . '</li>';
                $banner_messages[] = '<li class="pp-features-list-item">' . esc_html__('Replace the WordPress logo and favicon', 'capability-manager-enhanced') . '</li>';
                $banner_messages[] = '<li class="pp-features-list-item">' . esc_html__('Custom footer text', 'capability-manager-enhanced') . '</li>';
                $banner_messages[] = '<li class="pp-features-list-item">' . esc_html__('Replace "Howdy" text', 'capability-manager-enhanced') . '</li>';
                $banner_messages[] = '</ul>';
                $banner_messages[] = '<p><a class="button ppc-checkboxes-documentation-link" href="https://publishpress.com/knowledge-base/admin-styles/" target="blank">' . esc_html__('View Documentation', 'capability-manager-enhanced') . '</a></p>';
                $banner_title = __('How to use Admin Styles', 'capability-manager-enhanced');
                pp_capabilities_sidebox_banner($banner_title, $banner_messages);
                // add promo sidebar
                pp_capabilities_pro_sidebox();
                ?>
            </div><!-- .pp-column-right -->
        </div><!-- .pp-columns-wrapper -->
    </form>

    <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
        cme_publishpressFooter();
    }
    ?>
</div>
<?php
?>