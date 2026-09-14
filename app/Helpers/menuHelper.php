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

    private static function permitido(array $item): bool
    {
        $roles     = $item['roles'] ?? [];
        $guestOnly = !empty($item['guest_only']);
        $logado    = self::$usuario !== null;

        if ($guestOnly) return !$logado;
        if (empty($roles)) return true;
        if (!$logado) return false;

        $tipo = (string) (self::$usuario['tipo'] ?? '');
        return in_array($tipo, $roles, true);
    }

    private static function hrefAtivo(string $url, string $caminhoAtual): bool
    {
        $caminhoItem = parse_url($url, PHP_URL_PATH);

        // Raiz só ativa se for exatamente "/" ou o base path
        if ($caminhoItem === '/' || $caminhoItem === '') {
            return $caminhoAtual === '/' || $caminhoAtual === App::getBasePath() . '/';
        }

        return $caminhoItem === $caminhoAtual;
    }

    /**
     * Renderiza a sidebar (agrupada por seção).
     */
    public static function renderSidebar(): string
    {
        self::load();

        $items = self::$config['menu'] ?? [];
        $uri   = $_SERVER['REQUEST_URI'] ?? '';
        $base  = App::getBasePath();
        $caminhoAtual = parse_url($uri, PHP_URL_PATH);

        // Agrupa por seção mantendo a ordem de aparição
        $grupos = [];
        foreach ($items as $item) {
            if (!self::moduloAtivo($item['module'] ?? null)) continue;
            if (!self::permitido($item))                     continue;
            if (($item['type'] ?? 'link') === 'text')        continue; // não vai pra sidebar

            $sec = $item['secao'] ?? null;
            $grupos[$sec][] = $item;
        }

        if (empty($grupos)) return '';

        $html = '';
        foreach ($grupos as $secao => $itens) {
            if ($secao !== null && $secao !== '') {
                $html .= '<div class="nav-label">' . htmlspecialchars($secao, ENT_QUOTES, 'UTF-8') . '</div>';
            }

            foreach ($itens as $item) {
                $type = $item['type'] ?? 'link';

                // ---------- type=notif ----------
                if ($type === 'notif') {
                    $url = $base . ($item['url'] ?? '/notificacoes');
                    $ativo = self::hrefAtivo($url, $caminhoAtual);
                    $icon  = htmlspecialchars($item['icon'] ?? 'fas fa-bell', ENT_QUOTES, 'UTF-8');
                    $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');

                    $class = 'nav-item' . ($ativo ? ' active' : '');
                    $html .= '<a class="' . $class . '" href="' . $url . '">'
                          . '<i class="nav-icon ' . $icon . '"></i>'
                          . '<span>' . $label . '</span>'
                          . '<b id="notif-badge" style="display:none;">0</b>'
                          . '</a>';
                    continue;
                }

                // ---------- link padrão ----------
                $url   = $base . ($item['url'] ?? '#');
                $ativo = self::hrefAtivo($url, $caminhoAtual);
                $icon  = htmlspecialchars($item['icon'] ?? 'fas fa-circle', ENT_QUOTES, 'UTF-8');
                $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');

                $class   = 'nav-item' . ($ativo ? ' active' : '');
                $confirm = '';
                if (!empty($item['confirm'])) {
                    $msg     = htmlspecialchars($item['confirm'], ENT_QUOTES, 'UTF-8');
                    $confirm = ' onclick="return confirm(\'' . $msg . '\')"';
                }

                $html .= '<a class="' . $class . '" href="' . $url . '"' . $confirm . '>'
                      . '<i class="nav-icon ' . $icon . '"></i>'
                      . '<span>' . $label . '</span>'
                      . '</a>';
            }
        }

        return $html;
    }

    public static function isModuleEnabled(string $modulo): bool
    {
        self::load();
        return self::moduloAtivo($modulo);
    }
}