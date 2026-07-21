<?php

function sanitize_key($key) {
    return preg_replace('/[^a-z0-9_]+/', '_', strtolower((string) $key));
}

function sanitize_text_field($value) {
    return is_scalar($value) ? (string) $value : '';
}

function current_user_can($capability) {
    return in_array($capability, ['read', 'manage_capabilities_backup'], true);
}

function is_multisite() {
    return false;
}

function wp_die($message = '') {
    throw new Exception($message);
}

function esc_html__($text, $domain = null) {
    return $text;
}

require_once __DIR__ . '/../includes/backup-handler.php';

$handler = new Capsman_BackupHandler(new stdClass());
$input = [
    'editor' => [
        'name' => 'Editor',
        'capabilities' => [
            'read' => '1',
            'manage_capabilities' => '1',
            'manage_capabilities_backup' => '1',
            'manage_options' => '1',
        ],
    ],
];

$result = $handler->santize_import_role($input);

if (!isset($result['editor']['capabilities']['read']) || '1' !== $result['editor']['capabilities']['read']) {
    fwrite(STDERR, "Expected read capability to be preserved.\n");
    exit(1);
}

if (isset($result['editor']['capabilities']['manage_capabilities'])) {
    fwrite(STDERR, "manage_capabilities should be stripped from imported roles for non-administrators.\n");
    exit(1);
}

if (!isset($result['editor']['capabilities']['manage_capabilities_backup']) || '1' !== $result['editor']['capabilities']['manage_capabilities_backup']) {
    fwrite(STDERR, "Expected backup capability to be preserved.\n");
    exit(1);
}

if (isset($result['editor']['capabilities']['manage_options'])) {
    fwrite(STDERR, "Other admin-only capabilities should be stripped from imported roles.\n");
    exit(1);
}

echo "PASS: imported roles only preserve capabilities the current user can grant.\n";
