<?php
// app/Core/App.php

class App
{
    private static $config = null;

    public static function init()
    {
        if (self::$config === null) {
            $envFile = __DIR__ . '/../../.env';
            
            if (!file_exists($envFile)) {
                // Cria .env padrão se não existir
                $defaultEnv = "APP_NAME=ProjetoBase\n";
                $defaultEnv .= "APP_URL=http://localhost/ProjetoBase\n";
                $defaultEnv .= "APP_BASE_PATH=/ProjetoBase\n";
                $defaultEnv .= "DB_HOST=127.0.0.1\n";
                $defaultEnv .= "DB_PORT=3306\n";
                $defaultEnv .= "DB_NAME=ProjetoBase\n";
                $defaultEnv .= "DB_USER=root\n";
                $defaultEnv .= "DB_PASS=\n";
                $defaultEnv .= "MAIL_HOST=smtp.gmail.com\n";
                $defaultEnv .= "MAIL_PORT=587\n";
                $defaultEnv .= "MAIL_USER=\n";
                $defaultEnv .= "MAIL_PASS=\n";
                $defaultEnv .= "MAIL_FROM_NAME=ProjetoBase\n";
                file_put_contents($envFile, $defaultEnv);
            }

            self::$config = parse_ini_file($envFile);
            
            if (self::$config === false) {
                self::$config = [];
            }
            
            // Carregar como variáveis de ambiente também
            foreach (self::$config as $key => $value) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    public static function get($key, $default = null)
    {
        self::init();
        return self::$config[$key] ?? $_ENV[$key] ?? $default;
    }

    public static function getName()
    {
        return self::get('APP_NAME', 'ProjetoBase');
    }

    public static function getBasePath()
    {
        return self::get('APP_BASE_PATH', '/' . self::getName());
    }

    public static function getUrl()
    {
        return self::get('APP_URL', 'http://localhost' . self::getBasePath());
    }

    public static function getSessionName()
    {
        return strtoupper(str_replace(' ', '_', self::getName())) . '_SESSION';
    }

    public static function getSessionPath()
    {
        return self::getBasePath();
    }

    /**
     * Retorna todas as configurações
     */
    public static function getAll()
    {
        self::init();
        return self::$config;
    }
}