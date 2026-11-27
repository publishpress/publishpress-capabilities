<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/hub-language-packs.php';

function pp_capabilities_init_translations()
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;

    if (class_exists(\PublishPress\BundledTranslations\BundledTranslations::class)) {
        $translations = new \PublishPress\BundledTranslations\BundledTranslations(
            'capability-manager-enhanced',
            dirname(__DIR__) . '/languages',
            dirname(__DIR__) . '/capsman-enhanced.php'
        );
        $translations->init();
    }
    $packs = new PP_Capabilities_Hub_Language_Packs(
        'capability-manager-enhanced',
        'publishpress-capabilities',
        'CAPSMAN_VERSION'
    );
    $packs->register();
}
