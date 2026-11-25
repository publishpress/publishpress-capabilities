<?php

function add_action(...$arguments) {}
function current_user_can($capability) { return $capability === 'manage_capabilities_profile_features'; }
function is_multisite() { return false; }
function wp_unslash($value) { return $value; }
function sanitize_key($value) { return is_string($value) ? strtolower($value) : ''; }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid' && $action === 'pp-capabilities-profile-features'; }
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

$plugin_page = 'pp-capabilities-profile-features';
$_SERVER['REQUEST_METHOD'] = 'POST';
foreach ([
    ['_wpnonce' => 'valid', 'ppc-profile-features-role' => 'administrator'],
    ['_wpnonce' => 'valid', 'ppc-profile-features-role' => 'contributor'],
    ['ppc-profile-features-role' => 'author'],
] as $submission) {
    $_POST = $submission;
    try {
        $manager->profileFeaturesCaptureRedirect();
    } catch (RuntimeException $error) {
        if (403 === $error->getCode()) {
            continue;
        }
        throw $error;
    }
    throw new RuntimeException('Profile Features must deny this save with HTTP 403.');
}

$_POST = ['_wpnonce' => 'valid', 'ppc-profile-features-role' => 'author'];
$manager->profileFeaturesCaptureRedirect();
echo "Profile Features role-scope checks passed.\n";
