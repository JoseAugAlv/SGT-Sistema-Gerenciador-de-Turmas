<?php
// app/Config/database.php

require_once __DIR__ . '/../Core/App.php';

class Database
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection === null) {
            try {
                // Carregar configurações do .env via App
                App::init();
                
                $host = App::get('DB_HOST', '127.0.0.1');
                $dbname = App::get('DB_NAME', 'ProjetoBase');
                $user = App::get('DB_USER', 'root');
                $pass = App::get('DB_PASS', '');
                $port = App::get('DB_PORT', '3306');

                // Verificar se o host já contém a porta
                if (strpos($host, ':') !== false) {
                    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8";
                } else {
                    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8";
                }

                self::$connection = new PDO(
                    $dsn,
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("Erro de conexão com o banco: " . $e->getMessage());
                throw new Exception("Erro de conexão com o banco de dados: " . $e->getMessage());
            }
        }

        return self::$connection;
    }

    /**
     * Fecha a conexão (útil para testes)
     */
    public static function closeConnection()
    {
        self::$connection = null;
    }

    /**
     * Testa a conexão com o banco
     */
    public static function testConnection(): bool
    {
        try {
            self::getConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}