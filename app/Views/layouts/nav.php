<?php
// app/Views/layouts/nav.php
require_once __DIR__ . '/../../Helpers/menuHelper.php';

$basePath  = App::getBasePath();
$appName   = App::getName();
$logado    = !empty($_SESSION['usuario']);
$usuario   = $_SESSION['usuario'] ?? null;

// Iniciais para o avatar
$iniciais = '?';
if ($usuario) {
    $partes = preg_split('/\s+/', trim($usuario['nome'] ?? ''));
    $ini    = strtoupper(substr($partes[0] ?? '', 0, 1));
    if (count($partes) > 1) {
        $ini .= strtoupper(substr(end($partes), 0, 1));
    }
    $iniciais = $ini ?: '?';
}

// Breadcrumb
$tituloAtual = $tituloPagina;
$breadcrumb  = preg_replace('/\s+—\s+' . preg_quote($appName, '/') . '\s*$/', '', $tituloAtual);
$breadcrumb  = trim($breadcrumb ?: 'Início');

$inicialApp = strtoupper(substr($appName, 0, 1));
?>
<div class="app-shell">

    <!-- ============================================================ -->
    <!-- SIDEBAR                                                       -->
    <!-- ============================================================ -->
    <aside class="sidebar" id="sidebar" aria-label="Navegação principal">

        <div class="sidebar-header">
            <a class="brand" href="<?= $basePath ?>/">
                <div class="brand-mark"><?= h($inicialApp) ?></div>
                <div>
                    <strong><?= h($appName) ?></strong>
                    <span>Gestão de turmas</span>
                </div>
            </a>

            <button type="button"
                    class="sidebar-close"
                    id="sidebar-close"
                    aria-label="Fechar menu">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <nav class="main-nav">
            <?= MenuHelper::renderSidebar() ?>
        </nav>

        <?php if ($logado): ?>
            <div class="sidebar-bottom">
                <a class="help-card" href="<?= $basePath ?>/tutorial">
                    <span class="help-icon"><i class="fas fa-question"></i></span>
                    <span>
                        <strong>Precisa de ajuda?</strong>
                        <small>Veja o tutorial do sistema</small>
                    </span>
                    <span><i class="fas fa-chevron-right"></i></span>
                </a>

                <div class="profile-mini">
                    <a class="avatar avatar-indigo" href="<?= $basePath ?>/user" title="Minha Conta">
                        <?= h($iniciais) ?>
                    </a>
                    <div>
                        <strong><?= h($usuario['nome']) ?></strong>
                        <small><?= h($usuario['tipo']) ?></small>
                    </div>

                    <a class="logout-btn"
                       href="<?= $basePath ?>/logout"
                       title="Sair"
                       onclick="return confirm('Deseja realmente sair do sistema?')">
                        <i class="fas fa-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </aside>

    <!-- Overlay escuro quando a sidebar está aberta (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- ============================================================ -->
    <!-- MAIN                                                          -->
    <!-- ============================================================ -->
    <div class="main-content">

        <header class="topbar">
            <div class="topbar-left">

                <button type="button"
                        class="hamburger"
                        id="hamburger"
                        aria-label="Abrir menu">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="breadcrumb">
                    <span><?= h($appName) ?></span>
                    <span>/</span>
                    <strong><?= h($breadcrumb) ?></strong>
                </div>
            </div>

            <div class="topbar-actions">

                <label class="theme-switch" for="theme-toggle-input" title="Alternar tema claro/escuro">
                    <span><i class="fas fa-sun"></i></span>
                    <input type="checkbox" id="theme-toggle-input" class="theme-toggle-input" hidden>
                    <span class="switch-track"><span class="switch-thumb"></span></span>
                    <span><i class="fas fa-moon"></i></span>
                </label>

                <?php if ($logado): ?>
                    <a class="icon-button notification-button"
                       href="<?= $basePath ?>/notificacoes"
                       title="Notificações">
                        <i class="fas fa-bell"></i>
                        <span id="notif-dot" style="display:none;"></span>
                    </a>

                    <div class="user-menu" id="user-menu">
                        <button type="button"
                                class="avatar avatar-indigo"
                                id="user-menu-toggle"
                                title="Menu da conta"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <?= h($iniciais) ?>
                        </button>

                        <div class="user-menu-dropdown" id="user-menu-dropdown">
                            <div class="user-menu-header">
                                <strong><?= h($usuario['nome']) ?></strong>
                                <small><?= h($usuario['email']) ?></small>
                            </div>

                            <a href="<?= $basePath ?>/user" class="user-menu-item">
                                <i class="fas fa-user"></i> Minha Conta
                            </a>

                            <a href="<?= $basePath ?>/notificacoes/preferencias" class="user-menu-item">
                                <i class="fas fa-sliders"></i> Preferências
                            </a>

                            <a href="<?= $basePath ?>/lgpd/meus-direitos" class="user-menu-item">
                                <i class="fas fa-shield-halved"></i> Meus direitos (LGPD)
                            </a>

                            <div class="user-menu-divider"></div>

                            <a href="<?= $basePath ?>/logout"
                            class="user-menu-item user-menu-logout"
                            onclick="return confirm('Deseja realmente sair do sistema?')">
                                <i class="fas fa-right-from-bracket"></i> Sair
                            </a>
                        </div>
                    </div>
                                    <?php else: ?>
                    <a class="button button-ghost small" href="<?= $basePath ?>/login">
                        <i class="fas fa-right-to-bracket"></i> Entrar
                    </a>
                    <a class="button button-primary small" href="<?= $basePath ?>/login/cadastrar">
                        <i class="fas fa-user-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>

            </div>
        </header>

        <div class="page-wrap">