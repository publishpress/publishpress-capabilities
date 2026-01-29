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
    private $custom_scheme_url = '';
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
            'custom_scheme_base' => '#655997',
            'custom_scheme_text' => '#ffffff',
            'custom_scheme_highlight' => '#8a7bb9',
            'custom_scheme_notification' => '#d63638',
            'custom_scheme_background' => '#f0f2f1',

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

        // Generate custom scheme URL
        $this->custom_scheme_url = $this->generate_custom_scheme_url();

        // Register custom color scheme
        add_action('admin_init', [$this, 'register_custom_color_scheme'], 1);

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
            'admin_favicon',
            'custom_scheme_base',
            'custom_scheme_text',
            'custom_scheme_highlight',
            'custom_scheme_notification',
            'custom_scheme_background'
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
     * Generate custom scheme URL
     */
    private function generate_custom_scheme_url()
    {
        // Get current user's roles for the URL
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;

        $plugin_url = plugin_dir_url(__FILE__);

        $version = $this->settings['custom_scheme_version'] ?? time();

        // Start with base URL
        $url = $plugin_url . 'admin-styles-css.php?ppc_custom_scheme=1&css_ver=' . $version;

        // Add roles to URL for role specific CSS
        if (!empty($user_roles)) {
            $url .= '&roles=' . urlencode(implode(',', $user_roles));
        }

        return $url;
    }

    /**
     * Register custom color scheme with WordPress
     */
    public function register_custom_color_scheme()
    {
        // Ensure we have the latest settings loaded
        $this->load_settings_for_role($this->current_role);

        // Get custom colors from settings
        $colors = [
            $this->settings['custom_scheme_base'] ?? '#655997',
            $this->settings['custom_scheme_text'] ?? '#ffffff',
            $this->settings['custom_scheme_highlight'] ?? '#8a7bb9',
            $this->settings['custom_scheme_notification'] ?? '#d63638',
            $this->settings['custom_scheme_background'] ?? '#f0f2f1'
        ];

        // Clear any existing registration first
        global $_wp_admin_css_colors;
        if (isset($_wp_admin_css_colors['publishpress-custom'])) {
            unset($_wp_admin_css_colors['publishpress-custom']);
        }

        // Register the color scheme
        wp_admin_css_color(
            'publishpress-custom',
            __('Custom Colors', 'capsman-enhanced'),
            $this->custom_scheme_url,
            $colors,
            [
                'base' => $colors[0],
                'focus' => $colors[2],
                'current' => $colors[1]
            ]
        );
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

        // WordPress built-in: Custom footer text (with removal option)
        add_filter('admin_footer_text', function ($text) use ($user_settings) {
            return $this->custom_footer_text_for_settings($text, $user_settings);
        }, 9999);

        // WordPress built-in: Replace "Howdy" text
        if (!empty($user_settings['admin_replace_howdy'])) {
            add_filter('gettext', function ($translated, $text, $domain) use ($user_settings) {
                return $this->replace_howdy_text_for_settings($translated, $text, $domain, $user_settings);
            }, 10, 3);
        }

        // Custom: Add favicon
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
     * Custom footer text with removal option
     */
    public function custom_footer_text($text)
    {

        if (!empty($this->settings['admin_custom_footer_text'])) {
            return wp_kses_post($this->settings['admin_custom_footer_text']);
        }

        return $text;
    }

    /**
     * Replace "Howdy" text
     */
    public function replace_howdy_text($translated, $text, $domain)
    {
        if ('default' === $domain && 'Howdy, %s' === $text && !empty($this->settings['admin_replace_howdy'])) {
            $current_user = wp_get_current_user();
            if ($current_user->display_name) {
                return sprintf($this->settings['admin_replace_howdy'] . ', %s', $current_user->display_name);
            }
        }
        return $translated;
    }

    /**
     * Add favicon
     */
    public function add_favicon()
    {
        echo '<link rel="icon" href="' . esc_url($this->settings['admin_favicon']) . '" sizes="32x32" />' . "\n";
        echo '<link rel="icon" href="' . esc_url($this->settings['admin_favicon']) . '" sizes="192x192" />' . "\n";
        echo '<link rel="apple-touch-icon" href="' . esc_url($this->settings['admin_favicon']) . '" />' . "\n";
        echo '<meta name="msapplication-TileImage" content="' . esc_url($this->settings['admin_favicon']) . '" />' . "\n";
    }

    /**
     * Force admin color scheme
     */
    public function force_admin_color_scheme($color_scheme)
    {
        return $this->settings['admin_color_scheme'];
    }

    /**
     * Force role color scheme (overrides user selection when force_role_settings is enabled)
     */
    public function force_role_color_scheme($color_scheme)
    {
        // Always return the role's color scheme when force is enabled
        return $this->settings['admin_color_scheme'];
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission()
    {
        if (!isset($_POST['_wpnonce'])) {
            return;
        }

        if (!empty($_SERVER['REQUEST_METHOD']) && ('POST' == $_SERVER['REQUEST_METHOD']) && (isset($_POST['admin-styles-submit']) || isset($_POST['admin-styles-all-submit'])) && !empty($_REQUEST['_wpnonce'])) {

            check_admin_referer('pp-capabilities-admin-styles', '_wpnonce');

            if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_admin_styles')) {
                wp_die('<strong>' . esc_html__('You do not have permission to manage admin styles.', 'capability-manager-enhanced') . '</strong>');
            }

            // Get the target role
            $target_role = isset($_POST['ppc-admin-styles-role']) ? sanitize_key($_POST['ppc-admin-styles-role']) : '';

            // Check if saving for all roles
            $save_for_all = isset($_POST['admin-styles-all-submit']);

            // Get settings from POST
            $settings = isset($_POST['settings']) ? (array) $_POST['settings'] : [];

            // Add/update version timestamp
            $settings['custom_scheme_version'] = time();

            // Sanitize settings
            $settings = $this->sanitize_settings($settings);

            // Save role
            if (!empty($target_role)) {
                $this->set_current_role($target_role);
            }

            if ($save_for_all) {
                // Save for ALL roles
                $all_roles = wp_roles()->role_names;

                // Get existing role settings
                $role_settings = get_option('pp_capabilities_admin_styles_roles', []);

                // Apply to all roles
                foreach (array_keys($all_roles) as $role_name) {
                    $role_settings[$role_name] = $settings;
                }

                // Update option
                update_option('pp_capabilities_admin_styles_roles', $role_settings);
            } else {
                // Save for specific role
                if (empty($target_role)) {
                    wp_die('<strong>' . esc_html__('No role specified.', 'capability-manager-enhanced') . '</strong>');
                }

                // Get existing role settings
                $role_settings = get_option('pp_capabilities_admin_styles_roles', []);

                // Update settings for this role
                $role_settings[$target_role] = $settings;

                // Update option
                update_option('pp_capabilities_admin_styles_roles', $role_settings);

                // Also update current user's settings if this is their role
                if ($target_role === $this->current_role) {
                    $this->settings = $settings;
                }
            }

            // Reload settings immediately after saving
            $this->load_settings_for_role($target_role ?: $this->current_role);

            // Regenerate the custom scheme URL
            $this->custom_scheme_url = $this->generate_custom_scheme_url();

            // Re-register custom color scheme with new colors
            $this->register_custom_color_scheme();

            // Force immediate reload of the page to show updated UI and settings
            wp_redirect(add_query_arg([
                'page' => 'pp-capabilities-admin-styles',
                'settings-updated' => 'true',
                'role' => isset($_POST['ppc-admin-styles-role']) ? sanitize_text_field($_POST['ppc-admin-styles-role']) : ''
            ], admin_url('admin.php')));
            exit;
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

        // Enqueue JavaScript
        wp_enqueue_script(
            'pp-capabilities-admin-styles',
            plugin_dir_url(__FILE__) . 'assets/js/admin-styles.js',
            ['jquery', 'wp-color-picker'],
            CAPSMAN_VERSION,
            true
        );

        // Get all color schemes data for JavaScript
        $color_schemes = $this->get_color_schemes_data();

        // Localize script
        wp_localize_script('pp-capabilities-admin-styles', 'ppCapabilitiesAdminStyles', [
            'nonce' => wp_create_nonce('pp_capabilities_admin_styles'),
            'adminUrl' => admin_url('admin.php?page=pp-capabilities-admin-styles'),
            'moduleUrl' => plugin_dir_url(__FILE__),
            'colorSchemes' => $color_schemes,
            'labels' => [
                'selectImage' => __('Select Image', 'capsman-enhanced'),
                'useImage' => __('Use this Image', 'capsman-enhanced'),
                'saving' => __('Saving...', 'capsman-enhanced'),
                'saved' => __('Settings saved.', 'capsman-enhanced'),
                'saveForRole' => __('Save for %s', 'capsman-enhanced'),
                'currentLogoPreview' => __('Current logo preview', 'capsman-enhanced')
            ]
        ]);
    }

    /**
     * Get all color schemes data for JavaScript
     */
    private function get_color_schemes_data()
    {
        global $_wp_admin_css_colors;

        $schemes = [];

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

        // Get fresh settings to ensure we have the latest custom colors
        $fresh_settings = $this->settings;

        // Add our custom scheme if not already there
        if (!isset($schemes['publishpress-custom'])) {
            $schemes['publishpress-custom'] = [
                'name' => __('Custom Colors', 'capsman-enhanced'),
                'url' => $this->custom_scheme_url,
                'colors' => [
                    $fresh_settings['custom_scheme_base'] ?? '#655997',
                    $fresh_settings['custom_scheme_text'] ?? '#ffffff',
                    $fresh_settings['custom_scheme_highlight'] ?? '#8a7bb9',
                    $fresh_settings['custom_scheme_notification'] ?? '#d63638',
                    $fresh_settings['custom_scheme_background'] ?? '#f0f0f1'
                ],
                'icon_colors' => [
                    'base' => $fresh_settings['custom_scheme_base'] ?? '#655997',
                    'focus' => $fresh_settings['custom_scheme_highlight'] ?? '#8a7bb9',
                    'current' => $fresh_settings['custom_scheme_text'] ?? '#ffffff'
                ]
            ];
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
     * Get WordPress built-in color schemes plus custom option
     */
    public function get_color_schemes()
    {
        global $_wp_admin_css_colors;

        $schemes = [];

        // Add our custom scheme first
        $schemes['publishpress-custom'] = __('Custom Colors', 'capsman-enhanced');

        if (!empty($_wp_admin_css_colors)) {
            foreach ($_wp_admin_css_colors as $key => $scheme) {
                // Skip if scheme is not available or if it's our custom scheme
                if (empty($scheme->name) || $key === 'publishpress-custom') {
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
     * Get color scheme data including colors
     */
    public function get_color_scheme_data($scheme)
    {
        global $_wp_admin_css_colors;

        $data = [
            'name' => '',
            'colors' => [],
            'icons' => []
        ];

        if ($scheme === 'publishpress-custom') {
            $data['name'] = __('Custom Colors', 'capsman-enhanced');
            $data['colors'] = [
                $this->settings['custom_scheme_base'] ?? '#655997',
                $this->settings['custom_scheme_text'] ?? '#ffffff',
                $this->settings['custom_scheme_highlight'] ?? '#8a7bb9',
                $this->settings['custom_scheme_notification'] ?? '#d63638',
                $this->settings['custom_scheme_background'] ?? '#f0f0f1'
            ];
        } elseif (isset($_wp_admin_css_colors[$scheme])) {
            $data['name'] = $_wp_admin_css_colors[$scheme]->name;
            $data['colors'] = $_wp_admin_css_colors[$scheme]->colors;
            if (isset($_wp_admin_css_colors[$scheme]->icon_colors)) {
                $data['icons'] = $_wp_admin_css_colors[$scheme]->icon_colors;
            }
        } else {
            // Default scheme
            $data['name'] = __('Default', 'capsman-enhanced');
            $data['colors'] = ['#2271b1', '#72aee6', '#ffffff', '#d63638', '#f0f0f1'];
        }

        return $data;
    }
}