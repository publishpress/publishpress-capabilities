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
    // Form was just submitted - reload fresh settings
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
?>
<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper admin-styles">
    <div id="icon-capsman-admin" class="icon32"></div>
    <h2><?php esc_html_e('Admin Styles', 'capability-manager-enhanced'); ?></h2>

    <form method="post" id="ppc-admin-styles-form"
        action="<?php echo esc_url(admin_url('admin.php?page=pp-capabilities-admin-styles')); ?>">
        <?php wp_nonce_field('pp-capabilities-admin-styles', '_wpnonce'); ?>
        <input type="hidden" name="current_user_color" id="current_user_color"
            value="<?php echo esc_attr($current_user_color); ?>" />

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
                                <div class="pp-capabilities-submit-top" style="float:right; margin-bottom: 20px;">
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

                                            <div id="admin-styles-general" class="admin-styles-tab-content active">
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
                                                                            $is_custom = ($key === 'publishpress-custom');
                                                                            $is_selected = ($settings['admin_color_scheme'] === $key);
                                                                            $is_current_user = ($current_user_color === $key);
                                                                            ?>
                                                                            <div
                                                                                class="color-option <?php echo ($key === 'publishpress-custom') ? 'ppc-custom-scheme' : ''; ?> <?php echo $is_selected ? 'selected' : ''; ?>">
                                                                                <input type="radio"
                                                                                    name="admin_color_scheme_radio"
                                                                                    id="color-<?php echo esc_attr($key); ?>"
                                                                                    value="<?php echo esc_attr($key); ?>"
                                                                                    data-scheme="<?php echo esc_attr($key); ?>"
                                                                                    <?php checked($is_selected); ?>
                                                                                    class="tog" />
                                                                                <label
                                                                                    for="color-<?php echo esc_attr($key); ?>">
                                                                                    <?php echo esc_html($name); ?>
                                                                                    <?php if ($key === 'publishpress-custom'): ?>
                                                                                        <span class="custom-scheme-edit-icon"
                                                                                            title="<?php esc_attr_e('Edit custom colors', 'capsman-enhanced'); ?>">
                                                                                            <span
                                                                                                class="dashicons dashicons-edit"></span>
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
                                                                                        <!-- Default color preview for unknown schemes -->
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


                                                        <tr class="ppc-menu-row parent-menu">
                                                            <td colspan="2" class="value-column ppc-menu-checkbox">
                                                                <fieldset class="ppc-admin-color-schemes">
                                                                    <!-- Custom Scheme Color Editor (hidden by default) -->
                                                                    <div class="custom-scheme-editor"
                                                                        style="<?php echo ($settings['admin_color_scheme'] !== 'publishpress-custom') ? 'display: none;' : ''; ?> margin-top: 20px; padding: 20px; background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px;">
                                                                        <h4
                                                                            style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                                                                            <span
                                                                                class="dashicons dashicons-admin-customizer"></span>
                                                                            <?php esc_html_e('Custom Color Scheme Editor', 'capsman-enhanced'); ?>
                                                                        </h4>
                                                                        <p class="cme-subtext" style="margin-top: 5px;">
                                                                            <?php esc_html_e('Choose the colors for your custom scheme. Changes are previewed instantly.', 'capsman-enhanced'); ?>
                                                                        </p>

                                                                        <table
                                                                            class="custom-scheme-colors color-picker-row"
                                                                            style="width: 100%; margin-top: 15px;">
                                                                            <tr>
                                                                                <td
                                                                                    style="padding: 10px 0; width: 200px;">
                                                                                    <label for="custom_scheme_base">
                                                                                        <strong><?php esc_html_e('Base Color', 'capsman-enhanced'); ?></strong>
                                                                                    </label>
                                                                                    <p class="cme-subtext">
                                                                                        <?php esc_html_e('Primary color for menus and buttons', 'capsman-enhanced'); ?>
                                                                                    </p>
                                                                                </td>
                                                                                <td style="padding: 10px 0;">
                                                                                    <input type="text"
                                                                                        name="settings[custom_scheme_base]"
                                                                                        id="custom_scheme_base"
                                                                                        value="<?php echo esc_attr($settings['custom_scheme_base'] ?? '#655997'); ?>"
                                                                                        class="pp-capabilities-color-picker custom-scheme-color"
                                                                                        data-preview="true">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding: 10px 0;">
                                                                                    <label for="custom_scheme_text">
                                                                                        <strong><?php esc_html_e('Text Color', 'capsman-enhanced'); ?></strong>
                                                                                    </label>
                                                                                    <p class="cme-subtext">
                                                                                        <?php esc_html_e('Text color on colored backgrounds', 'capsman-enhanced'); ?>
                                                                                    </p>
                                                                                </td>
                                                                                <td style="padding: 10px 0;">
                                                                                    <input type="text"
                                                                                        name="settings[custom_scheme_text]"
                                                                                        id="custom_scheme_text"
                                                                                        value="<?php echo esc_attr($settings['custom_scheme_text'] ?? '#ffffff'); ?>"
                                                                                        class="pp-capabilities-color-picker custom-scheme-color"
                                                                                        data-preview="true">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding: 10px 0;">
                                                                                    <label
                                                                                        for="custom_scheme_highlight">
                                                                                        <strong><?php esc_html_e('Highlight Color', 'capsman-enhanced'); ?></strong>
                                                                                    </label>
                                                                                    <p class="cme-subtext">
                                                                                        <?php esc_html_e('Hover and active states', 'capsman-enhanced'); ?>
                                                                                    </p>
                                                                                </td>
                                                                                <td style="padding: 10px 0;">
                                                                                    <input type="text"
                                                                                        name="settings[custom_scheme_highlight]"
                                                                                        id="custom_scheme_highlight"
                                                                                        value="<?php echo esc_attr($settings['custom_scheme_highlight'] ?? '#8a7bb9'); ?>"
                                                                                        class="pp-capabilities-color-picker custom-scheme-color"
                                                                                        data-preview="true">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding: 10px 0;">
                                                                                    <label
                                                                                        for="custom_scheme_notification">
                                                                                        <strong><?php esc_html_e('Notification Color', 'capsman-enhanced'); ?></strong>
                                                                                    </label>
                                                                                    <p class="cme-subtext">
                                                                                        <?php esc_html_e('Error and warning indicators', 'capsman-enhanced'); ?>
                                                                                    </p>
                                                                                </td>
                                                                                <td style="padding: 10px 0;">
                                                                                    <input type="text"
                                                                                        name="settings[custom_scheme_notification]"
                                                                                        id="custom_scheme_notification"
                                                                                        value="<?php echo esc_attr($settings['custom_scheme_notification'] ?? '#d63638'); ?>"
                                                                                        class="pp-capabilities-color-picker custom-scheme-color"
                                                                                        data-preview="true">
                                                                                </td>
                                                                            </tr>
                                                                            <tr>
                                                                                <td style="padding: 10px 0;">
                                                                                    <label
                                                                                        for="custom_scheme_background">
                                                                                        <strong><?php esc_html_e('Background Color', 'capsman-enhanced'); ?></strong>
                                                                                    </label>
                                                                                    <p class="cme-subtext">
                                                                                        <?php esc_html_e('Page and container backgrounds', 'capsman-enhanced'); ?>
                                                                                    </p>
                                                                                </td>
                                                                                <td style="padding: 10px 0;">
                                                                                    <input type="text"
                                                                                        name="settings[custom_scheme_background]"
                                                                                        id="custom_scheme_background"
                                                                                        value="<?php echo esc_attr($settings['custom_scheme_background'] ?? '#f0f0f1'); ?>"
                                                                                        class="pp-capabilities-color-picker custom-scheme-color"
                                                                                        data-preview="true">
                                                                                </td>
                                                                            </tr>
                                                                        </table>

                                                                        <div style="display:none;" class="custom-scheme-preview-area"
                                                                            style="margin-top: 20px; padding: 15px; background: <?php echo esc_attr($settings['custom_scheme_background'] ?? '#f0f0f1'); ?>; border-radius: 4px; border: 1px solid #dcdcde;">
                                                                            <div
                                                                                style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                                                                <div
                                                                                    style="background: <?php echo esc_attr($settings['custom_scheme_base'] ?? '#655997'); ?>; color: <?php echo esc_attr($settings['custom_scheme_text'] ?? '#ffffff'); ?>; padding: 8px 15px; border-radius: 4px; font-weight: 500;">
                                                                                    <?php esc_html_e('Primary Button', 'capsman-enhanced'); ?>
                                                                                </div>
                                                                                <div
                                                                                    style="background: <?php echo esc_attr($settings['custom_scheme_highlight'] ?? '#8a7bb9'); ?>; color: <?php echo esc_attr($settings['custom_scheme_text'] ?? '#ffffff'); ?>; padding: 8px 15px; border-radius: 4px; font-weight: 500;">
                                                                                    <?php esc_html_e('Hover State', 'capsman-enhanced'); ?>
                                                                                </div>
                                                                                <div
                                                                                    style="background: <?php echo esc_attr($settings['custom_scheme_notification'] ?? '#d63638'); ?>; color: <?php echo esc_attr($settings['custom_scheme_text'] ?? '#ffffff'); ?>; padding: 8px 15px; border-radius: 4px; font-weight: 500;">
                                                                                    <?php esc_html_e('Notification', 'capsman-enhanced'); ?>
                                                                                </div>
                                                                            </div>
                                                                            <p class="cme-subtext"
                                                                                style="margin-top: 10px; margin-bottom: 0;">
                                                                                <?php esc_html_e('Live preview of your custom color scheme', 'capsman-enhanced'); ?>
                                                                            </p>
                                                                        </div>
                                                                </fieldset>
                                                            </td>
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
                                                                            <img src="<?php echo esc_url($settings['admin_favicon']); ?>" style="max-width: 20px; max-height: 20px; vertical-align: middle; margin-right: 5px;">
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
                            </div>


                            <div class="editor-features-footer-meta">
                                <div style="float:right;margin-top: 20px;">

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