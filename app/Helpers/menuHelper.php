<?php
// app/Helpers/menuHelper.php

require_once __DIR__ . '/../Core/App.php';

class MenuHelper
{
    private static $config  = null;
    private static $modules = null;
    private static $usuario = null;

    private static function load(): void
    {
        if (self::$config === null) {
            $file = __DIR__ . '/../Config/menu.php';
            self::$config = file_exists($file) ? require $file : ['menu' => []];
        }
        if (self::$modules === null) {
            $file = __DIR__ . '/../Config/modules.php';
            self::$modules = file_exists($file) ? require $file : ['modules' => []];
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        self::$usuario = $_SESSION['usuario'] ?? null;
    }

    private static function moduloAtivo(?string $modulo): bool
    {
        if (empty($modulo)) return true;
        $mods = self::$modules['modules'] ?? [];
        return !empty($mods[$modulo]);
    }

    private static function permitido(array $itemRoles): bool
    {
        if (empty($itemRoles)) return true;          // público
        if (!self::$usuario)  return false;          // deslogado não vê item restrito
        $tipo = (string) (self::$usuario['tipo'] ?? '');
        return in_array($tipo, $itemRoles, true);
    }

    /**
     * Renderiza os links do menu principal (usado dentro de <nav>).
     */
    public static function render(): string
    {
        self::load();

        $items = self::$config['menu'] ?? [];
        $links = [];

        foreach ($items as $item) {
            if (!self::moduloAtivo($item['module'] ?? null)) continue;
            if (!self::permitido($item['roles'] ?? []))       continue;

            $url   = App::getBasePath() . ($item['url'] ?? '#');
            $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');

            $links[] = '<a href="' . $url . '">' . $label . '</a>';
        }

        return implode(' — ', $links);
    }

    public static function isModuleEnabled(string $modulo): bool
    {
        self::load();
        return self::moduloAtivo($modulo);
    }
}