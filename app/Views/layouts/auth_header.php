<?php
// app/Views/layouts/auth_header.php
require_once __DIR__ . '/../../Core/App.php';
require_once __DIR__ . '/../../Helpers/ViewHelper.php';
require_once __DIR__ . '/../../Middleware/CsrfMiddleware.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

$basePath     = App::getBasePath();
$appName      = App::getName();
$tituloPagina = $tituloPagina ?? $appName;

$inicialApp = strtoupper(substr($appName, 0, 1));
$ano        = date('Y');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= h(CsrfMiddleware::generateToken()) ?>">
    <title><?= h($tituloPagina) ?></title>

    <link rel="icon" type="image/png" href="<?= ViewHelper::asset('img/logo-prumo.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Public+Sans:wght@400;500;600;700;800&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet" href="<?= ViewHelper::asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/layout.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/components.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/auth.css') ?>">

    <script>
        (function () {
            try {
                var key = 'sgt-tema';
                var salvo = localStorage.getItem(key);
                var tema = (salvo === 'dark' || salvo === 'light' || salvo === 'misto')
                    ? salvo
                    : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', tema);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body class="auth-body">

<div class="auth-shell">

    <!-- ============================================================ -->
    <!-- Painel lateral                                                -->
    <!-- ============================================================ -->
    <aside class="auth-side">
        <a class="auth-side-brand" href="<?= $basePath ?>/">
            <span class="brand-mark"><?= ViewHelper::marcaPrumo() ?></span>
            <div>
                <strong><?= h($appName) ?></strong>
                <span>Turmas e grêmios</span>
            </div>
        </a>

        <div class="auth-side-hero">
            <h2><?= h($authSideTitle ?? 'Organize turmas, projetos e avaliações.') ?></h2>
            <p><?= h($authSideSubtitle ?? 'Simples para o aluno, poderoso para o professor.') ?></p>

            <?php if (empty($authSideNoList)): ?>
                <ul class="auth-side-features">
                    <li>
                        <span class="feat-icone"><i class="fas fa-users"></i></span>
                        <span>Grupos com histórico de diretores e avaliação por pares, auto e coletiva.</span>
                    </li>
                    <li>
                        <span class="feat-icone"><i class="fas fa-book"></i></span>
                        <span>Atas com atividades, relatórios internos e fluxo de validação.</span>
                    </li>
                    <li>
                        <span class="feat-icone"><i class="fas fa-chart-line"></i></span>
                        <span>Boletim em tempo real com cálculo automático de média ponderada.</span>
                    </li>
                    <li>
                        <span class="feat-icone"><i class="fas fa-boxes"></i></span>
                        <span>Materiais com solicitação aberta a todos e controle de estoque.</span>
                    </li>
                </ul>

                <div class="auth-side-card">
                    <div class="sc-label">
                        <span class="sc-dot"></span>
                        <?= h(App::getName()) ?> · <?= h(App::get('APP_ENV', 'production') === 'development' ? 'Desenvolvimento' : 'Produção') ?>
                    </div>
                    <div class="sc-linha">
                        <span class="sc-key">Versão</span>
                        <span class="sc-val neu">v2.4</span>
                    </div>
                    <div class="sc-linha">
                        <span class="sc-key">Autenticação</span>
                        <span class="sc-val pos">Ativa</span>
                    </div>
                    <div class="sc-linha">
                        <span class="sc-key">Criptografia</span>
                        <span class="sc-val pos">Bcrypt 12</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="auth-side-footer">
            &copy; <?= $ano ?> <?= h($appName) ?> — Organização de turmas e grêmios
        </div>
    </aside>

    <!-- ============================================================ -->
    <!-- Painel principal                                              -->
    <!-- ============================================================ -->
    <main class="auth-main">

        <!-- Toggle de tema -->
        <div class="auth-theme-toggle" role="group" aria-label="Alternar tema">
            <button type="button" class="tema-btn" data-tema="light" title="Tema claro" aria-label="Tema claro">
                <i class="fas fa-sun"></i>
            </button>
            <button type="button" class="tema-btn" data-tema="dark" title="Tema escuro" aria-label="Tema escuro">
                <i class="fas fa-moon"></i>
            </button>
            <button type="button" class="tema-btn" data-tema="misto" title="Tema misto" aria-label="Tema misto">
                <i class="fas fa-circle-half-stroke"></i>
            </button>
        </div>

        <div class="auth-form-wrap">

            <a class="auth-mobile-brand" href="<?= $basePath ?>/">
                <span class="brand-mark"><?= ViewHelper::marcaPrumo() ?></span>
                <strong><?= h($appName) ?></strong>
            </a>

            <?php require_once __DIR__ . '/flashes.php'; ?>