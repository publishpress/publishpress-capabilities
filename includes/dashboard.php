<?php
/**
 * Capability Manager Dashboard.
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
?>

<div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper capabilities-dashboard">
    <h2>
        <?php esc_html_e('Dashboard', 'capability-manager-enhanced'); ?>
    </h2>
    <div class="pp-columns-wrapper clear">
        <div class="pp-column-left">
            <form id="ppc-capabilities-dashboard-form">
                <div class="dashboard-settings-boxes">
                    <?php foreach (pp_capabilities_dashboard_options() as $feature => $option) : ?>
                        <?php
                            $feature_capability = 'manage_capabilities';
                            if (!in_array($feature, ['capabilities'])) {
                                $feature_capability .= '_' . str_replace('-', '_', $feature);
                            }
                            $promo_feature = !empty($option['promo']);
                            $additional_class = $promo_feature ? ' dashboard-settings-box--disabled' : '';
                            if ($promo_feature || current_user_can($feature_capability)) : ?>
                            <div class="dashboard-settings-box <?php echo esc_attr($additional_class); ?>">
                                <h3>
                                    <?php
                                        echo esc_html($option['label']);
                                        echo $promo_feature ? ' <span>Pro</span>' : '';
                                    ?>
                                </h3>
                                <div class="dashboard-settings-description"><?php echo esc_html($option['description']); ?></div>
                                <div class="dashboard-settings-control">
                                    <div class="ppc-switch-button">
                                        <label class="switch">
                                            <input
                                                type="checkbox"
                                                value="1"
                                                <?php
                                                if ($promo_feature) {
                                                    echo ' disabled';
                                                } else {
                                                    echo 'data-feature="'. esc_attr($feature) .'"';
                                                    checked(pp_capabilities_feature_enabled($feature), true);
                                                }
                                                ?>
                                            />
                                            <span class="slider<?php
                                            echo ( $promo_feature ? ' slider--disabled' : '' ); ?>"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </form>
        </div><!-- .pp-column-left -->
        <div class="pp-column-right pp-capabilities-sidebar">
        </div><!-- .pp-column-right -->
    </div><!-- .pp-columns-wrapper -->
    <?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
        cme_publishpressFooter();
    }
    ?>
</div>
<?php
?>