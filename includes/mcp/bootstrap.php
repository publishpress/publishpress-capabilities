<?php

/**
 * Initialize Capabilities abilities and interface metadata capture.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class Bootstrap
{
    /**
     * @var self|null
     */
    private static $instance = null;

    /**
     * Retrieve the integration instance without registering duplicate hooks.
     *
     * @return self
     * @since 2.54.0
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register hooks independently of Hub and outside admin-only bootstrap.
     *
     * @since 2.54.0
     */
    private function __construct()
    {
        add_action('wp_abilities_api_categories_init', [$this, 'registerCategory']);
        add_action('wp_abilities_api_init', [$this, 'registerAbilities']);
        add_action('admin_menu', [MenuDiscovery::class, 'capture'], PHP_INT_MAX - 99);
        add_action('wp_dashboard_setup', [InterfaceCapture::class, 'dashboard'], 98);
        add_action('admin_bar_menu', [InterfaceCapture::class, 'toolbar'], PHP_INT_MAX);
    }

    /**
     * Register the shared category only when another plugin has not registered it.
     *
     * @return void
     * @since 2.54.0
     */
    public function registerCategory(): void
    {
        if (!function_exists('wp_register_ability_category') || wp_has_ability_category('publishpress')) {
            return;
        }

        wp_register_ability_category('publishpress', [
            'label' => __('PublishPress', 'capability-manager-enhanced'),
            'description' => __('Actions provided by PublishPress plugins.', 'capability-manager-enhanced'),
        ]);
    }

    /**
     * Register the plugin's typed actions through the existing registries.
     *
     * @return void
     * @since 2.54.0
     */
    public function registerAbilities(): void
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        RoleReadAbilities::registerAbilities();
        AbilityRegistry::registerAll();
    }
}
