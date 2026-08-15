<?php
/** Secure session bootstrap (HTTP-only cookie, SameSite=Lax). */

require_once __DIR__ . DIRECTORY_SEPARATOR . 'env.php';

if (session_status() === PHP_SESSION_NONE) {
    $secure   = filter_var(env('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN);
    $httpOnly = filter_var(env('SESSION_HTTP_ONLY', true), FILTER_VALIDATE_BOOLEAN);

    // Hardening flags supported by modern PHP.
    if (function_exists('session_set_cookie_params')) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Lax',
        ]);
    }

    session_name('STOCKWISE_SESSION');
    session_start();
}
