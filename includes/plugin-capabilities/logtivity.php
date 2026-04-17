<?php

/**
 * PublishPress Capabilities [Free]
 *
 * Capabilities filters for the plugin Logtivity.
 *
 * Generated with Capabilities Extractor
 */

if (!defined('ABSPATH')) {
    exit('No direct script access allowed');
}

add_filter('cme_plugin_capabilities', function ($pluginCaps) {
    $pluginCaps['Logtivity'] = [
        'view_logs',
        'view_log_settings'
    ];

    return $pluginCaps;
});