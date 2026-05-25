<?php

class PP_Capabilities_Test_User_UI extends PP_Capabilities_Test_User
{
    public function __construct()
    {
        // Admin Dashboard: Add "Test this user" as a Users row action.
        add_filter('user_row_actions', [$this, 'adminUsersRowActions'], 10, 2);
        // Adds "Test this user" to user profile edit page
        add_action('personal_options', [$this, 'adminUserEditAction']);

        // Admin Dashboard, Admin Bar and Front End Footer: Testing indicator, switch back link
        add_action('wp_enqueue_scripts', [$this, 'adminBarScripts'], 9);
        add_action('admin_enqueue_scripts', [$this, 'adminBarScripts'], 9);

        add_action('wp_footer', [$this, 'switchBackNotice']);
        add_action('all_admin_notices', [$this, 'switchBackNotice']);
        // Add Test User to admin bar
        add_action( 'wp_before_admin_bar_render', [$this, 'adminBarSearch'], 1);
        // Test User search ajax handler
        add_action('wp_ajax_ppc_search_test_user_by_ajax', [$this, 'searchTestUsers']);
    }

    /**
     * Add Test User to admin bar
     *
     * @return void
     */
    public function adminBarSearch() {
        global $wp_admin_bar;
        if (!current_user_can('manage_capabilities_user_testing') || !is_admin_bar_showing() || PP_Capabilities_Test_User::testerAuth() || empty(get_option('cme_test_user_admin_bar_search', 1))) {
            return;
        }

        $wp_admin_bar->add_menu(
            array(
                'id'    => 'pp_capabilities_test_user',
                'title' => esc_html__('User Testing', 'capability-manager-enhanced'),
                'href'  => '#',
            )
        );

        $wp_admin_bar->add_menu(
            array(
                'id'     => 'pp_capabilities_test_user_form',
                'parent' => 'pp_capabilities_test_user',
                'title'  => '
                <div class="ppc-test-user-admin-bar-form">
                <input
                    class="search-test-user"
                        type="text"
                        placeholder="' . __('Search user...', 'capability-manager-enhanced' ) . '"/>
                <button class="test-user-btn button"><span class="search-text">' . __( 'Search', 'capability-manager-enhanced' ) . '</span> <span class="spinner ppc-test-user-search-spinner" style="display: none;"></span></button>

                <div class="ppc-test-user-search-response"></div>
            </div>',
            )
        );

    }

    /**
     * Test User search ajax handler
     *
     * @return void
     */
    public function searchTestUsers() {

        $response['status']  = 'error';
        $response['message'] = __('No results found.', 'capability-manager-enhanced');
        $response['content'] = '';

        $security       = isset($_POST['security']) ? sanitize_key($_POST['security']) : false;
        $search_text    = isset($_POST['search_text']) ? sanitize_key($_POST['search_text']) : '';
        if (!$security
            || !wp_verify_nonce($security, 'ppc-test-user-admin-bar-action')
            || !current_user_can('manage_capabilities_user_testing')
        ) {
            $response['message'] = __('Permission denied.', 'capability-manager-enhanced');
        } else {
            $response['status']  = 'success';
            $excluded_roles = (array) get_option('cme_test_user_excluded_roles', []);

            $roles = wp_roles()->roles;
            $editable = function_exists('get_editable_roles') ?
                            array_keys(get_editable_roles()) :
                            array_keys(apply_filters('editable_roles', $roles));

            $included_roles = [];
            foreach ($editable as $role_name) {
                if (!in_array($role_name, $excluded_roles)) {
                    $included_roles[] = $role_name;
                }
            }

            if (!empty($included_roles)) {
                $user_args = ['number' => 10];

                if (!empty(trim($search_text))) {
                    $user_args['search'] = '*' . trim($search_text) . '*';
                }

                if (!empty(array_filter($included_roles))) {
                    $user_args['role__in'] = $included_roles;

                    $user_query = new WP_User_Query($user_args);

                    if ( ! empty( $user_query->results ) ) {
                        $role_users = [];
                        $user_lists = [];
                        foreach ( $user_query->results as $user ) {
                            if (PP_Capabilities_Test_User::canTestUser($user)) {
                                foreach ($user->roles as $role) {
                                    if (!in_array($user, $user_lists)) {
                                        if (isset($role_users[$role])) {
                                            $role_users[$role] = array_merge($role_users[$role], [$user]);
                                            $user_lists[] = $user;
                                        } else {
                                            $role_users[$role] = [$user];
                                            $user_lists[] = $user;
                                        }
                                    }
                                }
                            }
                        }

                        if (!empty($role_users)) {
                            $response_content = '';
                            foreach ($role_users as $role_slug => $user_lists) {
                                $role_name = !empty($roles[$role_slug]['name']) ? translate_user_role($roles[$role_slug]['name']) : $role_slug;
                                $response_content .= '<h2>'. $role_name .'</h2>';
                                foreach ($user_lists as $user) {
                                    $link = add_query_arg(
                                        [
                                            'ppc_test_user' => base64_encode($user->ID),
                                            '_wpnonce'      => wp_create_nonce('ppc-test-user')
                                        ],
                                        admin_url('users.php')
                                    );
                                    $response_content .= '
                                        <p class="result">
                                            <a href="' . esc_url($link) . '">' . esc_html($user->display_name) . '</a>
                                        </p>
                                    ';
                                }
                            }
                            $response['content'] = $response_content;
                        }
                    }
                }
            }
        }

        wp_send_json($response);
    }

    /**
     * Adds "Test this user" to Users screen row actions.
     *
     * @param $actions
     * @param $user
     *
     * @return $action
     */
    public function adminUsersRowActions($actions, $user)
    {
        if (PP_Capabilities_Test_User::canTestUser($user)) {

            $link = add_query_arg(
                [
                    'ppc_test_user' => base64_encode($user->ID),
                    '_wpnonce'      => wp_create_nonce('ppc-test-user')
                ],
                admin_url('users.php')
            );

            $actions['ppc_test_user'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($link),
                esc_html__('Test user', 'capability-manager-enhanced')
            );
        }

        return $actions;
    }

    /**
     * Adds "Test this user" to user profile edit page
     *
     * @return void
     */
    public function adminUserEditAction($user)
    {
        if (current_user_can('manage_capabilities_user_testing') && current_user_can('edit_user', $user->ID) && $user->ID !== get_current_user_id()) {
            $link = add_query_arg(
                [
                    'ppc_test_user' => base64_encode($user->ID),
                    '_wpnonce'      => wp_create_nonce('ppc-test-user')
                ],
                admin_url('users.php')
            );
            ?>
            <tr class="user-test-user-wrap">
                <th scope="row"><?php esc_html_e('Test user', 'capability-manager-enhanced'); ?></th>
                <td>
                    <?php
                    printf(
                        '<a href="%s" class="button">%s</a>',
                        esc_url($link),
                        esc_html__('Test this user', 'capability-manager-enhanced')
                    );
                    ?>
                </td>
            </tr>
            <?php
        }
    }

	function adminBarScripts() {
        if (did_action('admin_bar_init') && get_option('cme_test_user_admin_bar', 1)) {
            wp_enqueue_script('jquery');

            $action_name = (is_admin()) ? 'admin_footer' : 'wp_footer';
            add_action($action_name, [$this, 'modifyAdminBar']);
        }
    }

    public function modifyAdminBar()
    {
        if (!PP_Capabilities_Test_User::testerAuth()) {
            return;
        }
        ?>
            <style>
            .ppc-testing-user.top-notice {
                color: #E6A341;
                font-weight: bold !important;
            }

            #wp-admin-bar-user-actions a.pp-capabilities-return {
                color: #E6A341;
                text-align: center;
            }
            </style>

            <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready( function($) {
                if ($('#wp-admin-bar-user-actions a.pp-capabilities-return').length) {
                    return;
                }

                var switchBackDiv = '<span class="ppc-testing-user top-notice">'
                + '<?php echo wp_kses(
                        esc_html__('Testing: ', 'capability-manager-enhanced'),
                        [
                            'a' => ['href' => []],
                        ]
                    );
                    ?>'
                + '</span>';

                var sel = $('#wp-admin-bar-my-account a.ab-item:visible:has(span.display-name)');

                $(sel).html(switchBackDiv + $(sel).find('span.display-name').prop('outerHTML'));

                <?php
                $message = sprintf(
                    esc_html__('%1$sReturn to Administrator view%2$s', 'capability-manager-enhanced'),
                    sprintf('<a class="pp-capabilities-return" href="%s">', esc_url($this->switchBackLink())),
                    '</a>'
                );
                ?>

                $('#wp-admin-bar-logout').after('<?php echo $message;?>');
            });
            /* ]]> */
            </script>
        <?php
    }

    private function switchBackLink() {
        $user = wp_get_current_user();

        return add_query_arg(
            [
                'ppc_test_user'   => base64_encode($user->ID),
                'ppc_return_back' => 1,
                '_wpnonce'        => wp_create_nonce('ppc-test-user')
            ],
            home_url()
        );
    }

    /**
     * Add return message notice link
     */
    public function switchBackNotice()
    {
        if (!PP_Capabilities_Test_User::testerAuth()) {
            return;
        }

        if (!is_admin() && !get_option('cme_test_user_footer_notice', 1)) {
            return;
        }

        $user = wp_get_current_user();
        ?>

        <div class="ppc-testing-user notice published">
            <p>
                <span class="dashicons dashicons-admin-users" style="color:#E6A341"></span>
                <?php
                $message = sprintf(
                    esc_html__('Testing as user: %1$s. %2$sReturn to Administrator view%3$s', 'capability-manager-enhanced'),
                    $user->display_name,
                    sprintf('<a href="%s">', esc_url($this->switchBackLink())),
                    '</a>'
                );
                echo wp_kses(
                    $message, [
                        'a' => [
                            'href' => [],
                        ],
                    ]
                );
                ?>
            </p>
        </div>

        <?php if (!is_admin()) : ?>
            <style>
                .ppc-testing-user.notice {
                    position: relative !important;
                    background: #fff !important;
                    border: 1px solid #c3c4c7 !important;
                    border-left-width: 4px !important;
                    border-left-color: #E6A341 !important;
                    box-shadow: 0 1px 1px rgb(0 0 0 / 4%) !important;
                    padding-right: 38px !important;
                    padding: 1px 12px !important;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif !important;
                    color: #3c434a !important;
                    font-size: 13px !important;
                    line-height: 1.4em !important;
                    max-width: 640px !important;
                    margin: 0px auto !important;
                    margin-bottom: 20px !important;
                    margin-top: 10px !important;
                    width: 100% !important;
                    z-index: 99999;
                }
                .ppc-testing-user.notice .dashicons {
                    color: #E6A341 !important;
                }
                .ppc-testing-user.notice p {
                    font-size: 13px !important;
                    line-height: 1.5 !important;
                    margin: 0.5em 0 !important;
                    padding: 2px !important;
                }
                .ppc-testing-user.notice a {
                    padding-bottom: 2px !important;
                }
                .ppc-testing-user.notice a,
                .ppc-testing-user.notice a:hover,
                .ppc-testing-user.notice a:active
                .ppc-testing-user.notice a:visited {
                    color: #2271b1 !important;
                    text-decoration: underline !important;
                }
            </style>
        <?php else:?>
            <style>
                .ppc-testing-user.notice {
                    position: relative !important;
                    background: #fff !important;
                    border: 1px solid #c3c4c7 !important;
                    border-left-width: 4px !important;
                    border-left-color: #E6A341 !important;
                }
            </style>
        <?php endif;
    }
}
