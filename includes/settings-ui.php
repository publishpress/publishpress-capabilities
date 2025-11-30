<?php
/*
 * PublishPress Capabilities
 *
 * Plugin settings UI
 *
 */

class Capabilities_Settings_UI {
    public function __construct() {
        $this->settingsUI();
    }

    public function settingsUI() {
        $all_options        = pp_capabilities_settings_options();

        if (defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            $default_tab = (!empty($_REQUEST['pp_tab'])) ? sanitize_key($_REQUEST['pp_tab']) : '';
        } else {
            $default_tab = (!empty($_REQUEST['pp_tab'])) ? sanitize_key($_REQUEST['pp_tab']) : 'roles';
        }

        $sidebar_enabled = defined('PUBLISHPRESS_CAPS_PRO_VERSION') ? false : true;

        // TODO: Organize settings tab to an array with label, key and enable/disable options
        $admin_notice_tab_style = pp_capabilities_feature_enabled('admin-notices') ? '' : 'display: none;';
        ?>

        <div class="pp-columns-wrapper <?php echo ($sidebar_enabled) ? 'pp-enable-sidebar' : ''; ?> clear">
            <div class="pp-column-left">
                <ul id="publishpress-capability-settings-tabs" class="nav-tab-wrapper">
                    <?php do_action('pp_capabilities_settings_before_menu_list'); ?>
                    <li class="nav-tab <?php if ('roles' == $default_tab) echo 'nav-tab-active'?>"><a href="#ppcs-tab-roles"><?php esc_html_e('Roles', 'capability-manager-enhanced');?></a></li>
                    <li class="nav-tab <?php if ('capabilities' == $default_tab) echo 'nav-tab-active'?>"><a href="#ppcs-tab-capabilities"><?php esc_html_e('Capabilities', 'capability-manager-enhanced');?></a></li>
                    <li class="nav-tab <?php if ('editor-features' == $default_tab) echo 'nav-tab-active'?>"><a href="#ppcs-tab-editor-features"><?php esc_html_e('Editor Features', 'capability-manager-enhanced');?></a></li>
                    <li class="nav-tab <?php if ('profile-features' == $default_tab) echo 'nav-tab-active'?>"><a href="#ppcs-tab-profile-features"><?php esc_html_e('Profile Features', 'capability-manager-enhanced');?></a></li>
                    <li class="nav-tab <?php if ('admin-notices' == $default_tab) echo 'nav-tab-active'?>" style="<?php echo esc_attr($admin_notice_tab_style); ?>"><a href="#ppcs-tab-admin-notices"><?php esc_html_e('Admin Notices', 'capability-manager-enhanced');?></a></li>
                    <?php do_action('pp_capabilities_settings_after_menu_list'); ?>
                    <li class="nav-tab <?php if ('test-user' == $default_tab) echo 'nav-tab-active'?>"><a href="#ppcs-tab-test-user"><?php esc_html_e('User Testing', 'capability-manager-enhanced');?></a></li>
                </ul>

                <fieldset>
                    <table id="akmin">
                        <tr>
                            <td class="content">

                            <?php do_action('pp_capabilities_settings_before_menu_content'); ?>

                            <table class="form-table" role="presentation" id="ppcs-tab-roles" style="<?php if ('roles' != $default_tab) echo 'display: none'?>">
                                <tbody>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_capabilities_add_user_multi_roles', 0)), true, false);
                                    ?>
                                    <th scope="row"><?php esc_html_e('Multiples roles on "Add New User" screen', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_capabilities_add_user_multi_roles" id="cme_capabilities_add_user_multi_roles" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('This allows you to assign a new user to multiples roles.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                </tr>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_capabilities_edit_user_multi_roles', 0)), true, false);
                                    ?>
                                    <th scope="row"><?php esc_html_e('Multiples roles on "User Edit" screen', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_capabilities_edit_user_multi_roles" id="cme_capabilities_edit_user_multi_roles" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('This allows you to assign an existing user to multiple roles.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                </tr>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_role_same_page_redirect_cookie', 0)), true, false);
                                    ?>
                                    <th scope="row"><?php esc_html_e('Set login redirect cookie', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_role_same_page_redirect_cookie" id="cme_role_same_page_redirect_cookie" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('Use cookie to determine pages users were viewing before login. This is useful when login redirect is not working correctly due to wp_get_referer() limitation.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                            <table class="form-table" role="presentation" id="ppcs-tab-capabilities" style="<?php if ('capabilities' != $default_tab) echo 'display: none'?>">
                                <tbody>

                                <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_capabilities_show_private_taxonomies', 0)), true, false);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Show private taxonomies', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_capabilities_show_private_taxonomies" id="cme_capabilities_show_private_taxonomies" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('This will show all taxonomies on the "Capabilities" screen, even ones normally hidden in the WordPress admin area.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                </tr>

                                <?php do_action('pp_capabilities_settings_after_capabilities_content'); ?>

                                </tbody>
                            </table>

                            <table class="form-table" role="presentation" id="ppcs-tab-editor-features" style="<?php if ('editor-features' != $default_tab) echo 'display: none'?>">
                                <tbody>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_editor_features_private_post_type', 0)), true, false);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Show private post types', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_editor_features_private_post_type" id="cme_editor_features_private_post_type" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('Show all post types on the "Editor Features" screen, even ones normally hidden in the WordPress admin area.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                </tr>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_editor_features_classic_editor_tab', 0)), true, false);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Enable Classic Editor tab', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_editor_features_classic_editor_tab" id="cme_editor_features_classic_editor_tab" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('Always show the Classic Editor tab in "Editor Features" screen.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                </tr>

                                </tbody>
                            </table>

                            <table class="form-table" role="presentation" id="ppcs-tab-test-user" style="<?php if ('test-user' != $default_tab) echo 'display: none'?>">
                                <tbody>
                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_test_user_admin_bar', 1)), true, false);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Admin Bar modification', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_test_user_admin_bar" id="cme_test_user_admin_bar" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('When testing, display a caption and return link in the Admin Bar.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                    </tr>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_test_user_admin_bar_search', 1)), true, false);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Admin Bar search', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_test_user_admin_bar_search" id="cme_test_user_admin_bar_search" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('Add option to search and user testing in the Admin Bar.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                    </tr>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_test_user_footer_notice', 1)), true, false);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Front End footer notice', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_test_user_footer_notice" id="cme_test_user_footer_notice" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('When testing, display a return link in the front end footer.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                    </tr>

                                    <tr>
                                    <?php
                                        $excluded_roles = (array) get_option('cme_test_user_excluded_roles', []);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Exclude role from User Testing', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                            <select
                                                name="cme_test_user_excluded_roles[]"
                                                id="cme_test_user_excluded_roles"
                                                class="pp-capabilities-settings-chosen"
                                                data-placeholder="<?php esc_attr_e('Select roles...', 'capability-manager-enhanced'); ?>"
                                                multiple
                                            >
                                                <?php foreach (wp_roles()->roles as $role => $detail) : ?>
                                                    <option
                                                        value="<?php echo esc_attr($role); ?>"
                                                        <?php selected(in_array($role, $excluded_roles), true); ?>
                                                        >
                                                        <?php echo esc_html(translate_user_role($detail['name'])); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <br />
                                            <span class="description">
                                                <?php esc_html_e('Exclude users in selected roles from User Testing.', 'capability-manager-enhanced'); ?>
                                            </span>
                                        </label>
                                        <br>
                                    </td>
                                    </tr>

                                </tbody>
                            </table>

                            <table class="form-table" role="presentation" id="ppcs-tab-profile-features" style="<?php if ('profile-features' != $default_tab) echo 'display: none'?>">
                                <tbody>

                                    <tr>
                                    <?php
                                        $checked = checked(!empty(get_option('cme_profile_features_auto_redirect', 0)), true, false);
                                    ?>
                                    <th scope="row"> <?php esc_html_e('Automatically refresh profile elements', 'capability-manager-enhanced'); ?></th>
                                    <td>
                                        <label>
                                        <input type="checkbox" name="cme_profile_features_auto_redirect" id="cme_profile_features_auto_redirect" autocomplete="off" value="1" <?php echo $checked;?>>
                                        <span class="description">
                                            <?php esc_html_e('Always try to automatically find profile elements. This may cause temporary issues when updating user roles that do not have access to the WordPress admin area.', 'capability-manager-enhanced'); ?>
                                        </span>
                                        </label>
                                        <br>
                                    </td>
                                </tr>

                                </tbody>
                            </table>

                            <table class="form-table" role="presentation" id="ppcs-tab-admin-notices" style="<?php if ('admin-notices' != $default_tab) echo 'display: none'?>">
                                <?php
                                    $notice_type_options = [
                                        'success' => esc_html__('Success notices', 'capability-manager-enhanced'),
                                        'error'  => esc_html__('Error notices', 'capability-manager-enhanced'),
                                        'warning' => esc_html__('Warning notices', 'capability-manager-enhanced'),
                                        'info' => esc_html__('Info notices', 'capability-manager-enhanced'), 
                                    ];

                                    $admin_notice_settings = (array) get_option('cme_admin_notice_options', []);
                                ?>
                                <tbody>
                                    <tr>
                                        <td colspan="2" style="padding-left: 0;padding-top: 0;">
                                            <p class="description">
                                                <?php esc_html_e('The feature attempts to clean up the WordPress admin area by organizing extra messages and advertisements into the Admin Notices area. This will not remove any messages that appear when you perform an action.', 'capability-manager-enhanced'); ?>
                                            </p>
                                            <p style="margin-top: 10px;">
                                                <label>
                                                    <span><?php esc_html_e('Select Role', 'capability-manager-enhanced'); ?>:</span>
                                                    <select class="ppc-settings-role-subtab">
                                                        <?php 
                                                        $table_default_tab_role = '';
                                                        $table_tabs = [];
                                                        foreach (wp_roles()->roles as $role => $detail) : 
                                                            if ($table_default_tab_role == '') {
                                                                $table_default_tab_role = $role;
                                                            }
                                                            $active_option = ($table_default_tab_role == $role);
                                                            ?>
                                                            <option 
                                                                value="tab1" 
                                                                data-content="<?php echo esc_attr('.pp-admin-notices-settings-' . $role . '-content'); ?>"
                                                                <?php selected($active_option, true); ?>
                                                            >
                                                                <?php echo esc_html(translate_user_role($detail['name'])); ?>
                                                            </option>
                                                            <?php     
                                                        endforeach; ?>
                                                        
                                                            <?php 
                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo join(' | ', $table_tabs); 
                                                            ?>
                                                    </select>
                                                </label>
                                            </p>
                                        </td>
                                    </tr>
                                    <?php foreach (wp_roles()->roles as $role => $detail) :
                                        $visibility_class = ($table_default_tab_role == $role) ? '' : 'hidden-element';
                                        ?>
                                        <tr class="ppc-settings-tab-content pp-admin-notices-settings-<?php echo esc_attr($role); ?>-content <?php echo esc_attr($visibility_class); ?>">
                                            <?php
                                                $toolbar_access = !empty($admin_notice_settings[$role]['enable_toolbar_access']);
                                            ?>
                                            <th scope="row"><?php esc_html_e('Notification center access', 'capability-manager-enhanced'); ?></th>
                                            <td>
                                                <label>
                                                    <input type="checkbox" 
                                                        name="cme_admin_notice_options[<?php echo esc_attr($role); ?>][enable_toolbar_access]" id="cme_admin_notice_options_<?php echo esc_attr($role); ?>_enable_toolbar_access" 
                                                        value="1" 
                                                        <?php checked($toolbar_access, true);?>
                                                    >
                                                    <span class="description">
                                                        <?php printf(esc_html__('Enable %1s access to the Admin Notices area.', 'capability-manager-enhanced'), esc_html(translate_user_role($detail['name']))); ?>
                                                    </span>
                                                </label>
                                                <br>
                                            </td>
                                        </tr>

                                        <tr class="ppc-settings-tab-content pp-admin-notices-settings-<?php echo esc_attr($role); ?>-content <?php echo esc_attr($visibility_class); ?>">
                                        <?php
                                            $notice_type_remove = !empty($admin_notice_settings[$role]['notice_type_remove']) ? $admin_notice_settings[$role]['notice_type_remove'] : [];
                                        ?>
                                            <th scope="row"> <?php esc_html_e('Notifications to remove from WordPress admin pages', 'capability-manager-enhanced'); ?></th>
                                            <td>
                                                <?php foreach ($notice_type_options as $option_key => $option_label) : ?>
                                                <label>
                                                    <input type="checkbox" 
                                                        name="cme_admin_notice_options[<?php echo esc_attr($role); ?>][notice_type_remove][]" id="cme_admin_notice_options_<?php echo esc_attr($role); ?>_notice_type_remove_<?php echo esc_attr($option_key); ?>" 
                                                        value="<?php echo esc_attr($option_key); ?>" 
                                                        <?php checked(in_array($option_key, $notice_type_remove), true); ?>> <?php echo esc_html($option_label); ?>
                                                </label>
                                                <br><br>
                                                <?php endforeach; ?>
                                                
                                                <span class="description">
                                                    <?php printf(esc_html__('Select the notification types that should be hidden when a user in the %1s role is viewing WordPress admin screens.', 'capability-manager-enhanced'), esc_html(translate_user_role($detail['name']))); ?> <a target="_blank" href="https://publishpress.com/knowledge-base/notice-types"><?php esc_html_e('Click here for more on notice types.', 'capability-manager-enhanced'); ?></a>
                                                </span>
                                            </td>
                                        </tr>

                                        <tr class="ppc-settings-tab-content pp-admin-notices-settings-<?php echo esc_attr($role); ?>-content <?php echo esc_attr($visibility_class); ?>">
                                        <?php
                                            $notice_type_display = !empty($admin_notice_settings[$role]['notice_type_display']) ? $admin_notice_settings[$role]['notice_type_display'] : [];
                                        ?>
                                            <th scope="row"> <?php esc_html_e('Notifications to display in the Admin Notices area.', 'capability-manager-enhanced'); ?></th>
                                            <td>
                                                <?php foreach ($notice_type_options as $option_key => $option_label) : ?>
                                                <label>
                                                    <input type="checkbox" 
                                                        name="cme_admin_notice_options[<?php echo esc_attr($role); ?>][notice_type_display][]" id="cme_admin_notice_options_<?php echo esc_attr($role); ?>_notice_type_display_<?php echo esc_attr($option_key); ?>" 
                                                        value="<?php echo esc_attr($option_key); ?>" 
                                                        <?php checked(in_array($option_key, $notice_type_display), true); ?>> <?php echo esc_html($option_label); ?>
                                                </label>
                                                <br><br>
                                                <?php endforeach; ?>
                                                
                                                <span class="description">
                                                    <?php esc_html_e('Select the notification types that should be displayed in the Admin Notices area after been removed from the WordPress admin screens.', 'capability-manager-enhanced'); ?> <a target="_blank" href="https://publishpress.com/knowledge-base/notice-types"><?php esc_html_e('Click here for more on notice types.', 'capability-manager-enhanced'); ?></a>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                </tbody>
                            </table>

                            </td>
                        </tr>
                    </table>
                </fieldset>
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes');?>">
            <input type="hidden" name="pp_tab" id="pp_tab" value="<?php echo esc_attr($default_tab); ?>" />
        </div><!-- .pp-column-left -->
        <div class="pp-column-right pp-capabilities-sidebar">
            <?php pp_capabilities_pro_sidebox(); ?>
        </div><!-- .pp-column-right -->
    </div><!-- .pp-columns-wrapper -->

        <script>
        jQuery(document).ready(function ($) {

            $('#publishpress-capability-settings-tabs').find('li').click(function (e) {
                e.preventDefault();
                let active_tab_value = $(this).find('a').attr('href'); 
                let active_tab = active_tab_value.replace('#ppcs-tab-', '');

                $('#pp_tab').val(active_tab);

                $('#publishpress-capability-settings-tabs').children('li').filter('.nav-tab-active').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('[id^="ppcs-"]').hide();
                $($(this).find('a').first().attr('href')).show();
            });

            $('.pp-capabilities-settings-chosen').chosen({
                'width': '30em'
            });

        });
        </script>

    <?php
        echo "<input type='hidden' name='all_options' value='" . implode(',', array_map('esc_attr', $all_options)) . "' />";
    }
} // end class
