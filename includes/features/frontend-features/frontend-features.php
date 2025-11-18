<?php
/**
 * Capability Manager Frontend Features.
 * Hide and block selected Frontend Features like toolbar, dashboard widgets etc per-role.
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

use PublishPress\Capabilities\PP_Capabilities_Frontend_Features_UI;
use PublishPress\Capabilities\PP_Capabilities_Frontend_Features_Data;

require_once(dirname(CME_FILE) . '/includes/features/frontend-features/frontend-features-data.php');
require_once(dirname(CME_FILE) . '/includes/features/frontend-features/frontend-features-ui.php');


//instantiate frontend features ui
PP_Capabilities_Frontend_Features_UI::instance();


global $capsman;

$roles        = $capsman->roles;
$default_role = $capsman->get_last_role();

//add logged in and guest option
$ppc_other_permissions = [
    "ppc_users" => esc_html__('Logged In Users', 'capability-manager-enhanced'),
    "ppc_guest" => esc_html__('Logged Out Users', 'capability-manager-enhanced')
];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if (!empty($_REQUEST['role'])) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (array_key_exists(sanitize_key($_REQUEST['role']), $ppc_other_permissions)) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $default_role = sanitize_key($_REQUEST['role']);
        $role_caption = $ppc_other_permissions[$default_role];
    } else {
        $role_caption = translate_user_role($roles[$default_role]);
    }
} else {
    $role_caption = translate_user_role($roles[$default_role]);
}

if (!isset($ppc_other_permissions[$default_role])) {
    $role_caption .= ' ' . __('Role', 'capability-manager-enhanced');
}

$disabled_frontend_items = !empty(get_option('capsman_disabled_frontend_features')) ? (array)get_option('capsman_disabled_frontend_features') : [];
$disabled_frontend_items = array_key_exists($default_role, $disabled_frontend_items) ? (array)$disabled_frontend_items[$default_role] : [];

$frontend_features_elements = PP_Capabilities_Frontend_Features_Data::elementsLayout();

?>

<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper frontend-features">
    <div id="icon-capsman-admin" class="icon32"></div>
    <h2><?php esc_html_e('Frontend Features', 'capability-manager-enhanced'); ?>
    </h2>

    <form method="post" id="ppc-frontend-features-form" action="admin.php?page=pp-capabilities-frontend-features">
        <?php wp_nonce_field('pp-capabilities-frontend-features'); ?>

        <div class="pp-columns-wrapper pp-enable-sidebar">
            <div class="pp-column-left">
                <table id="akmin" style="border-bottom: none !important;">
                    <tr>
                        <td class="content">

                            <div class="publishpress-filters">
                                <div class="pp-capabilities-submit-top" style="float:right;">
                                    <input type="submit" name="frontend-features-submit"
                                        value="<?php esc_attr_e('Save Changes');?>"
                                        class="button-primary ppc-frontend-features-submit" />
                                </div>

                                <select name="ppc-frontend-features-role" class="ppc-frontend-features-role">
                                    <optgroup label="<?php esc_attr_e('Users');?>">
                                        <?php
                                            foreach ($ppc_other_permissions as $p_value => $p_title) {
                                                ?>
                                            <option
                                                value="<?php echo esc_attr($p_value); ?>"
                                                <?php selected($default_role, $p_value); ?>>
                                                <?php echo esc_html($p_title); ?>
                                                &nbsp;
                                            </option>
                                            <?php
                                            }
                                        ?>
                                    </optgroup>

                                    <optgroup label="<?php esc_attr_e('Roles');?>">
                                        <?php
                                        foreach ($roles as $role_name => $name)  {
                                            $name = translate_user_role($name);
                                        ?>
                                        <option
                                            value="<?php echo esc_attr($role_name); ?>"
                                            <?php selected($default_role, $role_name); ?>>
                                            <?php echo esc_html($name); ?>
                                            &nbsp;
                                        </option>
                                        <?php
                                        }
                                        ?>
                                    </optgroup>

                                </select> &nbsp;

                                <img class="loading"
                                    src="<?php echo esc_url_raw($capsman->mod_url); ?>/images/wpspin_light.gif"
                                    style="display: none">
                            </div>

                            <div id="pp-capability-menu-wrapper" class="postbox">
                                <div class="pp-capability-menus">

                                    <div class="pp-capability-menus-wrap">
                                        <div id="pp-capability-menus-general"
                                            class="pp-capability-menus-content editable-role" style="display: block;">

                                                    <?php
                                                      $sn = 0;
                                                      foreach ($frontend_features_elements as $section_title => $section_elements) :
                                                          $sn++;
                                                          $section_slug = strtolower(ppc_remove_non_alphanumeric_space_characters($section_title));
                                                            ?>
                                                    <div id="cme-cap-type-tables-<?php echo esc_attr($section_slug); ?>">
                                                        <table
                                                            class="wp-list-table widefat striped fixed pp-capability-menus-select <?php echo esc_attr($section_slug); ?>-table">
                                                            <tbody>
                                                                <?php do_action("pp_capabilities_frontend_features_{$section_slug}_before_subsection_tr");
                                                                $display_title_class = empty($section_elements) ? 'temporarily hidden-element' : '';
                                                                ?>
                                                                <tr class="custom-table-title <?php echo esc_attr($display_title_class); ?>">
                                                                    <td colspan="2" class="title-td">
                                                                        <label>
                                                                            <?php printf(esc_html__('Apply for %1$s', 'capability-manager-enhanced'), esc_html($role_caption)); ?>
                                                                        </label>
                                                                    </td>
                                                                </tr>
                                                                <tr class="custom-item-wrapper-tr <?php echo esc_attr($display_title_class); ?>">
                                                                    <td colspan="2" class="custom-item-wrapper-td">
                                                                        <table class="wp-list-table widefat striped table-view-list custom-items-table">
                                                                            <thead>
                                                                                <tr class="ppc-menu-row parent-menu">

                                                                                    <td class="restrict-column ppc-menu-checkbox">
                                                                                        <input id="check-all-item"
                                                                                            class="check-item check-all-menu-item"
                                                                                            type="checkbox"/>
                                                                                    </td>
                                                                                    <td class="menu-column ppc-menu-item" colspan="4">
                                                                                        <label for="check-all-item">
                                                                                    <span class="menu-item-link check-all-menu-link">
                                                                                        <strong></strong>
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
                                                                                    <td class="menu-column ppc-menu-item" colspan="4">
                                                                                        <label for="check-all-item-2">
                                                                                        <span class="menu-item-link check-all-menu-link">
                                                                                        <strong></strong>
                                                                                        </span>
                                                                                        </label>
                                                                                    </td>

                                                                                </tr>
                                                                            </tfoot>
                                                                            <tbody>
                                                                            <?php
                                                                            foreach ($section_elements as $section_id => $section_array) :
                                                                                $sn++;
                                                                                if (!$section_id) {
                                                                                    continue;
                                                                                }
                                                                                $section_tr_function = 'do_pp_capabilities_frontend_features_' . $section_slug . '_tr';
                                                                                $function_args = [
                                                                                    'disabled_frontend_items' => $disabled_frontend_items,
                                                                                    'section_array'           => $section_array,
                                                                                    'section_slug'            => $section_slug,
                                                                                    'section_id'              => $section_id,
                                                                                    'sn'                      => $sn
                                                                                ];
                                                                                //render tr ui for row
                                                                                if (method_exists('\PublishPress\Capabilities\PP_Capabilities_Frontend_Features_UI', $section_tr_function)) {
                                                                                    PP_Capabilities_Frontend_Features_UI::$section_tr_function($function_args);
                                                                                } elseif (function_exists($section_tr_function)) {
                                                                                    $section_tr_function($function_args);
                                                                                }
                                                                            endforeach; // $section_elements subsection loop
                                                                            ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <?php
                                                        endforeach; // $frontend_features_elements section loop
                                                    ?>
                                                    <?php do_action('pp_capabilities_frontend_features_after_table'); ?>
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <div class="editor-features-footer-meta">
                                <div class="ppc-footer-button" style="float:right">
                                    <input type="submit" name="frontend-features-submit"
                                        value="<?php esc_attr_e('Save Changes');?>"
                                        class="button-primary ppc-frontend-features-submit" />
                                </div>
                            </div>

                        </td>
                    </tr>
                </table>
            </div><!-- .pp-column-left -->
            <div class="pp-column-right pp-capabilities-sidebar">
                <?php
                $banner_messages = ['<p>'];
                $banner_messages[] = esc_html__('Frontend Features allows you to add or remove elements from the frontend of your site.', 'capability-manager-enhanced');
                $banner_messages[] = '</p><p>';
                $banner_messages[] = sprintf(esc_html__('%1$s = No change', 'capability-manager-enhanced'), '<input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" disabled>') . ' <br />';
                $banner_messages[] = sprintf(esc_html__('%1$s = Apply custom styling', 'capability-manager-enhanced'), '<input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" checked disabled>'). ' <br />';
                $banner_messages[] = '<p>';
                $banner_messages[] = '<p><a class="button ppc-checkboxes-documentation-link" href="https://publishpress.com/knowledge-base/frontend-features/"target="blank">' . esc_html__('View Documentation', 'capability-manager-enhanced') . '</a></p>';
                $banner_title  = __('How to use Frontend Features', 'capability-manager-enhanced');
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

<script type="text/javascript">
    /* <![CDATA[ */
    jQuery(document).ready(function($) {

        // Tabs and Content display
        $('.ppc-capabilities-tabs > ul > li').click(function() {
            var $pp_tab = $(this).attr('data-content');
            var $pp_tab_slug = $(this).attr('data-slug');
            var $element_counts = 0;

            $("[name='pp_caps_tab']").val($pp_tab_slug);

            // Show current Content
            $('.ppc-capabilities-content > div').hide();
            $('#' + $pp_tab).show();

            // Active current Tab
            $('.ppc-capabilities-tabs > ul > li').removeClass('ppc-capabilities-tab-active');
            $(this).addClass('ppc-capabilities-tab-active');

            //show or hide toggle all row if more than one entry
            $element_counts = $('table.' + $pp_tab_slug + '-table table.custom-items-table tr.custom-item-row').length;

            if ($element_counts > 1) {
                $('table.' + $pp_tab_slug + '-table .custom-item-toggle-row').removeClass('hidden-element');
            } else {
                $('table.' + $pp_tab_slug + '-table .custom-item-toggle-row').addClass('hidden-element');
            }

        });
        //trigger initial click for toggle all update
        $('.ppc-capabilities-tabs .ppc-capabilities-tab-active').trigger('click');

        // -------------------------------------------------------------
        //   Set form action attribute to include role
        // -------------------------------------------------------------
        $('#ppc-frontend-features-form').attr('action',
            '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-frontend-features&role=' . $default_role . '')); ?>'
        )

        // -------------------------------------------------------------
        //   Instant restricted item class
        // -------------------------------------------------------------
        $(document).on('change', '.pp-capability-menus-wrapper .ppc-menu-row .check-item', function() {

            if ($(this).is(':checked')) {
                //add class if value is checked
                $(this).closest('tr').find('.menu-item-link').addClass('restricted')

                //toggle all checkbox
                if ($(this).hasClass('check-all-menu-item')) {
                    $(this).closest('table').find(
                        "input[type='checkbox'][name='capsman_disabled_frontend_features[]']").prop(
                        'checked', true)
                    $(this).closest('table').find('.menu-item-link').addClass('restricted')
                } else {
                    $(this).closest('table').find('.check-all-menu-link').removeClass('restricted')
                    $(this).closest('table').find('.check-all-menu-item').prop('checked', false)
                }

            } else {
                //unchecked value
                $(this).closest('tr').find('.menu-item-link').removeClass('restricted')

                //toggle all checkbox
                if ($(this).hasClass('check-all-menu-item')) {
                    $(this).closest('table').find(
                        "input[type='checkbox'][name='capsman_disabled_frontend_features[]']").prop(
                        'checked', false)
                    $(this).closest('table').find('.menu-item-link').removeClass('restricted')
                } else {
                    $(this).closest('table').find('.check-all-menu-link').removeClass('restricted')
                    $(this).closest('table').find('.check-all-menu-item').prop('checked', false)
                }

            }

        })

        // -------------------------------------------------------------
        //   Load selected roles menu
        // -------------------------------------------------------------
        $(document).on('change', '.pp-capability-menus-wrapper .ppc-frontend-features-role', function() {

            //disable select
            $('.pp-capability-menus-wrapper .ppc-frontend-features-role').attr('disabled', true)

            //hide button
            $('.pp-capability-menus-wrapper .ppc-frontend-features-submit').hide()

            //show loading
            $('#pp-capability-menu-wrapper').hide()
            $('div.publishpress-caps-manage img.loading').show()

            //go to url
            window.location =
                '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-frontend-features&role=')); ?>' +
                $(this).val() + ''

        })

    });
    /* ]]> */
</script>
<?php
?>
