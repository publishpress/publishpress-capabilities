<?php
if (!class_exists('PP_Capabilities_Admin_Notices')) {
    class PP_Capabilities_Admin_Notices
    {
        /**
         * @var
         */
        private $admin_notice_data;

        public function __construct()
        {
            $this->admin_notice_data = (array) get_option('cme_admin_notice_data', []);

            // Add admin notices script and styles
            add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
            // Wrap admin menus in custom opening div
            $hooks = ['admin_notices', 'user_admin_notices', 'network_admin_notices'];
            foreach ($hooks as $hook) {
                add_action($hook, [$this, 'start_hook_capture'], PHP_INT_MIN);
                add_action($hook, [$this, 'end_hook_capture'], PHP_INT_MAX - 1);
            }
            // Add admin notices to admin toolbar
            add_action('admin_bar_menu', [$this, 'add_toolbar_item'], 998);
            // Render our toolbar panel in the footer
            add_action('admin_footer', [$this, 'render_panel']);
            // Admin notices whitelist, blacklist and undo ajax callback
            add_action('wp_ajax_ppc_admin_notice_action', [$this, 'adminNoticeAjaxHandler']);
        }

        /**
         * Initialize test user component
         */
        public static function init()
        {
            $instance = new self;
            return $instance;
        }

        /**
         * Add admin notices script and styles
         *
         * @return void
         */
        public function admin_scripts() {

            //enqueue styles
            wp_enqueue_style(
                'ppc-admin-notice-css',
                plugin_dir_url(CME_FILE) . 'includes/admin-notices/assets/css/admin-notices.css',
                [],
                PUBLISHPRESS_CAPS_VERSION,
                'all'
            );

            //enqueue scripts
            wp_enqueue_script(
                'ppc-admin-notice-js',
                plugin_dir_url(CME_FILE) . 'includes/admin-notices/assets/js/admin-notices.js',
                ['jquery'],
                PUBLISHPRESS_CAPS_VERSION,
                false
            );

            // localize scripts
            wp_localize_script(
                'ppc-admin-notice-js',
                'ppcAdminNoticesData',
                [
                    'nonce' => wp_create_nonce('ppc-admin-notices-action'),
                    'admin_notice_options' => $this->admin_notice_options(),
                    'admin_notice_data' => $this->admin_notice_data,
                    'whitelist_label' => esc_html__('Display this notice', 'capability-manager-enhanced'),
                    'blacklist_label' => esc_html__('Silence this notice', 'capability-manager-enhanced'),
                    'remove_whitelist_label' => esc_html__('Hide this notice', 'capability-manager-enhanced'),
                    'remove_blacklist_label' => esc_html__('Move to Hidden Notices', 'capability-manager-enhanced'),
                    'whitelist_note' => esc_html__('Displayed notices will no longer be removed from admin pages.', 'capability-manager-enhanced'),
                    'blacklist_note' => esc_html__('This notice will be moved to the "Silenced Notices" tab and you will not receive a notification if it appears again.', 'capability-manager-enhanced'),
                ]
            );

        }

        /**
         * Wrap admin menus in custom opening div
         * @return void
         */
        public function start_hook_capture() {
            // Wrap the whole admin notice inside our hidden div
            echo '<div class="ppc-admin-notices-selector" style="display: none;">';
        }

        /**
         * Close admin menu custom opening div
         * @return void
         */
        public function end_hook_capture() {
            // close the opened notice hidden selector
            echo '</div>';
        }

        /**
         * Get admin notice option for current role
         *
         * @return array
         */
        private function admin_notice_options() {
            global $admin_notice_options;

            if (!is_array($admin_notice_options)) {
                $cme_admin_notice_options = (array) get_option('cme_admin_notice_options');

                $admin_notice_options = [
                    'enable_toolbar_access' => 0,
                    'notice_type_remove' => [],
                    'notice_type_display' => []
                ];

                if (is_user_logged_in()) {
                    $user = wp_get_current_user();
                    foreach ((array) $user->roles as $user_role) {
                        if (isset($cme_admin_notice_options[$user_role])) {
                            $data = $cme_admin_notice_options[$user_role];
                            // Merge enable_toolbar_access, take the first non-empty value
                            if (empty($admin_notice_options["enable_toolbar_access"]) && !empty($data["enable_toolbar_access"])) {
                                $admin_notice_options["enable_toolbar_access"] = $data["enable_toolbar_access"];
                            }
                            // Merge notice_type_remove
                            if (!empty($data["notice_type_remove"])) {
                                $admin_notice_options["notice_type_remove"] = array_unique(
                                    array_merge($admin_notice_options["notice_type_remove"], $data["notice_type_remove"])
                                );
                            }
                            // Merge notice_type_display
                            if (!empty($data["notice_type_display"])) {
                                $admin_notice_options["notice_type_display"] = array_unique(
                                    array_merge($admin_notice_options["notice_type_display"], $data["notice_type_display"])
                                );
                            }
                        }
                    }
                }
            }

            return $admin_notice_options;
        }


        /**
         * Check if current user can manage notice
         * @return bool
         */
        private function canSeeAdminToolbar() {
            $admin_notice_options = $this->admin_notice_options();

            $can_see_admin_toobal = false;

            if (!empty($admin_notice_options['enable_toolbar_access']) && !empty($admin_notice_options['notice_type_remove']) && !empty($admin_notice_options['notice_type_display'])) {
                $can_see_admin_toobal = true;
            }

            return $can_see_admin_toobal;
        }

        /**
         * Add admin notices to admin toolbar
         *
         * @param \WP_Admin_Bar $wp_admin_bar WordPress admin bar.
         *
         * @return void
         */
        public function add_toolbar_item($wp_admin_bar) {

            if (!$this->canSeeAdminToolbar() || !is_admin_bar_showing()) {
                return;
            }

            $args = [
                'id'     => 'ppc-admin-notices-panel',
                'title'  => '<span class="ab-label">' . esc_html__('Admin Notices', 'capability-manager-enhanced') . ' <span class="ppc-admin-notices-count"></span></span>',
                'href'   => '#',
                'parent' => 'top-secondary',
                'meta'   => [
                    'class' => 'ppc-admin-notices-toolbar-item',
                ],
            ];
            $wp_admin_bar->add_node($args);
        }

        /**
         * Render our toolbar panel in the footer
         * @return void
         */
        public function render_panel() {
            if (!$this->canSeeAdminToolbar())
            {
                return;
            }
            ?>
            <div id="ppc-admin-notices-panel">
                <div class="admin-notices-tab" style="display: none;">
                    <div class="admin-notices-button-group" data-hide-selector=".ppc-panel-notice-item">
                        <label class="active-notices selected" style="display: none;">
                            <input type="radio" name="admin_notices_tab" value=".active-notices-item" checked>
                            <?php esc_html_e('Hidden Notices', 'capability-manager-enhanced'); ?><span class="tab-notice-count"></span>
                        </label>
                        <label class="blacklisted-notices" style="display: none;">
                            <input type="radio" name="admin_notices_tab" value=".blacklisted-notices-item">
                            <?php esc_html_e('Silenced Notices', 'capability-manager-enhanced'); ?><span class="tab-notice-count"></span>
                        </label>
                    </div>
                </div>
                <div class="ppc-admin-notices-panel-none empty-notices-message" style="display: none;">
                    <svg width="170" height="170" viewBox="0 0 170 170" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="85" cy="85" r="85" fill="#a8a8a8"></circle>
                        <path d="M97.6667 78.6665H72.3333C70.5917 78.6665 69.1667 80.0915 69.1667 81.8332C69.1667 83.5748 70.5917 84.9998 72.3333 84.9998H97.6667C99.4083 84.9998 100.833 83.5748 100.833 81.8332C100.833 80.0915 99.4083 78.6665 97.6667 78.6665ZM107.167 56.4998H104V53.3332C104 51.5915 102.575 50.1665 100.833 50.1665C99.0917 50.1665 97.6667 51.5915 97.6667 53.3332V56.4998H72.3333V53.3332C72.3333 51.5915 70.9083 50.1665 69.1667 50.1665C67.425 50.1665 66 51.5915 66 53.3332V56.4998H62.8333C61.1536 56.4998 59.5427 57.1671 58.355 58.3548C57.1673 59.5426 56.5 61.1535 56.5 62.8332V107.167C56.5 108.846 57.1673 110.457 58.355 111.645C59.5427 112.833 61.1536 113.5 62.8333 113.5H107.167C110.65 113.5 113.5 110.65 113.5 107.167V62.8332C113.5 59.3498 110.65 56.4998 107.167 56.4998ZM104 107.167H66C64.2583 107.167 62.8333 105.742 62.8333 104V72.3332H107.167V104C107.167 105.742 105.742 107.167 104 107.167ZM88.1667 91.3332H72.3333C70.5917 91.3332 69.1667 92.7582 69.1667 94.4998C69.1667 96.2415 70.5917 97.6665 72.3333 97.6665H88.1667C89.9083 97.6665 91.3333 96.2415 91.3333 94.4998C91.3333 92.7582 89.9083 91.3332 88.1667 91.3332Z" fill="#8E8E8E"></path>
                    </svg>
                    <h4><?php esc_html_e('Admin Notices', 'capability-manager-enhanced'); ?></h4>
                    <p><?php esc_html_e('There are currently no admin notices.', 'capability-manager-enhanced'); ?> <a target="_blank" href="<?php echo esc_url(admin_url('admin.php?page=pp-capabilities-admin-notices')); ?>"><?php esc_html_e('Edit the settings.', 'capability-manager-enhanced'); ?></a></p>
                </div>
                <div class="ppc-admin-notices-panel-content"><?php esc_html_e('Admin Notices', 'capability-manager-enhanced'); ?></div>
            </div>
            <?php
        }

        /**
         * Admin notices whitelist, blacklist and undo ajax callback
         *
         */
        public function adminNoticeAjaxHandler()
        {
            $response['status']  = 'error';
            $response['message'] = esc_html__('An error occured!', 'capability-manager-enhanced');
            $response['content'] = '';

            $nonce   = isset($_POST['nonce']) ? sanitize_key($_POST['nonce']) : '';
            $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
            $action_option = isset($_POST['action_option']) ? sanitize_text_field($_POST['action_option']) : '';
            $notice_id = isset($_POST['notice_id']) ? sanitize_text_field($_POST['notice_id']) : '';

            if (!$this->canSeeAdminToolbar()) {
                $response['message'] = esc_html__('You do not have permission to manage admin notices.', 'capability-manager-enhanced');
            } elseif (!wp_verify_nonce($nonce, 'ppc-admin-notices-action')) {
                $response['message'] = esc_html__('Invalid action. Reload this page and try again.', 'capability-manager-enhanced');
            } elseif (empty($action_type) || empty($action_option) && empty($notice_id)) {
                $response['message'] = esc_html__('Invalid form.', 'capability-manager-enhanced');
            } else {

                $admin_notice_data = (array) get_option('cme_admin_notice_data', []);

                // remove current notice from both whitelist and blacklist if present
                if (!empty($admin_notice_data['whitelist_notices'])) {
                    $admin_notice_data['whitelist_notices'] = array_values(array_diff($admin_notice_data['whitelist_notices'], [$notice_id]));
                }
                if (!empty($admin_notice_data['blacklist_notices'])) {
                    $admin_notice_data['blacklist_notices'] = array_values(array_diff($admin_notice_data['blacklist_notices'], [$notice_id]));
                }

                // add notice to whitelist/blacklist if action is default and not undo action
                if ($action_option == 'default') {
                    if ($action_type == 'whitelist') {
                        $admin_notice_data['whitelist_notices'][] = $notice_id;
                    } else {
                        $admin_notice_data['blacklist_notices'][] = $notice_id;
                    }
                }

                update_option('cme_admin_notice_data', $admin_notice_data);

                $response['message'] = esc_html__('Admin notice status updated successfully.', 'capability-manager-enhanced');
                $response['status']  = 'success';
            }

            wp_send_json($response);
        }
    }
}