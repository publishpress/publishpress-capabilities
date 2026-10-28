<?php

/** Settings transport types and native storage round trips. */
$settingFields = \PublishPress\Capabilities\Mcp\FeatureOperations::settingFields();
foreach ($settingFields as $field => $schema) {
    if ($schema['type'] !== 'boolean' || isset(\PublishPress\Capabilities\Mcp\ProSettingsOperations::fields()[$field])) {
        continue;
    }
    foreach ([true, false] as $value) {
        $saved = $call('update-plugin-settings', ['settings' => [$field => $value]]);
        $check(!is_wp_error($saved) && $saved['settings']->$field === $value, $field . ' boolean round-trip');
        $check(get_option($field) === ($value ? '1' : ''), $field . ' uses native checkbox storage');
    }
    foreach (['true', 'false', '0', '1', 0, 1, null, []] as $value) {
        $before = get_option($field);
        $check(is_wp_error($call('update-plugin-settings', ['settings' => [$field => $value]])), $field . ' rejects malformed boolean');
        $check(get_option($field) === $before, $field . ' malformed input saves nothing');
    }
}
foreach ([['subscriber'], [], ['editor', 'author']] as $roles) {
    $saved = $call('update-plugin-settings', ['settings' => ['cme_test_user_excluded_roles' => $roles]]);
    $check(!is_wp_error($saved) && $saved['settings']->cme_test_user_excluded_roles === $roles, 'Role-list arrays round-trip including empty lists');
}
foreach ([['1'], [1], [true], ['subscriber', 'subscriber'], ['missing-role'], ['key' => 'subscriber']] as $roles) {
    $before = get_option('cme_capabilities_add_user_multi_roles');
    $check(is_wp_error($call('update-plugin-settings', ['settings' => ['cme_capabilities_add_user_multi_roles' => true, 'cme_test_user_excluded_roles' => $roles]])), 'Invalid role lists reject the whole patch');
    $check(get_option('cme_capabilities_add_user_multi_roles') === $before, 'Invalid mixed patches preserve valid fields');
}
$check(is_wp_error($call('update-plugin-settings', ['settings' => []])), 'Empty settings patches are rejected');

$settingsTool = \WP\MCP\Domain\Tools\McpTool::fromAbility(wp_get_ability('publishpress-capabilities/update-plugin-settings'));
$check(!is_wp_error($settingsTool) && !is_wp_error($settingsTool->execute(['settings' => ['cme_test_user_excluded_roles' => ['subscriber']]])), 'Released adapter executes role-list settings without object/array conflicts');
$check(is_wp_error($settingsTool->execute(['settings' => ['cme_capabilities_add_user_multi_roles' => 'false']])), 'Released adapter rejects string checkbox settings');
