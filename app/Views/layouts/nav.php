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

// Telas sem título próprio: usa o nome da seção pela URL
if ($breadcrumb === $appName) {
    $secoes = [
        'turmas' => 'Turmas', 'alunos' => 'Alunos', 'projetos' => 'Projetos', 'grupos' => 'Grupos',
        'atas' => 'Atas', 'criterios' => 'Critérios', 'materiais' => 'Materiais', 'solicitacoes' => 'Materiais',
        'notificacoes' => 'Notificações', 'user' => 'Minha conta', 'lgpd' => 'Privacidade',
        'master' => 'Master', 'tutorial' => 'Tutorial', 'diretor' => 'Diretor',
        'configuracoes' => 'Configurações', 'sobre' => 'Sobre', 'termos' => 'Termos', 'usuarios' => 'Usuários',
    ];
    $rotaNav = trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), strlen($basePath)), '/');
    $breadcrumb = $secoes[explode('/', $rotaNav)[0]] ?? ($rotaNav === '' ? 'Painel' : $appName);
}

$inicialApp = strtoupper(substr($appName, 0, 1));

// Papel exibido embaixo do nome: o maior papel que a pessoa tem hoje
$papelExibido = '';
if ($usuario) {
    if (($usuario['tipo'] ?? '') === 'master') {
        $papelExibido = 'Master';
    } else {
        try {
            $pdoNav = Database::getConnection();
            $stNav = $pdoNav->prepare("SELECT 1 FROM turma_usuarios WHERE usuario_id = ? AND papel = 'representante' AND ativo = 1 LIMIT 1");
            $stNav->execute([(int) $usuario['id']]);
            if ($stNav->fetchColumn()) {
                $papelExibido = 'Representante';
            } else {
                $stNav = $pdoNav->prepare("SELECT 1 FROM grupo_diretores WHERE usuario_id = ? AND ativo = 1 LIMIT 1");
                $stNav->execute([(int) $usuario['id']]);
                $papelExibido = $stNav->fetchColumn() ? 'Diretor' : 'Aluno';
            }
        } catch (\Throwable $e) {
            $papelExibido = 'Aluno';
        }
    }
}
?>
<div class="app-shell">

    <!-- ============================================================ -->
    <!-- SIDEBAR                                                       -->
    <!-- ============================================================ -->
    <aside class="sidebar" id="sidebar" aria-label="Navegação principal">

        <div class="sidebar-header">
            <a class="brand" href="<?= $basePath ?>/">
                <div class="brand-mark"><?= ViewHelper::marcaPrumo() ?></div>
                <div>
                    <strong><?= h($appName) ?></strong>
                    <span>Turmas e grêmios</span>
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
                        <small><?= h($papelExibido) ?></small>
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
                    <span>/</span>
                    <strong><?= h($breadcrumb) ?></strong>
                </div>
            </div>

            <div class="topbar-actions">

                <?php if ($logado): ?>
                    <button type="button" class="busca-botao" data-abrir-busca aria-label="Buscar função (Ctrl+K)">
                        <i class="fas fa-magnifying-glass"></i>
                        <span>Buscar função…</span>
                        <kbd>Ctrl K</kbd>
                    </button>
                <?php endif; ?>

                <div class="theme-picker" role="group" aria-label="Tema">
                    <button type="button" class="tema-btn" data-tema="light" title="Tema claro" aria-label="Tema claro"><i class="fas fa-sun"></i></button>
                    <button type="button" class="tema-btn" data-tema="dark" title="Tema escuro" aria-label="Tema escuro"><i class="fas fa-moon"></i></button>
                    <button type="button" class="tema-btn" data-tema="misto" title="Tema misto" aria-label="Tema misto"><i class="fas fa-circle-half-stroke"></i></button>
                </div>

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
<?php require_once __DIR__ . '/flashes.php'; ?>
