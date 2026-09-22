<?php

function current_filter()
{
    return 'admin_notices';
}

require_once dirname(__DIR__) . '/includes/admin-notices/admin-notices.php';

$reflection = new ReflectionClass('PP_Capabilities_Admin_Notices');
$admin_notices = $reflection->newInstanceWithoutConstructor();
$notice = '<div class="notice"><button id="review">Review</button></div>'
    . '<script>document.getElementById("review").disabled = false;</script>'
    . '<style>#review { display: inline-block; }</style>';

ob_start();
$admin_notices->start_hook_capture();
echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Simulated trusted admin notice callback output.
$admin_notices->end_hook_capture();
$output = ob_get_clean();

if (false === strpos($output, $notice)) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- CLI regression test failure output.
    fwrite(STDERR, "Captured admin notice scripts and styles must be preserved.\n");
    exit(1);
}

echo "Admin notice output test passed.\n";
