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

//require_once(dirname(CME_FILE) . '/includes/features/restrict-profile-features.php');

global $capsman, $role_has_user;

$roles             = $capsman->roles;
$default_role      = $capsman->get_last_role();

$role_redirects = !empty(get_option('capsman_role_redirects')) ? (array)get_option('capsman_role_redirects') : [];
$current_role_redirects = array_key_exists($default_role, $role_redirects) ? (array)$role_redirects[$default_role] : [];

$default_tab = (!empty($_REQUEST['pp_caps_tab'])) ? sanitize_key($_REQUEST['pp_caps_tab']) : 'login_redirect';

$fields_tabs = [
    'login_redirect' => [
        'label' => esc_html__('Login Redirect', 'capability-manager-enhanced'),
        'icon'  => 'dashicons dashicons-image-rotate-right',
    ],
    'logout_redirect' => [
        'label' => esc_html__('Logout Redirect', 'capability-manager-enhanced'),
        'icon'  => 'dashicons dashicons-image-rotate-left',
    ],
    'registration_redirect' => [
        'label' => esc_html__('Registration Redirect', 'capability-manager-enhanced'),
        'icon'  => 'dashicons dashicons-database-add',
    ],
    'first_login_redirect' => [
        'label' => esc_html__('First Login Redirect', 'capability-manager-enhanced'),
        'icon'  => 'dashicons dashicons-arrow-right-alt',
    ],
];

$fields = [
    'login_redirect' => [
        'label'       => esc_html__('Login Redirect', 'capability-manager-enhanced'),
        'description' => esc_html__('Enter the URL users in this role should be redirected to after login.', 'capability-manager-enhanced'),
        'type'        => 'url',
        'value_key'   => 'login_redirect',
        'tab'         => 'login_redirect',
        'editable'    => true,
        'required'    => false,
    ],
    'logout_redirect' => [
        'label'       => esc_html__('Logout Redirect', 'capability-manager-enhanced'),
        'description' => esc_html__('Enter the URL users in this role should be redirected to after logout.', 'capability-manager-enhanced'),
        'type'        => 'url',
        'value_key'   => 'logout_redirect',
        'tab'         => 'logout_redirect',
        'editable'    => true,
        'required'    => false,
    ],
    'registration_redirect' => [
        'label'       => esc_html__('Registration Redirect', 'capability-manager-enhanced'),
        'description' => esc_html__('Enter the URL users in this role should be redirected to after registration.', 'capability-manager-enhanced'),
        'type'        => 'url',
        'value_key'   => 'registration_redirect',
        'tab'         => 'registration_redirect',
        'editable'    => true,
        'required'    => false,
    ],
    'first_login_redirect' => [
        'label'       => esc_html__('First Login Redirect', 'capability-manager-enhanced'),
        'description' => esc_html__('Enter the URL users in this role should be redirected to after their first login.', 'capability-manager-enhanced'),
        'type'        => 'url',
        'value_key'   => 'first_login_redirect',
        'tab'         => 'first_login_redirect',
        'editable'    => true,
        'required'    => false,
    ],
];
?>

    <div class="wrap publishpress-caps-manage pressshack-admin-wrapper pp-capability-menus-wrapper redirects-features">
        <div id="icon-capsman-admin" class="icon32"></div>
        <h2><?php printf(esc_html__('%s Redirects', 'capability-manager-enhanced'), translate_user_role($roles[$default_role])); ?></h2>

        <form method="post" action="" id="ppc-redirects-features-form" onkeydown="return event.key != 'Enter';">
            <?php wp_nonce_field('pp-capabilities-redirects-features'); ?>
            <input type="hidden" name="pp_caps_tab" value="<?php echo esc_attr($default_tab); ?>" />
            <div class="pp-columns-wrapper pp-enable-sidebar clear">
                <div class="pp-column-left">
                    <fieldset>
                        <table id="akmin">
                            <tr>
                                <td class="content">
                                    <div>

                                        <p>
                                        <div class="publishpress-filters" style="margin-bottom:5px;">
                                            <div class="pp-capabilities-submit-top" style="float:right;">
                                                <input type="submit" name="redirects-features-submit"
                                                    value="<?php esc_attr_e('Save Changes');?>"
                                                    class="button-primary ppc-redirects-features-submit" />
                                            </div>

                                            <select name="ppc-redirects-features-role" class="ppc-redirects-features-role">
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

                                                     <div class="ppc-redirects-section postbox">
                                                        <div class="inside">
                                                            <div class="main">

                                                                <ul class="ppc-redirects-tab">
                                                                    <?php
                                                                    foreach ($fields_tabs as $key => $args) {
                                                                        $active_tab = ($key === $default_tab) ? ' active' : '';
                                                                        ?>
                                                                        <li class="<?php echo esc_attr($active_tab); ?>"
                                                                            data-tab="<?php echo esc_attr($key); ?>"
                                                                            >
                                                                            <a href="#">
                                                                                <span class="<?php echo esc_attr($args['icon']); ?>"></span>
                                                                                <span><?php echo esc_html($args['label']); ?></span>
                                                                            </a>
                                                                        </li>
                                                                        <?php
                                                                    }
                                                                    ?>
                                                                </ul>

                                                                <div class="ppc-redirects-tab-content">
                                                                    <table class="form-table">
                                                                        <?php
                                                                        foreach ($fields as $key => $args) {
                                                                            $args['key']   = $key;
                                                                            $args['value'] = (is_array($current_role_redirects) && isset($current_role_redirects[$args['value_key']])) ? $current_role_redirects[$args['value_key']] : '';

                                                                            $tab_class = 'pp-redirects-tab-tr pp-redirects-' . $args['tab'] . '-tab';
                                                                            $tab_style = ($args['tab'] === $default_tab) ? '' : 'display:none;';
                                                                            ?>
                                                                            <tr valign="top"
                                                                                class="<?php echo esc_attr('form-field role-' . $key . '-wrap '. $tab_class); ?>"
                                                                                data-tab="<?php echo esc_attr($args['tab']); ?>"
                                                                                style="<?php echo esc_attr($tab_style); ?>"
                                                                                >
                                                                                <th scope="row">
                                                                                    <?php if (!empty($args['label'])) : ?>
                                                                                        <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($args['label']); ?></label>
                                                                                        <?php if ($args['required']) { ?>
                                                                                            <span class="required">*</span>
                                                                                        <?php } ?>
                                                                                    <?php endif; ?>
                                                                                </th>
                                                                                <td>
                                                                                <?php
                                                                                    if ($args['key'] === 'login_redirect') :
                                                                                        $referer_redirect = (is_array($current_role_redirects) && isset($current_role_redirects['referer_redirect']) && (int)$current_role_redirects['referer_redirect'] > 0) ? true : false;
                                                                                        $custom_redirect = (is_array($current_role_redirects) && isset($current_role_redirects['custom_redirect']) && (int)$current_role_redirects['custom_redirect'] > 0) ? true : false;
                                                                                        $custom_style    = (!$custom_redirect) ? 'display:none;' : '';

                                                                                        $form_url = $args['value'];
                                                                                        $base_url = '';
                                                                                        if (!empty($form_url)) {
                                                                                            $base_url = str_replace(home_url(), '', $form_url);
                                                                                        }
                                                                                    ?>
                                                                                    <div class="login-redirect-option">
                                                                                        <label>
                                                                                            <input name="referer_redirect"
                                                                                            id="referer_redirect"
                                                                                            type="checkbox"
                                                                                            value="1"
                                                                                            <?php checked(true, $referer_redirect); ?>
                                                                                            <?php echo ($args['required'] ? 'required="true"' : '');?>
                                                                                            <?php echo (!$args['editable'] ? 'readonly="readonly"' : ''); ?>/>
                                                                                            <span class="description"><?php echo esc_html__('Redirect users to the URL they were viewing before login.',  'capability-manager-enhanced'); ?></span>
                                                                                        </label>
                                                                                    </div>
                                                                                    <div class="login-redirect-option">
                                                                                        <label>
                                                                                            <input name="custom_redirect"
                                                                                            id="custom_redirect"
                                                                                            type="checkbox"
                                                                                            value="1"
                                                                                            <?php checked(true, $custom_redirect); ?>
                                                                                            <?php echo ($args['required'] ? 'required="true"' : '');?>
                                                                                            <?php echo (!$args['editable'] ? 'readonly="readonly"' : ''); ?>/>
                                                                                            <span class="description"><?php echo esc_html__('Redirect users to a specified URL.',  'capability-manager-enhanced'); ?></span>
                                                                                        </label>
                                                                                        <div class="custom-url-wrapper" style="<?php echo esc_attr($custom_style); ?>">
                                                                                            <div class="pp-redirects-internal-links-wrapper activated">
                                                                                                <div class="base-url">
                                                                                                    <?php esc_html_e(home_url()); ?>
                                                                                                </div>
                                                                                                <div class="base-input">
                                                                                                    <input name="<?php echo esc_attr($key); ?>"
                                                                                                    id="<?php echo esc_attr($key); ?>"
                                                                                                    type="text"
                                                                                                    value="<?php echo esc_attr($base_url); ?>"
                                                                                                    data-original_base="<?php echo esc_attr($base_url); ?>"
                                                                                                    data-base="<?php echo esc_attr($base_url); ?>"
                                                                                                    data-entry="<?php echo esc_attr($form_url); ?>"
                                                                                                    data-home_url="<?php echo esc_url(home_url()); ?>"
                                                                                                    data-message="<?php esc_attr_e('Enter the relative path only without domain for login redirect.',  'capability-manager-enhanced'); ?>"
                                                                                                    data-required_message="<?php esc_attr_e('You must enter the Login Redirect URL.',  'capability-manager-enhanced'); ?>"
                                                                                                    autocomplete="off"
                                                                                                <?php echo ($args['required'] ? 'required="true"' : '');?>
                                                                                                <?php echo (!$args['editable'] ? 'readonly="readonly"' : ''); ?>/>
                                                                                                </div>
                                                                                            </div>
                                                                                            <?php if (isset($args['description'])) : ?>
                                                                                                <p class="description"><?php echo esc_html($args['description']); ?></p>
                                                                                            <?php endif; ?>
                                                                                        </div>
                                                                                    </div>
                                                                                <?php  elseif ($args['type'] == 'url') : ?>
                                                                                    <?php
                                                                                        $form_url = $args['value'];
                                                                                        $base_url = '';
                                                                                        if (!empty($form_url)) {
                                                                                            $base_url = str_replace(home_url(), '', $form_url);
                                                                                        }
                                                                                    ?>
                                                                                    <div class="pp-redirects-internal-links-wrapper activated">
                                                                                        <div class="base-url">
                                                                                            <?php esc_html_e(home_url()); ?>
                                                                                        </div>
                                                                                        <div class="base-input">
                                                                                            <input name="<?php echo esc_attr($key); ?>"
                                                                                            id="<?php echo esc_attr($key); ?>"
                                                                                            type="text"
                                                                                            value="<?php echo esc_attr($base_url); ?>"
                                                                                            data-original_base="<?php echo esc_attr($base_url); ?>"
                                                                                            data-base="<?php echo esc_attr($base_url); ?>"
                                                                                            data-entry="<?php echo esc_attr($form_url); ?>"
                                                                                            data-home_url="<?php echo esc_url(home_url()); ?>"
                                                                                            data-message="<?php esc_attr_e('Enter the relative path only without domain for logout redirect.',  'capability-manager-enhanced'); ?>"
                                                                                            autocomplete="off"
                                                                                        <?php echo ($args['required'] ? 'required="true"' : '');?>
                                                                                        <?php echo (!$args['editable'] ? 'readonly="readonly"' : ''); ?>/>
                                                                                        </div>
                                                                                    </div>
                                                                                            <?php if (isset($args['description'])) : ?>
                                                                                                <p class="description"><?php echo esc_html($args['description']); ?></p>
                                                                                            <?php endif; ?>
                                                                                    </div>
                                                                                <?php else : ?>
                                                                                    <input name="<?php echo esc_attr($key); ?>"
                                                                                        id="<?php echo esc_attr($key); ?>"
                                                                                        type="<?php echo esc_attr($args['type']); ?>"
                                                                                        value="<?php echo esc_attr($args['value']); ?>"
                                                                                    <?php echo ($args['required'] ? 'required="true"' : '');?>
                                                                                    <?php echo (!$args['editable'] ? 'readonly="readonly"' : ''); ?>/>
                                                                                        <?php if (isset($args['description'])) : ?>
                                                                                            <p class="description"><?php echo esc_html($args['description']); ?></p>
                                                                                        <?php endif; ?>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <?php
                                                                        }
                                                                        ?>
                                                                    </table>
                                                                </div>
                                                                <div class="clear"></div>

                                                            </div>
                                                        </div>
                                                    </div>
		                                        </div>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="pp-capabilities-submit-bottom" style="float:right;">
                                        <input type="submit" name="redirects-features-submit"
                                            value="<?php esc_attr_e('Save Changes');?>"
                                            class="button-primary ppc-redirects-features-submit"/>
                                    </div>
                                    <div class="clear"></div>
                                </td>
                            </tr>
                        </table>

                    </fieldset>
                </div><!-- .pp-column-left -->
                <div class="pp-column-right pp-capabilities-sidebar">
                <?php
                $banner_messages = ['<p>'];
                $banner_messages[] = esc_html__('Redirect Features allows you to redirect users in a role after Registration, Login or Logout.', 'capability-manager-enhanced');
                $banner_messages[] = '</p>';
                $banner_messages[] = '<p><a class="button ppc-checkboxes-documentation-link" href="https://publishpress.com/knowledge-base/redirects/"target="blank">' . esc_html__('View Documentation', 'capability-manager-enhanced') . '</a></p>';
                $banner_title  = __('How to use Redirect Features', 'capability-manager-enhanced');
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


                $(document).on('click', '.ppc-redirects-tab li', function (event) {
                    event.preventDefault();

                    var clicked_tab = $(this).attr('data-tab');

                    $("[name='pp_caps_tab']").val(clicked_tab);
                });

                // -------------------------------------------------------------
                //   Set form action attribute to include role
                // -------------------------------------------------------------
                $('#ppc-redirects-features-form').attr('action', '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-redirects&role=' . $default_role . '')); ?>')

                // -------------------------------------------------------------
                //   Load selected roles
                // -------------------------------------------------------------
                $(document).on('change', '.pp-capability-menus-wrapper .ppc-redirects-features-role', function() {

                    //disable select
                    $('.pp-capability-menus-wrapper .ppc-redirects-features-role').attr('disabled', true)

                    //hide button
                    $('.pp-capability-menus-wrapper .ppc-redirects-features-submit').hide()

                    //show loading
                    $('#pp-capability-menu-wrapper').hide()
                    $('div.publishpress-caps-manage img.loading').show()

                    //go to url
                    window.location = '<?php echo esc_url_raw(admin_url('admin.php?page=pp-capabilities-redirects&role=')); ?>' + $(this).val() + ''
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
