<?php

namespace PublishPress\Capabilities;

use PublishPress\Capabilities\PP_Capabilities_Frontend_Features_Data;

require_once(dirname(CME_FILE) . '/includes/features/frontend-features/frontend-features-data.php');

class PP_Capabilities_Frontend_Features_Metaboxes
{
    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new PP_Capabilities_Frontend_Features_Metaboxes();
        }

        return self::$instance;
    }

    public function __construct()
    {
        if (is_admin() && pp_capabilities_feature_enabled('frontend-features') && defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            //add frontend features metabox
            add_action('add_meta_boxes', [$this, 'addFrontendFeaturesMetabox']);
            //save frontend metabox settings
            add_action("save_post", [$this, 'saveFrontendFeaturesData']);
        }
    }

    /**
     * Add frontend features metabox
     *
     * @return void
     */
    public function addFrontendFeaturesMetabox()
    {
        global $frontend_features_elements;

        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_frontend_features')) {
            return;
        }

        $frontend_features_elements = PP_Capabilities_Frontend_Features_Data::elementsLayout();

        $all_elements = call_user_func_array('array_merge', array_values($frontend_features_elements));

        if (!empty($all_elements)) {
            $post_types = array_column(array_values($all_elements), 'post_types');
            $post_types = array_unique(call_user_func_array('array_merge', $post_types));
            if (!empty($post_types)) {
                add_meta_box(
                    'ppc_frontend_metabox',
                    __('Frontend Features', 'capability-manager-enhanced'),
                    [$this, 'renderFrontendFeaturesMetabox'],
                    $post_types,
                    'side',
                    'high'
                );
            }
        }
    }

    /**
     * Render frontend features metaboxes
     *
     * @param \WP_Post $post
     * @return void
     */
    public function renderFrontendFeaturesMetabox(\WP_Post $post)
    {
        global $frontend_features_elements;

        self::loadFeaturesMetaboxAssets(); ?>
        <p>
            <?php esc_html_e('Choose Frontend Features that will apply to this post.', 'capability-manager-enhanced'); ?>
        </p>
        <?php
        foreach ($frontend_features_elements as $section_title => $section_elements) :
            if (is_array($section_elements) && !empty($section_elements)) :
            $section_slug  = '_ppc_' . strtolower(ppc_remove_non_alphanumeric_space_characters($section_title));

            $post_features = (array) get_post_meta($post->ID, $section_slug, true);
            ?>
            <div class="frontend-feature-metabox">
                <select name="<?php echo esc_attr($section_slug); ?>[]"
                    id="<?php echo esc_attr($section_slug); ?>"
                    class="chosen-cpt-select"
                    data-placeholder="<?php printf(esc_attr__('Select %1$s...', 'capability-manager-enhanced'), esc_html__($section_title)); ?>"
                    multiple>
                    <?php
                    foreach ($section_elements as $section_id => $section_array) :
                        if (!$section_id) {
                            continue;
                    }
                    ?>
                    <option value="<?php echo esc_attr($section_id); ?>" <?php selected(in_array($section_id, $post_features), true); ?>
                        >
                        <?php echo esc_html($section_array['label']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <br />
                <br />
            </div>
            <?php
            endif;
        endforeach;
        wp_nonce_field('ppc-frontend-features-metabox', 'ppc-frontend-features-metabox-nonce');
    }

    /**
     * Save Frontend Features data
     *
     * @param integer $post_id post id
     *
     * @return void
     */
    public function saveFrontendFeaturesData($post_id)
    {
        if ((!is_multisite() || !is_super_admin()) && !current_user_can('administrator') && !current_user_can('manage_capabilities_frontend_features')) {
            return;
        }

        if (empty($_POST['ppc-frontend-features-metabox-nonce'])
            || !wp_verify_nonce(sanitize_key($_POST['ppc-frontend-features-metabox-nonce']), 'ppc-frontend-features-metabox')) {
            return;
        }

        $frontend_elements = !empty($_POST['_ppc_frontendelements']) ? array_map('sanitize_text_field', $_POST['_ppc_frontendelements']) : [];

        update_post_meta($post_id, '_ppc_frontendelements', $frontend_elements);
    }

    /**
     * Enqueue admin required css/js
     *
     * @return void
     */
    public static function loadFeaturesMetaboxAssets()
    {
        //add chosen css
        wp_enqueue_style(
            'pp-capabilities-chosen-css',
            plugin_dir_url(CME_FILE) . 'common/libs/chosen-v1.8.7/chosen.css',
            false,
            CAPSMAN_VERSION
        );

        //add chosen js
        wp_enqueue_script(
            'pp-capabilities-chosen-js',
            plugin_dir_url(CME_FILE) . 'common/libs/chosen-v1.8.7/chosen.jquery.js',
            ['jquery'],
            CAPSMAN_VERSION
        );
        //initialize chosen select
        wp_add_inline_script(
            'pp-capabilities-chosen-js',
            ' (function($){
                $(function(){
                    if( $(".chosen-cpt-select").length ) {
                        $(".chosen-cpt-select").chosen({
                                                        "width": "100%",
                                                        "no_results_text": <?php echo wp_json_encode(__("No results match", "capability-manager-enhanced")); ?>
                          });
                    }
                });
             })(jQuery);'
        );
    }
}
?>