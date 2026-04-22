<?php
/**
 * Admin Notices settings page.
 */

$notice_type_options = [
    'success' => esc_html__('Success notices', 'capability-manager-enhanced'),
    'error' => esc_html__('Error notices', 'capability-manager-enhanced'),
    'warning' => esc_html__('Warning notices', 'capability-manager-enhanced'),
    'info' => esc_html__('Info notices', 'capability-manager-enhanced'),
];

global $capsman;

$roles        = wp_roles()->roles;
$default_role = $capsman->get_last_role();
$admin_notice_settings = (array) get_option('cme_admin_notice_options', []);
$selected_role = $default_role;

$selected_role_name = isset($roles[$selected_role]['name'])
    ? translate_user_role($roles[$selected_role]['name'])
    : esc_html__('Current Role', 'capability-manager-enhanced');

$selected_role_settings = isset($admin_notice_settings[$selected_role]) && is_array($admin_notice_settings[$selected_role])
    ? $admin_notice_settings[$selected_role]
    : [];
$toolbar_access = !empty($selected_role_settings['enable_toolbar_access']);
$notice_type_remove = !empty($selected_role_settings['notice_type_remove']) ? (array) $selected_role_settings['notice_type_remove'] : [];
$notice_type_display = !empty($selected_role_settings['notice_type_display']) ? (array) $selected_role_settings['notice_type_display'] : [];
?>

<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper admin-notices">
    <h2><?php esc_html_e('Admin Notices', 'capability-manager-enhanced'); ?></h2>

    <form class="basic-settings" method="post" action="<?php echo esc_url(admin_url('admin.php?page=pp-capabilities-admin-notices')); ?>">
        <?php wp_nonce_field('pp-capabilities-admin-notices'); ?>
        <input type="hidden" name="page" value="pp-capabilities-admin-notices">

        <div class="pp-columns-wrapper pp-enable-sidebar clear">
            <div class="pp-column-left">
                <fieldset>
                <table id="akmin">
                    <tr>
                        <td class="content">

                            <div class="publishpress-filters">
                                <select name="ppc-admin-notices-role" class="ppc-admin-notices-role">
                                    <?php foreach ($roles as $role => $detail) : ?>
                                        <option value="<?php echo esc_attr($role); ?>" <?php selected($selected_role, $role); ?>>
                                            <?php echo esc_html(translate_user_role($detail['name'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select> &nbsp;

                                    <img class="loading" src="<?php echo esc_url_raw(plugin_dir_url(CME_FILE) . 'images/wpspin_light.gif'); ?>" style="display: none">
                            </div>

                            <div class="pp-capabilities-submit-top" style="display: flex;gap: 10px;float:right; margin-bottom: 20px;">
                                <input type="submit" name="ppc-admin-notices-all-submit"
                                    value="<?php esc_attr_e('Save for all Roles', 'capability-manager-enhanced') ?>"
                                    class="button-secondary" style="float:right" />

                                <input type="submit" name="ppc-admin-notices-submit"
                                    value="<?php echo esc_attr(sprintf(esc_html__('Save for %s', 'capability-manager-enhanced'), esc_html($selected_role_name))); ?>"
                                    class="button-primary" style="float:right" />
                            </div>
                            <div class="clear"></div>

                            <div id="pp-capability-menu-wrapper" class="postbox" style="border: none;">
                                <div class="pp-capability-menus">
                                    <div class="pp-capability-menus-wrap">
                                        <div id="pp-capability-menus-general" class="pp-capability-menus-content editable-role" style="display: block;">
                                            <div id="ppc-capabilities-wrapper" class="postbox" style="display: block;">
                                            <div class="ppc-capabilities-content">
                                            <table class="form-table" role="presentation" id="ppcs-tab-admin-notices">
                                                <tbody>
                                                    <tr>
                                                        <th scope="row"><?php esc_html_e('Notification center access', 'capability-manager-enhanced'); ?></th>
                                                        <td>
                                                            <label>
                                                                <input
                                                                    type="checkbox"
                                                                    name="cme_admin_notice_options[enable_toolbar_access]"
                                                                    id="cme_admin_notice_options_enable_toolbar_access"
                                                                    value="1"
                                                                    <?php checked($toolbar_access, true); ?>
                                                                >
                                                                <span class="description">
                                                                    <?php printf(esc_html__('Enable %1$s access to the Admin Notices area.', 'capability-manager-enhanced'), esc_html($selected_role_name)); ?>
                                                                </span>
                                                            </label>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th scope="row"><?php esc_html_e('Notifications to remove from WordPress admin pages', 'capability-manager-enhanced'); ?></th>
                                                        <td>
                                                            <?php foreach ($notice_type_options as $option_key => $option_label) : ?>
                                                                <label>
                                                                    <input
                                                                        type="checkbox"
                                                                        name="cme_admin_notice_options[notice_type_remove][]"
                                                                        id="cme_admin_notice_options_notice_type_remove_<?php echo esc_attr($option_key); ?>"
                                                                        value="<?php echo esc_attr($option_key); ?>"
                                                                        <?php checked(in_array($option_key, $notice_type_remove, true), true); ?>
                                                                    >
                                                                    <?php echo esc_html($option_label); ?>
                                                                </label>
                                                                <br><br>
                                                            <?php endforeach; ?>

                                                            <span class="description">
                                                                <?php printf(esc_html__('Select the notification types that should be hidden when a user in the %1$s role is viewing WordPress admin screens.', 'capability-manager-enhanced'), esc_html($selected_role_name)); ?> <a target="_blank" href="https://publishpress.com/knowledge-base/notice-types"><?php esc_html_e('Click here for more on notice types.', 'capability-manager-enhanced'); ?></a>
                                                            </span>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th scope="row"><?php esc_html_e('Notifications to display in the Admin Notices area.', 'capability-manager-enhanced'); ?></th>
                                                        <td>
                                                            <?php foreach ($notice_type_options as $option_key => $option_label) : ?>
                                                                <label>
                                                                    <input
                                                                        type="checkbox"
                                                                        name="cme_admin_notice_options[notice_type_display][]"
                                                                        id="cme_admin_notice_options_notice_type_display_<?php echo esc_attr($option_key); ?>"
                                                                        value="<?php echo esc_attr($option_key); ?>"
                                                                        <?php checked(in_array($option_key, $notice_type_display, true), true); ?>
                                                                    >
                                                                    <?php echo esc_html($option_label); ?>
                                                                </label>
                                                                <br><br>
                                                            <?php endforeach; ?>

                                                            <span class="description">
                                                                <?php esc_html_e('Select the notification types that should be displayed in the Admin Notices area after been removed from the WordPress admin screens.', 'capability-manager-enhanced'); ?> <a target="_blank" href="https://publishpress.com/knowledge-base/notice-types"><?php esc_html_e('Click here for more on notice types.', 'capability-manager-enhanced'); ?></a>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="editor-features-footer-meta">
                                <div style="display: flex;gap: 10px;float:right;margin-top: 20px;">
                                    <input type="submit" name="ppc-admin-notices-all-submit"
                                        value="<?php esc_attr_e('Save for all Roles', 'capability-manager-enhanced') ?>"
                                        class="button-secondary" style="float:right" />

                                    <input type="submit" name="ppc-admin-notices-submit"
                                        value="<?php echo esc_attr(sprintf(esc_html__('Save for %s', 'capability-manager-enhanced'), esc_html($selected_role_name))); ?>"
                                        class="button-primary" style="float:right" />
                                </div>
                                <div class="clear"></div>
                            </div>

                        </td>
                    </tr>
                </table>
                </fieldset>
            </div><!-- .pp-column-left -->
            <div class="pp-column-right pp-capabilities-sidebar">
                <?php
                $banner_messages = ['<p>'];
                $banner_messages[] = esc_html__('Admin Notices allows you to clean up the WordPress admin area by organizing extra messages and advertisements into one place.', 'capability-manager-enhanced');
                $banner_messages[] = '</p><p>';
                $banner_messages[] = '<strong>' . esc_html__('Features include:', 'capability-manager-enhanced') . '</strong>';
                $banner_messages[] = '<ul class="pp-features-list">';
                $banner_messages[] = '<li class="pp-features-list-item">' . esc_html__('Move selected notice types out of admin pages', 'capability-manager-enhanced') . '</li>';
                $banner_messages[] = '<li class="pp-features-list-item">' . esc_html__('Show selected notices in a dedicated Admin Notices panel', 'capability-manager-enhanced') . '</li>';
                $banner_messages[] = '<li class="pp-features-list-item">' . esc_html__('Configure notice visibility by user role', 'capability-manager-enhanced') . '</li>';
                $banner_messages[] = '</ul>';
                $banner_messages[] = '<p><a class="button ppc-checkboxes-documentation-link" href="https://publishpress.com/knowledge-base/notice-types" target="blank">' . esc_html__('View Documentation', 'capability-manager-enhanced') . '</a></p>';
                $banner_title = __('How to use Admin Notices', 'capability-manager-enhanced');
                pp_capabilities_sidebox_banner($banner_title, $banner_messages);
                pp_capabilities_pro_sidebox();
                ?>
            </div>
        </div>
    </form>

    <script>
    jQuery(document).ready(function ($) {
        $(document).on('change', '.pp-capability-menus-wrapper .ppc-admin-notices-role', function () {
            $('.pp-capability-menus-wrapper .ppc-admin-notices-role').attr('disabled', true);
            $('.pp-capabilities-submit-top').hide();
            $('.editor-features-footer-meta').hide();
            $('#pp-capability-menu-wrapper').hide();
            $('div.publishpress-caps-manage img.loading').show();

            window.location = '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-admin-notices&role=')); ?>' + $(this).val();
        });
    });
    </script>

    <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
        cme_publishpressFooter();
    } ?>
</div>
