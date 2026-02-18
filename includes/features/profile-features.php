<?php
/**
 * Capability Manager Profile Features.
 * Hide and block selected Profile Features like toolbar, dashboard widgets etc per-role.
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

require_once(dirname(CME_FILE) . '/includes/features/restrict-profile-features.php');

global $capsman, $role_has_user;

$roles             = $capsman->roles;
$default_role      = $capsman->get_last_role();

$temporary_profile_role = \PublishPress\Capabilities\PP_Capabilities_Profile_Features::getTemporaryProfileFeaturesRole();
$role_is_enabled = ($default_role === $temporary_profile_role);

$disabled_profile_items = !empty(get_option('capsman_disabled_profile_features')) ? (array)get_option('capsman_disabled_profile_features') : [];
$disabled_profile_items = array_key_exists($default_role, $disabled_profile_items) ? (array)$disabled_profile_items[$default_role] : [];

$profile_features_elements = \PublishPress\Capabilities\PP_Capabilities_Profile_Features::elementsLayout();
$profile_features_elements = isset($profile_features_elements[$default_role]) ? $profile_features_elements[$default_role] : [];

if (get_option('cme_profile_features_auto_redirect')) {
    $refresh_url = admin_url('admin.php?page=pp-capabilities-profile-features&role=' . $default_role . '&refresh_element=1');
} else {
    $refresh_url = admin_url('admin.php?page=pp-capabilities-profile-features&role=' . $default_role . '&role_refresh=1');
}
?>

    <div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper profile-features <?php echo (empty($profile_features_elements) ? 'empty-elements' : ''); ?>">
        <div id="icon-capsman-admin" class="icon32"></div>
        <h2><?php esc_html_e('Profile Feature Restrictions', 'capability-manager-enhanced'); ?></h2>

        <form method="post" id="ppc-profile-features-form" action="admin.php?page=pp-capabilities-profile-features">
            <?php wp_nonce_field('pp-capabilities-profile-features'); ?>

            <div class="pp-columns-wrapper pp-enable-sidebar clear">
                <div class="pp-column-left">
                    <fieldset>
                        <table id="akmin">
                            <tr>
                                <td class="content">
                                    <div>

                                        <p>
                                        <div class="publishpress-filters">
                                            <div class="pp-capabilities-submit-top" style="float:right;">
                                                <input type="submit" name="profile-features-submit"
                                                    value="<?php esc_attr_e('Save Changes');?>"
                                                    class="button-primary ppc-profile-features-submit" />
                                            </div>

                                            <select name="ppc-profile-features-role" class="ppc-profile-features-role">
                                                <?php
                                                foreach ($roles as $role_name => $name) :
                                                    $name = translate_user_role($name);
                                                    ?>
                                                    <option value="<?php echo esc_attr($role_name); ?>" <?php selected($default_role,
                                                        $role_name); ?>><?php echo esc_html($name); ?></option>
                                                <?php
                                                endforeach;
                                                ?>
                                            </select> &nbsp;

                                            <img class="loading" src="<?php echo esc_url_raw($capsman->mod_url); ?>/images/wpspin_light.gif"
                                                    style="display: none">
                                        </div>
                                        </p>
                                    </div>

                                    <div id="pp-capability-menu-wrapper" class="postbox">
                                        <div class="pp-capability-menus">

		                                    <div class="pp-capability-menus-wrap">
		                                        <div id="pp-capability-menus-general"
		                                             class="pp-capability-menus-content editable-role" style="display: block;">

		                                            <table
		                                                class="wp-list-table widefat striped fixed pp-capability-menus-select">
                                                        <thead>
                                                            <tr class="ppc-menu-row parent-menu">

                                                                <td class="restrict-column ppc-menu-checkbox">
                                                                    <input id="check-all-item"
                                                                        class="check-item check-all-menu-item"
                                                                        type="checkbox"/>
                                                                </td>
                                                                <td class="menu-column ppc-menu-item">
                                                                    <label for="check-all-item">
                                                                <span class="menu-item-link check-all-menu-link">
                                                                    <strong>
                                                                    <?php esc_html_e('Toggle all', 'capability-manager-enhanced'); ?>
                                                                    </strong>
                                                                </span></label>
                                                                </td>

                                                            </tr>
                                                        </thead>
                                                        <tfoot>
                                                            <tr class="ppc-menu-row parent-menu">

                                                                <td class="restrict-column ppc-menu-checkbox">
                                                                    <input id="check-all-item-2"
                                                                        class="check-item check-all-menu-item"
                                                                        type="checkbox"/>
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

                                                            </tr>
                                                        </tfoot>

                                                        <tbody>
                                                        <?php if (empty($profile_features_elements)) : ?>
                                                            <tr class="ppc-menu-row parent-menu empty-features-element">
                                                                <td colspan="2">
                                                                    <?php
                                                                    if ($role_has_user) {
                                                                        esc_html_e('Click the "Find profile items for this role" button.', 'capability-manager-enhanced');
                                                                    } else {
                                                                        esc_html_e('There are no users in this role. Please select a role that has users and is able to access the "Profile" screen.', 'capability-manager-enhanced');
                                                                    }
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php else : ?>
                                                            <?php
                                                            $sn = 0;
                                                            foreach ($profile_features_elements as $section_id => $section_array) :
                                                                $sn++;
                                                                $item_name      = $section_array['label'];
                                                                $restrict_value = $section_array['elements'];
                                                                $element_type   = $section_array['element_type'];

                                                                if (!is_array($section_array) || empty($section_array)) {
                                                                     continue;
                                                                }

                                                                if (in_array($element_type, ['section', 'header'])) :
                                                                ?>
                                                                <tr class="ppc-menu-row parent-menu ppc-sortable-row <?php echo esc_attr($section_id); ?>"
                                                                    data-element_key="<?php echo esc_attr($section_id); ?>"
                                                                >
                                                                    <td class="restrict-column ppc-menu-checkbox">
                                                                        <input
                                                                            id="check-item-<?php echo (int) $sn; ?>"
                                                                            class="check-item" type="checkbox"
                                                                            name="capsman_disabled_profile_features[]"
                                                                            value="<?php echo esc_attr($restrict_value); ?>"
                                                                            <?php echo (in_array($restrict_value, $disabled_profile_items)) ? 'checked' : ''; ?>/>
                                                                    </td>
                                                                    <td class="menu-column ppc-menu-item">
                                                                        <label for="check-item-<?php echo (int) $sn; ?>">
                                                                            <strong class="menu-item-link<?php echo (in_array($restrict_value, $disabled_profile_items)) ? ' restricted' : ''; ?>">
                                                                                <span class="dashicons dashicons-portfolio"></span>
                                                                                <?php echo esc_html($item_name); ?>
                                                                            </strong>
                                                                        </label>
                                                                    </td>
                                                                </tr>
                                                                <?php else : ?>
                                                                <tr class="ppc-menu-row child-menu ppc-sortable-row <?php echo esc_attr($section_id); ?>"
                                                                    data-element_key="<?php echo esc_attr($section_id); ?>"
                                                                >
                                                                    <td class="restrict-column ppc-menu-checkbox">
                                                                        <input
                                                                            id="check-item-<?php echo (int) $sn; ?>"
                                                                            class="check-item" type="checkbox"
                                                                            name="capsman_disabled_profile_features[]"
                                                                            value="<?php echo esc_attr($restrict_value); ?>"
                                                                            <?php echo (in_array($restrict_value, $disabled_profile_items)) ? 'checked' : ''; ?>/>
                                                                    </td>
                                                                    <td class="menu-column ppc-menu-item">
                                                                        <label for="check-item-<?php echo (int) $sn; ?>">
                                                                            <span
                                                                                class="menu-item-link<?php echo (in_array($restrict_value, $disabled_profile_items)) ? ' restricted' : ''; ?>">
                                                                            <strong>
                                                                                 <?php echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <i class="dashicons dashicons-arrow-right"></i>'; ?>
                                                                                <?php echo esc_html($item_name); ?>
                                                                            </strong></span>
                                                                        </label>
                                                                    </td>
                                                                </tr>
                                                                <?php
                                                                endif;// $element_type check
                                                            endforeach; // $profile_features_elements loop
                                                        endif; // $profile_features_elements empty if
                                                	    ?>
		                                                <?php do_action('pp_capabilities_profile_features_after_table_tr'); ?>
		                                                </tbody>
		                                            </table>
		                                            <?php do_action('pp_capabilities_profile_features_after_table'); ?>
		                                        </div>

                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="capsman_profile_features_elements_order" class="capsman_profile_features_elements_order" value=""/>
                                    <input type="submit" name="profile-features-submit"
                                           value="<?php esc_attr_e('Save Changes');?>"
                                            style="float: right; margin-top: 10px;"
                                           class="button-primary ppc-profile-features-submit"/>
                                </td>
                            </tr>
                        </table>

                    </fieldset>
                </div><!-- .pp-column-left -->
                <div class="pp-column-right pp-capabilities-sidebar">
                <?php
                $current_role_name = translate_user_role($roles[$default_role]);
                $checkbox_checked = $role_is_enabled ? 'checked' : '';
                $button_disabled = $role_is_enabled ? '' : 'disabled="disabled"';
                $warning_display = $role_is_enabled ? 'display: none;' : '';
                $info_display = $role_is_enabled ? '' : 'display: none;';

                $banner_messages = [];
                $banner_messages[] = '<label style="display: block; margin-bottom: 8px;"><input type="checkbox" class="ppc-profile-features-allow-role" data-role="' . esc_attr($default_role) . '" ' . $checkbox_checked . '> ' . esc_html__('Allow PublishPress to find profile items for this role.', 'capability-manager-enhanced') . '</label>';
                $banner_messages[] = '<div style="margin-bottom: 15px;"><button type="button" class="button ppc-profile-features-refresh" data-refresh-url="' . esc_url($refresh_url) . '" ' . $button_disabled . '>' . esc_html__('Find profile items for this role', 'capability-manager-enhanced') . '</button></div>';
                $banner_messages[] = '<div class="ppc-profile-features-warning" style="display: none !important;padding: 10px; background-color: #fff8e5; border-left: 4px solid #ffb900; color: #856404; margin-bottom: 15px; ' . $warning_display . '"><strong><span class="dashicons dashicons-warning" aria-hidden="true"></span> ' . esc_html__('Warning:', 'capability-manager-enhanced') . '</strong> ' . sprintf(esc_html__('The %s role is not enabled for editing. The Find button is disabled.', 'capability-manager-enhanced'), '<strong>' . esc_html($current_role_name) . '</strong>') . '</div>';
                $banner_messages[] = '<div class="ppc-profile-features-info" style="padding: 8px; background-color: #e7f7fe; border-left: 4px solid #00a0d2; color: #0073aa; margin-bottom: 15px; ' . $info_display . '"><strong><span class="dashicons dashicons-info-outline" aria-hidden="true"></span></strong> ' . sprintf(esc_html__('This feature will test the "Profile" screen as a user in the %s role.', 'capability-manager-enhanced'), '<strong>' . esc_html($current_role_name) . '</strong>') . '</div>';

                $banner_title  = __('Refresh Profile Features', 'capability-manager-enhanced');
                pp_capabilities_sidebox_banner($banner_title, $banner_messages);
                ?>
                <?php
                $banner_messages = ['<p>'];
                $banner_messages[] = esc_html__('Profile Features allows you to remove elements from the Profile screen.', 'capability-manager-enhanced');
                $banner_messages[] = '</p><p>';
                $banner_messages[] = sprintf(esc_html__('%1$s = No change', 'capability-manager-enhanced'), '<input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" disabled>') . ' <br />';
                $banner_messages[] = sprintf(esc_html__('%1$s = This feature is denied', 'capability-manager-enhanced'), '<input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" checked disabled>') . ' <br />';
                $banner_messages[] = '</p>';
                $banner_messages[] = '<p><a class="button ppc-checkboxes-documentation-link" href="https://publishpress.com/knowledge-base/profile-features-screen/"target="blank">' . esc_html__('View Documentation', 'capability-manager-enhanced') . '</a></p>';
                $banner_title  = __('How to use Profile Features', 'capability-manager-enhanced');
                pp_capabilities_sidebox_banner($banner_title, $banner_messages);
                // add promo sidebar
                pp_capabilities_pro_sidebox();
                ?>
                </div><!-- .pp-column-right -->
            </div><!-- .pp-columns-wrapper -->
        </form>

        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function($) {

                // -------------------------------------------------------------
                //   Set form action attribute to include role
                // -------------------------------------------------------------
                $('#ppc-profile-features-form').attr('action', '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-profile-features&role=' . $default_role . '')); ?>')

                // -------------------------------------------------------------
                //   Instant restricted item class
                // -------------------------------------------------------------
                $(document).on('change', '.pp-capability-menus-wrapper .ppc-menu-row .check-item', function() {

                    if ($(this).is(':checked')) {
                        //add class if value is checked
                        $(this).closest('tr').find('.menu-item-link').addClass('restricted')

                        //toggle all checkbox
                        if ($(this).hasClass('check-all-menu-item')) {
                            $("input[type='checkbox'][name='capsman_disabled_profile_features[]']").prop('checked', true)
                            $('.menu-item-link').addClass('restricted')
                        } else {
                            $('.check-all-menu-link').removeClass('restricted')
                            $('.check-all-menu-item').prop('checked', false)
                        }

                    } else {
                        //unchecked value
                        $(this).closest('tr').find('.menu-item-link').removeClass('restricted')

                        //toggle all checkbox
                        if ($(this).hasClass('check-all-menu-item')) {
                            $("input[type='checkbox'][name='capsman_disabled_profile_features[]']").prop('checked', false)
                            $('.menu-item-link').removeClass('restricted')
                        } else {
                            $('.check-all-menu-link').removeClass('restricted')
                            $('.check-all-menu-item').prop('checked', false)
                        }

                    }

                })

                // -------------------------------------------------------------
                //   Load selected roles menu
                // -------------------------------------------------------------
                $(document).on('change', '.pp-capability-menus-wrapper .ppc-profile-features-role', function() {

                    //disable select
                    $('.pp-capability-menus-wrapper .ppc-profile-features-role').attr('disabled', true)

                    //hide button
                    $('.pp-capability-menus-wrapper .ppc-profile-features-submit').hide()

                    //show loading
                    $('#pp-capability-menu-wrapper').hide()
                    $('div.publishpress-caps-manage img.loading').show()

                    //go to url
                    window.location = '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-profile-features&role=')); ?>' + $(this).val() + ''

                })

            })
            /* ]]> */
        </script>

        <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
            cme_publishpressFooter();
        }
        ?>
    </div>
<?php
