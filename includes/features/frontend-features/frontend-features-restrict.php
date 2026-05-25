<?php

namespace PublishPress\Capabilities;

use PublishPress\Capabilities\PP_Capabilities_Frontend_Features_Data;

require_once(dirname(CME_FILE) . '/includes/features/frontend-features/frontend-features-data.php');

class PP_Capabilities_Frontend_Features_Restrict
{
    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new PP_Capabilities_Frontend_Features_Restrict();
        }

        return self::$instance;
    }

    public function __construct()
    {
        if (!is_admin() && pp_capabilities_feature_enabled('frontend-features')) {
            //set up frontend restriction global data to be used on pages
            add_action('init', [$this, 'setFrontendFeaturesRestrictionGlobal']);
            //add frontend features body class
            add_filter('body_class', [$this, 'setFrontendBodyClass']);
            //add frontend features header styles css
            add_action('wp_head', [$this, 'setFrontendStyles']);
        }
    }

    /**
     * Set up frontend restriction global data to be used on pages.
     */
    public function setFrontendFeaturesRestrictionGlobal()
    {
        global $ppc_ff_page_restriction_data;

        if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            return;
        }

        $cache_key = 'ppc_ff_page_restriction_cache';

        /**
         * Filter if frontend feature should use cache data.
         * Check https://github.com/publishpress/PublishPress-Capabilities/issues/1152
         * @var mixed
         */
        $use_cache_data = apply_filters('pp_capabilities_frontend_feature_cache', true);

        $page_restriction_data = $use_cache_data ? wp_cache_get($cache_key, 'data') : false;

        if ($page_restriction_data && is_array($page_restriction_data)) {
            //use cache data
            $ppc_ff_page_restriction_data = $page_restriction_data;
        } else {
            //set data placeholder
            $page_restriction_data = [
                'custom_styles'     => [],
                'frontend_elements' => [],
                'body_class'        => [],
                'element_data'      => []
            ];

            $disabled_features = !empty(get_option('capsman_disabled_frontend_features')) ? (array)get_option('capsman_disabled_frontend_features') : [];

            // get user roles
            if (is_user_logged_in()) {
                $user_roles = wp_get_current_user()->roles;
                //add logged in user items
                $user_roles[] = 'ppc_users';
            } else {
                $user_roles = ['ppc_guest'];
            }

            // get role restricted elements
            $role_disabled_features = [];
            foreach ($user_roles as $role) {
                if (!empty($disabled_features[$role])) {
                    $role_disabled_features = array_merge($role_disabled_features, $disabled_features[$role]);
                }
            }
            $role_disabled_features = array_unique($role_disabled_features);

            // populate global data if there's restricted item for role
            if (!empty($role_disabled_features)) {
                $frontend_element_data     = PP_Capabilities_Frontend_Features_Data::getFrontendElements();
                $ppc_disabled_customstyles = [];
                $ppc_disabled_frontendelements = [];
                $ppc_disabled_bodyclass = [];
                foreach ($role_disabled_features as $role_disabled_feature) {
                    if (isset($frontend_element_data[$role_disabled_feature])) {
                        $current_data_elements = $frontend_element_data[$role_disabled_feature]['elements'];
                        if (!empty(trim($current_data_elements['styles']))) {
                            $ppc_disabled_customstyles[] = $role_disabled_feature;
                        }
                        if (!empty(trim($current_data_elements['selector']))) {
                            $ppc_disabled_frontendelements[] = $role_disabled_feature;
                        }
                        if (!empty(trim($current_data_elements['bodyclass']))) {
                            $ppc_disabled_bodyclass[] = $role_disabled_feature;
                        }
                    }
                }
                $ppc_disabled_customstyles     = array_filter($ppc_disabled_customstyles);
                $ppc_disabled_frontendelements = array_filter($ppc_disabled_frontendelements);
                $ppc_disabled_bodyclass        = array_filter($ppc_disabled_bodyclass);

                $page_restriction_data['custom_styles']     = $ppc_disabled_customstyles;
                $page_restriction_data['frontend_elements'] = $ppc_disabled_frontendelements;
                $page_restriction_data['body_class']        = $ppc_disabled_bodyclass;

                $page_restriction_data['element_data']      = $frontend_element_data;

                do_action('ppc_frontend_features_role_raw_restricted_data', $role_disabled_features);
                do_action('ppc_frontend_features_role_restricted_data', $page_restriction_data);
            }

            //set global data
            $ppc_ff_page_restriction_data = $page_restriction_data;

            //cache result
            $expire_days = 7;
            $expire_days = apply_filters('ppc_ff_page_restriction_cache_expire_days', $expire_days);

            $expire = (int)$expire_days * DAY_IN_SECONDS;

            wp_cache_set($cache_key, $page_restriction_data, 'data', $expire); // phpcs:ignore WordPressVIPMinimum.Performance.LowExpiryCacheTime.CacheTimeUndetermined
        }
    }

    /**
     * Add frontend features header styles css
     */
    public function setFrontendStyles()
    {
        global $ppc_ff_page_restriction_data;

        $custom_css = '';

        // let handle custom styles
        if (is_array($ppc_ff_page_restriction_data)
            && isset($ppc_ff_page_restriction_data['custom_styles'])
            && !empty($ppc_ff_page_restriction_data['custom_styles'])
        ) {
            $custom_styles_elements = $ppc_ff_page_restriction_data['custom_styles'];
            $element_data           = $ppc_ff_page_restriction_data['element_data'];

            //check all element and add if enabled for current page
            foreach ($custom_styles_elements as $custom_styles_element) {
                $element_id = $custom_styles_element;
                if (isset($element_data[$element_id])) {
                    $current_element_data = $element_data[$element_id];
                    //add style if it's enabled for this page
                    if ($this->elementEnabledForCurrentPage($current_element_data['pages'], $element_id)) {
                        $custom_css .= $current_element_data['elements']['styles'];
                    }
                }
            }
        }

        // let handle frontend elements
        if (is_array($ppc_ff_page_restriction_data)
            && isset($ppc_ff_page_restriction_data['frontend_elements'])
            && !empty($ppc_ff_page_restriction_data['frontend_elements'])
        ) {
            $frontend_elements_elements = $ppc_ff_page_restriction_data['frontend_elements'];
            $element_data               = $ppc_ff_page_restriction_data['element_data'];

            //check all element and add if enabled for current page
            $frontend_element_selectors = [];
            foreach ($frontend_elements_elements as $frontend_elements_element) {
                $element_id = $frontend_elements_element;
                if (isset($element_data[$element_id])) {
                    $current_element_data = $element_data[$element_id];
                    //add element selector if it's enabled for this page
                    if ($this->elementEnabledForCurrentPage($current_element_data['pages'], $element_id)) {
                        $frontend_element_selectors[] = $current_element_data['elements']['selector'];
                    }
                }
            }
            if (!empty($frontend_element_selectors)) {
                $custom_css .= ' ';
                $custom_css .= join(', ', $frontend_element_selectors);
                $custom_css .= ' { display: none !important; }';
            }
        }

        if (!empty($custom_css)) :
            // Prevent breaking out of the style block while preserving valid CSS syntax.
            $safe_css = str_ireplace('</style', '<\/style', $custom_css);
            ?>

        <style>
            <?php echo $safe_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </style>

        <?php
        endif;
    }

    /**
     * Add frontend features body class
     *
     * @param array $classes Existing body classes.
     * @return array Amended body classes.
     */
    public function setFrontendBodyClass($classes)
    {
        global $ppc_ff_page_restriction_data;

        if (is_array($ppc_ff_page_restriction_data)
            && isset($ppc_ff_page_restriction_data['body_class'])
            && !empty($ppc_ff_page_restriction_data['body_class'])
        ) {
            $body_class_elements = $ppc_ff_page_restriction_data['body_class'];
            $element_data        = $ppc_ff_page_restriction_data['element_data'];
            $new_body_class      = [];

            //check all element and add class if enabled for current page
            foreach ($body_class_elements as $body_class_element) {
                $element_id = $body_class_element;
                if (isset($element_data[$element_id])) {
                    $current_element_data = $element_data[$element_id];
                    $current_element_data = $element_data[$element_id];
                    //add body class if it's enabled for this page
                    if ($this->elementEnabledForCurrentPage($current_element_data['pages'], $element_id)) {
                        $current_body_class = explode(' ', $current_element_data['elements']['bodyclass']);
                        $new_body_class = array_merge($new_body_class, $current_body_class);
                    }
                }
            }
            $classes = array_merge($classes, $new_body_class);
        }

        return $classes;
    }

    /**
     * Check if current page is in an element pages list
     *
     * @param array $enabled_pages
     *
     * @return bool
     */
    private function elementEnabledForCurrentPage($enabled_pages, $element_id)
    {
        global $post;

        if (in_array('whole_site', $enabled_pages)) {
            //all pages element
            return true;
        }

        if (in_array('homepage', $enabled_pages) && (is_front_page() || is_home())) {
            //homepage element
            return  true;
        }

        if (in_array('archive_pages', $enabled_pages) && is_archive()) {
            //archive element
            return true;
        }

        if (in_array('single_pages', $enabled_pages) && is_singular()) {
            //singular element
            return true;
        }

        if (is_singular() && is_object($post) && isset($post->ID)) {
            //single post
            $frontend_elements = (array) get_post_meta($post->ID, '_ppc_frontendelements', true);
            if (in_array($element_id, $frontend_elements)) {
                return true;
            }
        }

        return false;
    }
}
?>