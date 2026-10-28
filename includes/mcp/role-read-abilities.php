<?php

/**
 * Register and execute read-only role abilities.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class RoleReadAbilities
{
    /**
     * Match access to the existing capabilities editor without granting write access.
     *
     * @since 2.54.0
     */
    public static function canReadRoles(): bool
    {
        return current_user_can('manage_capabilities')
            && (!function_exists('pp_capabilities_feature_enabled') || pp_capabilities_feature_enabled('capabilities'));
    }

    /**
     * Register a discoverable list and a detailed role inspection action.
     *
     * @since 2.54.0
     */
    public static function registerAbilities(): void
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        $common = [
            'category' => 'publishpress',
            'permission_callback' => [self::class, 'canReadRoles'],
            'meta' => [
                'public' => false,
                'show_in_rest' => false,
                'mcp' => ['public' => false, 'type' => 'tool'],
                'annotations' => ['readonly' => true, 'destructive' => false, 'idempotent' => true],
            ],
        ];

        wp_register_ability('publishpress-capabilities/list-roles', array_merge($common, [
            'label' => __('List roles', 'capability-manager-enhanced'),
            'description' => __('List the current site’s registered role slugs and names. Does not change roles or return users.', 'capability-manager-enhanced'),
            'input_schema' => ['type' => 'object', 'additionalProperties' => false],
            'output_schema' => [
                'type' => 'object',
                'properties' => ['roles' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => ['slug' => ['type' => 'string'], 'name' => ['type' => 'string']],
                        'required' => ['slug', 'name'],
                    ],
                ]],
                'required' => ['roles'],
            ],
            'execute_callback' => [self::class, 'listRoles'],
        ]));

        wp_register_ability('publishpress-capabilities/get-role', array_merge($common, [
            'label' => __('Get role capabilities', 'capability-manager-enhanced'),
            'description' => __('Read a registered role’s stored capability grants and denials. These are role settings, not an individual user’s effective permissions.', 'capability-manager-enhanced'),
            'input_schema' => [
                'type' => 'object',
                'properties' => ['role' => ['type' => 'string', 'minLength' => 1]],
                'required' => ['role'],
                'additionalProperties' => false,
            ],
            'output_schema' => [
                'type' => 'object',
                'properties' => [
                    'slug' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'capabilities' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => ['name' => ['type' => 'string'], 'granted' => ['type' => 'boolean']],
                            'required' => ['name', 'granted'],
                        ],
                    ],
                ],
                'required' => ['slug', 'name', 'capabilities'],
            ],
            'execute_callback' => [self::class, 'getRole'],
        ]));
    }

    /** @since 2.54.0 */
    public static function listRoles(): array
    {
        $roles = [];
        foreach (wp_roles()->roles as $slug => $role) {
            $roles[] = ['slug' => (string) $slug, 'name' => translate_user_role($role['name'])];
        }

        return ['roles' => $roles];
    }

    /**
     * @param array $input Validated ability arguments.
     * @return array|\WP_Error
     * @since 2.54.0
     */
    public static function getRole(array $input)
    {
        $role = get_role($input['role']);
        if (!$role) {
            return new \WP_Error('publishpress_role_not_found', __('Role not found.', 'capability-manager-enhanced'));
        }

        $capabilities = [];
        foreach ($role->capabilities as $name => $granted) {
            $capabilities[] = ['name' => (string) $name, 'granted' => (bool) $granted];
        }

        return [
            'slug' => $input['role'],
            'name' => translate_user_role(wp_roles()->roles[$input['role']]['name']),
            'capabilities' => $capabilities,
        ];
    }
}
