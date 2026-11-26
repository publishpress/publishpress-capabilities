<?php

if (!defined('ABSPATH')) {
    exit('No direct script access allowed');
}

$activatedPlugins = (array) get_option('active_plugins', []);
if (is_multisite()) {
    $activatedPlugins = array_unique(array_merge(
        $activatedPlugins,
        array_keys((array) get_site_option('active_sitewide_plugins', []))
    ));
}

foreach ($activatedPlugins as $plugin) {
    $pluginName = explode('/', $plugin)[0];

    $filePath = __DIR__ . '/plugin-capabilities/' . $pluginName . '.php';
    if (is_readable($filePath)) {
        require_once $filePath;
    }
}
