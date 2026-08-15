<?php
/**
 * Security helpers: CSRF tokens, output escaping (XSS), request sanitizing.
 */

class Security
{
    /**
     * Generate (once per session / form) and return the CSRF token.
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Echo a hidden CSRF input field.
     */
    public static function csrfField(): string
    {
        $token = self::csrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate a submitted CSRF token. Returns true when valid.
     */
    public static function verifyCsrf(?string $token = null): bool
    {
        $token = $token ?? ($_POST['csrf_token'] ?? null);
        if (!is_string($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * XSS-safe output escaping. Use everywhere you echo user/DB data.
     */
    public static function e($value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize an associative input array (trim, strip tags, null when empty).
     */
    public static function clean(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $out[$key] = self::clean($value);
            } else {
                $value = trim((string)$value);
                $out[$key] = ($value === '') ? null : strip_tags($value);
            }
        }
        return $out;
    }

    /**
     * Abort with the given HTTP status and a plain message.
     */
    public static function abort(int $code = 404, string $message = 'Not Found'): void
    {
        http_response_code($code);
        echo $message;
        exit;
    }

    /**
     * Redirect helper. Uses BASE_URL so it works with or without a vhost.
     */
    public static function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }
}
