<?php

/**
 * Manage Pro custom editor selectors, hidden admin elements and URL rules.
 *
 * @author PublishPress
 * @copyright Copyright (c) 2026, PublishPress
 * @license GPL v2 or later
 * @since 2.54.0
 */

declare(strict_types=1);

namespace PublishPress\Capabilities\Mcp;

defined('ABSPATH') || exit;

class CustomRuleOperations
{
    /** @since 2.54.0 */
    public static function definitions(): array
    {
        return [
            'editor-block' => ['option' => 'ppc_feature_post_gutenberg_custom_data', 'feature' => 'editor-features', 'prefix' => ''],
            'editor-classic' => ['option' => 'ppc_feature_post_classic_custom_data', 'feature' => 'editor-features', 'prefix' => ''],
            'admin-css' => ['option' => 'ppc_admin_feature_css_hide_custom_data', 'feature' => 'admin-features', 'prefix' => 'ppc_hidden_css||'],
            'admin-url' => ['option' => 'ppc_admin_feature_block_url_custom_data', 'feature' => 'admin-features', 'prefix' => 'ppc_blocked_url||'],
        ];
    }

    /** @since 2.54.0 */
    public static function permission(array $input): bool
    {
        return defined('PUBLISHPRESS_CAPS_PRO_VERSION')
            && isset(self::definitions()[$input['kind']])
            && FeatureOperations::canAccess(self::definitions()[$input['kind']]['feature']);
    }

    /** @since 2.54.0 */
    public static function listing(array $input): array
    {
        $definition = self::definitions()[$input['kind']];
        $rules = [];
        foreach ((array) get_option($definition['option'], []) as $id => $settings) {
            $rules[] = ['id' => (string) $id, 'restriction_value' => $definition['prefix'] . $id, 'settings' => (object) $settings];
        }
        return ['rules' => $rules];
    }

    /** @since 2.54.0 */
    public static function save(array $input)
    {
        $definition = self::definitions()[$input['kind']];
        $label = sanitize_text_field($input['label']);
        $elements = sanitize_textarea_field($input['value']);
        if ($label === '' || $elements === '') {
            return RoleOperations::error('custom_rule_fields', __('A label and selector or URL are required.', 'capability-manager-enhanced'));
        }
        $data = (array) get_option($definition['option'], []);
        $id = $input['id'] ?? sanitize_key(wp_generate_uuid4());
        $settings = ['label' => $label, 'elements' => $elements];
        if ($input['kind'] === 'admin-css') {
            $settings['target_urls'] = sanitize_textarea_field(implode("\n", $input['target_urls'] ?? []));
            $settings['url_wildcard'] = (int) !empty($input['wildcard']);
        } elseif ($input['kind'] === 'admin-url') {
            $settings['wildcard'] = (int) !empty($input['wildcard']);
        }
        $data[$id] = $settings;
        update_option($definition['option'], $data, false);
        return ['id' => $id, 'restriction_value' => $definition['prefix'] . $id];
    }

    /** @since 2.54.0 */
    public static function delete(array $input)
    {
        $definition = self::definitions()[$input['kind']];
        $data = (array) get_option($definition['option'], []);
        if (!isset($data[$input['id']])) {
            return RoleOperations::error('rule_missing', __('Custom rule not found.', 'capability-manager-enhanced'));
        }
        unset($data[$input['id']]);
        update_option($definition['option'], $data, false);
        $keys = ['capsman_disabled_admin_features'];
        if ($definition['feature'] === 'editor-features') {
            $keys = [];
            foreach (array_unique(array_merge(['post', 'page'], apply_filters('pp_capabilities_feature_post_types', []))) as $postType) {
                $keys[] = 'capsman_feature_restrict_' . ($input['kind'] === 'editor-classic' ? 'classic_' : '') . $postType;
            }
        }
        foreach ($keys as $key) {
            $values = (array) get_option($key, []);
            foreach ($values as &$roleRestrictions) {
                $roleRestrictions = array_values(array_diff((array) $roleRestrictions, [$definition['prefix'] . $input['id']]));
            }
            unset($roleRestrictions);
            update_option($key, $values, false);
        }
        return ['deleted' => $input['id']];
    }

    /** @since 2.54.0 */
    public static function register(): void
    {
        $object = [AbilityRegistry::class, 'object'];
        $register = [AbilityRegistry::class, 'register'];
        $string = ['type' => 'string', 'minLength' => 1, 'maxLength' => 10000];
        $kind = ['type' => 'string', 'enum' => array_keys(self::definitions())];
        $register(
            'list-custom-rules',
            __('List custom rules', 'capability-manager-enhanced'),
            __('List Pro custom editor selectors, hidden admin elements or blocked URLs, including the exact restriction values used by feature settings.', 'capability-manager-enhanced'),
            $object(['kind' => $kind], ['kind']),
            ['rules' => ['type' => 'array', 'items' => ['type' => 'object']]],
            [self::class, 'listing'],
            [self::class, 'permission']
        );
        $register(
            'save-custom-rule',
            __('Save custom rule', 'capability-manager-enhanced'),
            __('Create/update a Pro custom editor selector, admin CSS hiding rule or blocked-URL rule. Save its restriction_value in the role feature settings to enable it.', 'capability-manager-enhanced'),
            $object(['kind' => $kind, 'id' => $string, 'label' => $string, 'value' => $string,
                'wildcard' => ['type' => 'boolean'], 'target_urls' => ['type' => 'array', 'items' => $string, 'maxItems' => 100]], ['kind', 'label', 'value']),
            ['id' => $string, 'restriction_value' => $string],
            [self::class, 'save'],
            [self::class, 'permission'],
            false,
            false
        );
        $register(
            'delete-custom-rule',
            __('Delete custom rule', 'capability-manager-enhanced'),
            __('Delete a Pro custom restriction rule and its role restriction references.', 'capability-manager-enhanced'),
            $object(['kind' => $kind, 'id' => $string], ['kind', 'id']),
            ['deleted' => $string],
            [self::class, 'delete'],
            [self::class, 'permission'],
            false,
            false
        );
    }
}
