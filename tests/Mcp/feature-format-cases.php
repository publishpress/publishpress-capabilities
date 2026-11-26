<?php
/** Native checkbox representations must be usable in typed patches. */
foreach (['admin-notices' => ['option' => 'cme_admin_notice_options', 'field' => 'enable_toolbar_access'],
    'redirects' => ['option' => 'capsman_role_redirects', 'field' => 'custom_redirect']] as $feature => $fixture) {
    foreach (['1' => true, '0' => false] as $native => $expected) {
        update_option($fixture['option'], ['editor' => [$fixture['field'] => (string) $native, 'unrelated' => 'preserve']]);
        $read = $call('get-feature-settings', ['feature' => $feature, 'role' => 'editor']);
        $check(!is_wp_error($read) && $read['settings']->{$fixture['field']} === $expected, $feature . ' native checkbox is returned as a JSON boolean');
        $saved = $call('update-feature-settings', ['feature' => $feature, 'role' => 'editor', 'settings' => [$fixture['field'] => $expected]]);
        $check(!is_wp_error($saved) && $saved['settings']->{$fixture['field']} === $expected, $feature . ' discovered checkbox round-trips');
        $check(get_option($fixture['option'])['editor']['unrelated'] === 'preserve', $feature . ' preserves unrelated native values');
    }
    $before = get_option($fixture['option']);
    $check(is_wp_error($call('update-feature-settings', ['feature' => $feature, 'role' => 'editor', 'settings' => [$fixture['field'] => 'false']])), $feature . ' string checkbox is rejected');
    $check(get_option($fixture['option']) === $before, $feature . ' malformed checkbox saves nothing');
}
$before = get_option('capsman_dashboard_features_status');
$check(is_wp_error($call('set-feature-enabled', ['feature' => 'redirects', 'enabled' => 'false'])), 'Dashboard feature switches reject string booleans');
$check(get_option('capsman_dashboard_features_status') === $before, 'Malformed dashboard switches save nothing');
