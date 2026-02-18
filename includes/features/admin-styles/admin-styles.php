<?php
/**
 * Main admin styles class with custom color scheme support
 */

namespace PublishPress\Capabilities;

defined('ABSPATH') || exit;

class PP_Capabilities_Admin_Styles
{
    private static $instance = null;

    public $settings = [];
    public $defaults = [];
    private $current_role = '';

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new PP_Capabilities_Admin_Styles();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        if (!pp_capabilities_feature_enabled('admin-styles')) {
            return;
        }

        $this->defaults = [
            // WordPress Builtin Features
            'admin_color_scheme' => 'fresh',
            'admin_custom_footer_text' => '',

            // Custom CSS based features
            'hide_color_scheme_ui' => false,
            'force_role_settings' => false,
            'admin_replace_howdy' => '',

            // Image based features
            'admin_logo' => '',
            'admin_favicon' => '',

            // Custom color scheme settings
            'custom_scheme_base' => '',
            'custom_scheme_text' => '',
            'custom_scheme_highlight' => '',
            'custom_scheme_notification' => '',
            'custom_scheme_background' => '',
            'custom_scheme_name' => '',

            // Element-specific colors
            'element_colors' => [
                'links' => [
                    'link_default' => '',
                    'link_hover' => '',
                    'link_delete' => '',
                    'link_trash' => '',
                    'link_spam' => '',
                    'link_inactive' => ''
                ],
                'tables' => [
                    'table_header_bg' => '',
                    'table_header_text' => '',
                    'table_row_bg' => '',
                    'table_row_color' => '',
                    'table_row_hover_bg' => '',
                    'table_border' => '',
                    'table_alt_row_bg' => '',
                    'table_alt_row_color' => ''
                ],
                'forms' => [
                    'input_border' => '',
                    'input_focus_border' => '',
                    'input_background' => '',
                    'input_text' => '',
                    'input_placeholder' => ''
                ],
                'buttons' => [
                    'button_primary_bg' => '',
                    'button_primary_text' => '',
                    'button_primary_hover_bg' => '',
                    'button_secondary_bg' => '',
                    'button_secondary_text' => '',
                    'button_secondary_hover_bg' => ''
                ],
                'admin_menu' => [
                    'menu_bg' => '',
                    'menu_text' => '',
                    'menu_icon' => '',
                    'menu_hover_bg' => '',
                    'menu_hover_text' => '',
                    'menu_current_bg' => '',
                    'menu_current_text' => '',
                    'menu_submenu_bg' => '',
                    'menu_submenu_text' => ''
                ],
                'admin_bar' => [
                    'adminbar_bg' => '',
                    'adminbar_text' => '',
                    'adminbar_icon' => '',
                    'adminbar_hover_bg' => '',
                    'adminbar_hover_text' => ''
                ],
                'dashboard_widgets' => [
                    'widget_bg' => '',
                    'widget_border' => '',
                    'widget_header_bg' => '',
                    'widget_title_text' => '',
                    'widget_body_text' => '',
                    'widget_link' => '',
                    'widget_link_hover' => ''
                ]
            ],

            // Version for cache busting
            'custom_scheme_version' => 0
        ];

        // Get current role from URL or default
        global $capsman;
        $this->current_role = isset($_GET['role']) ? sanitize_key($_GET['role']) : '';
        if (empty($this->current_role) && !empty($capsman)) {
            $this->current_role = $capsman->get_last_role();
        }

        // Load settings for current role
        $this->load_settings_for_role($this->current_role);

        // Register all custom schemes
        add_action('admin_init', [$this, 'register_all_custom_schemes'], 1);

        // Form submission handler
        add_action('admin_init', [$this, 'handle_form_submission']);

        // Apply settings
        $this->apply_settings();

        // Admin interface hooks
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    /**
     * Get merged settings for a user based on all their roles
     */
    private function get_user_settings($user_roles)
    {
        $all_role_settings = get_option('pp_capabilities_admin_styles_roles', []);

        // Start with global settings
        $user_settings = $this->defaults;

        // Define which settings should be overridden by roles
        $override_settings = [
            'admin_color_scheme',
            'admin_custom_footer_text',
            'hide_color_scheme_ui',
            'force_role_settings',
            'admin_replace_howdy',
            'admin_logo',
            'admin_favicon'
        ];

        // Check each role in reverse order (last role wins for conflicting settings)
        foreach (array_reverse($user_roles) as $role) {
            if (isset($all_role_settings[$role])) {
                $role_setting = $all_role_settings[$role];

                // Only override if the role has a non-empty/non-default value
                foreach ($override_settings as $key) {
                    if (isset($role_setting[$key])) {
                        // For checkboxes, check if true
                        if (
                            in_array($key, [
                                'hide_color_scheme_ui',
                                'force_role_settings'
                            ])
                        ) {
                            if (!empty($role_setting[$key])) {
                                $user_settings[$key] = $role_setting[$key];
                            }
                        }
                        // For text/color fields, check if not empty
                        elseif (!empty($role_setting[$key])) {
                            $user_settings[$key] = $role_setting[$key];
                        }
                    }
                }
            }
        }

        return $user_settings;
    }


    /**
     * Apply admin styles for specific settings
     */
    public function apply_admin_styles_for_settings($settings)
    {
        $css = '';

        // Custom admin logo
        if (!empty($settings['admin_logo'])) {
            $logo_url = esc_url($settings['admin_logo']);

            $css .= '
                /* Hide the entire WordPress logo icon container */
                #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon {
                    display: none !important;
                }

                /* Use the parent menu item as logo container */
                #wpadminbar #wp-admin-bar-wp-logo > .ab-item {
                    background-image: url("' . $logo_url . '") !important;
                    background-position: center center !important;
                    background-size: 20px 20px !important;
                    background-repeat: no-repeat !important;
                    min-width: 20px !important;
                    min-height: 32px !important;
                    padding: 0 7px !important;
                    position: relative !important;
                }

                /* Add overlay to hide any remaining dashicon */
                #wpadminbar #wp-admin-bar-wp-logo > .ab-item:after {
                    content: "" !important;
                    position: absolute !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    bottom: 0 !important;
                    background: inherit !important;
                    z-index: 1 !important;
                }

                /* Ensure the dashicon text is hidden */
                #wpadminbar #wp-admin-bar-wp-logo > .ab-item .screen-reader-text {
                    display: none !important;
                }

                /* Mobile */
                @media screen and (max-width: 782px) {
                    #wpadminbar #wp-admin-bar-wp-logo > .ab-item {
                        background-size: 26px 26px !important;
                        min-width: 30px !important;
                        min-height: 46px !important;
                        padding: 0 10px !important;
                    }
                }
            ';
        }

        // Apply custom CSS
        if (!empty($css)) {
            echo '<style id="pp-capabilities-admin-styles">' . $css . '</style>';
        }
    }

    /**
     * Apply color scheme filters for specific settings
     */
    private function apply_color_scheme_filters_for_settings($settings)
    {
        // If force role settings is enabled, always override
        if (!empty($settings['force_role_settings'])) {
            add_filter('get_user_option_admin_color', function ($color_scheme) use ($settings) {
                return $settings['admin_color_scheme'];
            }, 999);
            return;
        }

        // If a specific color scheme is set and not 'fresh', apply it
        if (!empty($settings['admin_color_scheme']) && $settings['admin_color_scheme'] !== 'fresh') {
            add_filter('get_user_option_admin_color', function ($color_scheme) use ($settings) {
                return $settings['admin_color_scheme'];
            }, 999);
        }
    }

    /**
     * Custom footer text for specific settings
     */
    public function custom_footer_text_for_settings($text, $settings)
    {

        if (!empty($settings['admin_custom_footer_text'])) {
            return wp_kses_post($settings['admin_custom_footer_text']);
        }

        return $text;
    }

    /**
     * Replace "Howdy" text for specific settings
     */
    public function replace_howdy_text_for_settings($translated, $text, $domain, $settings)
    {
        if ('default' === $domain && 'Howdy, %s' === $text && !empty($settings['admin_replace_howdy'])) {
            $current_user = wp_get_current_user();
            if ($current_user->display_name) {
                return sprintf($settings['admin_replace_howdy'] . ', %s', $current_user->display_name);
            }
        }
        return $translated;
    }

    /**
     * Add favicon for specific settings
     */
    public function add_favicon_for_settings($settings)
    {
        echo '<link rel="icon" href="' . esc_url($settings['admin_favicon']) . '" sizes="32x32" />' . "\n";
        echo '<link rel="icon" href="' . esc_url($settings['admin_favicon']) . '" sizes="192x192" />' . "\n";
        echo '<link rel="apple-touch-icon" href="' . esc_url($settings['admin_favicon']) . '" />' . "\n";
        echo '<meta name="msapplication-TileImage" content="' . esc_url($settings['admin_favicon']) . '" />' . "\n";
    }

    /**
     * Load settings for a specific role
     */
    public function load_settings_for_role($role)
    {
        // Get all role settings
        $all_role_settings = get_option('pp_capabilities_admin_styles_roles', []);

        // Get settings for this specific role
        if (isset($all_role_settings[$role])) {
            $this->settings = wp_parse_args($all_role_settings[$role], $this->defaults);
        } else {
            // Fallback to global settings
            $this->settings = $this->defaults;
        }

        return $this->settings;
    }

    /**
     * Generate CSS URL for custom style
     */
    private function generate_custom_style_url($style_slug)
    {
        $plugin_url = plugin_dir_url(__FILE__);

        // Get the custom style's individual version for cache busting
        $custom_styles = $this->get_custom_styles();
        $version = (isset($custom_styles[$style_slug]['custom_scheme_version']) ? $custom_styles[$style_slug]['custom_scheme_version'] : time());

        return $plugin_url . 'admin-styles-css.php?ppc_custom_scheme=1&ppc_custom_style=' . urlencode($style_slug) . '&css_ver=' . $version;
    }

    /**
     * Apply settings based on current user's roles
     */
    private function apply_settings()
    {
        // Get current user and their roles
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;

        if (empty($user_roles)) {
            return;
        }

        // Get merged settings for all user roles
        $user_settings = $this->get_user_settings($user_roles);

        // Apply admin styles via CSS
        add_action('admin_head', function () use ($user_settings) {
            $this->apply_admin_styles_for_settings($user_settings);
        }, 1);

        add_action('login_head', function () use ($user_settings) {
            $this->apply_admin_styles_for_settings($user_settings);
        });

        // Hide color scheme UI from user profiles if enabled
        if (!empty($user_settings['hide_color_scheme_ui'])) {
            add_action('admin_head-profile.php', [$this, 'hide_color_scheme_ui']);
            add_action('admin_head-user-edit.php', [$this, 'hide_color_scheme_ui']);
        }

        // Determine which color scheme filter to apply
        $this->apply_color_scheme_filters_for_settings($user_settings);

        // Custom footer text (with removal option)
        add_filter('admin_footer_text', function ($text) use ($user_settings) {
            return $this->custom_footer_text_for_settings($text, $user_settings);
        }, 9999);

        // Replace "Howdy" text
        if (!empty($user_settings['admin_replace_howdy'])) {
            add_filter('gettext', function ($translated, $text, $domain) use ($user_settings) {
                return $this->replace_howdy_text_for_settings($translated, $text, $domain, $user_settings);
            }, 10, 3);
        }

        // Add favicon
        if (!empty($user_settings['admin_favicon'])) {
            add_action('admin_head', function () use ($user_settings) {
                $this->add_favicon_for_settings($user_settings);
            });
            add_action('login_head', function () use ($user_settings) {
                $this->add_favicon_for_settings($user_settings);
            });
        }
    }

    /**
     * Hide color scheme UI from user profile pages
     */
    public function hide_color_scheme_ui()
    {
        ?>
        <style type="text/css">
            .user-admin-color-wrap,
            tr.user-admin-color-wrap,
            .user-admin-color-options,
            .user-admin-color-wrap+.description {
                display: none !important;
            }
        </style>
        <?php
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission()
    {

        // Only process if it's our form submission
        if (!isset($_POST['_wpnonce']) || !isset($_POST['page']) || $_POST['page'] !== 'pp-capabilities-admin-styles') {
            return;
        }

        // Check for our specific form submissions
        $is_custom_style_submit = isset($_POST['custom_style_action']) && in_array($_POST['custom_style_action'], ['save', 'delete']);
        $is_admin_styles_submit = isset($_POST['admin-styles-submit']) || isset($_POST['admin-styles-all-submit']);

        if (!$is_custom_style_submit && !$is_admin_styles_submit) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'pp-capabilities-admin-styles')) {
            wp_die('<strong>' . esc_html__('Security check failed.', 'capability-manager-enhanced') . '</strong>');
        }

        // Check permissions
        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_admin_styles')) {
            wp_die('<strong>' . esc_html__('You do not have permission to manage admin styles.', 'capability-manager-enhanced') . '</strong>');
        }

        // Handle custom style save/delete
        if ($is_custom_style_submit) {
            $this->handle_custom_style_action();

            wp_redirect(add_query_arg([
                'page' => 'pp-capabilities-admin-styles',
                'settings-updated' => 'true',
                'role' => isset($_POST['ppc-admin-styles-role']) ? sanitize_text_field($_POST['ppc-admin-styles-role']) : ''
            ], admin_url('admin.php')));
            exit;
        }

        // Handle regular admin styles save
        if ($is_admin_styles_submit) {
            // Get the target role
            $target_role = isset($_POST['ppc-admin-styles-role']) ? sanitize_key($_POST['ppc-admin-styles-role']) : '';
            // Check if saving for all roles
            $save_for_all = isset($_POST['admin-styles-all-submit']);
            // Get settings from POST
            $settings = isset($_POST['settings']) ? (array) $_POST['settings'] : [];
            // Ensure all general and element color fields are copied from element_colors/general and element_colors/* tabs
            if (!empty($settings['element_colors'])) {
                // Copy general tab colors
                if (!empty($settings['element_colors']['general'])) {
                    $gen = $settings['element_colors']['general'];
                    foreach (['custom_scheme_base','custom_scheme_text','custom_scheme_highlight','custom_scheme_notification','custom_scheme_background','custom_scheme_name'] as $key) {
                        if (isset($gen[$key])) {
                            $settings[$key] = $gen[$key];
                        }
                    }
                }
                // Copy all element color tabs
                foreach ($settings['element_colors'] as $tab => $colors) {
                    if ($tab === 'general') continue;
                    foreach ($colors as $color_key => $color_val) {
                        if (!isset($settings['element_colors'][$tab][$color_key]))  {
                            continue;
                        }
                        $settings['element_colors'][$tab][$color_key] = $color_val;
                    }
                }
            }
            // Update version timestamp
            $settings['custom_scheme_version'] = time();
            // Sanitize settings
            $settings = $this->sanitize_settings($settings);
            // Save role
            if (!empty($target_role)) {
                $this->set_current_role($target_role);
            }

            if ($save_for_all) {
                $all_roles = wp_roles()->role_names;
                $role_settings = get_option('pp_capabilities_admin_styles_roles', []);
                foreach (array_keys($all_roles) as $role_name) {
                    $role_settings[$role_name] = $settings;
                }
                update_option('pp_capabilities_admin_styles_roles', $role_settings);
            } else {
                if (empty($target_role)) {
                    wp_die('<strong>' . esc_html__('No role specified.', 'capability-manager-enhanced') . '</strong>');
                }
                $role_settings = get_option('pp_capabilities_admin_styles_roles', []);
                $role_settings[$target_role] = $settings;
                update_option('pp_capabilities_admin_styles_roles', $role_settings);
                if ($target_role === $this->current_role) {
                    $this->settings = $settings;
                }
            }
            $this->load_settings_for_role($target_role ?: $this->current_role);
            $this->register_all_custom_schemes();
            $redirect_url = admin_url('admin.php?page=pp-capabilities-admin-styles');
            if (!empty($target_role)) {
                $redirect_url = add_query_arg('role', $target_role, $redirect_url);
            }
            set_transient('ppc_admin_styles_saved_' . get_current_user_id(), 'true', 30);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }


    /**
     * Handle custom style actions (save, delete)
     */
    private function handle_custom_style_action()
    {
        $action = sanitize_key($_POST['custom_style_action']);

        if ($action === 'save') {
            $style_name = isset($_POST['custom_style_name']) ? sanitize_text_field($_POST['custom_style_name']) : '';
            $style_slug = isset($_POST['custom_style_slug']) ? sanitize_key($_POST['custom_style_slug']) : '';

            // Validate that name is not empty
            if (empty(trim($style_name))) {
                $redirect_url = admin_url('admin.php?page=pp-capabilities-admin-styles');
                if (isset($_POST['ppc-admin-styles-role']) && !empty($_POST['ppc-admin-styles-role'])) {
                    $redirect_url = add_query_arg('role', sanitize_text_field($_POST['ppc-admin-styles-role']), $redirect_url);
                }

                // Set transient for error message
                set_transient('ppc_custom_style_error_' . get_current_user_id(), 'empty_style_name', 30);

                wp_safe_redirect($redirect_url);
                exit;
            }

            // Handle user custom styles
            $custom_styles = $this->get_custom_styles();

            // Generate slug if not provided or if it's 'new'
            if (empty($style_slug) || $style_slug === 'new') {
                $style_slug = $this->generate_unique_slug($style_name, $custom_styles);
            }

            // Ensure slug has our prefix
            if (strpos($style_slug, 'ppc-custom-style-') !== 0) {
                $style_slug = 'ppc-custom-style-' . $style_slug;

                // Re-check uniqueness with new prefix
                $style_slug = $this->generate_unique_slug($style_slug, $custom_styles);
            }


            if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')
                && count($custom_styles) > 0
                && !isset($custom_styles[$style_slug])
            ) {
                // Free user are not allowed to add more than one custom admin styles
                $redirect_url = admin_url('admin.php?page=pp-capabilities-admin-styles');
                if (isset($_POST['ppc-admin-styles-role']) && !empty($_POST['ppc-admin-styles-role'])) {
                    $redirect_url = add_query_arg('role', sanitize_text_field($_POST['ppc-admin-styles-role']), $redirect_url);
                }

                wp_safe_redirect($redirect_url);
                exit;
            }

            // Get custom style colors
            $custom_style = [
                'name' => $style_name,
                'custom_scheme_base' => sanitize_hex_color($_POST['custom_style_custom_scheme_base'] ?? ''),
                'custom_scheme_text' => sanitize_hex_color($_POST['custom_style_custom_scheme_text'] ?? ''),
                'custom_scheme_highlight' => sanitize_hex_color($_POST['custom_style_custom_scheme_highlight'] ?? ''),
                'custom_scheme_notification' => sanitize_hex_color($_POST['custom_style_custom_scheme_notification'] ?? ''),
                'custom_scheme_background' => sanitize_hex_color($_POST['custom_style_custom_scheme_background'] ?? ''),
                'element_colors' => [],
                'custom_scheme_version' => time(),
                'created' => current_time('mysql')
            ];

            // Extract element colors
            $element_tabs = $this->get_element_color_tabs();
            foreach ($element_tabs as $tab_key => $tab_data) {
                if ($tab_key === 'general') {
                    // General colors are already handled above
                    continue;
                }

                $custom_style['element_colors'][$tab_key] = [];

                foreach ($tab_data['colors'] as $color_key => $color_config) {
                    $post_key = 'custom_style_' . $color_key;
                    $color_value = isset($_POST[$post_key]) ? sanitize_hex_color($_POST[$post_key]) : '';
                    $custom_style['element_colors'][$tab_key][$color_key] = $color_value;
                }
            }

            // Save custom style and move to top
            if (isset($custom_styles[$style_slug])) {
                unset($custom_styles[$style_slug]);
            }
            $custom_styles = array_merge([$style_slug => $custom_style], $custom_styles);
            $this->save_custom_styles($custom_styles);

            // Update the color scheme selection to use this new custom style if it's selected
            if (isset($_POST['settings']['admin_color_scheme']) && $_POST['settings']['admin_color_scheme'] === 'new') {
                $_POST['settings']['admin_color_scheme'] = $style_slug;
            }

            // Ensure the new custom style is active for the selected role
            $target_role = isset($_POST['ppc-admin-styles-role']) ? sanitize_key($_POST['ppc-admin-styles-role']) : '';
            if (!empty($target_role)) {
                $role_settings = get_option('pp_capabilities_admin_styles_roles', []);
                $role_settings[$target_role] = isset($role_settings[$target_role]) && is_array($role_settings[$target_role])
                    ? $role_settings[$target_role]
                    : [];
                $role_settings[$target_role]['admin_color_scheme'] = $style_slug;
                update_option('pp_capabilities_admin_styles_roles', $role_settings);
                if ($target_role === $this->current_role) {
                    $this->settings['admin_color_scheme'] = $style_slug;
                }
            }

            // Redirect
            $redirect_url = admin_url('admin.php?page=pp-capabilities-admin-styles');
            if (isset($_POST['ppc-admin-styles-role']) && !empty($_POST['ppc-admin-styles-role'])) {
                $redirect_url = add_query_arg('role', sanitize_text_field($_POST['ppc-admin-styles-role']), $redirect_url);
            }

            // Set transient for success message
            set_transient('ppc_custom_style_saved_' . get_current_user_id(), $style_name, 30);

            wp_safe_redirect($redirect_url);
            exit;

        } elseif ($action === 'delete' && isset($_POST['custom_style_slug'])) {
            $style_slug = sanitize_key($_POST['custom_style_slug']);

            if (!empty($style_slug)) {
                $custom_styles = $this->get_custom_styles();

                if (isset($custom_styles[$style_slug])) {
                    $style_name = $custom_styles[$style_slug]['name'];
                    unset($custom_styles[$style_slug]);
                    $this->save_custom_styles($custom_styles);

                    // If the deleted style was selected in settings, reset to default
                    if (isset($_POST['settings']['admin_color_scheme']) && $_POST['settings']['admin_color_scheme'] === $style_slug) {
                        $_POST['settings']['admin_color_scheme'] = 'fresh';
                    }

                    // Redirect
                    $redirect_url = admin_url('admin.php?page=pp-capabilities-admin-styles');
                    if (isset($_POST['ppc-admin-styles-role']) && !empty($_POST['ppc-admin-styles-role'])) {
                        $redirect_url = add_query_arg('role', sanitize_text_field($_POST['ppc-admin-styles-role']), $redirect_url);
                    }

                    set_transient('ppc_custom_style_deleted_' . get_current_user_id(), $style_name, 30);

                    wp_safe_redirect($redirect_url);
                    exit;
                }
            }
        }
    }

    public function set_current_role($role_name)
    {
        global $current_user;

        if ($role_name && !empty($current_user) && !empty($current_user->ID)) {
            update_option("capsman_last_role_{$current_user->ID}", $role_name);
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook)
    {

        if ($hook !== 'capabilities_page_pp-capabilities-admin-styles') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');

        // Enqueue CSS
        wp_enqueue_style(
            'pp-capabilities-admin-styles',
            plugin_dir_url(__FILE__) . 'assets/css/admin-styles.css',
            [],
            CAPSMAN_VERSION
        );

        if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            wp_enqueue_style(
                'pp-capabilities-admin-core',
                plugin_dir_url(CME_FILE) . 'includes-core/admin-core.css',
                [],
                PUBLISHPRESS_CAPS_VERSION,
                'all'
            );
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'pp-capabilities-admin-styles',
            plugin_dir_url(__FILE__) . 'assets/js/admin-styles.js',
            ['jquery', 'wp-color-picker'],
            CAPSMAN_VERSION,
            true
        );

        $color_schemes = $this->get_color_schemes_data();
        $custom_styles = $this->get_custom_styles();

        // Localize script
        wp_localize_script('pp-capabilities-admin-styles', 'ppCapabilitiesAdminStyles', [
            'nonce' => wp_create_nonce('pp_capabilities_admin_styles'),
            'adminUrl' => admin_url('admin.php?page=pp-capabilities-admin-styles'),
            'moduleUrl' => plugin_dir_url(__FILE__),
            'colorSchemes' => $color_schemes,
            'customStyles' => $custom_styles,
            'customStylesCounts' => is_array($custom_styles) ? count($custom_styles) : 0,
            'styleTemplates' => $this->get_style_templates(),
            'proInstalled' => defined('PUBLISHPRESS_CAPS_PRO_VERSION') ? 1 : 0,
            'labels' => [
                'selectImage' => __('Select Image', 'capsman-enhanced'),
                'useImage' => __('Use this Image', 'capsman-enhanced'),
                'saving' => __('Saving...', 'capsman-enhanced'),
                'saved' => __('Settings saved.', 'capsman-enhanced'),
                'saveForRole' => __('Save for %s', 'capsman-enhanced'),
                'currentLogoPreview' => __('Current logo preview', 'capsman-enhanced'),
                'addCustomStyle' => __('Add New Custom Style', 'capsman-enhanced'),
                'editCustomStyle' => __('Edit Custom Style', 'capsman-enhanced'),
                'confirmDeleteCustomStyle' => __('Are you sure you want to delete "%s" custom style?', 'capsman-enhanced'),
                'thisCustomStyle' => __('this custom style', 'capsman-enhanced'),
                'primaryButton' => __('Primary Button', 'capsman-enhanced'),
                'hoverState' => __('Hover State', 'capsman-enhanced'),
                'notification' => __('Notification', 'capsman-enhanced'),
                'livePreview' => __('Live preview of your custom color scheme', 'capsman-enhanced'),
                'styleNameRequired' => __('Custom Style Name is required.', 'capsman-enhanced'),
                'mainAdminColorRequired' => __('Main Admin Color is required.', 'capsman-enhanced'),
                'publishpressCustom' => __('PublishPress Custom', 'capsman-enhanced'),
                'displayName' => __('Display Name', 'capsman-enhanced'),
                'displayNameDescription' => __('Change how this style is displayed', 'capsman-enhanced'),
                'styleNameDescription' => __('Enter a name for your custom style', 'capsman-enhanced'),
                'styleName' => __('Custom Style Name', 'capsman-enhanced'),
            ]
        ]);
    }


    /**
     * Get built-in custom style templates
     */
    public function get_style_templates()
    {
        return [
            'forest' => [
                'name' => __('Forest', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#14532d',
                    'text' => '#ecfdf3',
                    'highlight' => '#22c55e',
                    'notification' => '#ef4444',
                    'background' => '#f0fdf4',
                    'surface' => '#ffffff',
                    'surface_alt' => '#dcfce7',
                    'border' => '#bbf7d0',
                    'muted' => '#166534',
                    'accent' => '#16a34a',
                    'accent_alt' => '#4ade80',
                    'danger' => '#ef4444'
                ]
            ],
            'mint-breeze' => [
                'name' => __('Mint Breeze', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#0f766e',
                    'text' => '#ecfeff',
                    'highlight' => '#14b8a6',
                    'notification' => '#ef4444',
                    'background' => '#f0fdfa',
                    'surface' => '#ffffff',
                    'surface_alt' => '#ccfbf1',
                    'border' => '#99f6e4',
                    'muted' => '#0f766e',
                    'accent' => '#0ea5a5',
                    'accent_alt' => '#22d3ee',
                    'danger' => '#ef4444'
                ]
            ],
            'cherry' => [
                'name' => __('Cherry', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#7f1d1d',
                    'text' => '#fef2f2',
                    'highlight' => '#ef4444',
                    'notification' => '#f97316',
                    'background' => '#fff1f2',
                    'surface' => '#ffffff',
                    'surface_alt' => '#fee2e2',
                    'border' => '#fecaca',
                    'muted' => '#b91c1c',
                    'accent' => '#dc2626',
                    'accent_alt' => '#f87171',
                    'danger' => '#b91c1c'
                ]
            ],
            'graphite' => [
                'name' => __('Graphite', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#111827',
                    'text' => '#f9fafb',
                    'highlight' => '#4b5563',
                    'notification' => '#f59e0b',
                    'background' => '#f8fafc',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e2e8f0',
                    'border' => '#cbd5f5',
                    'muted' => '#6b7280',
                    'accent' => '#374151',
                    'accent_alt' => '#94a3b8',
                    'danger' => '#ef4444'
                ]
            ],
            'midnight-teal' => [
                'name' => __('Midnight Teal', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#0b3b3c',
                    'text' => '#ecfeff',
                    'highlight' => '#2dd4bf',
                    'notification' => '#f97316',
                    'background' => '#f0fdfa',
                    'surface' => '#ffffff',
                    'surface_alt' => '#ccfbf1',
                    'border' => '#99f6e4',
                    'muted' => '#0f766e',
                    'accent' => '#14b8a6',
                    'accent_alt' => '#5eead4',
                    'danger' => '#f97316'
                ]
            ],
            'sunrise' => [
                'name' => __('Sunrise', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#7c2d12',
                    'text' => '#fff7ed',
                    'highlight' => '#f97316',
                    'notification' => '#dc2626',
                    'background' => '#fffbf5',
                    'surface' => '#ffffff',
                    'surface_alt' => '#ffe8d6',
                    'border' => '#fed7aa',
                    'muted' => '#9a3412',
                    'accent' => '#ea580c',
                    'accent_alt' => '#fb923c',
                    'danger' => '#dc2626'
                ]
            ],
            'desert-sand' => [
                'name' => __('Desert Sand', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#7a4a1a',
                    'text' => '#fff7ed',
                    'highlight' => '#f59e0b',
                    'notification' => '#ef4444',
                    'background' => '#fffbf5',
                    'surface' => '#ffffff',
                    'surface_alt' => '#ffedd5',
                    'border' => '#fed7aa',
                    'muted' => '#9a3412',
                    'accent' => '#f97316',
                    'accent_alt' => '#fdba74',
                    'danger' => '#ef4444'
                ]
            ],
            'amber-glow' => [
                'name' => __('Amber Glow', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#92400e',
                    'text' => '#fffbeb',
                    'highlight' => '#f59e0b',
                    'notification' => '#dc2626',
                    'background' => '#fffbf0',
                    'surface' => '#ffffff',
                    'surface_alt' => '#fef3c7',
                    'border' => '#fde68a',
                    'muted' => '#b45309',
                    'accent' => '#d97706',
                    'accent_alt' => '#fbbf24',
                    'danger' => '#dc2626'
                ]
            ],
            'citrus' => [
                'name' => __('Citrus', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#365314',
                    'text' => '#f7fee7',
                    'highlight' => '#84cc16',
                    'notification' => '#f97316',
                    'background' => '#f7fee7',
                    'surface' => '#ffffff',
                    'surface_alt' => '#ecfccb',
                    'border' => '#d9f99d',
                    'muted' => '#4d7c0f',
                    'accent' => '#65a30d',
                    'accent_alt' => '#a3e635',
                    'danger' => '#f97316'
                ]
            ],
            'ocean-deep' => [
                'name' => __('Ocean Deep', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#0f172a',
                    'text' => '#e2e8f0',
                    'highlight' => '#38bdf8',
                    'notification' => '#f97316',
                    'background' => '#f1f5f9',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e2e8f0',
                    'border' => '#cbd5e1',
                    'muted' => '#64748b',
                    'accent' => '#0284c7',
                    'accent_alt' => '#0ea5e9',
                    'danger' => '#ea580c'
                ]
            ],
            'sage' => [
                'name' => __('Sage', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#365314',
                    'text' => '#f7fee7',
                    'highlight' => '#4d7c0f',
                    'notification' => '#f97316',
                    'background' => '#f7fee7',
                    'surface' => '#ffffff',
                    'surface_alt' => '#ecfccb',
                    'border' => '#d9f99d',
                    'muted' => '#4d7c0f',
                    'accent' => '#65a30d',
                    'accent_alt' => '#bef264',
                    'danger' => '#f97316'
                ]
            ],
            'modern-slate' => [
                'name' => __('Modern Slate', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#1f2937',
                    'text' => '#f9fafb',
                    'highlight' => '#3b82f6',
                    'notification' => '#ef4444',
                    'background' => '#f3f4f6',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e5e7eb',
                    'border' => '#d1d5db',
                    'muted' => '#6b7280',
                    'accent' => '#2563eb',
                    'accent_alt' => '#0ea5e9',
                    'danger' => '#dc2626'
                ]
            ],
            'royal-plum' => [
                'name' => __('Royal Plum', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#4c1d95',
                    'text' => '#f5f3ff',
                    'highlight' => '#8b5cf6',
                    'notification' => '#ef4444',
                    'background' => '#f5f3ff',
                    'surface' => '#ffffff',
                    'surface_alt' => '#ede9fe',
                    'border' => '#ddd6fe',
                    'muted' => '#6d28d9',
                    'accent' => '#7c3aed',
                    'accent_alt' => '#a78bfa',
                    'danger' => '#ef4444'
                ]
            ],
            'nordic' => [
                'name' => __('Nordic', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#0f172a',
                    'text' => '#e2e8f0',
                    'highlight' => '#22d3ee',
                    'notification' => '#fb7185',
                    'background' => '#f8fafc',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e2e8f0',
                    'border' => '#cbd5f5',
                    'muted' => '#64748b',
                    'accent' => '#0ea5e9',
                    'accent_alt' => '#38bdf8',
                    'danger' => '#fb7185'
                ]
            ],
            'lagoon' => [
                'name' => __('Lagoon', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#0f172a',
                    'text' => '#e0f2fe',
                    'highlight' => '#38bdf8',
                    'notification' => '#f97316',
                    'background' => '#f0f9ff',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e0f2fe',
                    'border' => '#bae6fd',
                    'muted' => '#64748b',
                    'accent' => '#0284c7',
                    'accent_alt' => '#7dd3fc',
                    'danger' => '#f97316'
                ]
            ],
            'ink' => [
                'name' => __('Ink', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#111827',
                    'text' => '#e5e7eb',
                    'highlight' => '#6366f1',
                    'notification' => '#f87171',
                    'background' => '#f9fafb',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e5e7eb',
                    'border' => '#d1d5db',
                    'muted' => '#6b7280',
                    'accent' => '#4f46e5',
                    'accent_alt' => '#a5b4fc',
                    'danger' => '#ef4444'
                ]
            ],
            'orchid' => [
                'name' => __('Orchid', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#5b21b6',
                    'text' => '#f5f3ff',
                    'highlight' => '#c084fc',
                    'notification' => '#f97316',
                    'background' => '#faf5ff',
                    'surface' => '#ffffff',
                    'surface_alt' => '#f3e8ff',
                    'border' => '#e9d5ff',
                    'muted' => '#7c3aed',
                    'accent' => '#a855f7',
                    'accent_alt' => '#d8b4fe',
                    'danger' => '#f97316'
                ]
            ],
            'cobalt' => [
                'name' => __('Cobalt', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#1e3a8a',
                    'text' => '#eff6ff',
                    'highlight' => '#60a5fa',
                    'notification' => '#f97316',
                    'background' => '#f8fafc',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e0e7ff',
                    'border' => '#c7d2fe',
                    'muted' => '#4f46e5',
                    'accent' => '#3b82f6',
                    'accent_alt' => '#93c5fd',
                    'danger' => '#f97316'
                ]
            ],
            'stone' => [
                'name' => __('Stone', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#3f3f46',
                    'text' => '#f5f5f4',
                    'highlight' => '#a3a3a3',
                    'notification' => '#f59e0b',
                    'background' => '#fafafa',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e7e5e4',
                    'border' => '#d6d3d1',
                    'muted' => '#78716c',
                    'accent' => '#52525b',
                    'accent_alt' => '#d4d4d8',
                    'danger' => '#ef4444'
                ]
            ],
            'facebook-classic' => [
                'name' => __('Facebook Classic', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#3b5998',
                    'text' => '#ffffff',
                    'highlight' => '#4e71ba',
                    'notification' => '#d63638',
                    'background' => '#f5f6f7',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e9ebee',
                    'border' => '#ccd0d5',
                    'muted' => '#8b9dc3',
                    'accent' => '#3b5998',
                    'accent_alt' => '#6d84b4',
                    'danger' => '#d63638'
                ]
            ],
            'twitter-classic' => [
                'name' => __('Twitter Classic', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#1da1f2',
                    'text' => '#ffffff',
                    'highlight' => '#1a91da',
                    'notification' => '#e0245e',
                    'background' => '#f5f8fa',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e1e8ed',
                    'border' => '#ccd6dd',
                    'muted' => '#657786',
                    'accent' => '#1da1f2',
                    'accent_alt' => '#71c9f8',
                    'danger' => '#e0245e'
                ]
            ],
            'twitter-modern' => [
                'name' => __('Twitter Modern', 'capsman-enhanced'),
                'palette' => [
                    'base' => '#000000',
                    'text' => '#ffffff',
                    'highlight' => '#1d9bf0',
                    'notification' => '#f91880',
                    'background' => '#f7f9f9',
                    'surface' => '#ffffff',
                    'surface_alt' => '#e6ecf0',
                    'border' => '#dbe3ea',
                    'muted' => '#536471',
                    'accent' => '#1d9bf0',
                    'accent_alt' => '#6cc7ff',
                    'danger' => '#f91880'
                ]
            ]
        ];
    }

    /**
     * Get all color schemes data for JavaScript
     */
    private function get_color_schemes_data()
    {
        global $_wp_admin_css_colors;

        $schemes = [];

        // Add user custom styles
        $custom_styles = $this->get_custom_styles();

        foreach ($custom_styles as $slug => $style) {
            if (!empty($style['name'])) {
                $css_url = $this->generate_custom_style_url($slug);
                $menu_icon = $style['element_colors']['admin_menu']['menu_icon'] ?? '';
                $menu_hover_text = $style['element_colors']['admin_menu']['menu_hover_text'] ?? '';
                $menu_current_text = $style['element_colors']['admin_menu']['menu_current_text'] ?? '';
                $icon_base = $menu_icon ?: ($style['custom_scheme_text'] ?? '');
                $icon_focus = $menu_hover_text ?: $icon_base;
                $icon_current = $menu_current_text ?: $icon_base;

                $schemes[$slug] = [
                    'name' => $style['name'],
                    'url' => $css_url,
                    'colors' => [
                        $style['custom_scheme_base'] ?? '',
                        $style['custom_scheme_text'] ?? '',
                        $style['custom_scheme_highlight'] ?? '',
                        $style['custom_scheme_notification'] ?? '',
                        $style['custom_scheme_background'] ?? ''
                    ],
                    'icon_colors' => [
                        'base' => $icon_base,
                        'focus' => $icon_focus,
                        'current' => $icon_current
                    ],
                    'element_colors' => $style['element_colors'] ?? []
                ];
            }
        }

        // Add WordPress built-in schemes
        if (!empty($_wp_admin_css_colors)) {
            foreach ($_wp_admin_css_colors as $key => $scheme) {
                if (empty($scheme->name)) {
                    continue;
                }

                $schemes[$key] = [
                    'name' => $scheme->name,
                    'url' => $scheme->url,
                    'colors' => $scheme->colors,
                    'icon_colors' => isset($scheme->icon_colors) ? $scheme->icon_colors : []
                ];
            }
        }

        return $schemes;
    }

    /**
     * Sanitize settings
     */
    private function sanitize_settings($input)
    {
        $sanitized = [];

        foreach ($this->defaults as $key => $default) {
            if (!isset($input[$key])) {
                // For checkboxes, set to false if not submitted
                if (in_array($key, ['hide_color_scheme_ui', 'force_role_settings'])) {
                    $sanitized[$key] = false;
                } else {
                    $sanitized[$key] = $default;
                }
                continue;
            }

            $value = $input[$key];

            switch ($key) {
                case 'admin_custom_footer_text':
                    $sanitized[$key] = wp_kses_post($value);
                    break;

                case 'custom_scheme_base':
                case 'custom_scheme_text':
                case 'custom_scheme_highlight':
                case 'custom_scheme_notification':
                case 'custom_scheme_background':
                    $sanitized[$key] = sanitize_hex_color($value);
                    break;

                case 'element_colors':
                    $sanitized[$key] = $this->sanitize_element_colors($value);
                    break;

                case 'custom_scheme_version':
                    $sanitized[$key] = absint($value);
                    break;

                case 'hide_color_scheme_ui':
                case 'force_role_settings':
                    $sanitized[$key] = (bool) $value;
                    break;

                case 'admin_logo':
                case 'admin_favicon':
                    $sanitized[$key] = esc_url_raw($value);
                    break;

                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize element colors recursively
     */
    private function sanitize_element_colors($element_colors)
    {
        if (!is_array($element_colors)) {
            return $this->defaults['element_colors'];
        }

        $sanitized = [];

        foreach ($this->defaults['element_colors'] as $category => $colors) {
            if (!isset($element_colors[$category])) {
                $sanitized[$category] = $colors;
                continue;
            }

            $sanitized[$category] = [];

            foreach ($colors as $color_key => $default_color) {
                $sanitized[$category][$color_key] = '';

                if (isset($element_colors[$category][$color_key])) {
                    $value = $element_colors[$category][$color_key];

                    // Only sanitize if not empty
                    if (!empty($value)) {
                        $sanitized[$category][$color_key] = sanitize_hex_color($value);
                    }
                }
            }
        }

        return $sanitized;
    }

    /**
     * Get element color tabs configuration
     * Returns structure for UI tabs in custom style form
     */
    public function get_element_color_tabs()
    {
        return [
            'general' => [
                'label' => __('General', 'capsman-enhanced'),
                'description' => __('Main Admin Color settings', 'capsman-enhanced'),
                'colors' => [
                    'custom_scheme_base' => [
                        'label' => __('Main Admin Color', 'capsman-enhanced'),
                        'description' => __('Primary brand color', 'capsman-enhanced')
                    ],
                    'custom_scheme_text' => [
                        'label' => __('Text Color', 'capsman-enhanced'),
                        'description' => __('Primary text color', 'capsman-enhanced')
                    ],
                    'custom_scheme_highlight' => [
                        'label' => __('Highlight Color', 'capsman-enhanced'),
                        'description' => __('Used for hovers and highlights', 'capsman-enhanced')
                    ],
                    'custom_scheme_notification' => [
                        'label' => __('Notification Color', 'capsman-enhanced'),
                        'description' => __('Used for alerts and notifications', 'capsman-enhanced')
                    ],
                    'custom_scheme_background' => [
                        'label' => __('Background Color', 'capsman-enhanced'),
                        'description' => __('Page background color', 'capsman-enhanced')
                    ]
                ]
            ],
            'links' => [
                'label' => __('Links', 'capsman-enhanced'),
                'description' => __('Link element colors', 'capsman-enhanced'),
                'colors' => [
                    'link_default' => [
                        'label' => __('Default Link Color', 'capsman-enhanced'),
                        'description' => __('Standard link color', 'capsman-enhanced')
                    ],
                    'link_hover' => [
                        'label' => __('Link Hover Color', 'capsman-enhanced'),
                        'description' => __('Color on hover', 'capsman-enhanced')
                    ],
                    'link_delete' => [
                        'label' => __('Delete Link Color', 'capsman-enhanced'),
                        'description' => __('Color for delete/trash actions', 'capsman-enhanced')
                    ],
                    'link_trash' => [
                        'label' => __('Trash Link Color', 'capsman-enhanced'),
                        'description' => __('Color for trash actions', 'capsman-enhanced')
                    ],
                    'link_spam' => [
                        'label' => __('Spam Link Color', 'capsman-enhanced'),
                        'description' => __('Color for spam actions', 'capsman-enhanced')
                    ],
                    'link_inactive' => [
                        'label' => __('Inactive Link Color', 'capsman-enhanced'),
                        'description' => __('Color for inactive items', 'capsman-enhanced')
                    ]
                ]
            ],
            'tables' => [
                'label' => __('Tables', 'capsman-enhanced'),
                'description' => __('Table element colors', 'capsman-enhanced'),
                'colors' => [
                    'table_header_bg' => [
                        'label' => __('Table Header Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'table_header_text' => [
                        'label' => __('Table Header Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'table_row_bg' => [
                        'label' => __('Table Row Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'table_row_color' => [
                        'label' => __('Table Row Text Color', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'table_row_hover_bg' => [
                        'label' => __('Row Hover Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'table_border' => [
                        'label' => __('Table Border Color', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'table_alt_row_bg' => [
                        'label' => __('Alternate Row Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'table_alt_row_color' => [
                        'label' => __('Alternate Row Text Color', 'capsman-enhanced'),
                        'description' => ''
                    ]
                ]
            ],
            'forms' => [
                'label' => __('Forms', 'capsman-enhanced'),
                'description' => __('Form input colors', 'capsman-enhanced'),
                'colors' => [
                    'input_border' => [
                        'label' => __('Input Border Color', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'input_focus_border' => [
                        'label' => __('Input Focus Border', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'input_background' => [
                        'label' => __('Input Background Color', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'input_text' => [
                        'label' => __('Input Text Color', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'input_placeholder' => [
                        'label' => __('Placeholder Text Color', 'capsman-enhanced'),
                        'description' => ''
                    ]
                ]
            ],
            'buttons' => [
                'label' => __('Buttons', 'capsman-enhanced'),
                'description' => __('Button colors', 'capsman-enhanced'),
                'colors' => [
                    'button_primary_bg' => [
                        'label' => __('Primary Button Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'button_primary_text' => [
                        'label' => __('Primary Button Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'button_primary_hover_bg' => [
                        'label' => __('Primary Button Hover', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'button_secondary_bg' => [
                        'label' => __('Secondary Button Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'button_secondary_text' => [
                        'label' => __('Secondary Button Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'button_secondary_hover_bg' => [
                        'label' => __('Secondary Button Hover', 'capsman-enhanced'),
                        'description' => ''
                    ]
                ]
            ],
            'admin_menu' => [
                'label' => __('Admin Menu', 'capsman-enhanced'),
                'description' => __('Admin menu colors', 'capsman-enhanced'),
                'colors' => [
                    'menu_bg' => [
                        'label' => __('Menu Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_text' => [
                        'label' => __('Menu Text Color', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_icon' => [
                        'label' => __('Menu Icon Color', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_hover_bg' => [
                        'label' => __('Menu Hover Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_hover_text' => [
                        'label' => __('Menu Hover Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_current_bg' => [
                        'label' => __('Current Menu Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_current_text' => [
                        'label' => __('Current Menu Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_submenu_bg' => [
                        'label' => __('Submenu Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'menu_submenu_text' => [
                        'label' => __('Submenu Text Color', 'capsman-enhanced'),
                        'description' => ''
                    ]
                ]
            ],
            'admin_bar' => [
                'label' => __('Admin Bar', 'capsman-enhanced'),
                'description' => __('Admin bar colors', 'capsman-enhanced'),
                'colors' => [
                    'adminbar_bg' => [
                        'label' => __('Admin Bar Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'adminbar_text' => [
                        'label' => __('Admin Bar Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'adminbar_icon' => [
                        'label' => __('Admin Bar Icon', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'adminbar_hover_bg' => [
                        'label' => __('Admin Bar Hover Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'adminbar_hover_text' => [
                        'label' => __('Admin Bar Hover Text', 'capsman-enhanced'),
                        'description' => ''
                    ]
                ]
            ],
            'dashboard_widgets' => [
                'label' => __('Dashboard Widgets', 'capsman-enhanced'),
                'description' => __('Dashboard widget colors', 'capsman-enhanced'),
                'colors' => [
                    'widget_bg' => [
                        'label' => __('Widget Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'widget_border' => [
                        'label' => __('Widget Border', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'widget_header_bg' => [
                        'label' => __('Widget Header Background', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'widget_title_text' => [
                        'label' => __('Widget Title Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'widget_body_text' => [
                        'label' => __('Widget Body Text', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'widget_link' => [
                        'label' => __('Widget Link', 'capsman-enhanced'),
                        'description' => ''
                    ],
                    'widget_link_hover' => [
                        'label' => __('Widget Link Hover', 'capsman-enhanced'),
                        'description' => ''
                    ]
                ]
            ]
        ];
    }

    /**
     * Get WordPress built-in color schemes plus custom option
     */
    public function get_color_schemes()
    {
        global $_wp_admin_css_colors;

        $schemes = [];

        if (!empty($_wp_admin_css_colors)) {
            foreach ($_wp_admin_css_colors as $key => $scheme) {
                if (empty($scheme->name)) {
                    continue;
                }
                $schemes[$key] = $scheme->name;
            }
        }

        // Always include the default scheme
        if (!isset($schemes['fresh'])) {
            $schemes['fresh'] = __('Default', 'capsman-enhanced');
        }

        return $schemes;
    }


    /**
     * Get custom admin styles
     */
    public function get_custom_styles()
    {
        return get_option('pp_capabilities_custom_admin_styles', []);
    }

    /**
     * Save custom admin styles
     */
    public function save_custom_styles($styles)
    {
        return update_option('pp_capabilities_custom_admin_styles', $styles);
    }

    /**
     * Get color scheme data including custom ones
     */
    public function get_color_scheme_data($scheme)
    {
        global $_wp_admin_css_colors;

        $data = [
            'name' => '',
            'colors' => [],
            'icons' => []
        ];

        // Check WordPress built-in schemes first
        if (isset($_wp_admin_css_colors[$scheme])) {
            $data['name'] = $_wp_admin_css_colors[$scheme]->name;
            $data['colors'] = $_wp_admin_css_colors[$scheme]->colors;
            if (isset($_wp_admin_css_colors[$scheme]->icon_colors)) {
                $data['icons'] = $_wp_admin_css_colors[$scheme]->icon_colors;
            }
        } else {
            // Check if it's a user custom style
            $custom_styles = $this->get_custom_styles();
            if (isset($custom_styles[$scheme])) {
                $style = $custom_styles[$scheme];
                $data['name'] = $style['name'];
                $data['colors'] = [
                    $style['custom_scheme_base'] ?? '',
                    $style['custom_scheme_text'] ?? '',
                    $style['custom_scheme_highlight'] ?? '',
                    $style['custom_scheme_notification'] ?? '',
                    $style['custom_scheme_background'] ?? ''
                ];
                $menu_icon = $style['element_colors']['admin_menu']['menu_icon'] ?? '';
                $menu_hover_text = $style['element_colors']['admin_menu']['menu_hover_text'] ?? '';
                $menu_current_text = $style['element_colors']['admin_menu']['menu_current_text'] ?? '';
                $icon_base = $menu_icon ?: ($style['custom_scheme_text'] ?? '');
                $icon_focus = $menu_hover_text ?: $icon_base;
                $icon_current = $menu_current_text ?: $icon_base;
                $data['icons'] = [
                    'base' => $icon_base,
                    'focus' => $icon_focus,
                    'current' => $icon_current
                ];
            } else {
                // Default scheme
                $data['name'] = __('Default', 'capsman-enhanced');
                $data['colors'] = ['#1d2327', '#ffffff', '#0073aa', '#d63638', '#f0f0f1'];
            }
        }

        return $data;
    }

    /**
     * Generate unique slug for custom style
     */
    public function generate_unique_slug($name, $existing_styles = [])
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower($name);
        $slug = preg_replace('/\s+/', '-', $slug);

        // Keep all Unicode letters, numbers, and hyphens
        $slug = preg_replace('/[^\p{L}\p{N}-]/u', '', $slug);

        // Replace multiple hyphens with single hyphen
        $slug = preg_replace('/-+/', '-', $slug);

        // Remove hyphens from start and end
        $slug = trim($slug, '-');

        // Remove any existing prefixes
        $slug = preg_replace('/^(ppc-custom-style-|custom-style-)/', '', $slug);

        // Add our standard prefix
        $slug = 'ppc-custom-style-' . $slug;

        // make sure slug is unique
        $original_slug = $slug;
        $counter = 1;

        while (isset($existing_styles[$slug])) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Register all custom color schemes
     */
    public function register_all_custom_schemes()
    {
        global $_wp_admin_css_colors;

        // First clear any existing custom schemes
        if (!empty($_wp_admin_css_colors)) {
            foreach (array_keys($_wp_admin_css_colors) as $key) {
                if (strpos($key, 'ppc-custom-style-') === 0) {
                    unset($_wp_admin_css_colors[$key]);
                }
            }
        }

        // Register user custom styles first
        $custom_styles = $this->get_custom_styles();

        foreach ($custom_styles as $slug => $style) {
            if (!empty($style['name'])) {
                $colors = [
                    $style['custom_scheme_base'] ?? '',
                    $style['custom_scheme_text'] ?? '',
                    $style['custom_scheme_highlight'] ?? '',
                    $style['custom_scheme_notification'] ?? '',
                    $style['custom_scheme_background'] ?? ''
                ];
                $menu_icon = $style['element_colors']['admin_menu']['menu_icon'] ?? '';
                $menu_hover_text = $style['element_colors']['admin_menu']['menu_hover_text'] ?? '';
                $menu_current_text = $style['element_colors']['admin_menu']['menu_current_text'] ?? '';
                $icon_base = $menu_icon ?: ($style['custom_scheme_text'] ?? '');
                $icon_focus = $menu_hover_text ?: $icon_base;
                $icon_current = $menu_current_text ?: $icon_base;

                // Generate CSS URL for this custom style
                $css_url = $this->generate_custom_style_url($slug);

                wp_admin_css_color(
                    $slug,
                    $style['name'],
                    $css_url,
                    $colors,
                    [
                        'base' => $icon_base,
                        'focus' => $icon_focus,
                        'current' => $icon_current
                    ]
                );
            }
        }
    }
}