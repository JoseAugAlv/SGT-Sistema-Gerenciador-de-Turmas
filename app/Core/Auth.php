<?php

class Auth
{
    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['usuario']);
    }

    public static function user()
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function role(): ?int
    {
        $user = self::user();
        return $user['role'] ?? null;
    }

    public static function hasRole(array $allowedRoles): bool
    {
        $role = self::role();
        return $role !== null && in_array($role, $allowedRoles);
    }
}