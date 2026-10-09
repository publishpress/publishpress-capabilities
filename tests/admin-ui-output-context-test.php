<?php

$root = dirname(__DIR__);
$admin = file_get_contents($root . '/includes/admin.php');
$styles_ui = file_get_contents($root . '/includes/features/admin-styles/admin-styles-ui.php');
$styles_endpoint = file_get_contents($root . '/includes/features/admin-styles/admin-styles-css.php');
$styles_output = file_get_contents($root . '/includes/features/admin-styles/admin-styles.php');

$checks = [
    'Capability rows must not pass form controls through wp_kses_post().' => false === strpos($admin, 'wp_kses_post($row)'),
    'The custom-style confirmation must explicitly allow its submit input.' => false !== strpos($styles_ui, "'input' => ["),
    'Generated text/css must not be HTML-escaped.' => false === strpos($styles_endpoint, 'esc_html(ppc_generate_custom_scheme_css'),
    'Inline generated CSS must not be HTML-escaped.' => false === strpos($styles_output, 'esc_html($css)'),
];

foreach ($checks as $message => $passed) {
    if (!$passed) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

echo "Admin UI output context test passed.\n";
