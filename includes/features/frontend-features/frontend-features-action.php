<?php

namespace PublishPress\Capabilities;

use PublishPress\Capabilities\PP_Capabilities_Frontend_Features_Data;
use PublishPress\Capabilities\PP_Capabilities_Frontend_Features_UI;

require_once(dirname(CME_FILE) . '/includes/features/frontend-features/frontend-features-data.php');
require_once(dirname(CME_FILE) . '/includes/features/frontend-features/frontend-features-ui.php');

class PP_Capabilities_Frontend_Features_Action
{
    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new PP_Capabilities_Frontend_Features_Action();
        }

        return self::$instance;
    }

    public function __construct()
    {
        if (is_admin() && pp_capabilities_feature_enabled('frontend-features')) {
            //ajax handler for frontend element new entry submission
            add_action('wp_ajax_ppc_submit_frontend_element_by_ajax', [$this, 'frontendElementNewEntryAjaxHandler']);
            //ajax handler for deleting frontend features item
            add_action('wp_ajax_ppc_delete_frontend_feature_item_by_ajax', [$this, 'frontendFeaturesDeleteItemAjaxHandler']);
        }
    }

    /**
     * Ajax handler for frontend element new entry submission
     *
     */
    public static function frontendElementNewEntryAjaxHandler()
    {
        $response['status']  = 'error';
        $response['message'] = esc_html__('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        $custom_label   = isset($_POST['custom_label']) ? sanitize_text_field(wp_unslash($_POST['custom_label'])) : '';
        $custom_element_selector = isset($_POST['custom_element_selector']) ? sanitize_textarea_field(wp_unslash($_POST['custom_element_selector'])) : '';
        $custom_element_styles = isset($_POST['custom_element_styles']) ? sanitize_textarea_field(wp_unslash($_POST['custom_element_styles'])) : '';
        $custom_element_bodyclass = isset($_POST['custom_element_bodyclass']) ? sanitize_textarea_field(wp_unslash($_POST['custom_element_bodyclass'])) : '';
        $element_pages  = (isset($_POST['element_pages']) && is_array($_POST['element_pages'])) ? array_map('sanitize_text_field', wp_unslash($_POST['element_pages'])) : [];
        $element_post_types  = (isset($_POST['element_post_types']) && is_array($_POST['element_post_types'])) ? array_map('sanitize_text_field', wp_unslash($_POST['element_post_types'])) : [];
        $security       = isset($_POST['security']) ? sanitize_key(wp_unslash($_POST['security'])) : '';
        $item_id        = isset($_POST['item_id']) ? sanitize_key(wp_unslash($_POST['item_id'])) : '';

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_frontend_features')) {
            $response['message'] = esc_html__('You do not have permission to manage frontend features.', 'capability-manager-enhanced');
        } elseif (!wp_verify_nonce($security, 'frontend-element-nonce')) {
            $response['message'] = esc_html__('Invalid action. Reload this page and try again.', 'capability-manager-enhanced');
        } elseif (empty(trim($custom_label)) || (empty(trim($custom_element_selector)) && empty(trim($custom_element_styles)) && empty(trim($custom_element_bodyclass)))) {
            $response['message'] = esc_html__('All fields are required.', 'capability-manager-enhanced');
        } elseif (empty($element_pages) && empty($element_post_types)) {
            $response['message'] = esc_html__('Load on page types is required.', 'capability-manager-enhanced');
        } else {
            $element_id       = (!empty($item_id)) ? $item_id : uniqid(true);
            $data             = PP_Capabilities_Frontend_Features_Data::getFrontendElements();

            $custom_element = [
                'selector'  => $custom_element_selector,
                'styles'    => $custom_element_styles,
                'bodyclass' => $custom_element_bodyclass,
            ];

            $data[$element_id] = [
                'element_id'    => $element_id,
                'label'         => $custom_label,
                'elements'      => $custom_element,
                'pages'         => $element_pages,
                'post_types'    => $element_post_types
            ];

            update_option('capsman_frontend_features_elements', $data);

            $function_args = [
                'disabled_frontend_items' => [$element_id],
                'section_array'           => $data[$element_id],
                'section_slug'            => 'frontendelements',
                'section_id'              => $element_id,
                'sn'                      => time(),
                'additional_class'        => 'ppc-menu-overlay-item'
            ];

            $response['content'] = PP_Capabilities_Frontend_Features_UI::do_pp_capabilities_frontend_features_frontendelements_tr($function_args, false);
            if ($item_id) {
                $response['message'] = esc_html__('Frontend element item updated. Save changes to enable for role.', 'capability-manager-enhanced');
            } else {
                $response['message'] = esc_html__('New frontend element added. Save changes to enable for role.', 'capability-manager-enhanced');
            }
            $response['status']  = 'success';
        }

        wp_send_json($response);
    }

    /**
     * Ajax handler for deleting frontend features item
     *
     */
    public static function frontendFeaturesDeleteItemAjaxHandler()
    {
        $response = [];
        $response['status']  = 'error';
        $response['message'] = esc_html__('An error occured!', 'capability-manager-enhanced');
        $response['content'] = '';

        $item_id      = isset($_POST['item_id']) ? sanitize_key($_POST['item_id']) : '';
        $security     = isset($_POST['security']) ? sanitize_key($_POST['security']) : '';

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_frontend_features')) {
            $response['message'] = esc_html__('You do not have permission to manage frontend features.', 'capability-manager-enhanced');
        } elseif (!wp_verify_nonce($security, 'frontend-delete' . $item_id .'-nonce')) {
            $response['message'] = esc_html__('Invalid action. Reload this page and try again.', 'capability-manager-enhanced');
        } elseif (empty(trim($item_id))) {
            $response['message'] = esc_html__('Invalid request!.', 'capability-manager-enhanced');
        } else {
            $item_deleted = false;

            $data       = PP_Capabilities_Frontend_Features_Data::getFrontendElements();
            $option_key = 'capsman_frontend_features_elements';

            if (is_array($data) && array_key_exists($item_id, $data)) {
                unset($data[$item_id]);
                update_option($option_key, $data);
                $item_deleted = true;
            }
            
            if ($item_deleted) {
                $response['status']  = 'success';
                $response['message'] = esc_html__('Selected item deleted successfully', 'capability-manager-enhanced');
            }
        }

        wp_send_json($response);
    }
}
