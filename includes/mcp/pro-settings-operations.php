<?php

/**
 * Typed Pro settings with the dependency and lock rules used by the Pro UI.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

use PublishPress\Capabilities\Pro;

class ProSettingsOperations
{
    /**
     * Load the side-effect-free Pro helper for REST requests as well as admin screens.
     *
     * @return bool
     * @since 2.54.0
     */
    public static function loadHelper(): bool
    {
        if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            return false;
        }
        if (!class_exists(Pro::class) && defined('PUBLISHPRESS_CAPS_PRO_ABSPATH')) {
            $file = PUBLISHPRESS_CAPS_PRO_ABSPATH . '/includes-pro/classes/Pro.php';
            if (is_readable($file)) {
                require_once $file;
            }
        }
        return class_exists(Pro::class);
    }

    /** @since 2.54.0 */
    public static function fields(): array
    {
        if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            return [];
        }
        return array_fill_keys([
            'cme_display_branding', 'cme_custom_status_control', 'cme_custom_status_postmeta_caps',
            'presspermit_privacy_statuses_enabled', 'presspermit_custom_privacy_edit_caps',
        ], ['type' => 'boolean']);
    }

    /**
     * Validate the whole patch before any settings are written.
     *
     * @param array $settings Requested settings patch.
     * @return true|\WP_Error
     * @since 2.54.0
     */
    public static function validate(array $settings)
    {
        $requested = array_intersect_key($settings, self::fields());
        unset($requested['cme_display_branding']);
        if (!$requested) {
            return true;
        }
        if (!self::loadHelper()) {
            return RoleOperations::error('pro_settings_unavailable', __('The Pro settings integration is not available.', 'capability-manager-enhanced'));
        }
        $statusFields = ['cme_custom_status_control', 'cme_custom_status_postmeta_caps'];
        if (array_intersect(array_keys($requested), $statusFields)) {
            if (!Pro::customStatusPermissionsAvailable() || Pro::presspermitStatusControlActive()) {
                return RoleOperations::error('status_settings_locked', __('Custom status settings are unavailable or controlled by the active status integration.', 'capability-manager-enhanced'));
            }
            if (!empty($settings['cme_custom_status_postmeta_caps'])) {
                $control = $settings['cme_custom_status_control'] ?? get_option('cme_custom_status_control');
                if (!$control || !Pro::customStatusPostMetaPermissions('', '', ['ignore_capabilities_option' => true])) {
                    return RoleOperations::error('status_meta_unavailable', __('Enable supported custom status control before status-specific editing capabilities.', 'capability-manager-enhanced'));
                }
            }
        }
        if (array_intersect(array_keys($requested), ['presspermit_privacy_statuses_enabled', 'presspermit_custom_privacy_edit_caps'])) {
            if (!defined('PRESSPERMIT_PRO_VERSION') || !Pro::presspermitStatusControlActive()) {
                return RoleOperations::error('privacy_settings_unavailable', __('Privacy settings require the active Permissions Pro status integration.', 'capability-manager-enhanced'));
            }
            if (
                (isset($requested['presspermit_privacy_statuses_enabled']) && defined('PPS_PRIVACY_STATUSES_LOCKED'))
                || (isset($requested['presspermit_custom_privacy_edit_caps']) && defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED'))
            ) {
                return RoleOperations::error('privacy_settings_locked', __('A requested privacy setting is locked by site configuration.', 'capability-manager-enhanced'));
            }
        }
        return true;
    }
}
