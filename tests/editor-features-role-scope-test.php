<?php

function add_action(...$arguments) {}
function current_user_can($capability) { return $capability === 'manage_capabilities_editor_features'; }
function is_multisite() { return false; }
function wp_unslash($value) { return $value; }
function sanitize_key($value) { return strtolower($value); }
function wp_verify_nonce($nonce, $action) { return $nonce === 'valid' && $action === 'pp-capabilities-editor-features'; }
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
$plugin_page = 'pp-capabilities-editor-features';
$_SERVER['REQUEST_METHOD'] = 'POST';
foreach (['administrator', 'contributor'] as $role) {
    $_POST = ['_wpnonce' => 'valid', 'ppc-editor-features-role' => $role];
    $denial = 0;
    try {
        $manager->validateEditorFeaturesSubmission();
    } catch (RuntimeException $error) {
        $denial = $error->getCode();
    }
    if (403 !== $denial) {
        throw new RuntimeException('Protected Editor Features role must be denied with HTTP 403.');
    }
}
$_POST['ppc-editor-features-role'] = 'author';
$manager->validateEditorFeaturesSubmission();
echo "Editor Features role-scope checks passed.\n";
