<?php

namespace PublishPress\Capabilities;

class PP_Capabilities_Frontend_Features_UI
{
    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new PP_Capabilities_Frontend_Features_UI();
        }

        return self::$instance;
    }

    public function __construct()
    {
        if (is_admin() && pp_capabilities_feature_enabled('frontend-features')) {
            //load settings page scripts
            self::loadFeaturesAdminAssets();
            //add frontend features form
            add_action('pp_capabilities_frontend_features_frontendelements_before_subsection_tr', [$this, 'fontendElementsForm']);
        }
    }

    /**
     * Add News form
     *
     */
    public function fontendElementsForm()
    {
        $pro_icon = ! defined('PUBLISHPRESS_CAPS_PRO_VERSION') ? '<span class="dashicons dashicons-lock"></span> &nbsp;' : '';
        ?>
        <tr class="ppc-menu-row child-menu frontendelements">
            <td colspan="2" class="form-td">
                <table class="frontend-features-form simple-form frontendelements-form">
                    <tr class="ppc-menu-row parent-menu">
                        <td colspan="2">
                            <p class="cme-subtext">
                                <?php esc_html_e('This feature allows you to modify the site frontend by hiding IDs or classes, adding CSS styles, or adding body classes.', 'capability-manager-enhanced'); ?>
                            </p>
                            <p class="editing-custom-item">
                                <strong><?php esc_html_e('Editing:', 'capability-manager-enhanced'); ?></strong>
                                <span class="title"></span>
                            </p>
                        </td>
                    </tr>

                    <tr class="field-row">
                        <th scope="row">
                            <?php esc_html_e('Title:', 'capability-manager-enhanced'); ?>
                            <font color="red">*</font>
                        </th>
                        <td>
                            <input class="frontend-element-new-name frontent-form-field frontendelements-form-label" type="text" /><br />
                            <span class="description">
                                <?php esc_html_e('This will only show here in the WordPress admin area.', 'capability-manager-enhanced'); ?>
                            </span>
                        </td>
                    </tr>

                    <tr class="field-row">
                        <th scope="row">
                            <?php esc_html_e('Features:', 'capability-manager-enhanced'); ?>
                            <font color="red">*</font>
                        </th>
                        <td>
                            <div class="frontend-element-toggle">
                                <div class="ppc-button-group"
                                    data-hide-selector=".frontend-features-toggle">
                                    <label class="element-classes selected"><input type="radio" name="frontend_feature_active" value=".frontend-element-classes" checked><?php esc_html_e('Hide IDs or Classes', 'capability-manager-enhanced'); ?></label>
                                    <label class="custom-css"><input type="radio" name="frontend_feature_active" value=".frontend-element-styles"> <?php esc_html_e('Add Custom CSS', 'capability-manager-enhanced'); ?></label>
                                    <label class="body-class"><input type="radio" name="frontend_feature_active" value=".frontend-element-bodyclass"> <?php esc_html_e('Add Body Class', 'capability-manager-enhanced'); ?></label>
                                </div>
                            </div>
                            <div class="frontend-element-classes frontend-features-toggle">
                                <textarea class="frontend-element-new-element frontent-form-field frontendelements-form-element"></textarea><br />
                                <span class="description">
                                    <?php esc_html_e('Enter IDs or classes to hide. Separate multiple values with a comma (.custom-item-one, .custom-item-two, #new-item-id).', 'capability-manager-enhanced'); ?>
                                </span>
                            </div>
                            <div class="frontend-element-styles frontend-features-toggle hidden-element">

                                <div class="code-mirror-before"><div><?php echo htmlentities('<style type="text/css">'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div>
                                <textarea class="frontend-element-new-styles ppc-code-editor-page-css frontendelements-form-styles"></textarea>
                                <div class="code-mirror-after"><div><?php echo htmlentities('</style>'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div></div>
                                <br />
                                <div class="css-new-element-clear"></div>
                                <span class="description">
                                    <?php esc_html_e('Enter custom CSS to be added to frontend pages. Examples: .custom-style-1 { color: red; } #custom-header { background: red; } ', 'capability-manager-enhanced'); ?>
                                </span>
                            </div>
                            <div class="frontend-element-bodyclass frontend-features-toggle hidden-element">
                                <textarea class="frontend-new-element-class frontendelements-form-field frontendelements-form-bodyclass"></textarea><br />
                                <ul class="pp-capabilities-description description">
                                    <li><?php esc_html_e('Enter classes to add the body HTML. Do not include the . before the HTML.', 'capability-manager-enhanced'); ?></li>
                                    <li><?php esc_html_e('Separate multiple values with a space (custom-style-one custom-style-two).', 'capability-manager-enhanced'); ?></li>
                                    <li><?php esc_html_e('You can add the CSS for your classes by clicking "Add Custom CSS".', 'capability-manager-enhanced'); ?></li>
                                    <li style="visibility: hidden;margin-top: -50px;"><?php esc_html_e('Enter classes that should be added to the body HTML. Separate multiple values with a space (custom-item-one custom-item-two).', 'capability-manager-enhanced'); ?></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <tr class="field-row">
                        <th scope="row">
                            <?php esc_html_e('Load on page types:', 'capability-manager-enhanced'); ?>
                            <font color="red">*</font>
                        </th>
                        <td>
                            <div class="frontend-element-toggle">
                                <div class="ppc-button-group"
                                    data-hide-selector=".frontend-features-pages">
                                    <label class="whole-site selected"><input type="radio" name="frontend_feature_pages" value=".frontend-element-whole-site" checked><?php esc_html_e('Whole Site', 'capability-manager-enhanced'); ?></label>
                                    <label class="other-pages"><input type="radio" name="frontend_feature_pages" value=".frontend-element-other-pages"> <?php echo $pro_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e('Selected Pages', 'capability-manager-enhanced'); ?></label>
                                </div>
                            </div>
                            <div class="frontend-element-other-pages frontend-features-pages hidden-element">
                                <?php do_action('pp_capabilities_frontend_features_pages'); ?>
                            </div>
                            <div class="frontend-element-whole-site frontend-features-pages ppc-button-group-border">
                                <span class="description">
                                    <?php esc_html_e('This feature will be added to all pages.', 'capability-manager-enhanced'); ?>
                                </span>
                            </div>
                        </td>
                    </tr>

                    <tr class="field-row frontend-element-other-pages frontend-features-pages hidden-element">
                        <th scope="row">
                            <?php esc_html_e('Add post metabox:', 'capability-manager-enhanced'); ?>
                        </th>
                        <td>
                            <?php do_action('pp_capabilities_frontend_features_metabox_post_types'); ?>
                        </td>
                    </tr>

                    <tr class="field-row">
                        <td colspan="2">
                            <input type="hidden" class="custom-edit-id" value="" />
                            <input class="frontend-element-form-nonce" type="hidden"
                                value="<?php echo esc_attr(wp_create_nonce('frontend-element-nonce')); ?>" />
                            <div class="custom-item-submit-buttons">
                                <div class="cancel-custom-item-edit button button-secondary"
                                    data-section="frontendelements">
                                    <?php esc_html_e('Cancel Edit', 'capability-manager-enhanced'); ?></div>

                                <button type="button" class="submit-button frontend-element-form-submit button button-secondary"
                                    data-required="<?php esc_attr_e('All fields are required.', 'capability-manager-enhanced'); ?>"
                                    data-add="<?php esc_attr_e('Add New', 'capability-manager-enhanced'); ?>"
                                    data-edit="<?php esc_attr_e('Save Edit', 'capability-manager-enhanced'); ?>"
                                    data-section="frontendelements">
                                    <?php esc_html_e('Add New', 'capability-manager-enhanced'); ?></button>
                            </div>
                            <span class="ppc-feature-post-loader spinner"></span>
                            <div class="ppc-post-features-note"></div>
                            </th>
                    </tr>

                </table>
            </td>
        </tr>
    <?php
    }

    /**
     * Load frontend element tr
     *
     * @param array $args
     * @param boolean $echo
     * @return string
     */
    public static function do_pp_capabilities_frontend_features_frontendelements_tr($args, $echo = true)
    {
        $disabled_frontend_items = $args['disabled_frontend_items'];
        $section_array           = $args['section_array'];
        $section_slug            = $args['section_slug'];
        $section_id              = $args['section_id'];
        $item_name               = $section_array['label'];
        $restrict_value          = $section_id;
        $additional_class        = isset($args['additional_class']) ? $args['additional_class'] : '';
        $additional_class       .= ' custom-item-' . $section_id;
        $section_elements        = $section_array['elements'];
        $element_selector        = '';
        $element_styles          = '';
        $element_bodyclass       = '';
        if (isset($section_elements['selector'])) {
            $element_selector = $section_elements['selector'];
        }
        if (isset($section_elements['styles'])) {
            $element_styles = $section_elements['styles'];
        }
        if (isset($section_elements['bodyclass'])) {
            $element_bodyclass = $section_elements['bodyclass'];
        }

        ob_start(); ?>
        <tr
            class="custom-item-row ppc-menu-row child-menu <?php echo esc_attr($section_slug . ' ' . $additional_class); ?>">
            <td class="restrict-column ppc-menu-checkbox">
                <input id="check-item-<?php echo esc_attr($section_id); ?>"
                    class="check-item" type="checkbox" name="capsman_disabled_frontend_features[]"
                    value="<?php echo esc_attr($restrict_value); ?>"
                    <?php echo (in_array($restrict_value, $disabled_frontend_items)) ? 'checked' : ''; ?>/>
            </td>
            <td class="menu-column ppc-menu-item primay-td">

                <label for="check-item-<?php echo esc_attr($section_id); ?>">
                    <span
                        class="content-title-column menu-item-link<?php echo (in_array($restrict_value, $disabled_frontend_items)) ? ' restricted' : ''; ?>">
                        <strong>
                            <i class="dashicons dashicons-arrow-right"></i>
                            <?php echo esc_html($section_array['label']); ?>
                        </strong>
                    </span>
                </label>
                <div class="custom-item-output">
                    <?php if (!empty($element_selector)) : ?>
                        <strong><?php esc_html_e('Hidden IDs or Classes', 'capability-manager-enhanced'); ?>:</strong> <pre
                            class="custom-item-display frontend-selector-output"><?php echo esc_html($element_selector); ?>
                        </pre>
                    <?php endif; ?>
                    <?php if (!empty($element_bodyclass)) : ?>
                        <strong><?php esc_html_e('Body Class', 'capability-manager-enhanced'); ?>:</strong> <pre
                            class="custom-item-display frontend-boddyclass-output"><?php echo esc_html($element_bodyclass); ?>
                        </pre>
                    <?php endif; ?>
                    <?php if (!empty($element_styles)) : ?>
                        <strong><?php esc_html_e('Custom CSS', 'capability-manager-enhanced'); ?>:</strong> <pre
                            class="custom-item-display frontend-styles-output"><?php echo esc_html($element_styles); ?>
                        </pre>
                        <div class="css-new-element-update"></div>
                        <div class="ppc-code-editor-refresh-editor"></div>
                    <?php endif; ?>
                    <div>
                        <p class="frontend-feature-entry-pages">
                            <?php if (!empty($section_array['pages'])) : ?>
                            <strong><?php esc_html_e('Pages', 'capability-manager-enhanced'); ?>:</strong>
                            <?php echo esc_html(ucwords(str_replace(['-', '_'], ' ', join(', ', (array) $section_array['pages'])))); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="frontend-feature-entry-post-types">
                            <?php if (!empty($section_array['post_types'])) : ?>
                            <strong><?php esc_html_e('Post Type Metabox', 'capability-manager-enhanced'); ?>:</strong>
                            <?php echo esc_html(ucwords(str_replace(['-', '_'], ' ', join(', ', (array) $section_array['post_types'])))); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </td>
            <td>
                <div class="button view-custom-item"><?php esc_html_e('View'); ?></div>
            </td>
            <td>
                <div class="button edit-custom-item"
                    data-section="<?php echo esc_attr($section_slug); ?>"
                    data-label="<?php echo esc_attr($section_array['label']); ?>"
                    data-selector="<?php echo esc_attr($element_selector); ?>"
                    data-bodyclass="<?php echo esc_attr($element_bodyclass); ?>"
                    data-pages="<?php echo esc_attr(join(', ', (array) $section_array['pages'])); ?>"
                    data-post-types="<?php echo esc_attr(join(', ', (array) $section_array['post_types'])); ?>"
                    data-id="<?php echo esc_attr($section_id); ?>">
                <?php esc_html_e('Edit'); ?>
                </div>
            </td>
            <td>
                <div
                    class="button frontend-features-delete-item frontend-feature-red"
                    data-section="<?php echo esc_attr($section_slug); ?>"
                    data-id="<?php echo esc_attr($section_id); ?>"
                    data-delete-nonce="<?php echo esc_attr(wp_create_nonce('frontend-delete' . $section_id .'-nonce')); ?>">
                    <?php esc_html_e('Delete'); ?>
                </div>
            </td>
        </tr>
        <?php
        $return = ob_get_clean();

        if ($echo) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $return;
        } else {
            return $return;
        }
    }

    /**
     * Enqueue admin required css/js
     *
     * @return void
     */
    public static function loadFeaturesAdminAssets()
    {
        //add code editor
        wp_enqueue_code_editor(array('type' => 'text/html'));
        //initialize code editor
        wp_add_inline_script(
            'code-editor',
            ' (function($){
                $(function(){
                    if( $(".ppc-code-editor-page-css").length ) {
                        var editorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {};
                        editorSettings.codemirror = _.extend(
                            {},
                            editorSettings.codemirror,
                            {
                                indentUnit: 2,
                                tabSize: 2,
                                mode: "css",
                                autoRefresh:true,
                            }
                        );
                        css_editor = wp.codeEditor.initialize( $(".ppc-code-editor-page-css"), editorSettings );
                        $(document).on("keyup", ".CodeMirror-code", function(){
                            css_editor.codemirror.save();
                            $(".ppc-code-editor-page-css").val(css_editor.codemirror.getValue());
                            $(".ppc-code-editor-page-css").trigger("change");
                        });
                        $(document).on("click", ".css-new-element-clear", function(){
                            css_editor.codemirror.setValue("");
                            $(".ppc-code-editor-page-css").val("");
                            $(".ppc-code-editor-page-css").trigger("change");
                        });
                        $(document).on("click", ".css-new-element-update", function(){
                            var element_value = $(this).closest(".custom-item-row").find(".frontend-styles-output").html();
                            css_editor.codemirror.setValue(element_value);
                            $(".ppc-code-editor-page-css").val(element_value);
                            $(".ppc-code-editor-page-css").trigger("change");
                        });
                        $(document).on("click", ".ppc-code-editor-refresh-editor", function(){
                            css_editor.codemirror.refresh();
                        });
                    }
                });
             })(jQuery);'
        );

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