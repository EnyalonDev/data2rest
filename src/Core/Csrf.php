<?php

namespace App\Core;

/**
 * Cross-Site Request Forgery (CSRF) Protection
 * Generates and verifies security tokens to prevent unauthorized form submissions.
 */
class Csrf
{
    /**
     * Retrieves the current CSRF token from the session, generating one if it doesn't exist.
     * 
     * @return string
     */
    public static function getToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifies if the provided token matches the one in the session.
     * 
     * @param string|null $token
     * @return bool
     */
    public static function verify($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $stored = $_SESSION['csrf_token'] ?? null;

        if (!$stored || !$token) {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /**
     * Renders a hidden input field with the CSRF token.
     * Useful for direct inclusion in Blade templates.
     * 
     * @return string
     */
    public static function field()
    {
        $token = self::getToken();
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}
