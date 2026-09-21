<?php
/*
 * PublishPress Capabilities [Free]
 * 
 * Process updates to plugin settings
 * 
 */

add_action('init', function() {
    if (check_admin_referer('pp-capabilities-settings') && current_user_can('manage_capabilities_settings')) {
        if (!empty($_POST['all_options'])) {
            foreach (array_map('sanitize_key', explode(',', sanitize_text_field(wp_unslash($_POST['all_options'])))) as $option_name) {
                foreach (['cme_', 'capsman', 'pp_capabilities', 'presspermit'] as $prefix) {
                    if (0 === strpos($option_name, $prefix)) {
                        $value = isset($_POST[$option_name]) ? wp_unslash($_POST[$option_name]) : '';
                        $value = is_array($value) ? map_deep($value, 'sanitize_text_field') : sanitize_text_field($value);
            
                        if (!is_array($value)) {
                            $value = trim($value);
                        }
                        
                        update_option($option_name, $value);
                    }
                }
            }
        }
    }
});
