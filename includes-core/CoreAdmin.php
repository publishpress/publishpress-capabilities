<?php
namespace PublishPress\Capabilities;

class CoreAdmin {
    function __construct() {

        if (is_admin()) {

            require_once PUBLISHPRESS_CAPS_ABSPATH . '/lib/vendor/publishpress/wordpress-version-notices/includes.php';

            add_filter(\PPVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER, function ($settings) {
                $settings['capabilities'] = [
                    'message' => __("You're using PublishPress Capabilities Free. The Pro version has more features and support. %sUpgrade to Pro%s", 'capability-manager-enhanced'),
                    'link'    => 'https://publishpress.com/links/capabilities-banner',
                    'screens' => [
                        ['base' => 'capabilities_page_pp-capabilities-dashboard'],
                        ['base' => 'capabilities_page_pp-capabilities'],
                        ['base' => 'capabilities_page_pp-capabilities-roles'],
                        ['base' => 'capabilities_page_pp-capabilities-editor-features'],
                        ['base' => 'capabilities_page_pp-capabilities-admin-features'],
                        ['base' => 'capabilities_page_pp-capabilities-profile-features'],
                        ['base' => 'capabilities_page_pp-capabilities-frontend-features'],
                        ['base' => 'capabilities_page_pp-capabilities-nav-menus'],
                        ['base' => 'capabilities_page_pp-capabilities-backup'],
                        ['base' => 'capabilities_page_pp-capabilities-settings'],
                        //all menu could become a top menu page if main top menu is disabled/they're the only menu
                        ['base' => 'toplevel_page_pp-capabilities-dashboard'],
                        ['base' => 'toplevel_page_pp-capabilities'],
                        ['base' => 'toplevel_page_pp-capabilities-roles'],
                        ['base' => 'toplevel_page_pp-capabilities-editor-features'],
                        ['base' => 'toplevel_page_pp-capabilities-admin-features'],
                        ['base' => 'toplevel_page_pp-capabilities-profile-features'],
                        ['base' => 'toplevel_page_pp-capabilities-nav-menus'],
                        ['base' => 'toplevel_page_pp-capabilities-backup'],
                        ['base' => 'toplevel_page_pp-capabilities-settings'],
                    ]
                ];

                return $settings;
            });
            add_filter(
                \PPVersionNotices\Module\MenuLink\Module::SETTINGS_FILTER,
                function ($settings) {
                    $settings['publishpress-capabilities'] = [
                        'parent' => 'pp-capabilities-dashboard',
                        'label'  => __('Upgrade to Pro', 'capability-manager-enhanced'),
                        'link'   => 'https://publishpress.com/links/capabilities-menu',
                    ];

                    return $settings;
            });
        }

        add_filter('pp_capabilities_sub_menu_lists', [$this, 'actCapabilitiesSubmenus'], 10, 2);

        //Editor feature metaboxes promo
        add_action('pp_capabilities_features_gutenberg_after_table_tr', [$this, 'metaboxesPromo']);
        add_action('pp_capabilities_features_classic_after_table_tr', [$this, 'metaboxesPromo']);

        //Admin features promo
        add_filter('pp_capabilities_admin_features_elements', [$this, 'adminFeaturesElements'], 50);
        add_filter('pp_capabilities_admin_features_icons', [$this, 'adminFeatureIcons']);
        add_filter('pp_capabilities_admin_features_titles', [$this, 'adminFeatureTitles']);
        add_action('pp_capabilities_admin_features_blockedbyurl_before_subsection_tr', [$this, 'adminFeaturePromo']);
        add_action('pp_capabilities_admin_features_hidecsselement_before_subsection_tr', [$this, 'adminFeaturePromo']);

        //Frontend features pages promo
        add_action('pp_capabilities_frontend_features_pages', [$this, 'frontendFeaturesPagesPromo']);

        //Frontend features promo
        add_action('pp_capabilities_frontend_features_metabox_post_types', [$this, 'frontendFeaturesPromo']);
        // Add frontend feature promo to dashboard
        add_filter('pp_capabilities_dashboard_features', [$this, 'addFeaturesPromotoDashboard']);
    }

    function actCapabilitiesSubmenus($sub_menu_pages, $cme_fakefunc) {
        if (!$cme_fakefunc) {
            //add admin menu after profile features menu
            $profile_features_offset = array_search('profile-features', array_keys($sub_menu_pages));
            $profile_features_menu   = [];
            $profile_features_menu['admin-menus'] = [
                'title'             => __('Admin Menus', 'capability-manager-enhanced'),
                'capabilities'      => (is_multisite() && is_super_admin()) ? 'read' : 'manage_capabilities_admin_menus',
                'page'              => 'pp-capabilities-admin-menus',
                'callback'          => [$this, 'AdminMenusPromo'],
                'dashboard_control' => true,
            ];

            $sub_menu_pages = array_merge(
                array_slice($sub_menu_pages, 0, $profile_features_offset),
                $profile_features_menu,
                array_slice($sub_menu_pages, $profile_features_offset, null)
            );
        }

        return $sub_menu_pages;
    }

    function AdminMenusPromo() {
        wp_enqueue_style('pp-capabilities-admin-core', plugin_dir_url(CME_FILE) . 'includes-core/admin-core.css', [], PUBLISHPRESS_CAPS_VERSION, 'all');
        include (dirname(__FILE__) . '/admin-menus-promo.php');
    }

    function metaboxesPromo(){
        wp_enqueue_style('pp-capabilities-admin-core', plugin_dir_url(CME_FILE) . 'includes-core/admin-core.css', [], PUBLISHPRESS_CAPS_VERSION, 'all');
        include (dirname(__FILE__) . '/editor-features-promo.php');
    }
    function adminFeaturesElements($elements) {
        $elements['Hide CSS Element'] = [];
        $elements['Blocked by URL'] = [];

        return $elements;
    }

    function adminFeatureIcons($icons) {

        $icons['hidecsselement']    = 'hidden';
        $icons['blockedbyurl']      = 'admin-links';

        return $icons;
    }

    function adminFeatureTitles($titles) {

        $titles['Hide CSS Element'] = esc_html__('Hide CSS Elements', 'capability-manager-enhanced');
        $titles['Blocked by URL']   = __('Block by URL', 'capability-manager-enhanced');

        return $titles;
    }

    function adminFeaturePromo(){
        wp_enqueue_style('pp-capabilities-admin-core', plugin_dir_url(CME_FILE) . 'includes-core/admin-core.css', [], PUBLISHPRESS_CAPS_VERSION, 'all');
        include (dirname(__FILE__) . '/admin-features-promo.php');
    }

    function frontendFeaturesPromo(){
        wp_enqueue_style('pp-capabilities-admin-core', plugin_dir_url(CME_FILE) . 'includes-core/admin-core.css', [], PUBLISHPRESS_CAPS_VERSION, 'all');
        include (dirname(__FILE__) . '/frontend-features-promo.php');
    }

    function addFeaturesPromotoDashboard($features) {

        $features['admin-menus'] = [
            'promo'        => 1,
            'label'        => esc_html__('Admin Menus', 'capability-manager-enhanced'),
            'description'  => esc_html__('Admin Menus allows you to edit the admin menu links and control who has access.', 'capability-manager-enhanced'),
        ];

        return $features;
    }

    function frontendFeaturesPagesPromo(){
        ?>
        <div class="pp-promo-overlay-row div-pp-promo-blur">
            <select class="chosen-cpt-select frontendelements-form-pages"
                data-placeholder="<?php esc_attr_e('Select pages...', 'capability-manager-enhanced'); ?>" multiple>
                <option value=""></option>
            </select>
            <br />
            <small>
                <?php esc_html_e('You can select page types where this element will be added.', 'capability-manager-enhanced'); ?>
            </small>
            <input type="text" style="visibility: hidden;" /> <!-- using this to balance the space needed due to field size -->
        </div>
        <?php
    }
}
