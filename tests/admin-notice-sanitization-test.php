<?php

function wp_kses_post($content)
{
    return $content;
}

require_once dirname(__DIR__) . '/includes/admin-notices/admin-notices.php';

$reflection = new ReflectionClass('PP_Capabilities_Admin_Notices');
$admin_notices = $reflection->newInstanceWithoutConstructor();
$method = $reflection->getMethod('sanitize_admin_notices');
$method->setAccessible(true);

$notice = '<div class="notice"><p>Review prompt</p><script>function delayReviewPrompt() { window.open("test"); }</script><style>.notice { color: red; }</style></div>';
$sanitized = $method->invoke($admin_notices, $notice);

if (false !== strpos($sanitized, 'delayReviewPrompt') || false !== strpos($sanitized, 'color: red')) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- CLI regression test failure output.
    fwrite(STDERR, "Captured script or style contents must not be exposed as admin text.\n");
    exit(1);
}

if (false === strpos($sanitized, 'Review prompt')) {
    // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- CLI regression test failure output.
    fwrite(STDERR, "Sanitization must preserve the admin notice content.\n");
    exit(1);
}

echo "Admin notice sanitization test passed.\n";
