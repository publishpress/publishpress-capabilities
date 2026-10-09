<?php

const DAY_IN_SECONDS = 86400;
const HOUR_IN_SECONDS = 3600;

$valid_cookie = 'administrator|2000000000|original-session-token|signature';

function wp_validate_auth_cookie($cookie, $scheme = '')
{
    global $valid_cookie;

    return 'logged_in' === $scheme && $valid_cookie === $cookie ? 7 : false;
}

function wp_parse_auth_cookie($cookie, $scheme = '')
{
    global $valid_cookie;

    if ('logged_in' !== $scheme || $valid_cookie !== $cookie) {
        return false;
    }

    return [
        'username' => 'administrator',
        'expiration' => 2000000000,
        'token' => 'original-session-token',
        'hmac' => 'signature',
    ];
}

class WP_Session_Tokens
{
    public static function get_instance($user_id)
    {
        return new self();
    }

    public function verify($token)
    {
        return 'original-session-token' === $token;
    }
}

require_once dirname(__DIR__) . '/includes/test-user.php';

$method = new ReflectionMethod('PP_Capabilities_Test_User', 'getRestorableSessionToken');
$method->setAccessible(true);

$restored_token = $method->invoke(null, $valid_cookie, 7);
if ('original-session-token' !== $restored_token) {
    fwrite(STDERR, "Expected the original verified session token to be restored.\n");
    exit(1);
}

if ('' !== $method->invoke(null, $valid_cookie, 8)) {
    fwrite(STDERR, "A stored cookie must not restore a token for another user.\n");
    exit(1);
}

if ('' !== $method->invoke(null, 'invalid-cookie', 7)) {
    fwrite(STDERR, "An invalid stored cookie must not restore a session token.\n");
    exit(1);
}

echo "Test-user session token test passed.\n";
