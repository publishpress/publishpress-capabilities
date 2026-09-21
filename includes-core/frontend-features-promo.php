<?php

/**
 * Capability Manager Frontend Features Promo.
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
<div class="pp-promo-overlay-row">
    <div class="pp-promo-upgrade-notice">
        <p>
            <?php esc_html_e(
                'You can use Frontend Features to target specific posts and pages. This feature is available in PublishPress Capabilities Pro.',
                'capability-manager-enhanced'
            ); ?>
        </p>
        <p>
            <a href="https://publishpress.com/links/capabilities-banner" target="_blank">
                <?php esc_html_e('Upgrade to Pro', 'capability-manager-enhanced'); ?>
            </a>
        </p>
    </div>

</div>
<div class="pp-promo-overlay-row div-pp-promo-blur">
    <select class="chosen-cpt-select frontendelements-form-post-types" data-placeholder="<?php esc_attr_e('Select post types...', 'capability-manager-enhanced'); ?>" multiple>
        <option value=""></option>
    </select>
    <br />
    <small>
        <?php esc_html_e('This will add a metabox on the post editing screen. You can use this feature to add body classes only for that post.', 'capability-manager-enhanced'); ?>
    </small>
    <!-- using this to balance the space needed due to field size -->
    <input type="text" aria-label="<?php esc_attr_e('Layout spacer', 'capability-manager-enhanced'); ?>" style="visibility: hidden; width: 0; display: block;" />
    <input type="text" aria-label="<?php esc_attr_e('Layout spacer', 'capability-manager-enhanced'); ?>" style="visibility: hidden; width: 0; display: block;" />
</div>
