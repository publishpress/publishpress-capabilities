<?php

function __($text)
{
    return $text;
}

require_once dirname(__DIR__) . '/includes/admin-load.php';

$reflection = new ReflectionClass('PP_Capabilities_Admin_UI');
$admin_ui = $reflection->newInstanceWithoutConstructor();

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Simulate the missing WordPress admin title that caused the regression.
$title = null;
$admin_ui->setUserCapabilitiesPageTitle();

if ('User Capabilities' !== $title) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- CLI regression test failure output.
    fwrite(STDERR, "The hidden user capabilities page must set a non-null admin title.\n");
    exit(1);
}

echo "User capabilities page title test passed.\n";
