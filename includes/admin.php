<?php
/**
 * PublishPress Capabilities [Free]
 *
 * UI output for Capabilities screen.
 *
 * Provides admin pages to create and manage roles and capabilities.
 *
 * @author		Jordi Canals, Kevin Behrens
 * @copyright   Copyright (C) 2009, 2010 Jordi Canals, (C) 2020 PublishPress
 * @license		GNU General Public License version 2
 * @link		https://publishpress.com
 *
 *	Copyright 2009, 2010 Jordi Canals <devel@jcanals.cat>
 *	Modifications Copyright 2020, PublishPress <help@publishpress.com>
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	version 2 as published by the Free Software Foundation.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 **/

global $capsman, $cme_cap_helper, $current_user, $sidebar_metabox_state;

do_action('publishpress-caps_manager-load');

$sidebar_metabox_state = get_user_meta($current_user->ID, 'ppc_sidebar_metabox_state', true);
if ($sidebar_metabox_state == '' || !is_array($sidebar_metabox_state)) {
    $sidebar_metabox_state = [];
    $sidebar_metabox_state['how_to_user_capabilities'] = 'opened';
}

if (!isset($sidebar_metabox_state['multi_site'])) {
    $sidebar_metabox_state['multi_site'] = 'opened';
}
$roles = $this->roles;
$default = $this->current;

if ( $block_read_removal = _cme_is_read_removal_blocked( $this->current ) ) {
	if ( $current = get_role($default) ) {
		if ( empty( $current->capabilities['read'] ) ) {
			ak_admin_error( sprintf( __( 'Warning: This role cannot access the dashboard without the read capability. %1$sClick here to fix this now%2$s.', 'capability-manager-enhanced' ), '<a href="javascript:void(0)" class="cme-fix-read-cap">', '</a>' ) );
		}
	}
}

// include extractor plugin capabilites
require_once (dirname(CME_FILE) . '/includes/extractor-capabilities.php');

require_once (dirname(CME_FILE) . '/includes/roles/roles-functions.php');

require_once( dirname(__FILE__).'/pp-ui.php' );
$pp_ui = new Capsman_PP_UI();

if( defined('PRESSPERMIT_ACTIVE') ) {
	$pp_metagroup_caps = $pp_ui->get_metagroup_caps( $default );
} else {
	$pp_metagroup_caps = array();
}

if (defined('PUBLISHPRESS_REVISIONS_VERSION') && function_exists('rvy_get_option')) {
	$pp_revisions_copy   = rvy_get_option("copy_posts_capability");
	$pp_revisions_revise = rvy_get_option("revise_posts_capability");

	if (version_compare(PUBLISHPRESS_REVISIONS_VERSION, '3.7.15-beta3', '>=')) {
		$pp_revisions_approve = true;
	} else {
		$pp_revisions_approve = false;
	}
} else {
	$pp_revisions_copy   = false;
	$pp_revisions_revise = false;
	$pp_revisions_approve = false;
}

$cme_negate_all_tooltip_msg = '<span class="tool-tip-text">
<p>'. esc_html__('Negate All', 'capability-manager-enhanced') .'</p>
<i></i>
</span>';
$cme_negate_none_tooltip_msg = '<span class="tool-tip-text">
<p>'. esc_html__('Negate None', 'capability-manager-enhanced') .'</p>
<i></i>
</span>';
?>
<div class="wrap publishpress-caps-manage pressshack-admin-wrapper">
	<div id="icon-capsman-admin" class="icon32"></div>

	<h1><?php esc_html_e('Role Capabilities', 'capability-manager-enhanced') ?></h1>

	<?php
	pp_capabilities_roles()->notify->display();
	?>

	<script type="text/javascript">
	/* <![CDATA[ */
	jQuery(document).ready( function($) {
		$('#publishpress_caps_form').attr('action', 'admin.php?page=pp-capabilities&role=' + $('select[name="role"]').val());

		$('select[name="role"]').change(function(){
			window.location = '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities&role=')); ?>' + $(this).val() + '';
		});
	});
	/* ]]> */
	</script>

	<form id="publishpress_caps_form" method="post" action="admin.php?page=<?php echo esc_attr($this->ID);?>">
	<?php wp_nonce_field('capsman-general-manager'); ?>

	<?php
	if (empty($_REQUEST['pp_caps_tab']) && !empty($_REQUEST['added'])) {
		$pp_tab = 'additional';
	} else {
		$pp_tab = (!empty($_REQUEST['pp_caps_tab'])) ? sanitize_key($_REQUEST['pp_caps_tab']) : 'edit';
	}
	?>

	<input type="hidden" name="pp_caps_tab" value="<?php echo esc_attr($pp_tab);?>" />

	<fieldset>
	<table id="akmin" class="clear"><tr><td>
	<div class="pp-columns-wrapper pp-enable-sidebar">
		<div class="pp-column-left">
            <div style="margin-bottom: 20px;">
                <div class="pp-capabilities-submit-top" style="float:right">
					<div class="capabilities-top-action">
						<div class="pp-capabilities-global-search">
							<input type="text" id="pp-global-capability-search"
								class="regular-text"
								placeholder="<?php esc_attr_e('Search capabilities...', 'capability-manager-enhanced'); ?>" />
							<div id="pp-search-results-summary" style="font-size: 12px; color: #666;"></div>
						</div>
						<div>
							<?php
							$caption = (in_array(sanitize_key(get_locale()), ['en_EN', 'en_US'])) ? 'Save Capabilities' : __('Save Changes');
							?>
							<input type="submit" name="SaveRole" value="<?php echo esc_attr($caption);?>" class="button-primary" />
						</div>
					</div>
                </div>

                <select name="role">
                    <?php
                    foreach ( $roles as $role_name => $name ) {
                        $role_name = sanitize_key($role_name);

                        if (pp_capabilities_is_editable_role($role_name)) {
                            $name = translate_user_role($name);
                            echo '<option value="' . esc_attr($role_name) .'"'; selected($default, $role_name); echo '> ' . esc_html($name) . ' &nbsp;</option>';
                        }
                    }
                    ?>
                </select>
            </div>
			<?php
			$img_url = $capsman->mod_url . '/images/';
			?>

			<?php
			if ( defined( 'PRESSPERMIT_ACTIVE' ) ) {
				$pp_ui->show_capability_hints( $default );
			}

			if ( defined( 'MULTISITE' ) && MULTISITE ) {
				global $wp_roles;
				global $wpdb;

				if ( ! empty($_REQUEST['cme_net_sync_role'] ) ) {
					$main_site_id = (function_exists('get_main_site_id')) ? get_main_site_id() : 1;
					switch_to_blog($main_site_id);
					wp_cache_delete( $wpdb->prefix . 'user_roles', 'options' );
				}

				( method_exists( $wp_roles, 'for_site' ) ) ? $wp_roles->for_site() : $wp_roles->reinit();
			}
			$capsman->reinstate_db_roles();

			$current = get_role($default);

			$rcaps = $current->capabilities;

			$is_administrator = current_user_can( 'administrator' ) || (is_multisite() && is_super_admin());

			$custom_types = get_post_types( array( '_builtin' => false ), 'names' );
			$custom_tax = get_taxonomies( array( '_builtin' => false ), 'names' );

			$defined = [];
			$defined['type'] = apply_filters('cme_filterable_post_types', get_post_types(['public' => true, 'show_ui' => true], 'object', 'or'));

			if (in_array(get_locale(), ['en_EN', 'en_US'])) {
				$defined['type']['wp_navigation']->label = __('Nav Menus (Block)', 'capability-manager-enhanced');
			} else {
				$defined['type']['wp_navigation']->label .= ' (' . __('Block', 'capability-manager-enhanced') . ')';
			}

			$defined['taxonomy'] = apply_filters('cme_filterable_taxonomies', get_taxonomies(['public' => true, 'show_ui' => true], 'object', 'or'));
			$defined['taxonomy']['nav_menu'] = get_taxonomy('nav_menu');

			if (in_array(get_locale(), ['en_EN', 'en_US'])) {
				$defined['taxonomy']['nav_menu']->label = __('Nav Menus (Legacy)', 'capability-manager-enhanced');
			} else {
				$defined['taxonomy']['nav_menu']->label .= ' (' . __('Legacy', 'capability-manager-enhanced') . ')';
			}

			// bbPress' dynamic role def requires additional code to enforce stored caps
			$unfiltered['type'] = apply_filters('presspermit_unfiltered_post_types', ['forum','topic','reply','wp_block']);
			$unfiltered['type'] = (defined('PP_CAPABILITIES_NO_LEGACY_FILTERS')) ? $unfiltered['type'] : apply_filters('pp_unfiltered_post_types', $unfiltered['type']);

			$unfiltered['taxonomy'] = apply_filters('presspermit_unfiltered_post_types', ['post_status', 'topic-tag']);  // avoid confusion with Edit Flow administrative taxonomy
			$unfiltered['taxonomy'] = (defined('PP_CAPABILITIES_NO_LEGACY_FILTERS')) ? $unfiltered['taxonomy'] : apply_filters('pp_unfiltered_taxonomies', $unfiltered['taxonomy']);

			$enabled_taxonomies = cme_get_assisted_taxonomies();

			$cap_properties['edit']['type'] = array( 'edit_posts' );

			foreach( $defined['type'] as $type_obj ) {
				if ( 'attachment' != $type_obj->name ) {
					if ( isset( $type_obj->cap->create_posts ) && ( $type_obj->cap->create_posts != $type_obj->cap->edit_posts ) ) {
						$cap_properties['edit']['type'][]= 'create_posts';
						break;
					}
				}
			}

			$cap_properties['edit']['type'][]= 'edit_others_posts';
			$cap_properties['edit']['type'] = array_merge( $cap_properties['edit']['type'], array( 'publish_posts', 'edit_published_posts', 'edit_private_posts' ) );

			$cap_properties['delete']['type'] = array( 'delete_posts', 'delete_others_posts' );
			$cap_properties['delete']['type'] = array_merge( $cap_properties['delete']['type'], array( 'delete_published_posts', 'delete_private_posts' ) );

            if (defined('PRESSPERMIT_VERSION') && defined('PRESSPERMIT_COLLAB_VERSION')) {
                $cap_properties['list']['type'] = ['list_posts', 'list_others_posts', 'list_published_posts', 'list_private_posts'];
            }

			if ($pp_revisions_copy) {
				$cap_properties['copy']['type'] = ['copy_posts', 'copy_others_posts', 'copy_published_posts', 'copy_private_posts'];
			}

			if ($pp_revisions_revise) {
				$cap_properties['revise']['type'] = ['revise_posts', 'revise_others_posts', 'revise_published_posts', 'revise_private_posts'];
			}

			if ($pp_revisions_approve) {
				$cap_properties['approve']['type'] = ['approve_posts', 'approve_others_posts'];
			}

			$cap_properties['read']['type'] = array( 'read_private_posts' );

            $cap_properties['taxonomies']['taxonomy'] =  array( 'manage_terms', 'edit_terms', 'assign_terms', 'delete_terms' );

			$stati = get_post_stati( array( 'internal' => false ) );

			$cap_type_names = array(
				'' => __( '&nbsp;', 'capability-manager-enhanced' ),
				'read' => __( 'Visibility', 'capability-manager-enhanced' ),
				'edit' => __( 'Editing', 'capability-manager-enhanced' ),
				'delete' => __( 'Deletion', 'capability-manager-enhanced' ),
                'taxonomies' => __( 'Taxonomies', 'capability-manager-enhanced' ),
			);

            if (defined('PRESSPERMIT_VERSION') && defined('PRESSPERMIT_COLLAB_VERSION')) {
                $cap_type_names['list'] = __('Listing', 'capability-manager-enhanced');
            }

            if ($pp_revisions_copy) {
                $cap_type_names['copy'] = __('Create Revision', 'capability-manager-enhanced');
            }

            if ($pp_revisions_revise) {
                $cap_type_names['revise'] = __('Submit Revision', 'capability-manager-enhanced');
            }

			if ($pp_revisions_approve) {
				$cap_type_names['approve'] = __('Approve Revision', 'capability-manager-enhanced');
			}

			$cap_tips = array(
				'read_private' => esc_attr__( 'Can read posts which are currently published with private visibility.', 'capability-manager-enhanced' ),
				'edit' => esc_attr__( 'Has basic editing capability (but may need other capabilities based on post status and ownership).', 'capability-manager-enhanced' ),
				'edit_others' => esc_attr__( 'Can edit posts which were created by other users.', 'capability-manager-enhanced' ),
				'edit_published' => esc_attr__( 'Can edit posts which are currently published.', 'capability-manager-enhanced' ),
				'edit_private' => esc_attr__( 'Can edit posts which are currently published with private visibility.', 'capability-manager-enhanced' ),
				'publish' => esc_attr__( 'Can make a post publicly visible.', 'capability-manager-enhanced' ),
				'delete' => esc_attr__( 'Has basic deletion capability (but may need other capabilities based on post status and ownership).', 'capability-manager-enhanced' ),
				'delete_others' => esc_attr__( 'Can delete posts which were created by other users.', 'capability-manager-enhanced' ),
				'delete_published' => esc_attr__( 'Can delete posts which are currently published.', 'capability-manager-enhanced' ),
				'delete_private' => esc_attr__( 'Can delete posts which are currently published with private visibility.', 'capability-manager-enhanced' ),
			);

			$default_caps = array( 'read_private_posts', 'edit_posts', 'edit_others_posts', 'edit_published_posts', 'edit_private_posts', 'publish_posts', 'delete_posts', 'delete_others_posts', 'delete_published_posts', 'delete_private_posts',
								   'read_private_pages', 'edit_pages', 'edit_others_pages', 'edit_published_pages', 'edit_private_pages', 'publish_pages', 'delete_pages', 'delete_others_pages', 'delete_published_pages', 'delete_private_pages',
								   'manage_categories'
								   );

            if (defined('PRESSPERMIT_PRO_FILE') && defined('PRESSPERMIT_COLLAB_VERSION')) {
                $default_caps = array_merge($default_caps, ['list_posts', 'list_others_posts', 'list_published_posts', 'list_private_posts', 'list_pages', 'list_others_pages', 'list_published_pages', 'list_private_pages']);
            }

            if ($pp_revisions_copy) {
                $default_caps = array_merge($default_caps, ['copy_posts', 'copy_others_posts', 'copy_pages', 'copy_others_pages']);
            }

            if ($pp_revisions_revise) {
                $default_caps = array_merge($default_caps, ['revise_posts', 'revise_others_posts', 'revise_pages', 'revise_others_pages']);
            }

			$type_caps = array();
			$type_metacaps = array();

			// Role Scoper and PP1 adjust attachment access based only on user's capabilities for the parent post
			if ( defined('OLD_PRESSPERMIT_ACTIVE') ) {
				unset( $defined['type']['attachment'] );
			}
			?>

			<script type="text/javascript">
			/* <![CDATA[ */
			jQuery(document).ready( function($) {
				if ($('.ppc-capabilities-tabs li.ppc-capabilities-tab-active').hasClass('ppc-full-width')) {
					$('.capabilities-sidebar').hide();
					$('#ppc-capabilities-wrapper .ppc-capabilities-content').css('grid-template-columns', '1fr');
				}

				// Tabs and Content display
				$(document).on('click', '.ppc-capabilities-tabs > ul > li', function() {
					var $pp_tab = $(this).attr('data-content');
					var data_slug = $(this).attr('data-slug');

					$("[name='pp_caps_tab']").val(data_slug);

					// Show current Content
					$('.ppc-capabilities-content > div').not('.capabilities-sidebar').hide();
					$('#' + $pp_tab).show();

					var post_ops = ['read', 'edit', 'delete', 'list'];
					$('.capabilities-sidebar .ppc-post-types').toggle(post_ops.indexOf(data_slug,) != -1);

					$('.capabilities-sidebar .ppc-taxonomies').toggle(data_slug == 'taxonomies');
					$('.capabilities-sidebar .ppc-detailed-taxonomies').toggle(data_slug == 'taxonomies');

					if ($(this).hasClass('ppc-full-width')) {
						$('.capabilities-sidebar').hide();
						$('#ppc-capabilities-wrapper .ppc-capabilities-content').css('grid-template-columns', '1fr');
					} else {
						$('.capabilities-sidebar').show();

						if ($(window).width() > 1199) {
							$('#ppc-capabilities-wrapper .ppc-capabilities-content').css('grid-template-columns', '1fr 200px 70px');
						}
					}

					$('#' + $pp_tab + '-taxonomy').show();

					// Active current Tab
					$('.ppc-capabilities-tabs > ul > li').removeClass('ppc-capabilities-tab-active');
					$(this).addClass('ppc-capabilities-tab-active');

					// Add search functionality
					var searchTerm = $('#pp-global-capability-search').val().trim();
					if (searchTerm.length >= 2) {
						// Handle special case for taxonomies tab
						var filterInputId = (data_slug === 'taxonomies') ?
							'cme-cap-type-tables-' + data_slug + '-taxonomy .ppc-filter-text' :
							'cme-cap-type-tables-' + data_slug + ' .ppc-filter-text';
						var $filterInput = $(filterInputId);
						if ($filterInput.length > 0) {
							$filterInput.val(searchTerm).trigger('input');
						}
					}

					// Scroll to content area (for responsive display)
					if ($(window).width() <= 1199) {
						$([document.documentElement, document.body]).animate({
							scrollTop: $("#capabilities_content").offset().top - 20
						}, 500);
					}
				});
			});
			/* ]]> */
			</script>

			<div id="ppc-capabilities-wrapper" class="postbox">
				<div class="ppc-capabilities-tabs">
					<ul>
						<?php
						$full_width_tabs = apply_filters('pp_capabilities_full_width_tabs', []);

						if (empty($_REQUEST['pp_caps_tab']) && !empty($_REQUEST['added'])) {
							$active_tab_slug = 'additional';
						} else {
							$active_tab_slug = (!empty($_REQUEST['pp_caps_tab'])) ? sanitize_key($_REQUEST['pp_caps_tab']) : 'edit';
						}

						$active_tab_id = "cme-cap-type-tables-{$active_tab_slug}";

						$ppc_tab_active = 'ppc-capabilities-tab-active';

						// caps: edit, delete, read
						foreach( array_keys($cap_properties) as $cap_type ) {
							$tab_id = "cme-cap-type-tables-$cap_type";
							$classes = [];

							if ($tab_id == $active_tab_id) {
								$classes []= $ppc_tab_active;
							}

							if (!empty($full_width_tabs[$cap_type])) {
								$classes []= 'ppc-full-width';
							}

							$class = implode(' ', $classes);

							echo '<li data-slug="'. esc_attr($cap_type) . '"' . ' data-content="cme-cap-type-tables-' . esc_attr($cap_type) . '" class="' . esc_attr($class) . '">'
								. esc_html($cap_type_names[$cap_type]) .
							'</li>';
						}

						if ($extra_tabs = apply_filters('pp_capabilities_extra_post_capability_tabs', [])) {
							foreach($extra_tabs as $tab_slug => $tab_caption) {
								$tab_slug = esc_attr($tab_slug);

								$tab_id = "cme-cap-type-tables-{$tab_slug}";

								$classes = [];

								if ($tab_id == $active_tab_id) {
									$classes []= $ppc_tab_active;
								}

								if (!empty($full_width_tabs[$tab_slug])) {
									$classes []= 'ppc-full-width';
								}

								$class = implode(' ', $classes);

								echo '<li data-slug="' . esc_attr($tab_slug) . '"' . ' data-content="' . esc_attr($tab_id) . '" class="' . esc_attr($class) . '">'
								. esc_html($tab_caption) .
								'</li>';
							}
						}

                        //grouped capabilities
                        $grouped_caps       = [];
                        $grouped_caps_lists = [];

                        //add media related caps
                        $grouped_caps['Media'] = [
                            'edit_files',
                            'upload_files',
                            'unfiltered_upload',
                        ];
                        $grouped_caps_lists = array_merge($grouped_caps_lists, $grouped_caps['Media']);

                        //add comments related caps
                        $grouped_caps['Comments'] = [
                            'moderate_comments'
                        ];
                        if (isset($rcaps['edit_comment'])) {
                            $type_metacaps['edit_comment'] = 1;
                        }
                        $grouped_caps_lists = array_merge($grouped_caps_lists, $grouped_caps['Comments']);

                        //add users related caps
                        $grouped_caps['Users'] = [
                            'create_users',
                            'delete_users',
                            'edit_users',
                            'list_users',
                            'promote_users',
                            'remove_users',
                        ];
                        $grouped_caps_lists = array_merge($grouped_caps_lists, $grouped_caps['Users']);

                        //add admin options related caps
                        $grouped_caps['Admin'] = [
                            'manage_options',
                            'edit_dashboard',
                            'export',
                            'import',
                            'read',
                            'update_core',
                            'unfiltered_html',
                        ];
                        $grouped_caps_lists = array_merge($grouped_caps_lists, $grouped_caps['Admin']);

                        //add themes related caps
                        $grouped_caps['Themes'] = [
                            'delete_themes',
                            'edit_themes',
                            'install_themes',
                            'switch_themes',
                            'update_themes',
                            'edit_theme_options',
                            'manage_links',
                        ];
                        $grouped_caps_lists = array_merge($grouped_caps_lists, $grouped_caps['Themes']);

                        //add plugin related caps
                        $grouped_caps['Plugins'] = [
                            'activate_plugins',
                            'delete_plugins',
                            'edit_plugins',
                            'install_plugins',
                            'update_plugins',
                        ];
                        $grouped_caps_lists = array_merge($grouped_caps_lists, $grouped_caps['Plugins']);

                        if (is_multisite()) {
                            //add multisite caps
							$grouped_caps['Multisite'] = [
								'create_sites',
                                'delete_sites',
                                'manage_network',
                                'manage_sites',
                                'manage_network_users',
                                'manage_network_plugins',
                                'manage_network_themes',
                                'manage_network_options',
                                'upgrade_network',
                                'setup_network',
							];
                            $grouped_caps_lists = array_merge($grouped_caps_lists, $grouped_caps['Multisite']);
                        }

						$grouped_caps = apply_filters('cme_grouped_capabilities', $grouped_caps);

						foreach($grouped_caps as $grouped_title => $__grouped_caps) {
							$tab_slug = pp_capabilities_convert_to_slug(sanitize_title($grouped_title));
							switch ($grouped_title) {
								case 'Comments' :
									$grouped_title = __('Comments');
									break;

								case 'Media' :
									$grouped_title = __('Media');
									break;

								case 'Users' :
									$grouped_title = __('Users');
									break;

								case 'Themes' :
									$grouped_title = __('Themes');
									break;

								case 'Plugins' :
									$grouped_title = __('Plugins');
									break;

								case 'Multisite' :
									$grouped_title = esc_html__('Multisite', 'capability-manager-enhanced');
									break;

								case 'Admin' :
									$grouped_title = esc_html__('Admin', 'capability-manager-enhanced');
									break;

								default:
									$grouped_title = esc_html($grouped_title);
							}

							$tab_id = 'cme-cap-type-tables-' . $tab_slug;
							$tab_active = ($tab_id == $active_tab_id) ? $ppc_tab_active : '';

							echo '<li data-slug="' . esc_attr($tab_slug) . '" data-content="' . esc_attr($tab_id) . '" class="' . esc_attr($tab_active) . '">'
								. esc_html(str_replace('_', ' ', $grouped_title)) .
							'</li>';
						}

						// caps: plugins
						$plugin_caps =  apply_filters('cme_plugin_capabilities', []);

						foreach($plugin_caps as $plugin_title => $__plugin_caps) {
							$plugin_title = esc_html($plugin_title);

							$tab_slug = pp_capabilities_convert_to_slug(sanitize_title($plugin_title));
							$tab_id = 'cme-cap-type-tables-' . $tab_slug;
							$tab_name = esc_html(str_replace('_', ' ', $plugin_title));
							// support extractor staging label
							$tab_name = str_replace('(CAPABILITYEXTRACTOR)', '<span class="capability-extractor-label">CE</span>', $tab_name);
							$tab_active = ($tab_id == $active_tab_id) ? $ppc_tab_active : '';

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '<li data-slug="' . esc_attr($tab_slug) . '" data-content="' . esc_attr($tab_id) . '" class="' . esc_attr($tab_active) . '">'
								. $tab_name .
							'</li>';
						}

						$tab_id = "cme-cap-type-tables-invalid";
						$tab_active = ($tab_id == $active_tab_id) ? $ppc_tab_active : '';
						$tab_caption = esc_html__( 'Invalid Capabilities', 'capability-manager-enhanced' );
						echo '<li id="cme_tab_invalid_caps" data-slug="invalid" data-content="' . esc_attr($tab_id) . '" class="' . esc_attr($tab_active) . '" style="display:none;">' . esc_html($tab_caption) . '</li>';

						$tab_id = "cme-cap-type-tables-additional";
						$tab_active = ($tab_id == $active_tab_id) ? $ppc_tab_active : '';
						$tab_caption = esc_html__( 'Additional', 'capability-manager-enhanced' );
						echo '<li data-slug="additional" data-content="' . esc_attr($tab_id) . '" class="' . esc_attr($tab_active) . '">' . esc_html($tab_caption) . '</li>';
						?>
					</ul>
				</div>

				<div id="capabilities_content" class="ppc-capabilities-content">
					<?php
					// caps: read, edit, deletion
					foreach( array_keys($cap_properties) as $cap_type ) {

						foreach( array_keys($defined) as $item_type ) {


                            if (!isset($cap_properties[$cap_type][$item_type])) {
                                continue;
                            }
							if ( ! count( $cap_properties[$cap_type][$item_type] ) )
								continue;

							$tab_id = "cme-cap-type-tables-" . pp_capabilities_convert_to_slug($cap_type);
							$div_display = ($tab_id == $active_tab_id) ? 'block' : 'none';

							$any_caps = false;

							if ($item_type == 'taxonomy') {
								$tab_id .= '-taxonomy';

								ob_start();
							}

							echo "<div id='" . esc_attr($tab_id) . "' style='display:" . esc_attr($div_display) . ";'>";

							if ('taxonomy' == $item_type) {
								$caption_pattern = esc_html__('Term %s Capabilities', 'capability-manager-enhanced');
							} else {
								$caption_pattern = (in_array($cap_type, ['copy', 'revise']))
								? esc_html__('%s Capabilities', 'capability-manager-enhanced')
							 	: esc_html__('Post %s Capabilities', 'capability-manager-enhanced');
							}

							$cap_type_name = ('taxonomies' == $cap_type) ? esc_html__('Taxonomy', 'capability-manager-enhanced') : $cap_type_names[$cap_type];

							echo '<h3>' .  sprintf($caption_pattern, esc_html($cap_type_name)) . '</h3>';

							echo '<div class="ppc-filter-wrapper">';
								echo '<select class="ppc-filter-select">';
									$filter_caption = ('taxonomy' == $item_type) ? __('Filter by taxonomy', 'capability-manager-enhanced') : __('Filter by post type', 'capability-manager-enhanced');
									echo '<option value="">' . esc_html($filter_caption) . '</option>';
								echo '</select>';
								echo ' <button class="button secondary-button ppc-filter-select-reset" type="button">' . esc_html__('Clear') . '</button>';
							echo '</div>';

							echo "<table class='widefat striped cme-typecaps cme-typecaps-basic cme-typecaps-" . esc_attr($cap_type) . "'>";

							echo '<thead><tr><th class="pp-header-checkall">';
							echo '<input type="checkbox" name="pp_toggle_all" class="excluded-input" autocomplete="off"> &nbsp;';
							echo '</th>';

							// label cap properties
							foreach( $cap_properties[$cap_type][$item_type] as $prop ) {
								$prop = str_replace( '_posts', '', $prop );
								$prop = str_replace( '_pages', '', $prop );
								$prop = str_replace( '_terms', '', $prop );

								if (in_array($prop, ['copy_published', 'copy_private', 'revise_published', 'revise_private'])) {
									echo "<th></th>";
									continue;
								}

								$th_class = ( 'taxonomy' == $item_type ) ? 'term-cap' : 'post-cap';

								$tip_text = '';
								if ( isset( $cap_tips[$prop] ) ) {
									$th_class .= ' ppc-tool-tip';
									$tip_text = '<div class="tool-tip-text">
										<p>'. $cap_tips[$prop] .'</p>
										<i></i>
									</div>';
								}

								echo "<th style='text-align:center;' class='" . esc_attr($th_class) . "'>" . $tip_text;

								if ( ( 'delete' != $prop ) || ( 'taxonomy' != $item_type ) || cme_get_detailed_taxonomies() ) {
									echo str_replace('_', '<br />', esc_html(ucwords($prop)));
								}

								echo '</th>';
							}

							echo '</tr></thead>';
                            $attachement_cap_position = 0;
                            foreach( $defined[$item_type] as $key => $type_obj ) {
								if ( in_array( $key, $unfiltered[$item_type] ) )
									continue;

								if (in_array($cap_type, ['copy', 'revise'])) {
									global $revisionary;

									if (!empty($revisionary) && !empty($revisionary->enabled_post_types) && empty($revisionary->enabled_post_types[$key])) {
										continue;
									}
								}

								$row = "<tr class='cme_type_" . esc_attr($key) . "'>";

								if ( $cap_type ) {

                                    if (empty($force_distinct_ui) && empty($cap_properties[$cap_type][$item_type])) {
                                        continue;
                                    }

                                    if (defined('PRESSPERMIT_VERSION') || defined('PRESSPERMIT_PRO_VERSION')) {
                                        //add list capabilities
                                        if (isset($type_obj->cap->edit_posts) && !isset($type_obj->cap->list_posts)) {
                                            $type_obj->cap->list_posts = str_replace('edit_', 'list_', $type_obj->cap->edit_posts);
                                        }
                                        if (isset($type_obj->cap->edit_others_posts) && !isset($type_obj->cap->list_others_posts)) {
                                            $type_obj->cap->list_others_posts = str_replace('edit_', 'list_', $type_obj->cap->edit_others_posts);
                                        }
                                        if (isset($type_obj->cap->edit_published_posts) && !isset($type_obj->cap->list_published_posts)) {
                                            $type_obj->cap->list_published_posts = str_replace('edit_', 'list_', $type_obj->cap->edit_published_posts);
                                        }
                                        if (isset($type_obj->cap->edit_private_posts) && !isset($type_obj->cap->list_private_posts)) {
                                            $type_obj->cap->list_private_posts = str_replace('edit_', 'list_', $type_obj->cap->edit_private_posts);
                                        }
                                    }

									if ($pp_revisions_copy) {
										//add copy capabilities
										if (isset($type_obj->cap->edit_posts) && !isset($type_obj->cap->copy_posts)) {
											$type_obj->cap->copy_posts = str_replace('edit_', 'copy_', $type_obj->cap->edit_posts);
										}
										if (isset($type_obj->cap->edit_others_posts) && !isset($type_obj->cap->copy_others_posts)) {
											$type_obj->cap->copy_others_posts = str_replace('edit_', 'copy_', $type_obj->cap->edit_others_posts);
										}
									}

									if ($pp_revisions_revise) {
										//add revise capabilities
										if (isset($type_obj->cap->edit_posts) && !isset($type_obj->cap->revise_posts)) {
											$type_obj->cap->revise_posts = str_replace('edit_', 'revise_', $type_obj->cap->edit_posts);
										}
										if (isset($type_obj->cap->edit_others_posts) && !isset($type_obj->cap->revise_others_posts)) {
											$type_obj->cap->revise_others_posts = str_replace('edit_', 'revise_', $type_obj->cap->edit_others_posts);
										}
									}

									if ($pp_revisions_approve) {
										//add approve capabilities
										if (isset($type_obj->cap->edit_posts) && !isset($type_obj->cap->approve_posts)) {
											$type_obj->cap->approve_posts = str_replace('edit_', 'approve_', $type_obj->cap->edit_posts);
										}
										if (isset($type_obj->cap->edit_others_posts) && !isset($type_obj->cap->approve_others_posts)) {
											$type_obj->cap->approve_others_posts = str_replace('edit_', 'approve_', $type_obj->cap->edit_others_posts);
										}
									}

									if ('wp_navigation' == $type_obj->name) {
										$type_label = __('Nav Menus (Block)', 'capability-manager-enhanced');
									} elseif ('nav_menu' == $type_obj->name) {
										$type_label = __('Nav Menus (Legacy)', 'capability-manager-enhanced');
									} else {
										$type_label = (defined('CME_LEGACY_MENU_NAME_LABEL') && !empty($type_obj->labels->menu_name)) ? $type_obj->labels->menu_name : $type_obj->labels->name;
									}

									if (!empty($type_obj->name)) {
										if ('taxonomy' == $item_type) {
											$type_tooltip = sprintf(__( 'The slug for this taxonomy is %s', 'capability-manager-enhanced' ), '<strong>' . esc_html($type_obj->name) . '</strong>' );
										} else {
											$type_tooltip = sprintf(__( 'The slug for this post type is %s', 'capability-manager-enhanced' ), '<strong>' . esc_html($type_obj->name) . '</strong>' );
										}
										$type_tooltip_class = 'ppc-tool-tip disabled';
										$type_tooltip_msg  = '<span class="tool-tip-text">
										<p>'. $type_tooltip .'</p>
										<i></i>
									</span>';
									} else {
										$type_tooltip_class = '';
										$type_tooltip_msg  = '';
									}

									$row .= "<td>";
									$row .= '<input type="checkbox" class="pp-row-action-rotate excluded-input"> &nbsp;';
									$row .= "<span class='{$type_tooltip_class}'><a class='cap_type' href='#toggle_type_caps'>" . esc_html($type_label) . '</a> '. $type_tooltip_msg .'</span>';
									$row .= '<a style="display: none;" href="#" class="neg-type-caps">&nbsp;x&nbsp;</a>';
									$row .= '</td>';

									$display_row = ! empty($force_distinct_ui);
									$col_count = 0;

									foreach( $cap_properties[$cap_type][$item_type] as $prop ) {
										$td_classes = array();
										$checkbox = '';
										$cap_title = '';
										$disabled_cap = false;

                                        if ($type_obj->name === 'attachment') {
                                            $attachement_cap_position++;
                                        }

										if ( ! empty($type_obj->cap->$prop) && ( in_array( $type_obj->name, array( 'post', 'page' ) )
										|| ! in_array( $type_obj->cap->$prop, $default_caps )
										|| ( ( 'manage_categories' == $type_obj->cap->$prop ) && ( 'manage_terms' == $prop ) && ( 'category' == $type_obj->name ) ) ) ) {

											// if edit_published or edit_private cap is same as edit_posts cap, don't display a checkbox for it
											if ( ( ! in_array( $prop, array( 'edit_published_posts', 'edit_private_posts', 'create_posts' ) ) || ( $type_obj->cap->$prop != $type_obj->cap->edit_posts ) )
											&& ( ! in_array( $prop, array( 'delete_published_posts', 'delete_private_posts' ) ) || ( $type_obj->cap->$prop != $type_obj->cap->delete_posts ) )
											&& ( ! in_array( $prop, array( 'edit_terms', 'delete_terms' ) ) || ( $type_obj->cap->$prop != $type_obj->cap->manage_terms ) )

											&& ( ! in_array( $prop, array( 'manage_terms', 'edit_terms', 'delete_terms', 'assign_terms' ) )
												|| empty($cme_cap_helper->all_taxonomy_caps[$type_obj->cap->$prop])
												|| ( $cme_cap_helper->all_taxonomy_caps[ $type_obj->cap->$prop ] <= 1 )
												|| $type_obj->cap->$prop == str_replace( '_terms', "_{$type_obj->name}s", $prop )
												|| $type_obj->cap->$prop == str_replace( '_terms', "_" . _cme_get_plural($type_obj->name, $type_obj), $prop )
												)

											&& ( in_array( $prop, array( 'manage_terms', 'edit_terms', 'delete_terms', 'assign_terms' ) )
												|| empty($cme_cap_helper->all_type_caps[$type_obj->cap->$prop])
												|| ( $cme_cap_helper->all_type_caps[ $type_obj->cap->$prop ] <= 1 )
												|| $type_obj->cap->$prop == 'upload_files' && 'create_posts' == $prop && 'attachment' == $type_obj->name
												|| $type_obj->cap->$prop == str_replace( '_posts', "_{$type_obj->name}s", $prop )
												|| $type_obj->cap->$prop == str_replace( '_pages', "_{$type_obj->name}s", $prop )
												|| $type_obj->cap->$prop == str_replace( '_posts', "_" . _cme_get_plural($type_obj->name, $type_obj), $prop )
												|| $type_obj->cap->$prop == str_replace( '_pages', "_" . _cme_get_plural($type_obj->name, $type_obj), $prop )
												)
                                            && (!in_array($type_obj->cap->$prop, $grouped_caps_lists)) //capability not enforced in $grouped_caps_lists
											&& (('manage_post_tags' != $type_obj->cap->$prop) || ('post_tag' == $type_obj->name))
											) {
												// only present these term caps up top if we are ensuring that they get enforced separately from manage_terms
												if ( in_array( $prop, array( 'edit_terms', 'delete_terms', 'assign_terms' ) ) && ( ! in_array( $type_obj->name, cme_get_detailed_taxonomies() ) || defined( 'OLD_PRESSPERMIT_ACTIVE' ) ) ) {
													continue;
												}

												$cap_name = sanitize_text_field($type_obj->cap->$prop);

												if ( 'taxonomy' == $item_type )
													$td_classes []= "term-cap";
												else
													$td_classes []= "post-cap";

												if ( $is_administrator || current_user_can($cap_name) ) {
													$chk_classes = [];

                                                    $cap_title = '';
													if (! empty($pp_metagroup_caps[$cap_name]) ) {
														$tool_tip = sprintf(__( '%s: assigned by Permission Group', 'capability-manager-enhanced' ), '<strong>' . $cap_name . '</strong>' );
														$chk_classes []= 'cm-has-via-pp';
													} else {
														$tool_tip = sprintf(__( 'This capability is %s', 'capability-manager-enhanced' ), '<strong>' . $cap_name . '</strong>' );
													}

													$chk_class = ( $chk_classes ) ? ' class="' . implode(' ', $chk_classes) . '"' : '';

                                                    $checkbox = '<div class="ppc-tool-tip disabled"><input type="checkbox"' . $chk_class . ' name="caps[' . esc_attr($cap_name) . ']" autocomplete="off" value="1" ' . checked(1, ! empty($rcaps[$cap_name]), false ) . ' />
                                                        <div class="tool-tip-text">
                                                            <p>'. $tool_tip .'</p>
                                                            <i></i>
                                                        </div>
                                                    </div>';

													$type_caps [$cap_name] = true;
													$display_row = true;
													$any_caps = true;
													$disabled_cap = false;
												}
											} else {

												// only present these term caps up top if we are ensuring that they get enforced separately from manage_terms
												if ( in_array( $prop, array( 'edit_terms', 'delete_terms', 'assign_terms' ) ) && ( ! in_array( $type_obj->name, cme_get_detailed_taxonomies() ) || defined( 'OLD_PRESSPERMIT_ACTIVE' ) ) ) {
													continue;
												}

												$disabled_cap = true;
                                                $display_row = true;
                                                $cap_name = sanitize_text_field($type_obj->cap->$prop);
												$cap_title = '';

												if (($cap_name === 'manage_categories') && !defined('PRESSPERMIT_ACTIVE')) {
													$tool_tip = sprintf(__( 'This capability is controlled by %s', 'capability-manager-enhanced' ), '<strong>manage_categories</strong>' );

												} else {
													$tool_tip  = sprintf(__('This capability is controlled by %s Use the sidebar settings to allow this to be controlled independently.', 'capability-manager-enhanced'), '<strong>' . $cap_name . '</strong>.<br /><br />');
												}

                                                $checkbox = '<div class="ppc-tool-tip disabled"><input disabled class="disabled" type="checkbox" ' . checked(1, ! empty($rcaps[$cap_name]), false ) . ' />
                                                    <div class="tool-tip-text">
                                                        <p>'. $tool_tip .'</p>
                                                        <i></i>
                                                    </div>
                                                </div>';
											}

											if ( isset($rcaps[$cap_name]) && empty($rcaps[$cap_name]) ) {
												$td_classes []= "cap-neg";
											}
										} else {
                                            if ($type_obj->name === 'attachment') {
                                                if ($attachement_cap_position === 1 || $attachement_cap_position === 3) {
                                                    $tool_tip  =__('Use the sidebar settings to allow this to be controlled independently.', 'capability-manager-enhanced');
                                                } else {
                                                    $tool_tip  =__('This capability is not available for this post type.', 'capability-manager-enhanced');
                                                }

                                            } else {
                                                $tool_tip  =__('This capability is not available for this post type.', 'capability-manager-enhanced');
                                            }
                                            $checkbox = '<div class="ppc-tool-tip disabled">&nbsp; &nbsp; &nbsp; &nbsp;
                                                <div class="tool-tip-text">
                                                    <p>'. $tool_tip .'</p>
                                                    <i></i>
                                                </div>
                                            </div>';
											$td_classes []= "cap-unreg";
										}

                                        $td_classes[] = 'capability-checkbox-rotate';
                                        $td_classes[] = $cap_name;

										$td_class = ( $td_classes ) ? implode(' ', $td_classes) : '';

										$row .= '<td class="' . esc_attr($td_class) . '" title="' . esc_attr($cap_title) . '"' . "><span class='ppc-tool-tip disabled cap-x'>X</span>$checkbox";

										if ( !$disabled_cap && false !== strpos( $td_class, 'cap-neg' ) )
											$row .= '<input type="hidden" class="cme-negation-input" name="caps[' . esc_attr($cap_name) . ']" value="" />';

										$row .= "</td>";

										$col_count++;
									}

									if ('taxonomy' == $item_type) {
										for ($i = $col_count; $i < 4; $i++) {
											$row .= "<td></td>";
										}
									}

									if (!empty($type_obj->map_meta_cap) && !defined('PP_CAPABILITIES_NO_INVALID_SECTION')) {
										if ('type' == $item_type) {
											if (!in_array($type_obj->cap->read_post, $grouped_caps_lists)
                                                && !in_array($type_obj->cap->edit_post, $grouped_caps_lists)
                                                && !in_array($type_obj->cap->delete_post, $grouped_caps_lists)
                                                ) {
                                                    $type_metacaps[$type_obj->cap->read_post] = true;
                                                    $type_metacaps[$type_obj->cap->edit_post] = isset($type_obj->cap->edit_posts) && ($type_obj->cap->edit_post != $type_obj->cap->edit_posts);
                                                    $type_metacaps[$type_obj->cap->delete_post] = isset($type_obj->cap->delete_posts) && ($type_obj->cap->delete_post != $type_obj->cap->delete_posts);
                                                }
										} elseif ('taxonomy' == $item_type && !empty($type_obj->cap->edit_term) && !empty($type_obj->cap->delete_term)) {
											if (!in_array($type_obj->cap->edit_term, $grouped_caps_lists)
                                                && !in_array($type_obj->cap->delete_term, $grouped_caps_lists)
                                                ) {
                                                    $type_metacaps[$type_obj->cap->edit_term] = true;
                                                    $type_metacaps[$type_obj->cap->delete_term] = true;
                                                }
										}
									}
								}

								if ( $display_row ) {
									$row .= '</tr>';

									// Escaped piecemeal upstream; cannot be late-escaped until upstream UI output logic is reworked
									echo $row;
								}
							}

							echo '</table>';

							if ($cap_type === 'list' && defined('PRESSPERMIT_VERSION') && defined('PRESSPERMIT_COLLAB_VERSION')) {
                                echo '<p class="pp-subtext"> '. esc_html__('Admin listing access is normally provided by the "Edit" capabilities. These "List" capabilities only apply if the corresponding "Edit" capability is missing. Also, these "List" capabilities can grant access, but not deny access.', 'capability-manager-enhanced') .' </p>';
                            }

							do_action('publishpress-caps_manager_postcaps_table', $cap_type, $item_type, compact('current', 'rcaps', 'pp_metagroup_caps', 'is_administrator', 'default_caps', 'custom_types', 'defined', 'unfiltered', 'pp_metagroup_caps', 'active_tab_id'));

							echo '</div>';

							if ($item_type == 'taxonomy') {
								if ($any_caps)  {
									ob_flush();
								} else {
									ob_clean();
								}
							}

						} // end foreach item type
					}

					if (empty($caps_manager_postcaps_section)) {
						$caps_manager_postcaps_section = '';
					}

					do_action('publishpress-caps_manager_postcaps_section', compact('current', 'rcaps', 'pp_metagroup_caps', 'is_administrator', 'default_caps', 'custom_types', 'defined', 'unfiltered', 'pp_metagroup_caps','caps_manager_postcaps_section', 'active_tab_id'));

					$type_caps = apply_filters('publishpress_caps_manager_typecaps', $type_caps);

					// clicking on post type name toggles corresponding checkbox selections

					// caps: grouped
					$grouped_caps = apply_filters('cme_grouped_capabilities', $grouped_caps);

					foreach($grouped_caps as $grouped_title => $__grouped_caps) {

						$tab_id = 'cme-cap-type-tables-' . esc_attr(pp_capabilities_convert_to_slug($grouped_title));

						switch ($grouped_title) {
							case 'Comments' :
								$grouped_title = __('Comments');
								break;

							case 'Media' :
								$grouped_title = __('Media');
								break;

							case 'Users' :
								$grouped_title = __('Users');
								break;

							case 'Themes' :
								$grouped_title = __('Themes');
								break;

							case 'Plugins' :
								$grouped_title = __('Plugins');
								break;

							case 'Multisite' :
								$grouped_title = esc_html__('Multisite', 'capability-manager-enhanced');
								break;

							case 'Admin' :
								$grouped_title = esc_html__('Admin', 'capability-manager-enhanced');
								break;

							default:
								$grouped_title = esc_html($grouped_title);
						}

						$_grouped_caps = array_fill_keys($__grouped_caps, true);

						$div_display = ($tab_id == $active_tab_id) ? 'block' : 'none';

						echo '<div id="' . esc_attr($tab_id) . '" style="display:' . esc_attr($div_display) . '">';

						echo '<h3 class="cme-cap-section">' . esc_html(str_replace('_', ' ', $grouped_title)) . '</h3>';

						echo '<div class="ppc-filter-wrapper">';
							echo '<input type="text" class="regular-text ppc-filter-text" placeholder="' . esc_attr__('Filter by capability', 'capability-manager-enhanced') . '">';
							echo ' <button class="button secondary-button ppc-filter-text-reset" type="button">' . esc_html__('Clear') . '</button>';
						echo '</div>';
						echo '<div class="ppc-filter-no-results" style="display:none;">' . esc_html__( 'No results found. Please try again with a different word.', 'capability-manager-enhanced' ) . '</div>';

						echo '<table class="widefat fixed striped form-table cme-checklist single-checkbox-table">';

						$centinel_ = true;
						$checks_per_row = get_option( 'cme_form-rows', 1 );
						$i = 0; $first_row = true;

                        ?>
						<tr class="cme-bulk-select">
                            <td colspan="<?php echo (int) $checks_per_row;?>">
                                <input type="checkbox" class="cme-check-all" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>
                        <?php
						foreach( array_keys($_grouped_caps) as $cap_name ) {
							$cap_name = sanitize_text_field($cap_name);

							if ( isset( $type_caps[$cap_name] ) || isset($type_metacaps[$cap_name]) ) {
								continue;
							}

							if ( ! $is_administrator && ! current_user_can($cap_name) )
								continue;

							// Output first <tr>
							if ( $centinel_ == true ) {
								echo '<tr class="' . esc_attr($cap_name) . '">';
								$centinel_ = false;
							}

							if ( $i == $checks_per_row ) {
								echo '</tr><tr class="' . esc_attr($cap_name) . '">';
								$i = 0;
							}

							if ( ! isset( $rcaps[$cap_name] ) )
								$class = 'cap-no';
							else
								$class = ( $rcaps[$cap_name] ) ? 'cap-yes' : 'cap-neg';

							if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
								$class .= ' cap-metagroup';
								$title_text = sprintf( __( '%s: assigned by Permission Group', 'capability-manager-enhanced' ), $cap_name );
							} else {
								$title_text = $cap_name;
							}

							$disabled = '';
							$checked = !empty($rcaps[$cap_name]) ? 'checked' : '';
							$cap_title = $title_text;
							?>
							<td class="<?php echo esc_attr($class); ?>"><span class="ppc-tool-tip disabled cap-x">X</span><span class="ppc-tool-tip disabled"><label><input type="checkbox" name="caps[<?php echo esc_attr($cap_name); ?>]" class="pp-single-action-rotate" autocomplete="off" value="1" <?php echo esc_attr($checked) . esc_attr($disabled);?> />
							<span>
							<?php
							echo esc_html(str_replace( '_', ' ', $cap_name));
							?>
							</span></label></span><a href="#" class="neg-cap" style="visibility: hidden;">&nbsp;x&nbsp;</a>
							<?php if ( false !== strpos( $class, 'cap-neg' ) ) :?>
								<input type="hidden" class="cme-negation-input" name="caps[<?php echo esc_attr($cap_name); ?>]" value="" />
							<?php endif; ?>
							</td>

							<?php
							++$i;
						}

						if ( $i == $checks_per_row ) {
							echo '</tr>';
							$i = 0;
						} elseif ( ! $first_row ) {
							// Now close a wellformed table
							for ( $i; $i < $checks_per_row; $i++ ){
								echo '<td>&nbsp;</td>';
							}
							echo '</tr>';
						}
						?>

						<tr class="cme-bulk-select">
							<td colspan="<?php echo (int) $checks_per_row;?>">
								<input type="checkbox" class="cme-check-all" autocomplete="off" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>

						</table>
						</div>
					<?php
					}

					// caps: other

					$tab_id = "cme-cap-type-tables-other";
					$div_display = ($tab_id == $active_tab_id) ? 'block' : 'none';
					?>
					<div id="<?php echo esc_attr($tab_id);?>" style="display:<?php echo esc_attr($div_display);?>">
						<?php

						echo '<h3>' . esc_html__( 'WordPress Core Capabilities', 'capability-manager-enhanced' ) . '</h3>';

						echo '<div class="ppc-filter-wrapper">';
							echo '<input type="text" class="regular-text ppc-filter-text" placeholder="' . esc_attr__('Filter by capability', 'capability-manager-enhanced') . '">';
							echo ' <button class="button secondary-button ppc-filter-text-reset" type="button">' . esc_html__('Clear') . '</button>';
						echo '</div>';
						echo '<div class="ppc-filter-no-results" style="display:none;">' . esc_html__( 'No results found. Please try again with a different word.', 'capability-manager-enhanced' ) . '</div>';

						echo '<table class="widefat fixed striped form-table cme-checklist">';

						$centinel_ = true;
						$checks_per_row = get_option( 'cme_form-rows', 1 );
						$i = 0; $first_row = true;

                        ?>
						<tr class="cme-bulk-select">
                            <td colspan="<?php echo (int) $checks_per_row;?>">
                                <input type="checkbox" class="cme-check-all" autocomplete="off" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>

						<tr class="cme-bulk-select">
							<td colspan="<?php echo (int) $checks_per_row;?>">
								<input type="checkbox" class="cme-check-all" autocomplete="off" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>

						</table>
					</div>

					<?php
					$all_capabilities = apply_filters( 'capsman_get_capabilities', array_keys( $this->capabilities ), $this->ID );
					$all_capabilities = apply_filters( 'members_get_capabilities', $all_capabilities );

					// caps: plugins
					$plugin_caps = apply_filters('cme_plugin_capabilities', $plugin_caps);

					$plugin_cap_descriptions = apply_filters('cme_capability_descriptions', []);

					foreach($plugin_caps as $plugin_title => $__plugin_caps) {
						$plugin_title = esc_html($plugin_title);

						$_plugin_caps = array_fill_keys($__plugin_caps, true);

						$tab_id = 'cme-cap-type-tables-' . esc_attr(pp_capabilities_convert_to_slug($plugin_title));
						$tab_name = esc_html(str_replace('_', ' ', $plugin_title));
						// support extractor staging label
						$tab_name = str_replace('(CAPABILITYEXTRACTOR)', '<span class="capability-extractor-label">CE</span>', $tab_name);
						$div_display = ($tab_id == $active_tab_id) ? 'block' : 'none';

						echo '<div id="' . esc_attr($tab_id) . '" style="display:' . esc_attr($div_display) . '" class="cme-plugin-cap-table">';

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<h3 class="cme-cap-section">' . sprintf(esc_html__( 'Plugin Capabilities &ndash; %s', 'capability-manager-enhanced' ), $tab_name) . '</h3>';

						echo '<div class="ppc-filter-wrapper">';
							echo '<input type="text" class="regular-text ppc-filter-text" placeholder="' . esc_attr__('Filter by capability', 'capability-manager-enhanced') . '">';
							echo ' <button class="button secondary-button ppc-filter-text-reset" type="button">' . esc_html__('Clear') . '</button>';
						echo '</div>';
						echo '<div class="ppc-filter-no-results" style="display:none;">' . esc_html__( 'No results found. Please try again with a different word.', 'capability-manager-enhanced' ) . '</div>';

						echo '<table class="widefat fixed striped form-table cme-checklist single-checkbox-table">';

						$centinel_ = true;
						$checks_per_row = get_option( 'cme_form-rows', 1 );
						$i = 0; $first_row = true;

                        ?>
						<tr class="cme-bulk-select">
                            <td colspan="<?php echo (int) $checks_per_row + 1;?>" style="width: 100%">
                                <input type="checkbox" class="cme-check-all" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>
                        <?php
						foreach( array_keys($_plugin_caps) as $cap_name ) {
							$cap_name = sanitize_text_field($cap_name);

							if ( isset( $type_caps[$cap_name] ) || in_array($cap_name, $grouped_caps_lists) || isset($type_metacaps[$cap_name]) ) {
								continue;
							}

							if ( ! $is_administrator && ! current_user_can($cap_name) )
								continue;

							// Output first <tr>
							if ( $centinel_ == true ) {
								echo '<tr class="' . esc_attr($cap_name) . '">';
								$centinel_ = false;
							}

							if ( $i == $checks_per_row ) {
								echo '</tr><tr class="' . esc_attr($cap_name) . '">';
								$i = 0;
							}

							if ( ! isset( $rcaps[$cap_name] ) )
								$class = 'cap-no';
							else
								$class = ( $rcaps[$cap_name] ) ? 'cap-yes' : 'cap-neg';

							if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
								$class .= ' cap-metagroup';
								$title_text = sprintf( __( '%s: assigned by Permission Group', 'capability-manager-enhanced' ), $cap_name );
							} else {
								$title_text = $cap_name;
							}

                            if ($cap_name === 'manage_capabilities_user_testing') {
                                $warning_message = '&nbsp; <span class="ppc-tool-tip"><span class="dashicons dashicons-info-outline"></span><span class="tool-tip-text"><p>'. sprintf(esc_html__('The User Testing feature also requires the %1$s edit_users %2$s capability.', 'capability-manager-enhanced'), '<strong>', '</strong>') .'</p><i></i></span></span>';
                            } else {
                                $warning_message = '';
                            }

							$disabled = '';
							$checked = !empty($rcaps[$cap_name]) ? 'checked' : '';

							$cap_title = $title_text;
							?>
							<td class="<?php echo esc_attr($class); ?>"><span class="ppc-tool-tip disabled cap-x">X</span><span class="ppc-tool-tip disabled"><label><input type="checkbox" name="caps[<?php echo esc_attr($cap_name); ?>]" class="pp-single-action-rotate" autocomplete="off" value="1" <?php echo esc_attr($checked) . esc_attr($disabled);?> />
							<span>
							<?php
							echo esc_html(str_replace( '_', ' ', $cap_name));
							?>
							</span></label></span><?php echo $warning_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><a href="#" class="neg-cap" style="visibility: hidden;">&nbsp;x&nbsp;</a>
							<?php if ( false !== strpos( $class, 'cap-neg' ) ) :?>
								<input type="hidden" class="cme-negation-input" name="caps[<?php echo esc_attr($cap_name); ?>]" value="" />
							<?php endif; ?>
							</td>

							<td class="pp-cap-description">
							<?php if (!empty($plugin_cap_descriptions[$cap_name])) {
								echo $plugin_cap_descriptions[$cap_name];
							}
							?>
							</td>

							<?php
							++$i;
						}

						if ( $i == $checks_per_row ) {
							echo '</tr>';
							$i = 0;
						} elseif ( ! $first_row ) {
							// Now close a wellformed table
							for ( $i; $i < $checks_per_row; $i++ ){
								echo '<td>&nbsp;</td>';
							}
							echo '</tr>';
						}
						?>

						<tr class="cme-bulk-select">
							<td colspan="<?php echo (int) $checks_per_row + 1;?>">
								<input type="checkbox" class="cme-check-all" autocomplete="off" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>

						</table>
						</div>
					<?php
					}

					// caps: invalid
					if (array_intersect(array_keys(array_filter($type_metacaps)), $all_capabilities) && array_intersect_key($type_metacaps, array_filter($rcaps))) {
						$tab_id = "cme-cap-type-tables-invalid";
						$div_display = ($tab_id == $active_tab_id) ? 'block' : 'none';

						echo '<div id="' . esc_attr($tab_id) . '" style="display:' . esc_attr($div_display) . '">';
						echo '<h3 class="cme-cap-section">' . esc_html__( 'Invalid Capabilities', 'capability-manager-enhanced' ) . '</h3>';
						?>

						<div>
						<span class="cme-subtext">
							<?php esc_html_e('The following entries have no effect. Please assign desired capabilities on the Editing / Deletion / Private tabs.', 'capability-manager-enhanced');?>
						</span>
						</div>

						<table class="widefat fixed striped form-table cme-checklist single-checkbox-table">
						<tr>
						<?php
						$i = 0; $first_row = true;
                        $invalid_caps_capabilities = [];
						foreach( $all_capabilities as $cap_name ) {
							if ( ! isset($this->capabilities[$cap_name]) )
								$this->capabilities[$cap_name] = str_replace( '_', ' ', $cap_name );
						}

						uasort( $this->capabilities, 'strnatcasecmp' );  // sort by array values, but maintain keys );

						foreach ( $this->capabilities as $cap_name => $cap ) :
							$cap_name = sanitize_text_field($cap_name);

							if (!isset($type_metacaps[$cap_name]) || empty($rcaps[$cap_name])) {
								continue;
							}

							if ( ! $is_administrator && empty( $current_user->allcaps[$cap_name] ) ) {
								continue;
							}

							if ( $i == $checks_per_row ) {
								echo '</tr><tr>';
								$i = 0; $first_row = false;
							}

							if ( ! isset( $rcaps[$cap_name] ) )
								$class = 'cap-no';
							else
								$class = ( $rcaps[$cap_name] ) ? 'cap-yes' : 'cap-neg';

							$title_text = $cap_name;

							$disabled = '';
							$checked = !empty($rcaps[$cap_name]) ? 'checked' : '';
                            $invalid_caps_capabilities[] = $cap_name;
						?>
							<td class="<?php echo esc_attr($class); ?>"><span class="ppc-tool-tip disabled cap-x">X</span><label title="<?php echo esc_attr($title_text);?>"><input type="checkbox" name="caps[<?php echo esc_attr($cap_name); ?>]" class="pp-single-action-rotate" autocomplete="off" value="1" <?php echo esc_attr($checked) . esc_attr($disabled);?> />
							<span>
							<?php
							echo esc_html(str_replace( '_', ' ', $cap ));
							?>
							</span></label><a href="#" class="neg-cap" style="visibility: hidden;">&nbsp;x&nbsp;</a>
							<?php if ( false !== strpos( $class, 'cap-neg' ) ) :?>
								<input type="hidden" class="cme-negation-input" name="caps[<?php echo esc_attr($cap_name); ?>]" value="" />
							<?php endif; ?>
							</td>
							<td>
							</td>
						<?php
							$i++;
						endforeach;

						if ( ! empty($lock_manage_caps_capability) ) {
							echo '<input type="hidden" name="caps[manage_capabilities]" value="1" />';
						}

						if ( $i == $checks_per_row ) {
							echo '</tr><tr>';
							$i = 0;
						} else {
							if ( ! $first_row ) {
								// Now close a wellformed table
								for ( $i; $i < $checks_per_row; $i++ ){
									echo '<td>&nbsp;</td>';
								}
								echo '</tr>';
							}
						}
						?>

                        <?php if (!empty($invalid_caps_capabilities)) : ?>
                            <script type="text/javascript">
                            /* <![CDATA[ */
                            jQuery(document).ready( function($) {
                                $('#cme_tab_invalid_caps').show();
                            });
                            /* ]]> */
                            </script>
                        <?php endif; ?>

					</table>
					</div>
						<?php
					} // endif any invalid caps

					$tab_id = "cme-cap-type-tables-additional";
					$div_display = ($tab_id == $active_tab_id) ? 'block' : 'none';
					?>
					<div id="<?php echo esc_attr($tab_id);?>" style="display:<?php echo esc_attr($div_display);?>">
						<?php
						// caps: additional
						echo '<h3 class="cme-cap-section">' . esc_html__( 'Additional Capabilities', 'capability-manager-enhanced' ) . '</h3>';

						echo '<div class="ppc-filter-wrapper">';
							echo '<input type="text" class="regular-text ppc-filter-text" placeholder="' . esc_attr__('Filter by capability', 'capability-manager-enhanced') . '">';
							echo ' <button class="button secondary-button ppc-filter-text-reset" type="button">' . __('Clear') . '</button>';
						echo '</div>';
						echo '<div class="ppc-filter-no-results" style="display:none;">' . esc_html__( 'No results found. Please try again with a different word.', 'capability-manager-enhanced' ) . '</div>';
						?>
						<table class="widefat fixed striped form-table cme-checklist single-checkbox-table">

						<tr class="cme-bulk-select">
                            <td colspan="<?php echo (int) $checks_per_row;?>">
                                <input type="checkbox" class="cme-check-all" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>

						<?php
						$centinel_ = true;
						$i = 0; $first_row = true;

						foreach( $all_capabilities as $cap_name ) {
							if ( ! isset($this->capabilities[$cap_name]) )
								$this->capabilities[$cap_name] = str_replace( '_', ' ', $cap_name );
						}

						uasort( $this->capabilities, 'strnatcasecmp' );  // sort by array values, but maintain keys );

						$additional_caps = apply_filters('publishpress_caps_manage_additional_caps', $this->capabilities);
						$caps_empty = true;
						foreach ($additional_caps as $cap_name => $cap) :
							$cap_name = sanitize_text_field($cap_name);

							if ((isset($type_caps[$cap_name]) && !isset($type_metacaps[$cap_name]))
							|| in_array($cap_name, $grouped_caps_lists)
							|| (isset($type_metacaps[$cap_name]) && !empty($rcaps[$cap_name])) ) {
								continue;
							}

							if (!isset($type_metacaps[$cap_name]) || !empty($rcaps[$cap_name])) {
								foreach(array_keys($plugin_caps) as $plugin_title) {
									if ( in_array( $cap_name, $plugin_caps[$plugin_title]) ) {
										continue 2;
									}
								}
							}

							if ( ! $is_administrator && empty( $current_user->allcaps[$cap_name] ) ) {
								continue;
							}

							// Levels are not shown.
							if ( preg_match( '/^level_(10|[0-9])$/i', $cap_name ) ) {
								continue;
							}

							// Output first <tr>
							if ( $centinel_ == true ) {
								echo '<tr class="' . esc_attr($cap_name) . '">';
								$centinel_ = false;
							}

							if ( $i == $checks_per_row ) {
								echo '</tr><tr class="' . esc_attr($cap_name) . '">';
								$i = 0; $first_row = false;
							}

							if ( ! isset( $rcaps[$cap_name] ) )
								$class = 'cap-no';
							else
								$class = ( $rcaps[$cap_name] ) ? 'cap-yes' : 'cap-neg';

							if ( ! empty($pp_metagroup_caps[$cap_name]) ) {
								$class .= ' cap-metagroup';
								$title_text = sprintf( esc_html__( '%s: assigned by Permission Group', 'capability-manager-enhanced' ), '<strong>' . $cap_name . '</strong>' );
							} else {
								$title_text = '';
							}

							$disabled = '';
							$checked = !empty($rcaps[$cap_name]) ? 'checked' : '';

							if ( 'manage_capabilities' == $cap_name ) {
								if (!current_user_can('administrator') && (!is_multisite() || !is_super_admin())) {
									continue;
								} elseif ( 'administrator' == $default ) {
									$class .= ' cap-locked';
									$lock_manage_caps_capability = true;
									$disabled = ' disabled ';
								}
							}

						$caps_empty = false;
						?>
							<td class="<?php echo esc_attr($class); ?>"><span class="ppc-tool-tip disabled cap-x">X</span><span class="ppc-tool-tip disabled"><label><input type="checkbox" name="caps[<?php echo esc_attr($cap_name); ?>]" class="pp-single-action-rotate" autocomplete="off" value="1" <?php echo esc_attr($checked) . ' ' . esc_attr($disabled);?> />
							<span>
							<?php
							echo esc_html(str_replace( '_', ' ', $cap ));
							?>
							</span></label><?php if ($title_text) :?><span class="tool-tip-text" style="text-align: center;">
								<p><?php echo $title_text; ?></p>
								<i></i>
							</span><?php endif;?></span><a href="#" class="neg-cap" style="visibility: hidden;">&nbsp;x&nbsp;</a>
							<?php if ( false !== strpos( $class, 'cap-neg' ) ) :?>
								<input type="hidden" class="cme-negation-input" name="caps[<?php echo esc_attr($cap_name); ?>]" value="" />
							<?php endif; ?>
							</td>
						<?php
							$i++;
						endforeach;

						if ( ! empty($lock_manage_caps_capability) ) {
							echo '<input type="hidden" name="caps[manage_capabilities]" value="1" />';
						}

						if ( $i == $checks_per_row ) {
							echo '</tr><tr>';
							$i = 0;
						} else {
							if ( ! $first_row ) {
								// Now close a wellformed table
								for ( $i; $i < $checks_per_row; $i++ ){
									echo '<td>&nbsp;</td>';
								}
								echo '</tr>';
							}
						}
						?>
						<?php if ($caps_empty) : ?>
							<tr>
								<td colspan="<?php echo (int) $checks_per_row;?>">
									<div>
										<?php esc_html_e( 'You have no additional capabilities.', 'capability-manager-enhanced' ); ?>
									</div>
								</td>
							</tr>
						<?php endif; ?>
						<tr class="cme-bulk-select">
							<td colspan="<?php echo (int) $checks_per_row;?>">
								<input type="checkbox" class="cme-check-all" autocomplete="off" title="<?php esc_attr_e('check / uncheck all', 'capability-manager-enhanced');?>"> <span><?php _e('Capability Name', 'capability-manager-enhanced');?></span>
								<span style="float:right">
								&nbsp;&nbsp;<span class="ppc-tool-tip disabled"><a class="cme-neg-all" href="#" >X</a> <?php echo $cme_negate_all_tooltip_msg; ?> </span> <span class="ppc-tool-tip disabled"><a class="cme-switch-all" href="#" >X</a> <?php echo $cme_negate_none_tooltip_msg; ?> </span>
								</span>
							</td>
						</tr>

						</table>
					</div>

					<div class="capabilities-sidebar pp-column-right">
						<?php
						do_action('publishpress-caps_sidebar_top');

						$banners = new PublishPress\WordPressBanners\BannersMain;

						$banner_messages = [];
						$banner_messages[] = esc_html__('Capabilities allows you change the permissions for any user role.', 'capability-manager-enhanced');
						$banner_messages[] = sprintf(esc_html__('%1$s = Capability granted %2$s', 'capability-manager-enhanced'), '<table class="pp-capabilities-cb-key"><tr><td class="pp-cap-icon pp-cap-icon-checked"><input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" checked disabled></td><td>', '</td></tr>');
						$banner_messages[] = sprintf(esc_html__('%1$s = Capability not granted %2$s', 'capability-manager-enhanced'), '<tr><td class="pp-cap-icon"><input type="checkbox" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'" disabled></td><td class="pp-cap-not-checked-definition">', '</td></tr>');
						$banner_messages[] = sprintf(esc_html__('%1$s = Capability denied, even if granted by another role %2$s', 'capability-manager-enhanced'), '<tr><td class="pp-cap-icon pp-cap-x"><span class="cap-x pp-cap-key" title="'. esc_attr__('usage key', 'capability-manager-enhanced') .'">X</span></td><td class="cap-x-definition">', '</td></tr></table>');
						if (defined('PRESSPERMIT_ACTIVE') && function_exists('presspermit')) {
							if ($group = presspermit()->groups()->getMetagroup('wp_role', $this->current)) {
								$additional_message = sprintf(
									// back compat with existing language string
									str_replace(
										['&lt;strong&gt;', '&lt;/strong&gt;'],
										['<strong>', '</strong>'],
										esc_html__('You can also configure this role as a %sPermission Group%s.', 'capability-manager-enhanced')
									),
									'<a href="' . esc_url_raw(admin_url("admin.php?page=presspermit-edit-permissions&action=edit&agent_id={$group->ID}")) . '">',
									'</a>'
								);
								$banner_messages[] = '<p class="cme-subtext">' . $additional_message . '</p>';
							}
						}

						?>
						<div class="ppc-sidebar-panel-metabox meta-box-sortables">
							<?php $meta_box_state = (isset($sidebar_metabox_state['how_to_user_capabilities'])) ? $sidebar_metabox_state['how_to_user_capabilities'] : 'closed';  ?>
							<div class="postbox ppc-sidebar-panel <?php echo esc_attr($meta_box_state); ?>">
								<input
									name="ppc_metabox_state[how_to_user_capabilities]"
									type="hidden"
									class="metabox-state"
									value="<?php echo esc_attr($meta_box_state); ?>"
								/>
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php esc_html_e('How to use Capabilities', 'capability-manager-enhanced'); ?></h2>
									<div class="handle-actions">
										<button type="button" class="handlediv">
											<span class="toggle-indicator"></span>
										</button>
									</div>
								</div>
								<div class="inside">
								<?php
									$banners->pp_display_banner(
										'',
										'',
										$banner_messages,
										'https://publishpress.com/knowledge-base/capabilities-screen/',
										__('View Documentation', 'capability-manager-enhanced'),
										'',
										'button ppc-checkboxes-documentation-link'
									);
									?>
								</div>
							</div>
						</div>

						<?php
						$pp_ui->pp_types_ui( $defined['type'] );
						$pp_ui->pp_taxonomies_ui( $defined['taxonomy'] );
						?>

						<div class="ppc-sidebar-panel-metabox meta-box-sortables ppc-safe">
							<?php $meta_box_state = (isset($sidebar_metabox_state['capabilities_safe_to_use'])) ? $sidebar_metabox_state['capabilities_safe_to_use'] : 'closed';  ?>
							<div class="postbox ppc-sidebar-panel <?php echo esc_attr($meta_box_state); ?>">
								<input
									name="ppc_metabox_state[capabilities_safe_to_use]"
									type="hidden"
									class="metabox-state"
									value="<?php echo esc_attr($meta_box_state); ?>"
								/>
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php esc_html_e( 'Capabilities is Safe to Use', 'capability-manager-enhanced' ); ?></h2>
									<div class="handle-actions">
										<button type="button" class="handlediv">
											<span class="toggle-indicator"></span>
										</button>
									</div>
								</div>
								<div class="inside">
								<?php
										$banners->pp_display_banner(
											'',
											'',
											array(
												__( 'WordPress stores role capabilities in your database, where they remain even if the plugin is deactivated.', 'capability-manager-enhanced' ),
												__( 'Whenever you use PublishPress Capabilities to save changes, it also creates a backup which you can use to restore a previous configuration.', 'capability-manager-enhanced' )
											),
											admin_url( 'admin.php?page=pp-capabilities-backup' ),
											__( 'Go to the Backup feature', 'capability-manager-enhanced' ),
											'',
											'button'
										);
									?>
								</div>
							</div>
						</div>

						<div class="ppc-sidebar-panel-metabox meta-box-sortables ppc-add-cap">
							<?php $meta_box_state = (isset($sidebar_metabox_state['add_capability'])) ? $sidebar_metabox_state['add_capability'] : 'closed';  ?>
							<div class="postbox ppc-sidebar-panel <?php echo esc_attr($meta_box_state); ?>">
								<input
									name="ppc_metabox_state[add_capability]"
									type="hidden"
									class="metabox-state"
									value="<?php echo esc_attr($meta_box_state); ?>"
								/>
								<div class="postbox-header">
									<h2 class="hndle ui-sortable-handle"><?php esc_html_e('Add a New Capability', 'capability-manager-enhanced'); ?></h2>
									<div class="handle-actions">
										<button type="button" class="handlediv">
											<span class="toggle-indicator"></span>
										</button>
									</div>
								</div>
								<div class="inside" style="text-align:center;">
									<p>
										<input type="text" name="capability-name" class="regular-text" placeholder="<?php echo 'capability_name';?>" /><br />
										<input type="submit" name="AddCap" value="<?php esc_attr_e('Add to role', 'capability-manager-enhanced') ?>" class="button" />
									</p>
									<br />
									<div class="cme-subtext"><?php _e('New capabilities are controlled on the Additonal tab.', 'capability-manager-enhanced');?></div>
								</div>
							</div>
						</div>

						<?php if (is_multisite() && is_super_admin() && is_main_site()) : ?>
							<div class="ppc-sidebar-panel-metabox meta-box-sortables ppc-multi-site">
								<?php $meta_box_state = (isset($sidebar_metabox_state['multi_site'])) ? $sidebar_metabox_state['multi_site'] : 'closed';  ?>
								<div class="postbox ppc-sidebar-panel <?php echo esc_attr($meta_box_state); ?>">
									<input
										name="ppc_metabox_state[multi_site]"
										type="hidden"
										class="metabox-state"
										value="<?php echo esc_attr($meta_box_state); ?>"
									/>
									<div class="postbox-header">
										<h2 class="hndle ui-sortable-handle"><?php esc_html_e('Multisite', 'capability-manager-enhanced'); ?></h2>
										<div class="handle-actions">
											<button type="button" class="handlediv">
												<span class="toggle-indicator"></span>
											</button>
										</div>
									</div>
									<div class="inside">
										<?php cme_network_role_ui( $default ); ?>
									</div>
								</div>
							</div>
						<?php endif; ?>

						<?php
							do_action('publishpress-caps_sidebar_bottom');
						?>

					</div><!-- right sidebar within tab panel -->
				</div>
			</div>


			<script type="text/javascript">
			/* <![CDATA[ */
			jQuery(document).ready( function($) {
				$('a[href="#pp-more"]').click( function() {
					$('#pp_features').show();
					return false;
				});
				$('a[href="#pp-hide"]').click( function() {
					$('#pp_features').hide();
					return false;
				});
			});
			/* ]]> */
			</script>

			<?php /* play.png icon by Pavel: http://kde-look.org/usermanager/search.php?username=InFeRnODeMoN */ ?>

			<div id="pp_features" style="display:none"><div class="pp-logo"><a href="https://publishpress.com/presspermit/"><img src="<?php echo esc_url_raw($img_url);?>pp-logo.png" alt="<?php esc_attr_e('PublishPress Permissions', 'capability-manager-enhanced');?>" /></a></div><div class="features-wrap"><ul class="pp-features">
			<li>
			<?php esc_html_e( "Automatically define type-specific capabilities for your custom post types and taxonomies", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/regulate-post-type-access" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Assign standard WP roles supplementally for a specific post type", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/regulate-post-type-access" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Assign custom WP roles supplementally for a specific post type <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			</li>

			<li>
			<?php esc_html_e( "Customize reading permissions per-category or per-post", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/category-exceptions" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Customize editing permissions per-category or per-post <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/page-editing-exceptions" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Custom Post Visibility statuses, fully implemented throughout wp-admin <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/custom-post-visibility" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Custom Moderation statuses for access-controlled, multi-step publishing workflow <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/multi-step-moderation" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Regulate permissions for Edit Flow post statuses <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/edit-flow-integration" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Customize the moderated editing of published content with Revisionary or Post Forking <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/published-content-revision" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "Grant Spectator, Participant or Moderator access to specific bbPress forums <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			</li>

			<li>
			<?php esc_html_e( "Grant supplemental content permissions to a BuddyPress group <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			<a href="https://presspermit.com/tutorial/buddypress-content-permissions" target="_blank"><img class="cme-play" alt="*" src="<?php echo esc_url_raw($img_url);?>play.png" /></a></li>

			<li>
			<?php esc_html_e( "WPML integration to mirror permissions to translations <em>(Pro)</em>", 'capability-manager-enhanced' );?>
			</li>

			<li>
			<?php esc_html_e( "Member support forum", 'capability-manager-enhanced' );?>
			</li>

			</ul></div>

			<?php
			echo '<div>';
			printf( esc_html__('%1$sgrab%2$s %3$s', 'capability-manager-enhanced'), '<strong>', '</strong>', '<span class="plugins update-message"><a href="' . esc_url_raw(cme_plugin_info_url('press-permit-core')) . '" class="thickbox" title="' . sprintf( esc_attr__('%s (free install)', 'capability-manager-enhanced'), 'Permissions Pro' ) . '">Permissions Pro</a></span>' );
			echo '&nbsp;&nbsp;&bull;&nbsp;&nbsp;';
			printf( esc_html__('%1$sbuy%2$s %3$s', 'capability-manager-enhanced'), '<strong>', '</strong>',  '<a href="https://publishpress.com/presspermit/" target="_blank" title="' . sprintf( esc_attr__('%s info/purchase', 'capability-manager-enhanced'), 'Permissions Pro' ) . '">Permissions&nbsp;Pro</a>' );
			echo '&nbsp;&nbsp;&bull;&nbsp;&nbsp;';
			echo '<a href="#pp-hide">hide</a>';
			echo '</div></div>';

			///
			?>
			<script type="text/javascript">
			/* <![CDATA[ */
			jQuery(document).ready( function($) {
				$('a[href="#toggle_type_caps"]').click( function() {
					var chks = $(this).closest('tr').find('input');
					var set_checked = ! $(chks).first().is(':checked');

					$(chks).each(function(i,e) {
						$('input[name="' + $(this).attr('name') + '"]').prop('checked', set_checked);
					});

					return false;
				});

				$('input[name^="caps["]').click(function() {
					$('input[name="' + $(this).attr('name') + '"]').prop('checked', $(this).prop('checked'));
				});
			});
			/* ]]> */
			</script>

			<div style="display:none; float:right;">
			<?php
			$level = ak_caps2level($rcaps);
			?>
			<span title="<?php esc_attr_e('Role level is mostly deprecated. However, it still determines eligibility for Post Author assignment and limits the application of user editing capabilities.', 'capability-manager-enhanced');?>">

			<?php (in_array(get_locale(), ['en_EN', 'en_US'])) ? printf('Role Level:') : esc_html_e('Level:', 'capability-manager-enhanced');?> <select name="level">
			<?php for ( $l = $this->max_level; $l >= 0; $l-- ) {?>
					<option value="<?php echo (int) $l; ?>" style="text-align:right;"<?php selected($level, $l); ?>>&nbsp;<?php echo (int) $l; ?>&nbsp;</option>
				<?php }
				?>
			</select>
			</span>

			</div>

		<p class="submit" style="padding-top:0;">
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="current" value="<?php echo esc_attr($default); ?>" />

			<?php
			$save_caption = (in_array(sanitize_key(get_locale()), ['en_EN', 'en_US'])) ? 'Save Capabilities' : esc_html__('Save Changes');
			?>
			<input type="submit" name="SaveRole" value="<?php echo esc_attr($save_caption);?>" class="button-primary" style="float: right;" /> &nbsp;
		</p>

		</div><!-- .pp-column-left -->
	</div><!-- .pp-columns-wrapper -->
	</td></tr></table> <!-- .akmin -->
	</fieldset>
	</form>

	<?php if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION') || get_option('cme_display_branding')) {
		cme_publishpressFooter();
	}
	?>
</div>

<?php
function cme_network_role_ui( $default ) {
	if (!is_multisite() || !is_super_admin() || !is_main_site()) {
		return false;
	}
	?>
		<?php
		if ( ! $autocreate_roles = get_site_option( 'cme_autocreate_roles' ) )
			$autocreate_roles = array();
		?>
		<div style="margin-bottom: 5px">
		<label for="cme_autocreate_role" title="<?php esc_attr_e('Create this role definition in new (future) sites', 'capability-manager-enhanced');?>"><input type="checkbox" name="cme_autocreate_role" id="cme_autocreate_role" autocomplete="off" value="1" <?php echo checked(in_array($default, $autocreate_roles));?>> <?php esc_html_e('Include in new sites.', 'capability-manager-enhanced'); ?> </label>
		</div>
		<div>
		<label for="cme_net_sync_role" title="<?php echo esc_attr__('Copy / update this role definition to all sites now', 'capability-manager-enhanced');?>"><input type="checkbox" name="cme_net_sync_role" id="cme_net_sync_role" autocomplete="off" value="1"> <?php esc_html_e('Sync role to all sites.', 'capability-manager-enhanced'); ?> </label>
		</div>
		<div>
		<label for="cme_net_sync_options" title="<?php echo esc_attr__('Copy option settings to all sites now', 'capability-manager-enhanced');?>"><input type="checkbox" name="cme_net_sync_options" id="cme_net_sync_options" autocomplete="off" value="1"> <?php esc_html_e('Sync options to all sites.', 'capability-manager-enhanced'); ?> </label>
		</div>
<?php
	return true;
}

function cme_plugin_info_url( $plugin_slug ) {
	$_url = "plugin-install.php?tab=plugin-information&plugin=$plugin_slug&TB_iframe=true&width=640&height=678";
	return ( is_multisite() ) ? network_admin_url($_url) : admin_url($_url);
}
