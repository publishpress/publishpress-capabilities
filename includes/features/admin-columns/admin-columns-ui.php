<?php

use PublishPress\Capabilities\PP_Capabilities_Admin_Columns;

global $capsman;

$roles = $capsman->roles;
$default_role = $capsman->get_last_role();
$admin_columns = PP_Capabilities_Admin_Columns::instance();
$post_types = $admin_columns->getPostTypes();
$settings = (array) get_option(PP_Capabilities_Admin_Columns::OPTION_NAME, []);
$role_settings = isset($settings[$default_role]) && is_array($settings[$default_role])
    ? $settings[$default_role]
    : [];
$post_type_slugs = array_keys($post_types);
$active_tab_slug = !empty($_REQUEST['pp_caps_tab']) ? sanitize_key($_REQUEST['pp_caps_tab']) : 'post';

if (!isset($post_types[$active_tab_slug])) {
    $active_tab_slug = reset($post_type_slugs);
}
?>

<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper admin-columns">
    <div id="icon-capsman-admin" class="icon32"></div>
    <h2><?php esc_html_e('Admin Column Restrictions', 'capability-manager-enhanced'); ?></h2>

    <form method="post" id="ppc-admin-columns-form" action="admin.php?page=pp-capabilities-admin-columns">
        <?php wp_nonce_field('pp-capabilities-admin-columns'); ?>
        <input type="hidden" name="pp_caps_tab" value="<?php echo esc_attr($active_tab_slug); ?>">

        <div class="pp-columns-wrapper pp-enable-sidebar">
            <div class="pp-column-left">
                <table id="akmin">
                    <tr>
                        <td class="content">
                            <div class="publishpress-filters">
                                <select name="ppc-admin-columns-role" class="ppc-admin-columns-role">
                                    <?php foreach ($roles as $role_name => $name) : ?>
                                        <option value="<?php echo esc_attr($role_name); ?>" <?php selected($default_role, $role_name); ?>>
                                            <?php echo esc_html(translate_user_role($name)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <img class="loading" src="<?php echo esc_url($capsman->mod_url . '/images/wpspin_light.gif'); ?>" style="display:none" alt="">

                                <input type="submit" name="admin-columns-submit"
                                    value="<?php esc_attr_e('Save Changes'); ?>"
                                    class="button-primary ppc-admin-columns-submit" style="float:right">
                            </div>

                            <div id="pp-capability-menu-wrapper" class="postbox">
                                <div id="ppc-capabilities-wrapper" class="postbox">
                                    <div class="ppc-capabilities-tabs">
                                        <ul>
                                            <?php foreach ($post_types as $post_type => $post_type_object) :
                                                $hidden_count = isset($role_settings[$post_type])
                                                    ? count(array_filter((array) $role_settings[$post_type]))
                                                    : 0;
                                                ?>
                                                <li data-slug="<?php echo esc_attr($post_type); ?>"
                                                    data-content="ppc-admin-columns-<?php echo esc_attr($post_type); ?>"
                                                    class="<?php echo $post_type === $active_tab_slug ? 'ppc-capabilities-tab-active' : ''; ?>">
                                                    <span><?php echo esc_html($post_type_object->labels->singular_name); ?></span>
                                                    <?php if ($hidden_count > 0) : ?>
                                                        <span class="pp-capabilities-count-indicator"><?php echo (int) $hidden_count; ?></span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                    <div class="ppc-capabilities-content admin-columns-content">
                                        <?php foreach ($post_types as $post_type => $post_type_object) :
                                            $columns = $admin_columns->getColumns($post_type);
                                            $hidden_columns = isset($role_settings[$post_type])
                                                ? (array) $role_settings[$post_type]
                                                : [];
                                            $all_columns_hidden = !empty($columns)
                                                && empty(array_diff(array_keys($columns), $hidden_columns));
                                            ?>
                                            <div id="ppc-admin-columns-<?php echo esc_attr($post_type); ?>"
                                                style="<?php echo $post_type === $active_tab_slug ? '' : 'display:none;'; ?>">
                                                <table class="wp-list-table widefat fixed striped pp-capability-menus-select">
                                                    <thead>
                                                        <tr>
                                                            <td class="restrict-column ppc-menu-checkbox">
                                                                <input type="checkbox" class="check-all-admin-columns"
                                                                    <?php checked($all_columns_hidden); ?>>
                                                            </td>
                                                            <th>
                                                                <strong>
                                                                    <?php
                                                                    printf(
                                                                        esc_html__('%s Columns', 'capability-manager-enhanced'),
                                                                        esc_html($post_type_object->labels->singular_name)
                                                                    );
                                                                    ?>
                                                                </strong>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($columns)) : ?>
                                                            <tr>
                                                                <td colspan="2"><?php esc_html_e('No optional columns were found for this post type.', 'capability-manager-enhanced'); ?></td>
                                                            </tr>
                                                        <?php else : ?>
                                                            <?php foreach ($columns as $column_name => $column_label) :
                                                                $field_id = 'ppc-admin-column-' . $post_type . '-' . sanitize_html_class($column_name);
                                                                $plain_label = is_scalar($column_label)
                                                                    ? trim(wp_strip_all_tags((string) $column_label))
                                                                    : '';
                                                                if ('' === $plain_label) {
                                                                    $plain_label = ucwords(str_replace(['-', '_'], ' ', $column_name));
                                                                }
                                                                ?>
                                                                <tr>
                                                                    <td class="restrict-column ppc-menu-checkbox">
                                                                        <input id="<?php echo esc_attr($field_id); ?>"
                                                                            class="check-item"
                                                                            type="checkbox"
                                                                            name="capsman_admin_columns[<?php echo esc_attr($post_type); ?>][]"
                                                                            value="<?php echo esc_attr($column_name); ?>"
                                                                            <?php checked(in_array($column_name, $hidden_columns, true)); ?>>
                                                                    </td>
                                                                    <td>
                                                                        <label for="<?php echo esc_attr($field_id); ?>">
                                                                            <strong><?php echo esc_html($plain_label); ?></strong>
                                                                            <code><?php echo esc_html($column_name); ?></code>
                                                                        </label>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-columns-footer">
                                <input type="submit" name="admin-columns-submit"
                                    value="<?php esc_attr_e('Save Changes'); ?>"
                                    class="button-primary ppc-admin-columns-submit" style="float:right">
                                <div class="clear"></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="pp-column-right pp-capabilities-sidebar">
                <?php
                $banner_messages = [
                    '<p>' . esc_html__('Admin Columns allows you to hide columns from post list screens for users in a selected role.', 'capability-manager-enhanced') . '</p>',
                    '<p><input type="checkbox" disabled> = ' . esc_html__('No change', 'capability-manager-enhanced') . '<br>',
                    '<input type="checkbox" checked disabled> = ' . esc_html__('This column is hidden', 'capability-manager-enhanced') . '</p>',
                    '<p>' . esc_html__('The selection checkbox and Title column always remain visible.', 'capability-manager-enhanced') . '</p>',
                ];
                pp_capabilities_sidebox_banner(
                    __('How to use Admin Columns', 'capability-manager-enhanced'),
                    $banner_messages
                );
                pp_capabilities_pro_sidebox();
                ?>
            </div>
        </div>
    </form>

    <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
        cme_publishpressFooter();
    } ?>
</div>
