<?php

/**
 * Register capability mapping, recovery, network and Pro feature actions.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class AdvancedRegistry
{
    /** @since 2.54.0 */
    public static function registerAll(): void
    {
        $objectSchema = [AbilityRegistry::class, 'object'];
        $register = [AbilityRegistry::class, 'register'];
        $string = ['type' => 'string', 'minLength' => 1, 'maxLength' => 500];
        $strings = ['type' => 'array', 'items' => $string, 'uniqueItems' => true, 'maxItems' => 1000];
        $boolean = ['type' => 'boolean'];
        $object = ['type' => 'object'];
        $typeFields = ['post_types' => $strings, 'taxonomies' => $strings,
            'detailed_taxonomies' => $strings, 'separate_create_capability' => $boolean];
        $register(
            'prepare-profile-capture',
            __('Prepare profile capture', 'capability-manager-enhanced'),
            __('Enable/disable the existing five-minute profile capture window and return the admin page to visit. Actual DOM capture runs in the browser with its own authenticated session.', 'capability-manager-enhanced'),
            $objectSchema(['enabled' => $boolean, 'role' => $string], ['enabled']),
            ['enabled' => $boolean, 'expires' => ['type' => 'integer'], 'capture_url' => ['type' => 'string']],
            [AdvancedOperations::class, 'profileCapture'],
            static function () {
                return FeatureOperations::canAccess('profile-features');
            },
            false,
            false
        );
        $register(
            'update-admin-feature-ui-settings',
            __('Update admin feature UI settings', 'capability-manager-enhanced'),
            __('Set the Admin Features hide-submenu preference. This is a global presentation preference, not a role access rule.', 'capability-manager-enhanced'),
            $objectSchema(['hide_submenu' => $boolean], ['hide_submenu']),
            ['hide_submenu' => $boolean],
            [AdvancedOperations::class, 'adminFeatureSettings'],
            static function () {
                return FeatureOperations::canAccess('admin-features');
            },
            false
        );
        $register(
            'get-type-capability-settings',
            __('Get type capability settings', 'capability-manager-enhanced'),
            __('Read type-specific post/taxonomy capability mappings and discover registered types.', 'capability-manager-enhanced'),
            $objectSchema([]),
            ['settings' => $object, 'choices' => $object],
            [AdvancedOperations::class, 'typeSettings'],
            'manage_capabilities'
        );
        $register(
            'update-type-capability-settings',
            __('Update type capability settings', 'capability-manager-enhanced'),
            __('Patch type-specific capabilities and detailed taxonomy mapping. Detailed taxonomies also enable type-specific capabilities. Takes effect on the next request.', 'capability-manager-enhanced'),
            $objectSchema(['settings' => $objectSchema($typeFields)], ['settings']),
            ['settings' => $object, 'choices' => $object],
            [AdvancedOperations::class, 'updateTypes'],
            'manage_capabilities',
            false
        );
        $register(
            'set-role-level',
            __('Set role level', 'capability-manager-enhanced'),
            __('Set a legacy WordPress role level from 0 to 10. Administrator stays at level 10. dry_run previews capability changes.', 'capability-manager-enhanced'),
            $objectSchema(['role' => $string, 'level' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 10], 'dry_run' => $boolean], ['role', 'level']),
            ['role' => $string, 'before' => $object, 'after' => $object, 'applied' => $boolean],
            [RoleOperations::class, 'setLevel'],
            'manage_capabilities',
            false
        );
        $register(
            'reset-roles',
            __('Reset roles', 'capability-manager-enhanced'),
            __('Role reset requires the native Backup screen. MCP refuses recovery hooks that could introduce administrator-level grants.', 'capability-manager-enhanced'),
            $objectSchema([]),
            ['reset' => $boolean],
            [AdvancedOperations::class, 'resetRoles'],
            'manage_capabilities_backup',
            false,
            false
        );
        $register(
            'test-user-access',
            __('Test user access', 'capability-manager-enhanced'),
            __('Test an eligible user’s permission through the User Testing access rules without switching cookies or the MCP identity.', 'capability-manager-enhanced'),
            $objectSchema(['user_id' => ['type' => 'integer', 'minimum' => 1], 'capability' => $string,
                'object_id' => ['type' => 'integer', 'minimum' => 1]], ['user_id', 'capability']),
            ['user_id' => ['type' => 'integer'], 'capability' => $string, 'allowed' => $boolean],
            [UserOperations::class, 'check'],
            static function ($input) {
                require_once dirname(__DIR__) . '/test-user.php';
                $user = get_user_by('id', $input['user_id']);
                return $user && \PP_Capabilities_Test_User::canTestUser($user);
            }
        );
        if (is_multisite()) {
            $register(
                'sync-role-network',
                __('Sync role network', 'capability-manager-enhanced'),
                __('Queue the existing role/capability and option synchronization across network sites. Requires a main-site network administrator. MCP refuses synchronization of roles with administrator-level capabilities.', 'capability-manager-enhanced'),
                $objectSchema(['role' => $string, 'sync_roles' => $boolean, 'sync_options' => $boolean], ['role']),
                ['token' => $string],
                [AdvancedOperations::class, 'networkSync'],
                static function () {
                    return is_multisite() && is_super_admin() && is_main_site() && current_user_can('manage_capabilities');
                },
                false,
                false
            );
        }
        if (!defined('PUBLISHPRESS_CAPS_PRO_VERSION')) {
            return;
        }
        CustomRuleOperations::register();
        $postPermission = static function ($input) {
            return defined('PUBLISHPRESS_CAPS_PRO_VERSION') && FeatureOperations::canAccess('frontend-features')
                && current_user_can('edit_post', $input['post_id']);
        };
        $register(
            'get-post-frontend-elements',
            __('Get post frontend elements', 'capability-manager-enhanced'),
            __('Read a post’s frontend element overrides (Pro), respecting edit_post permission.', 'capability-manager-enhanced'),
            $objectSchema(['post_id' => ['type' => 'integer', 'minimum' => 1]], ['post_id']),
            ['post_id' => ['type' => 'integer'], 'elements' => $strings],
            [AdvancedOperations::class, 'frontendPost'],
            $postPermission
        );
        $register(
            'set-post-frontend-elements',
            __('Set post frontend elements', 'capability-manager-enhanced'),
            __('Replace a post’s enabled frontend element overrides (Pro). Empty list clears them.', 'capability-manager-enhanced'),
            $objectSchema(['post_id' => ['type' => 'integer', 'minimum' => 1], 'elements' => $strings], ['post_id', 'elements']),
            ['post_id' => ['type' => 'integer'], 'elements' => $strings],
            [AdvancedOperations::class, 'frontendPost'],
            $postPermission,
            false
        );
        $register(
            'manage-custom-admin-menu',
            __('Manage custom admin menu', 'capability-manager-enhanced'),
            __('Create/update/delete a custom admin menu or separator (Pro). Menu data contains no executable callbacks. Refreshes menu snapshots.', 'capability-manager-enhanced'),
            $objectSchema([
                'action' => ['type' => 'string', 'enum' => ['save', 'delete']], 'slug' => $string,
                'type' => ['type' => 'string', 'enum' => ['menu', 'submenu']], 'title' => $string,
                'capability' => $string, 'parent' => $string, 'position_after' => $string, 'position' => ['type' => 'number', 'minimum' => 0],
                'separator' => $boolean, 'separator_color' => ['type' => 'string', 'pattern' => '^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$'], 'icon' => $string,
            ], ['action', 'slug', 'type']),
            ['slug' => $string, 'action' => $string],
            [AdvancedOperations::class, 'customMenu'],
            static function () {
                return defined('PUBLISHPRESS_CAPS_PRO_VERSION') && FeatureOperations::canAccess('admin-menus');
            },
            false
        );
    }
}
