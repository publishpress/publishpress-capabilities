<?php
/*
 * PublishPress Capabilities [Free]
 *
 * Capabilities UI: PublishPress Permissions integration
 *
 * This module also contains the Settings UI for Type-Specific Types / Taxonomies, which were previously a front end to PublishPress Permissions
 *
 */

class Capsman_PP_UI {
	function get_metagroup_caps( $default ) {
		global $wpdb;

		if ( defined( 'PRESSPERMIT_ACTIVE' ) ) {
			$pp_supplemental_roles = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT role_name FROM $wpdb->ppc_roles AS r INNER JOIN $wpdb->pp_groups AS g ON g.ID = r.agent_id AND r.agent_type = 'pp_group' WHERE g.metagroup_type = 'wp_role' AND g.metagroup_id = %s",
					$default
				)
			);
		} else {
			$pp_supplemental_roles = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT role_name FROM $wpdb->pp_roles AS r INNER JOIN $wpdb->pp_groups AS g ON g.ID = r.group_id AND r.group_type = 'pp_group' AND r.scope = 'site' WHERE g.metagroup_type = 'wp_role' AND g.metagroup_id = %s",
					$default
				)
			);
		}

		if (!function_exists('pp_get_enabled_types') || !function_exists('pp_init_cap_caster')) {
			return [];
		}

		$pp_filtered_types = pp_get_enabled_types('post');
		$pp_metagroup_caps = array();
		$pp_cap_caster = pp_init_cap_caster();

		foreach( $pp_supplemental_roles as $_role_name ) {
			$role_specs = explode( ':', $_role_name );
			if ( empty($role_specs[2]) || ! in_array( $role_specs[2], $pp_filtered_types ) )
				continue;

			// add all type-specific caps whose base property cap is included in this pattern role
			// i.e. If 'edit_posts' is in the pattern role, grant $type_obj->cap->edit_posts
			$pp_metagroup_caps = array_merge( $pp_metagroup_caps, array_fill_keys( $pp_cap_caster->get_typecast_caps( $_role_name, 'site' ), true ) );
		}

		return $pp_metagroup_caps;
	}

	function show_capability_hints( $default ) {
		if ( pp_capabilities_get_permissions_option('display_hints') ) {
			echo '<ul class="ul-disc publishpress-caps-extra-hints" style="margin-top:10px;display:none">';

			$pp_prefix = (defined('PPC_VERSION') && !defined('PRESSPERMIT_VERSION')) ? 'pp' : 'presspermit';

			if ( defined( 'PPCE_VERSION' ) || ! defined( 'PRESSPERMIT_ACTIVE' ) || in_array( $default, array( 'subscriber', 'contributor', 'author', 'editor' ) ) ) {
				echo '<li>';
				if ( defined( 'PPCE_VERSION' ) || ! defined( 'PRESSPERMIT_ACTIVE' ) ) {
					if ( pp_capabilities_get_permissions_option( 'advanced_options' ) )
						$parenthetical = ' (' . sprintf( esc_html__( 'see %1$sRole Usage%2$s: "Pattern Roles"', 'capability-manager-enhanced' ), "<a href='" . admin_url("admin.php?page={$pp_prefix}-role-usage") . "'>", '</a>' ) . ')';
					else
						$parenthetical = ' (' . sprintf( esc_html__( 'activate %1$sAdvanced settings%2$s, see Role Usage', 'capability-manager-enhanced' ), "<a href='" . admin_url("admin.php?page={$pp_prefix}-settings&pp_tab=advanced") . "'>", '</a>' ). ')';
				} else
					$parenthetical = '';

				if ( defined( 'PRESSPERMIT_ACTIVE' ) )
					printf( esc_html__( '"Posts" capabilities selected here also define type-specific role assignment for Permission Groups%s.', 'capability-manager-enhanced' ), $parenthetical ) ;
				else
					printf( esc_html__( '"Posts" capabilities selected here also define type-specific role assignment for Permit Groups%s.', 'capability-manager-enhanced' ), $parenthetical ) ;

				echo '</li>';
			}

			$status_hint = '';
			if ( defined( 'PRESSPERMIT_ACTIVE' ) )
				if ( defined( 'PPS_VERSION' ) )
					$status_hint = sprintf( __( 'Capabilities for custom statuses can be manually added here. (See %sPermissions > Post Statuses%s for applicable names). %sSupplemental status-specific roles%s are usually more convenient, though.', 'capability-manager-enhanced' ), "<a href='" . admin_url("admin.php?page={$pp_prefix}-statuses&show_caps=1") . "'>", '</a>', "<a href='" . admin_url("admin.php?page={$pp_prefix}-groups") . "'>", '</a>' ) ;
				elseif ( pp_capabilities_get_permissions_option( 'display_extension_hints' ) )
					$status_hint = sprintf( __( 'Capabilities for custom statuses can be manually added here. Or activate the PP Custom Post Statuses extension to assign status-specific supplemental roles.', 'capability-manager-enhanced' ), "<a href='" . admin_url("admin.php?page={$pp_prefix}-role-usage") . "'>", '</a>' ) ;

			elseif ( defined( 'PP_VERSION' ) )
				$status_hint = sprintf( __( 'Capabilities for custom statuses can be manually added to a role here (see Conditions > Status > Capability Mapping for applicable names). However, it is usually more convenient to use Permit Groups to assign a supplemental status-specific role.', 'capability-manager-enhanced' ), "<a href='" . admin_url("admin.php?page={$pp_prefix}-role-usage") . "'>", '</a>' ) ;

			if ( $status_hint )
				echo "<li>" . esc_html($status_hint) . "</li>";

			echo '</ul>';
		}
	}

	// Note: CME can now impose type-specific capabilities without Press Permit Core active
	function pp_types_ui( $defined_types ) {
        global $sidebar_metabox_state;
        ?>
        <div class="ppc-sidebar-panel-metabox meta-box-sortables ppc-post-types">
            <?php $meta_box_state = (isset($sidebar_metabox_state['unique_capabilities_for_post_types'])) ? $sidebar_metabox_state['unique_capabilities_for_post_types'] : 'closed';  ?>
            <div class="postbox ppc-sidebar-panel <?php echo esc_attr($meta_box_state); ?>">
                <input
                    name="ppc_metabox_state[unique_capabilities_for_post_types]"
                    type="hidden"
                    class="metabox-state"
                    value="<?php echo esc_attr($meta_box_state); ?>"
                />
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle"><?php esc_html_e('Unique Post Type Capabilities', 'capability-manager-enhanced'); ?></h2>
                    <div class="handle-actions">
                        <button type="button" class="handlediv">
                            <span class="toggle-indicator"></span>
                        </button>
                    </div>
                </div>
                <div class="inside" style="text-align:center;">
                <?php
				$learn_more_url = 'https://publishpress.com/knowledge-base/type-specific-capabilities/';
				echo "<p class='cme-hint'>" . sprintf(
					/* translators: %1$s and %2$s are link tags for 'Find out more.' */
					esc_html__( 'Allow post type permissions to be controlled separately from other areas of WordPress. %1$sFind out more.%2$s', 'capability-manager-enhanced' ),
					'<a href="' . esc_url( $learn_more_url ) . '" target="_blank" rel="noopener noreferrer">',
					'</a>'
				) . "</p>";

				if ( defined( 'PRESSPERMIT_ACTIVE' ) && pp_capabilities_get_permissions_option( 'display_hints' ) ) :?>
				<div class="cme-subtext" style="margin-top:0">
				</div>
				<?php endif;

				echo "<table style='width:100%'><tr>";

				// bbPress' dynamic role def requires additional code to enforce stored caps
				$unfiltered = apply_filters('presspermit_unfiltered_post_types', ['forum','topic','reply','wp_block', 'customize_changeset']);
				$unfiltered = (defined('PP_CAPABILITIES_NO_LEGACY_FILTERS')) ? $unfiltered : apply_filters('pp_unfiltered_post_types', $unfiltered);  // maintain legacy filter to support custom code

				$hidden = apply_filters('presspermit_hidden_post_types', []);
				$hidden = apply_filters('pp_hidden_post_types', $hidden);  // maintain legacy filter to support custom code

				echo '<td style="width:50%">';

				$option_basename = 'enabled_post_types';
				$pp_prefix = (defined('PPC_VERSION') && !defined('PRESSPERMIT_VERSION')) ? 'pp' : 'presspermit';

				$enabled = get_option( $pp_prefix . '_' . $option_basename, array( 'post' => true, 'page' => true ) );

				foreach( $defined_types as $key => $type_obj ) {
					if ( ! $key )
						continue;

					if ( in_array( $key, $unfiltered ) )
						continue;

					$key = sanitize_key($key);

					$id = "$option_basename-" . $key;
					?>
					<div style="text-align:left">
					<?php if ( ! empty( $hidden[$key] ) ) :?>
						<input name="<?php echo(esc_attr($id));?>" type="hidden" id="<?php echo(esc_attr($id));?>" value="1" />
						<input name="<?php echo(esc_attr($option_basename) . "-options[]");?>" type="hidden" value="<?php echo(esc_attr($key))?>" />

					<?php else:
						$type_tooltip = sprintf(__( 'The slug for this post type is %s', 'capability-manager-enhanced' ), '<strong>' . esc_html($key) . '</strong>' );
						?>
						<div class="agp-vspaced_input">
						<span class="ppc-tool-tip disabled"><label for="<?php echo(esc_attr($id));?>">
						<input name="<?php echo(esc_attr($option_basename) . "-options[]");?>" type="hidden" value="<?php echo(esc_attr($key))?>" />
						<input name="<?php echo(esc_attr($id));?>" type="checkbox" id="<?php echo(esc_attr($id));?>" autocomplete="off" value="1" <?php checked('1', ! empty($enabled[$key]) );?> /> <?php echo(esc_html($type_obj->label));?>

						<?php
						echo ('</label><span class="tool-tip-text">
						<p>'. $type_tooltip .'</p>
						<i></i>
					</span></span></div>');

					endif;  // displaying checkbox UI

					echo '</div>';
				}
				echo '</td>';
				?>
				</tr>
				</table>

				<?php

				$define_create_posts_cap = get_option("{$pp_prefix}_define_create_posts_cap");?>

					<div style="margin-top:20px;margin-bottom:10px" class="ppc-tool-tip">
					<label for="pp_define_create_posts_cap">
					<input name="pp_define_create_posts_cap" type="checkbox" id="pp_define_create_posts_cap" autocomplete="off" value="1" <?php checked('1', $define_create_posts_cap );?> /> <?php esc_html_e('Use the "Create" capability for selected post types', 'capability-manager-enhanced');?>
                    <div class="tool-tip-text"><p><?php esc_attr_e('This will cause a new capability to be required for creating posts, and enable a checkbox column here for role assignments. Normally, post creation is controlled by the "Edit" capability.', 'capability-manager-enhanced');?></p><i></i></div>
					</label>
					</div>

				<?php
				do_action('pp-capabilities-type-specific-ui');
				?>

				<input type="submit" name="update_filtered_types" value="<?php esc_attr_e('Update', 'capability-manager-enhanced') ?>" class="button" />
                </div>
            </div>
        </div>
		<?php
	}

	// Note: CME can now impose type-specific capabilities without Press Permit Core active
	function pp_taxonomies_ui( $defined_taxonomies ) {
        global $sidebar_metabox_state;
		?>
        <div class="ppc-sidebar-panel-metabox meta-box-sortables ppc-taxonomies" style="display:none;">
            <?php $meta_box_state = (isset($sidebar_metabox_state['unique_capabilities_for_taxonomies'])) ? $sidebar_metabox_state['unique_capabilities_for_taxonomies'] : 'closed';  ?>
            <div class="postbox ppc-sidebar-panel <?php echo esc_attr($meta_box_state); ?>">
                <input
                    name="ppc_metabox_state[unique_capabilities_for_taxonomies]"
                    type="hidden"
                    class="metabox-state"
                    value="<?php echo esc_attr($meta_box_state); ?>"
                />
                <div class="postbox-header">
                    <h2 class="hndle ui-sortable-handle"><?php esc_html_e('Unique Taxonomy Capabilities', 'capability-manager-enhanced'); ?></h2>
                    <div class="handle-actions">
                        <button type="button" class="handlediv">
                            <span class="toggle-indicator"></span>
                        </button>
                    </div>
                </div>
                <div class="inside" style="text-align:center;">
				<?php
				echo "<p class='cme-hint'>" . esc_html__( 'Allow taxonomy permissions to be controlled separately from other areas of WordPress.', 'capability-manager-enhanced' ) . "</p>";

				echo "<table style='width:100%'><tr>";

				$unfiltered = apply_filters( 'pp_unfiltered_taxonomies', array( 'post_status', 'topic-tag' ) );  // avoid confusion with Edit Flow administrative taxonomy
				$hidden = apply_filters( 'pp_hidden_taxonomies', array() );

				echo '<td style="width:50%">';

				$pp_prefix = (defined('PPC_VERSION') && !defined('PRESSPERMIT_VERSION')) ? 'pp' : 'presspermit';

				$option_basename = 'enabled_taxonomies';
				$option_name = $pp_prefix . '_' . $option_basename;

				$enabled = get_option( $option_name, array() );

				foreach( $defined_taxonomies as $taxonomy => $type_obj ) {
					if ( ! $taxonomy )
						continue;

					if ( in_array( $taxonomy, $unfiltered ) )
						continue;

					$taxonomy = sanitize_key($taxonomy);

					$id = "$option_basename-" . $taxonomy;
					?>
					<div style="text-align:left">
					<?php if ( ! empty( $hidden[$taxonomy] ) ) :?>
						<input name="<?php echo(esc_attr($id));?>" type="hidden" id="<?php echo(esc_attr($id));?>" value="1" />
						<input name="<?php echo(esc_attr($option_basename) . '-options[]');?>" type="hidden" value="<?php echo(esc_attr($taxonomy))?>" />

					<?php else:
						$type_tooltip = sprintf(__( 'The slug for this taxonomy is %s', 'capability-manager-enhanced' ), '<strong>' . esc_html($taxonomy) . '</strong>' );
						?>

						<span class="ppc-tool-tip disabled"><label for="<?php echo(esc_attr($id));?>">
						<input name="<?php echo(esc_attr($option_basename) . '-options[]');?>" type="hidden" value="<?php echo(esc_attr($taxonomy))?>" />
						<input name="<?php echo(esc_attr($id));?>" type="checkbox" autocomplete="off" id="<?php echo(esc_attr($id));?>" value="1" <?php checked('1', ! empty($enabled[$taxonomy]) );?> /> <?php echo(esc_html($type_obj->label));?>

						<?php
						echo ('</label><span class="tool-tip-text">
						<p>'. $type_tooltip .'</p>
						<i></i>
					</span></span></div>');

					endif;  // displaying checkbox UI

					echo '</div>';
				}
				echo '</td>';

				?>
				</tr>
				</table>

				<input type="submit" name="update_filtered_taxonomies" value="<?php esc_attr_e('Update', 'capability-manager-enhanced') ?>" class="button" />
                </div>
            </div>
        </div>

        <div class="ppc-sidebar-panel-metabox meta-box-sortables ppc-detailed-taxonomies" style="display:none;">
                <?php $meta_box_state = (isset($sidebar_metabox_state['detailed_capabilities_for_taxonomies'])) ? $sidebar_metabox_state['detailed_capabilities_for_taxonomies'] : 'closed';  ?>
                <div class="postbox ppc-sidebar-panel <?php echo esc_attr($meta_box_state); ?>">
                    <input
                        name="ppc_metabox_state[detailed_capabilities_for_taxonomies]"
                        type="hidden"
                        class="metabox-state"
                        value="<?php echo esc_attr($meta_box_state); ?>"
                    />
                    <div class="postbox-header">
                        <h2 class="hndle ui-sortable-handle"><?php esc_html_e('Detailed Taxonomy Capabilities', 'capability-manager-enhanced'); ?></h2>
                        <div class="handle-actions">
                            <button type="button" class="handlediv">
                                <span class="toggle-indicator"></span>
                            </button>
                        </div>
                    </div>
                    <div class="inside" style="text-align:center;">

				<?php
				echo "<p class='cme-hint'>" . esc_html__( 'Allow "Edit", "Delete" and "Assign" capabilities separately from the "Manage" capability.', 'capability-manager-enhanced' ) . "</p>";

				echo "<table style='width:100%'><tr>";

				$unfiltered = apply_filters( 'pp_unfiltered_taxonomies', array( 'post_status', 'topic-tag' ) );  // avoid confusion with Edit Flow administrative taxonomy
				$hidden = apply_filters( 'pp_hidden_taxonomies', array() );

				echo '<td style="width:50%">';

				$option_basename = 'detailed_taxonomies';
				$option_name = 'cme_' . $option_basename;

				$enabled = get_option( $option_name, array() );

				foreach( $defined_taxonomies as $taxonomy => $type_obj ) {
					if ( ! $taxonomy )
						continue;

					if ( in_array( $taxonomy, $unfiltered ) )
						continue;

					$taxonomy = sanitize_key($taxonomy);

					$id = "$option_basename-" . $taxonomy;
					?>
					<div style="text-align:left">
					<?php if ( ! empty( $hidden[$taxonomy] ) ) :?>
						<input name="<?php echo(esc_attr($id));?>" type="hidden" id="<?php echo(esc_attr($id));?>" value="1" />
						<input name="<?php echo(esc_attr($option_basename) . '-options[]');?>" type="hidden" value="<?php echo(esc_attr($taxonomy))?>" />

					<?php else:
						$type_tooltip = sprintf(__( 'The slug for this taxonomy is %s', 'capability-manager-enhanced' ), '<strong>' . esc_html($taxonomy) . '</strong>' );
						 ?>
						<div class="agp-vspaced_input">
						<span class="ppc-tool-tip disabled"><label for="<?php echo(esc_attr($id));?>">
						<input name="<?php echo(esc_attr($option_basename) . '-options[]');?>" type="hidden" value="<?php echo(esc_attr($taxonomy))?>" />
						<input name="<?php echo(esc_attr($id));?>" type="checkbox" autocomplete="off" id="<?php echo(esc_attr($id));?>" value="1" <?php checked('1', ! empty($enabled[$taxonomy]) );?> /> <?php echo(esc_html($type_obj->label));?>

						<?php
						echo ('</label><span class="tool-tip-text">
						<p>'. $type_tooltip .'</p>
						<i></i>
					</span></span></div>');

					endif;  // displaying checkbox UI

					echo '</div>';
				}
				echo '</td>';

				?>
				</tr>
				</table>

				<input type="submit" name="update_detailed_taxonomies" value="<?php esc_attr_e('Update', 'capability-manager-enhanced') ?>" class="button" />
                    </div>
                </div>
            </div>
		<?php
	}
}

