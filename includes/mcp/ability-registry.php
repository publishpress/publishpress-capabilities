<?php

/**
 * Register typed abilities covering the Capabilities menus.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class AbilityRegistry
{
    /** @since 2.54.0 */
    public static function object(array $properties, array $required = []): array
    {
        return ['type' => 'object', 'properties' => $properties, 'required' => $required, 'additionalProperties' => false];
    }

    /** @since 2.54.0 */
    public static function register(string $name, string $label, string $description, array $input, array $output, callable $callback, $permission, bool $readonly = true, bool $idempotent = false): void
    {
        wp_register_ability('publishpress-capabilities/' . $name, [
            'label' => $label,
            'description' => $description, 'category' => 'publishpress',
            'input_schema' => $input, 'output_schema' => self::object($output, array_keys($output)),
            'permission_callback' => is_callable($permission) ? $permission : static function () use ($permission) {
                return RoleOperations::canManage($permission);
            },
            'execute_callback' => $callback,
            'meta' => [
                'public' => false, 'show_in_rest' => false, 'mcp' => ['public' => false, 'type' => 'tool'],
                'annotations' => ['readonly' => $readonly, 'destructive' => !$readonly, 'idempotent' => $readonly || $idempotent],
            ],
        ]);
    }

    /** @since 2.54.0 */
    public static function registerAll(): void
    {
        if (!function_exists('wp_register_ability')) {
            return;
        }
        self::roles();
        self::features();
        self::backups();
        self::interfaceActions();
        self::users();
        AdvancedRegistry::registerAll();
    }

    /** @since 2.54.0 */
    private static function roles(): void
    {
        $role = ['type' => 'string', 'minLength' => 1, 'maxLength' => 100];
        $name = ['type' => 'string', 'minLength' => 1, 'maxLength' => 100];
        $string = ['type' => 'string'];
        $object = ['type' => 'object'];
        $list = ['type' => 'array', 'items' => $string];
        $roleOutput = ['slug' => $string, 'name' => $string, 'capabilities' => ['type' => 'array', 'items' => $object]];
        self::register(
            'list-capabilities',
            __('List capabilities', 'capability-manager-enhanced'),
            __('List known role, post-type and taxonomy capabilities, optionally filtered by name.', 'capability-manager-enhanced'),
            self::object(['search' => ['type' => 'string', 'maxLength' => 100]]),
            ['capabilities' => $list],
            [RoleOperations::class, 'catalog'],
            'manage_capabilities'
        );
        self::register(
            'compare-roles',
            __('Compare roles', 'capability-manager-enhanced'),
            __('Compare stored role grants and denials. Null means a capability is absent, false means explicitly denied.', 'capability-manager-enhanced'),
            self::object(['role' => $role, 'other_role' => $role], ['role', 'other_role']),
            ['differences' => ['type' => 'array', 'items' => $object]],
            [RoleOperations::class, 'compare'],
            'manage_capabilities'
        );
        self::register(
            'create-role',
            __('Create role', 'capability-manager-enhanced'),
            __('Create an empty role or copy capabilities from source_role. Does not overwrite an existing role. Use update-role-capabilities for grants.', 'capability-manager-enhanced'),
            self::object(['slug' => ['type' => 'string', 'pattern' => '^[a-z][a-z0-9_]{1,39}$'], 'name' => $name, 'source_role' => $role], ['slug', 'name']),
            $roleOutput,
            [RoleOperations::class, 'create'],
            'manage_capabilities_roles',
            false,
            false
        );
        self::register(
            'rename-role',
            __('Rename role', 'capability-manager-enhanced'),
            __('Change a role display name while retaining its slug, users, settings and capabilities.', 'capability-manager-enhanced'),
            self::object(['role' => $role, 'name' => $name], ['role', 'name']),
            $roleOutput,
            [RoleOperations::class, 'rename'],
            'manage_capabilities_roles',
            false
        );
        self::register(
            'delete-role',
            __('Delete role', 'capability-manager-enhanced'),
            __('Delete a custom editable role. Existing users with no remaining role move to the site default. System/default/own roles are protected.', 'capability-manager-enhanced'),
            self::object(['role' => $role], ['role']),
            ['deleted' => $string, 'users_moved_to_default_role' => ['type' => 'integer']],
            [RoleOperations::class, 'delete'],
            'manage_capabilities_roles',
            false,
            false
        );
        self::register(
            'update-role-capabilities',
            __('Update role capabilities', 'capability-manager-enhanced'),
            __('Patch capabilities: grant=true, deny=false, remove=unset. Unmentioned capabilities remain unchanged. Use dry_run to inspect before/after without saving. Changes are validated before writes.', 'capability-manager-enhanced'),
            self::object(
                [
                'role' => $role, 'dry_run' => ['type' => 'boolean', 'default' => false],
                'changes' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 500, 'items' => self::object([
                    'capability' => ['type' => 'string', 'pattern' => '^[A-Za-z][A-Za-z0-9_\-]{0,127}$'],
                    'action' => ['type' => 'string', 'enum' => ['grant', 'deny', 'remove']],
                ], ['capability', 'action'])],
                ],
                ['role', 'changes']
            ),
            ['role' => $string, 'before' => $object, 'after' => $object, 'applied' => ['type' => 'boolean']],
            [RoleOperations::class, 'patch'],
            'manage_capabilities',
            false
        );
        self::register(
            'get-role-options',
            __('Get role options', 'capability-manager-enhanced'),
            __('Read role editor preference, login/dashboard access and assignment disabling settings.', 'capability-manager-enhanced'),
            self::object(['role' => $role], ['role']),
            ['role' => $string, 'settings' => $object],
            [RoleOperations::class, 'options'],
            'manage_capabilities_roles'
        );
        self::register(
            'update-role-options',
            __('Update role options', 'capability-manager-enhanced'),
            __('Patch role options without changing capabilities. Administrator/default/own-role lockout protections apply.', 'capability-manager-enhanced'),
            self::object(
                ['role' => $role, 'settings' => self::object([
                'role_editor' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['classic_editor', 'block_editor']], 'uniqueItems' => true],
                'disable_code_editor' => ['type' => 'boolean'], 'disable_role_user_login' => ['type' => 'boolean'],
                'block_dashboard_access' => ['type' => 'boolean'], 'disable_role' => ['type' => 'boolean'],
                'disable_woocommerce_admin_restrictions' => ['type' => 'boolean'],
                'include_new_sites' => ['type' => 'boolean'],
                'permissions_only' => ['type' => 'boolean'],
                ])],
                ['role', 'settings']
            ),
            ['role' => $string, 'settings' => $object],
            [RoleOperations::class, 'updateOptions'],
            'manage_capabilities_roles',
            false
        );
    }

    /** @since 2.54.0 */
    private static function features(): void
    {
        $feature = ['type' => 'string', 'enum' => array_keys(FeatureOperations::definitions())];
        $string = ['type' => 'string'];
        $object = ['type' => 'object'];
        $selector = ['feature' => $feature, 'role' => ['type' => 'string', 'minLength' => 1],
            'post_type' => $string, 'editor' => ['type' => 'string', 'enum' => ['block', 'classic']]];
        $permission = static function ($input) {
            return FeatureOperations::canAccess($input['feature']);
        };
        $output = ['feature' => $string, 'role' => $string, 'settings' => $object];
        self::register(
            'list-features',
            __('List features', 'capability-manager-enhanced'),
            __('Discover all Capabilities menus, enabled states, permission names and supported configuration fields.', 'capability-manager-enhanced'),
            self::object([]),
            ['features' => ['type' => 'array', 'items' => $object]],
            [FeatureOperations::class, 'catalog'],
            'manage_capabilities_dashboard'
        );
        self::register(
            'set-feature-enabled',
            __('Set feature enabled', 'capability-manager-enhanced'),
            __('Enable or disable a Capabilities dashboard feature on this site. Disabled features reject their configuration actions.', 'capability-manager-enhanced'),
            self::object(['feature' => $string, 'enabled' => ['type' => 'boolean'], 'network_sync' => ['type' => 'boolean']], ['feature', 'enabled']),
            ['feature' => $string, 'enabled' => ['type' => 'boolean']],
            [FeatureOperations::class, 'toggle'],
            'manage_capabilities_dashboard',
            false
        );
        self::register(
            'get-feature-choices',
            __('Get feature choices', 'capability-manager-enhanced'),
            __('Discover existing editor/admin elements, captured profile/frontend elements, menu snapshots, navigation items or admin style templates. Does not capture new browser UI.', 'capability-manager-enhanced'),
            self::object($selector, ['feature']),
            ['feature' => $string, 'choices' => $object],
            [FeatureOperations::class, 'choices'],
            $permission
        );
        self::register(
            'get-feature-settings',
            __('Get feature settings', 'capability-manager-enhanced'),
            __('Read a role\'s settings for an enabled feature. Editor restrictions are specific to post_type and editor. Navigation also supports ppc_guest and ppc_users.', 'capability-manager-enhanced'),
            self::object($selector, ['feature', 'role']),
            $output,
            [FeatureOperations::class, 'read'],
            $permission
        );
        $fields = [];
        foreach (FeatureOperations::definitions() as $definition) {
            $fields = array_merge($fields, $definition['fields']);
        }
        $selector['settings'] = self::object($fields);
        $selector['dry_run'] = ['type' => 'boolean'];
        self::register(
            'update-feature-settings',
            __('Update feature settings', 'capability-manager-enhanced'),
            __('Patch feature settings for one role. List fields replace their lists; unmentioned fields and other roles remain unchanged. dry_run validates and returns proposed values without saving. Discover fields with list-features.', 'capability-manager-enhanced'),
            self::object($selector, ['feature', 'role', 'settings']),
            $output,
            [FeatureOperations::class, 'update'],
            $permission,
            false
        );
        $bulkSelector = $selector;
        unset($bulkSelector['role']);
        $bulkSelector['roles'] = ['type' => 'array', 'minItems' => 1, 'maxItems' => 1000, 'uniqueItems' => true,
            'items' => ['type' => 'string', 'minLength' => 1]];
        self::register(
            'bulk-update-feature-settings',
            __('Bulk update feature settings', 'capability-manager-enhanced'),
            __('Apply the same feature settings to multiple roles, validating all targets before writing. dry_run returns proposed settings only.', 'capability-manager-enhanced'),
            self::object($bulkSelector, ['feature', 'roles', 'settings']),
            ['roles' => ['type' => 'array', 'items' => $object]],
            [FeatureOperations::class, 'bulkUpdate'],
            $permission,
            false
        );
        self::register(
            'get-plugin-settings',
            __('Get plugin settings', 'capability-manager-enhanced'),
            __('Read the plugin settings and their supported input schema, including multiple roles, visibility, user testing and menu restriction priority.', 'capability-manager-enhanced'),
            self::object([]),
            ['settings' => $object, 'schema' => $object],
            [FeatureOperations::class, 'settings'],
            'manage_capabilities_settings'
        );
        self::register(
            'update-plugin-settings',
            __('Update plugin settings', 'capability-manager-enhanced'),
            __('Patch the explicitly supported plugin settings. Does not accept arbitrary WordPress option names.', 'capability-manager-enhanced'),
            self::object(['settings' => self::object(FeatureOperations::settingFields())], ['settings']),
            ['settings' => $object, 'schema' => $object],
            [FeatureOperations::class, 'updateSettings'],
            'manage_capabilities_settings',
            false
        );
    }

    /** @since 2.54.0 */
    private static function backups(): void
    {
        $string = ['type' => 'string'];
        $object = ['type' => 'object'];
        $list = ['type' => 'array', 'items' => $string];
        self::register(
            'list-backups',
            __('List backups', 'capability-manager-enhanced'),
            __('List saved manual, initial and automatic Capabilities role backups. Returns identifiers for restore-backup.', 'capability-manager-enhanced'),
            self::object([]),
            ['backups' => ['type' => 'array', 'items' => $object]],
            [BackupOperations::class, 'listing'],
            'manage_capabilities_backup'
        );
        self::register(
            'create-backup',
            __('Create backup', 'capability-manager-enhanced'),
            __('Save the current roles and plugin feature settings using the existing manual backup format. Replaces the last manual backup.', 'capability-manager-enhanced'),
            self::object([]),
            ['backup' => $string, 'sections' => $list],
            [BackupOperations::class, 'create'],
            'manage_capabilities_backup',
            false,
            false
        );
        self::register(
            'restore-backup',
            __('Restore backup', 'capability-manager-enhanced'),
            __('Restore a listed backup. Manual backups include feature settings; initial/automatic backups restore roles. Use dry_run before applying.', 'capability-manager-enhanced'),
            self::object(['backup' => $string, 'dry_run' => ['type' => 'boolean']], ['backup']),
            ['applied' => ['type' => 'boolean'], 'settings' => $list],
            [BackupOperations::class, 'restore'],
            'manage_capabilities_backup',
            false
        );
        self::register(
            'export-configuration',
            __('Export configuration', 'capability-manager-enhanced'),
            __('Export plugin-owned configuration in the same allowlisted format used by the Backup screen. Returns available_sections for selective export and optionally filters user_roles by role slug. Contains permission configuration, not password secrets.', 'capability-manager-enhanced'),
            self::object(['sections' => ['type' => 'array', 'minItems' => 1, 'uniqueItems' => true, 'items' => $string],
                'roles' => ['type' => 'array', 'minItems' => 1, 'uniqueItems' => true, 'items' => $string]]),
            ['configuration' => $object, 'available_sections' => $list],
            [BackupOperations::class, 'export'],
            'manage_capabilities_backup'
        );
        self::register(
            'import-configuration',
            __('Import configuration', 'capability-manager-enhanced'),
            __('Validate and import a configuration export using the existing backup validators. Reject unsupported entries before writing; dry_run validates without saving.', 'capability-manager-enhanced'),
            self::object(['configuration' => $object, 'dry_run' => ['type' => 'boolean']], ['configuration']),
            ['applied' => ['type' => 'boolean'], 'settings' => $list],
            [BackupOperations::class, 'import'],
            'manage_capabilities_backup',
            false
        );
    }

    /** @since 2.54.0 */
    private static function interfaceActions(): void
    {
        $string = ['type' => 'string', 'maxLength' => 500];
        $strings = ['type' => 'array', 'items' => $string, 'maxItems' => 1000];
        $feature = ['type' => 'string', 'enum' => ['profile-features', 'frontend-features']];
        $permission = static function ($input) {
            return FeatureOperations::canAccess($input['feature']);
        };
        self::register(
            'save-interface-element',
            __('Save interface element', 'capability-manager-enhanced'),
            __('Create or update a profile selector or frontend selector/CSS/body-class element. Does not enable it for a role; use update-feature-settings afterwards.', 'capability-manager-enhanced'),
            self::object(
                [
                'feature' => $feature, 'id' => $string, 'label' => $string, 'role' => $string,
                'selector' => ['type' => 'string', 'maxLength' => 5000], 'styles' => ['type' => 'string', 'maxLength' => 50000],
                'bodyclass' => $string, 'pages' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['whole_site', 'homepage', 'archive_pages', 'single_pages']], 'uniqueItems' => true], 'post_types' => $strings,
                'element_type' => ['type' => 'string', 'enum' => ['custom', 'section', 'header']],
                ],
                ['feature', 'label']
            ),
            ['id' => $string, 'feature' => $string],
            [InterfaceOperations::class, 'saveElement'],
            $permission,
            false,
            false
        );
        self::register(
            'delete-interface-element',
            __('Delete interface element', 'capability-manager-enhanced'),
            __('Delete a profile/frontend element and remove its saved restriction references.', 'capability-manager-enhanced'),
            self::object(['feature' => $feature, 'id' => $string, 'role' => $string], ['feature', 'id']),
            ['deleted' => $string],
            [InterfaceOperations::class, 'deleteElement'],
            $permission,
            false,
            false
        );
        self::register(
            'update-admin-menu-presentation',
            __('Update admin menu presentation', 'capability-manager-enhanced'),
            __('Rename menus for a role, set global menu/submenu ordering, update menu UI settings, or reset custom presentation. This does not change capabilities.', 'capability-manager-enhanced'),
            self::object([
                'action' => ['type' => 'string', 'enum' => ['rename', 'reorder', 'settings', 'reset']],
                'role' => $string, 'menu' => $string, 'title' => $string, 'icon' => $string,
                'type' => ['type' => 'string', 'enum' => ['menu', 'submenu']],
                'show_menu_slug' => ['type' => 'boolean'], 'hide_submenu' => ['type' => 'boolean'],
                'groups' => ['type' => 'array', 'items' => self::object(['menu' => $string, 'submenus' => $strings], ['menu', 'submenus'])],
            ], ['action']),
            ['updated' => ['type' => 'boolean'], 'action' => $string],
            [InterfaceOperations::class, 'menuPresentation'],
            static function () {
                return FeatureOperations::canAccess('admin-menus');
            },
            false
        );
        $styleFields = FeatureOperations::styleFields();
        $styleFields['advanced_rules'] = ['type' => 'array', 'maxItems' => 200, 'items' => ['type' => 'object']];
        self::register(
            'save-admin-style',
            __('Save admin style', 'capability-manager-enhanced'),
            __('Create/update a named custom admin color style using existing sanitizers and CSS generation. Free supports one custom style; Pro supports multiple.', 'capability-manager-enhanced'),
            self::object(['slug' => ['type' => 'string', 'pattern' => '^ppc-custom-style-[a-z0-9_-]+$'], 'name' => $string, 'settings' => self::object($styleFields)], ['name', 'settings']),
            ['slug' => $string, 'style' => ['type' => 'object']],
            static function ($input) {
                return FeatureOperations::styles()->saveStyle($input);
            },
            static function () {
                return FeatureOperations::canAccess('admin-styles');
            },
            false,
            false
        );
        self::register(
            'delete-admin-style',
            __('Delete admin style', 'capability-manager-enhanced'),
            __('Delete a custom admin style and its generated CSS. Roles using it return to the default color scheme.', 'capability-manager-enhanced'),
            self::object(['slug' => $string], ['slug']),
            ['deleted' => $string],
            static function ($input) {
                return FeatureOperations::styles()->deleteStyle($input);
            },
            static function () {
                return FeatureOperations::canAccess('admin-styles');
            },
            false,
            false
        );
        self::register(
            'list-application-password-scopes',
            __('List application password scopes', 'capability-manager-enhanced'),
            __('List manageable application password names and denied capabilities. Returns no plaintext passwords or hashes. Requires the application-password capabilities feature to be enabled.', 'capability-manager-enhanced'),
            self::object([]),
            ['subjects' => ['type' => 'array', 'items' => ['type' => 'object']]],
            [InterfaceOperations::class, 'passwordSubjects'],
            static function () {
                return current_user_can('manage_capabilities') && pp_capabilities_application_password_capabilities_enabled();
            }
        );
        self::register(
            'update-application-password-scope',
            __('Update application password scope', 'capability-manager-enhanced'),
            __('Replace the denied capability list for one application password. Restrictions only reduce its user\'s capabilities; they do not grant privileges.', 'capability-manager-enhanced'),
            self::object(['subject' => $string, 'denied' => $strings], ['subject', 'denied']),
            ['subject' => $string, 'denied' => $strings],
            [InterfaceOperations::class, 'passwordRestrictions'],
            static function ($input) {
                return current_user_can('manage_capabilities') && pp_capabilities_can_manage_application_password_subject($input['subject']);
            },
            false
        );
    }

    /** @since 2.54.0 */
    private static function users(): void
    {
        self::register(
            'update-user-roles',
            __('Update user roles', 'capability-manager-enhanced'),
            __('Replace an existing site user\'s assigned roles using the profile form rules. Requires multiple-role editing to be enabled, preserves forum roles, and supports dry_run.', 'capability-manager-enhanced'),
            self::object([
                'user_id' => ['type' => 'integer', 'minimum' => 1],
                'roles' => ['type' => 'array', 'minItems' => 1, 'maxItems' => 100,
                    'uniqueItems' => true, 'items' => ['type' => 'string', 'minLength' => 1]],
                'dry_run' => ['type' => 'boolean'],
            ], ['user_id', 'roles']),
            ['user_id' => ['type' => 'integer'], 'before' => ['type' => 'array', 'items' => ['type' => 'string']],
                'after' => ['type' => 'array', 'items' => ['type' => 'string']], 'applied' => ['type' => 'boolean']],
            [UserOperations::class, 'updateRoles'],
            static function ($input) {
                return current_user_can('promote_users') && current_user_can('edit_user', $input['user_id'])
                    && current_user_can('promote_user', $input['user_id']);
            },
            false
        );

        $id = ['type' => 'integer', 'minimum' => 1];
        $string = ['type' => 'string'];
        $permission = static function ($input) {
            return current_user_can('manage_capabilities') && pp_capabilities_feature_enabled('capabilities')
                && current_user_can('edit_user', $input['user_id']);
        };
        self::register(
            'get-user-capabilities',
            __('Get user capabilities', 'capability-manager-enhanced'),
            __('Inspect a site member\'s roles and effective capability values, as on the User Capabilities screen. No identity switching or user changes.', 'capability-manager-enhanced'),
            self::object(['user_id' => $id], ['user_id']),
            ['user_id' => $id, 'roles' => ['type' => 'array', 'items' => $string], 'capabilities' => ['type' => 'array', 'items' => ['type' => 'object']]],
            [UserOperations::class, 'inspect'],
            $permission
        );
        self::register(
            'check-user-capability',
            __('Check user capability', 'capability-manager-enhanced'),
            __('Test a user\'s effective permission, optionally for an object ID. Applies WordPress permission filters without impersonating that user or changing the MCP session.', 'capability-manager-enhanced'),
            self::object(['user_id' => $id, 'capability' => $string, 'object_id' => $id], ['user_id', 'capability']),
            ['user_id' => $id, 'capability' => $string, 'allowed' => ['type' => 'boolean']],
            [UserOperations::class, 'check'],
            $permission
        );
    }
}
