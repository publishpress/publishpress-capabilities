<?php

namespace PublishPress\Capabilities;

class Plugin_Capabilities
{
    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Plugin_Capabilities();
        }

        return self::$instance;
    }

    public function __construct()
    {
        //PublishPress Capabilities
        add_filter('cme_plugin_capabilities', [$this, 'cme_publishpress_capabilities_capabilities']);
        //PublishPress Authors
        add_filter('cme_plugin_capabilities', [$this, 'cme_multiple_authors_capabilities']);
        //Gravity Forms
        add_filter('cme_plugin_capabilities', [$this, 'cme_gravityforms_capabilities']);
        //WPML
        add_filter('cme_plugin_capabilities', [$this, 'cme_wpml_capabilities']);
        //WS Form
        add_filter('cme_plugin_capabilities', [$this, 'cme_wsform_capabilities']);
        //TaxoPress
        add_filter('cme_plugin_capabilities', [$this, 'cme_taxopress_capabilities']);
        //WooCommerce`
        add_filter('cme_plugin_capabilities', [$this, 'cme_woocommerce_capabilities']);
        //Echo Knowledge Base
        add_filter('cme_plugin_capabilities', [$this, 'cme_echo_knowledge_base_capabilities']);
        // Yoast SEO
        add_filter('cme_plugin_capabilities', [$this, 'cme_yoast_seo_capabilities']);
    }

    /**
     * PublishPress Capabilities
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_publishpress_capabilities_capabilities($plugin_caps)
    {

        $plugin_caps['PublishPress Capabilities'] = apply_filters('cme_publishpress_capabilities_capabilities', []);

        return $plugin_caps;
    }

    /**
     * PublishPress Authors
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_multiple_authors_capabilities($plugin_caps)
    {

        if (defined('PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION')) {
            if ($_caps = apply_filters('cme_multiple_authors_capabilities', [])) {
                $plugin_caps['PublishPress Authors'] = $_caps;
            }
        }

        return $plugin_caps;
    }

    /**
     * Gravity Forms
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_gravityforms_capabilities($plugin_caps)
    {
        if (defined('GF_PLUGIN_DIR_PATH')) {
            $plugin_caps['Gravity Forms'] = apply_filters(
                'cme_gravityforms_capabilities',
                [
                    'gravityforms_create_form',
                    'gravityforms_delete_forms',
                    'gravityforms_edit_forms',
                    'gravityforms_preview_forms',
                    'gravityforms_view_entries',
                    'gravityforms_edit_entries',
                    'gravityforms_delete_entries',
                    'gravityforms_view_entry_notes',
                    'gravityforms_edit_entry_notes',
                    'gravityforms_export_entries',
                    'gravityforms_view_settings',
                    'gravityforms_edit_settings',
                    'gravityforms_view_updates',
                    'gravityforms_view_addons',
                    'gravityforms_system_status',
                    'gravityforms_uninstall',
                    'gravityforms_logging',
                    'gravityforms_api_settings',
                ]
            );
        }

        return $plugin_caps;
    }

    /**
     * WPML
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_wpml_capabilities($plugin_caps)
    {

        if (defined('WPML_PLUGIN_FILE')) {
            $plugin_caps['WPML'] = apply_filters(
                'cme_wpml_capabilities',
                [
                    'wpml_manage_translation_management',
                    'wpml_manage_languages',
                    'wpml_manage_translation_options',
                    'wpml_manage_troubleshooting',
                    'wpml_manage_taxonomy_translation',
                    'wpml_manage_wp_menus_sync',
                    'wpml_manage_translation_analytics',
                    'wpml_manage_string_translation',
                    'wpml_manage_sticky_links',
                    'wpml_manage_navigation',
                    'wpml_manage_theme_and_plugin_localization',
                    'wpml_manage_media_translation',
                    'wpml_manage_support',
                    'wpml_manage_woocommerce_multilingual',
                    'wpml_operate_woocommerce_multilingual',
                ]
            );
        }

        return $plugin_caps;
    }

    /**
     * WS Form
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_wsform_capabilities($plugin_caps)
    {
        if (defined('WS_FORM_VERSION')) {
            $plugin_caps['WS Form'] = apply_filters(
                'cme_wsform_capabilities',
                [
                    'create_form',
                    'delete_form',
                    'edit_form',
                    'export_form',
                    'import_form',
                    'publish_form',
                    'read_form',
                    'delete_submission',
                    'edit_submission',
                    'export_submission',
                    'read_submission',
                    'create_form_style',
                    'delete_form_style',
                    'edit_form_style',
                    'export_form_style',
                    'import_form_style',
                    'publish_form_style',
                    'read_form_style',
                    'manage_options_wsform',
                ]
            );
        }

        return $plugin_caps;
    }

    /**
     * TaxoPress
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_taxopress_capabilities($plugin_caps)
    {

        if (defined('STAGS_VERSION')) {
            $plugin_caps['TaxoPress'] = apply_filters(
                'cme_taxopress_capabilities',
                [
                    'simple_tags',
                    'admin_simple_tags'
                ]
            );
        }

        return $plugin_caps;
    }

    /**
     * Echo Knowledge Base
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_echo_knowledge_base_capabilities($plugin_caps)
    {

        if (defined('EPKB_PLUGIN_NAME')) {
            $plugin_caps['Echo Knowledge Base'] = apply_filters(
                'cme_echo_knowledge_base_capabilities',
                [
                    'admin_eckb_access_manager_page',
                    'admin_eckb_access_crud_users',
                    'admin_eckb_access_frontend_editor_write',
                    'admin_eckb_access_search_analytics_read',
                    'admin_eckb_access_order_articles_write',
                    'admin_eckb_access_need_help_read',
                    'admin_eckb_access_addons_news_read'
                ]
            );
        }

        return $plugin_caps;
    }

    /**
     * Yoast SEO
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_yoast_seo_capabilities($plugin_caps) {
        if (defined('WPSEO_FILE')) {
            $plugin_caps['Yoast SEO'] = apply_filters('cme_yoast_seo_capabilities',
                [
                        'view_site_health_checks',
                        'wpseo_bulk_edit',
                        'wpseo_edit_advanced_metadata',
                        'wpseo_manage_options'

                ]
            );
        }

        return $plugin_caps;
    }

    /**
     * WooCommerce
     *
     * @param array $plugin_caps
     *
     * @return array
     */
    public function cme_woocommerce_capabilities($plugin_caps)
    {

        if (defined('WC_PLUGIN_FILE') && class_exists('WC_Install')) {
            $woocommerce_caps = array_merge(...array_values(\WC_Install::get_core_capabilities()));
            sort($woocommerce_caps);
           // https://github.com/woocommerce/woocommerce/blob/f513ce7c253b0b05511e1eb592b9345aaf084f9a/plugins/woocommerce/includes/class-wc-install.php#L1788C3-L1814C4
            $plugin_caps['WooCommerce'] = apply_filters(
                'cme_woocommerce_capabilities',
                $woocommerce_caps
            );
        }

        return $plugin_caps;
    }
}
