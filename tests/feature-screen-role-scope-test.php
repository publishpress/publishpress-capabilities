<?php

function add_action(...$arguments) {}
function current_user_can($capability) { return $capability === $GLOBALS['feature_capability']; }
function is_multisite() { return false; }
function wp_unslash($value) { return $value; }
function sanitize_key($value) { return is_string($value) ? strtolower($value) : ''; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid' && $action === $GLOBALS['feature_nonce_action']; }
function pp_capabilities_is_editable_role($role) { return $role === 'author'; }
function esc_html__($message, $domain) { return $message; }
function wp_die($message, $title, $arguments) { throw new RuntimeException($message, $arguments['response']); }

require dirname(__DIR__) . '/includes/manager.php';

$manager = new class extends CapabilityManager {
    public function __construct() {}
    public function generateNames() {
        $this->roles = ['author' => 'Author', 'contributor' => 'Contributor'];
    }
};

$_SERVER['REQUEST_METHOD'] = 'POST';
$screens = [
    ['frontend-features', 'frontend_features', 'ppc-frontend-features-role', 'frontend-features', true],
    ['nav-menus', 'nav_menus', 'ppc-nav-menu-role', 'nav-menus', true],
    ['redirects', 'redirects', 'ppc-redirects-features-role', 'redirects-features', false],
];
foreach ($screens as [$screen, $capability, $role_field, $nonce_action, $allow_audience]) {
    $plugin_page = 'pp-capabilities-' . $screen;
    $feature_capability = 'manage_capabilities_' . $capability;
    $feature_nonce_action = 'pp-capabilities-' . $nonce_action;
    $denied_roles = ['administrator', 'contributor', 'unknown'];
    if (!$allow_audience) {
        $denied_roles[] = 'ppc_users';
    }
    foreach ($denied_roles as $role) {
        $_POST = ['_wpnonce' => 'valid', $role_field => $role];
        try {
            $manager->validateRoleScopedFeatureSubmission();
        } catch (RuntimeException $error) {
            if (403 === $error->getCode()) {
                continue;
            }
            throw $error;
        }
        throw new RuntimeException("Expected HTTP 403 for an out-of-scope role on {$screen}.");
    }

    foreach ($allow_audience ? ['author', 'ppc_users', 'ppc_guest'] : ['author'] as $role) {
        $_POST = ['_wpnonce' => 'valid', $role_field => $role];
        $manager->validateRoleScopedFeatureSubmission();
    }
}

echo "Feature screen role-scope checks passed.\n";
