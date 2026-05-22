<?php
declare(strict_types=1);

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public static function csrfToken(): string
    {
        self::startSession();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function validateCsrf(string $token): bool
    {
        self::startSession();
        return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
    }
}
