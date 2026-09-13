<?php
// app/Helpers/MenuHelper.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Notificacao.php';

class MenuHelper
{
    private static $menuConfig = null;
    private static $modules = null;
    private static $usuario = null;

    private static function loadConfig()
    {
        if (self::$menuConfig === null) {
            $menuFile = __DIR__ . '/../../app/Config/menu.php';
            if (file_exists($menuFile)) {
                self::$menuConfig = require $menuFile;
            } else {
                self::$menuConfig = ['menu' => []];
            }
        }

        if (self::$modules === null) {
            $modulesFile = __DIR__ . '/../../app/Config/modules.php';
            if (file_exists($modulesFile)) {
                self::$modules = require $modulesFile;
            } else {
                self::$modules = ['modules' => []];
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        self::$usuario = $_SESSION['usuario'] ?? null;
    }

    private static function hasRole($itemRoles)
    {
        if (empty($itemRoles)) {
            return true;
        }

        if (!self::$usuario) {
            return false;
        }

        $role = (int) self::$usuario['role'];
        return in_array($role, $itemRoles);
    }

    private static function isModuleActive($module)
    {
        if (empty($module)) {
            return true;
        }

        $modules = self::$modules['modules'] ?? [];
        return isset($modules[$module]) && $modules[$module] === true;
    }

    public static function render()
    {
        self::loadConfig();

        $menuItems = self::$menuConfig['menu'] ?? [];
        $html = '';

        foreach ($menuItems as $item) {
            if (isset($item['module']) && !self::isModuleActive($item['module'])) {
                continue;
            }

            if (!self::hasRole($item['roles'] ?? [])) {
                continue;
            }

            if (isset($item['subitems']) && !empty($item['subitems'])) {
                $subitems = array_filter($item['subitems'], function($sub) {
                    if (isset($sub['module']) && !self::isModuleActive($sub['module'])) {
                        return false;
                    }
                    return self::hasRole($sub['roles'] ?? []);
                });

                if (!empty($subitems)) {
                    $item['subitems'] = $subitems;
                    $html .= self::renderDropdown($item);
                }
            } else {
                $html .= self::renderItem($item);
            }
        }

        return $html;
    }

    private static function renderItem($item)
    {
        $label = htmlspecialchars($item['label']);
        $icon = $item['icon'] ?? 'fas fa-circle';
        $url = self::getUrl($item['url'] ?? '#');
        $badge = self::getBadge($item['badge'] ?? null);

        return "<li><a href=\"{$url}\"><i class=\"{$icon}\"></i> {$label}{$badge}</a></li>";
    }

    private static function renderDropdown($item)
    {
        $label = htmlspecialchars($item['label']);
        $icon = $item['icon'] ?? 'fas fa-circle';
        $badge = self::getBadge($item['badge'] ?? null);
        
        // ID único para o submenu
        $submenuId = 'submenu_' . md5($item['label'] . uniqid());

        $html = "<li class=\"has-submenu\">";
        $html .= "<a href=\"javascript:void(0)\" class=\"submenu-toggle\" data-target=\"{$submenuId}\">";
        $html .= "<i class=\"{$icon}\"></i>";
        $html .= " {$label}{$badge}";
        $html .= " <i class=\"fas fa-chevron-down sub-arrow\"></i>";
        $html .= "</a>";
        $html .= "<ul class=\"submenu\" id=\"{$submenuId}\">";

        foreach ($item['subitems'] as $subitem) {
            $subLabel = htmlspecialchars($subitem['label']);
            $subIcon = $subitem['icon'] ?? 'fas fa-circle';
            $subUrl = self::getUrl($subitem['url'] ?? '#');
            $subBadge = self::getBadge($subitem['badge'] ?? null);

            $html .= "<li>";
            $html .= "<a href=\"{$subUrl}\">";
            $html .= "<i class=\"{$subIcon}\"></i> {$subLabel}{$subBadge}";
            $html .= "</a>";
            $html .= "</li>";
        }

        $html .= "</ul>";
        $html .= "</li>";

        return $html;
    }

    public static function renderSidebar()
    {
        self::loadConfig();

        $menuItems = self::$menuConfig['sidebar_menu'] ?? self::$menuConfig['menu'] ?? [];
        $html = '';
        $hasItems = false;

        foreach ($menuItems as $item) {
            if (isset($item['module']) && !self::isModuleActive($item['module'])) {
                continue;
            }

            if (!self::hasRole($item['roles'] ?? [])) {
                continue;
            }

            if (isset($item['subitems']) && !empty($item['subitems'])) {
                $subitems = array_filter($item['subitems'], function($sub) {
                    if (isset($sub['module']) && !self::isModuleActive($sub['module'])) {
                        return false;
                    }
                    return self::hasRole($sub['roles'] ?? []);
                });

                if (!empty($subitems)) {
                    $item['subitems'] = $subitems;
                    $html .= self::renderSidebarDropdown($item);
                    $hasItems = true;
                }
            } else {
                $html .= self::renderSidebarItem($item);
                $hasItems = true;
            }
        }

        return $hasItems ? $html : '';
    }

    private static function renderSidebarItem($item)
    {
        $label = htmlspecialchars($item['label']);
        $icon = $item['icon'] ?? 'fas fa-circle';
        $url = self::getUrl($item['url'] ?? '#');
        $badge = self::getBadge($item['badge'] ?? null);

        return "<a href=\"{$url}\"><i class=\"{$icon}\"></i> {$label}{$badge}</a>";
    }

    private static function renderSidebarDropdown($item)
    {
        $label = htmlspecialchars($item['label']);
        $icon = $item['icon'] ?? 'fas fa-circle';

        $html = "<div class=\"sidebar-divider\"></div>";
        $html .= "<div class=\"sidebar-title\"><i class=\"{$icon}\"></i> {$label}</div>";

        foreach ($item['subitems'] as $subitem) {
            $subLabel = htmlspecialchars($subitem['label']);
            $subIcon = $subitem['icon'] ?? 'fas fa-circle';
            $subUrl = self::getUrl($subitem['url'] ?? '#');
            $subBadge = self::getBadge($subitem['badge'] ?? null);

            $html .= "<a href=\"{$subUrl}\"><i class=\"{$subIcon}\"></i> {$subLabel}{$subBadge}</a>";
        }

        return $html;
    }

    private static function getUrl($url)
    {
        $basePath = App::getBasePath();

        if (strpos($url, 'http') === 0) {
            return $url;
        }

        if ($url === '#') {
            return '#';
        }

        return $basePath . $url;
    }

    private static function getBadge($badgeKey)
    {
        if (empty($badgeKey)) {
            return '';
        }

        $count = 0;

        if ($badgeKey === 'notificacoes' && self::$usuario) {
            try {
                $notificacao = new Notificacao();
                $count = $notificacao->contarNaoLidas(self::$usuario['id']);
            } catch (Exception $e) {
                $count = 0;
            }
        }

        if ($count > 0) {
            return " <span class=\"badge-notificacao\">{$count}</span>";
        }

        return '';
    }

    public static function isModuleEnabled($module)
    {
        self::loadConfig();
        $modules = self::$modules['modules'] ?? [];
        return isset($modules[$module]) && $modules[$module] === true;
    }

    public static function getActiveModules()
    {
        self::loadConfig();
        $modules = self::$modules['modules'] ?? [];
        $active = [];

        foreach ($modules as $key => $value) {
            if ($value === true) {
                $active[] = $key;
            }
        }

        return $active;
    }

    public static function hasSpecificRole($role)
    {
        if (!self::$usuario) {
            return false;
        }
        return (int) self::$usuario['role'] === $role;
    }

    public static function getUserRole()
    {
        return self::$usuario['role'] ?? null;
    }

    public static function getRoleName()
    {
        $roles = [
            1 => 'Master',
            2 => 'Admin',
            3 => 'Operador',
            4 => 'Usuario'
        ];
        return $roles[self::$usuario['role'] ?? 4] ?? 'Visitante';
    }
}