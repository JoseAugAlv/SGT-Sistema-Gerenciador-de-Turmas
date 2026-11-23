<?php
// app/Config/SessionConfig.php

require_once __DIR__ . '/../Core/App.php';

class SessionConfig
{
    public static function configure()
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        // App::get carrega o .env (antes, $_ENV ainda estava vazio aqui e o cookie nunca ficava "secure")
        $isProduction = App::get('APP_ENV') === 'production';
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_set_cookie_params([
            'lifetime' => 3600,
            'path' => App::getSessionPath(),
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'secure' => $isProduction && $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_name(App::getSessionName());
        session_start();

        if (!isset($_SESSION['last_regenerate'])) {
            $_SESSION['last_regenerate'] = time();
        } elseif (time() - $_SESSION['last_regenerate'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regenerate'] = time();
        }
    }
}