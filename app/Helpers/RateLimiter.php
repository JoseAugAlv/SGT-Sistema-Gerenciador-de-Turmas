<?php
// app/Helpers/RateLimiter.php

class RateLimiter
{
    private static function getKey(string $identifier): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'login_attempts_' . md5($ip . '_' . $identifier);
    }
    
    public static function check(string $identifier, int $maxAttempts = 5, int $timeWindow = 300): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = self::getKey($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
            return true;
        }
        
        $data = $_SESSION[$key];
        $elapsed = time() - $data['first_attempt'];
        
        // Reset após o tempo limite
        if ($elapsed > $timeWindow) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
            return true;
        }
        
        if ($data['count'] >= $maxAttempts) {
            return false;
        }
        
        return true;
    }
    
    public static function increment(string $identifier): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = self::getKey($identifier);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
            return;
        }
        
        $_SESSION[$key]['count']++;
    }
    
    public static function reset(string $identifier): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $key = self::getKey($identifier);
        unset($_SESSION[$key]);
    }
}