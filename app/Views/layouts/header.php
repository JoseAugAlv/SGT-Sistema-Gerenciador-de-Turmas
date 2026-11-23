<?php
// app/Views/layouts/header.php
require_once __DIR__ . '/../../Core/App.php';
require_once __DIR__ . '/../../Helpers/ViewHelper.php';
require_once __DIR__ . '/../../Middleware/CsrfMiddleware.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

$basePath     = App::getBasePath();
$appName      = App::getName();
$tituloPagina = $tituloPagina ?? $appName;
$cssPagina = $cssPagina ?? null;
if (is_string($cssPagina)) $cssPagina = [$cssPagina];
if (!is_array($cssPagina))  $cssPagina = [];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= h(CsrfMiddleware::generateToken()) ?>">
    <title><?= h($tituloPagina) ?></title>

    <!-- Fontes -->
    <link rel="icon" type="image/png" href="<?= ViewHelper::asset('img/logo-prumo.png') ?>">
    <!-- App instalável (PWA) -->
    <link rel="manifest" href="<?= App::getBasePath() ?>/manifest.webmanifest">
    <meta name="theme-color" content="#1d4ed8">
    <meta name="app-base" content="<?= h(App::getBasePath()) ?>">
    <?php if (!empty($_SESSION['usuario']['id'])): ?><meta name="usuario-id" content="<?= (int) $_SESSION['usuario']['id'] ?>"><?php endif; ?>
    <link rel="apple-touch-icon" href="<?= App::getBasePath() ?>/public/img/logo-prumo.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Public+Sans:wght@400;500;600;700;800&display=swap">

    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS do sistema -->
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/layout.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/components.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/footer.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= ViewHelper::asset('css/tour.css') ?>">

    <?php foreach ($cssPagina as $arquivoCss): ?>
        <link rel="stylesheet" href="<?= h(ViewHelper::asset('css/' . $arquivoCss)) ?>">
    <?php endforeach; ?>

    <!-- Aplica o tema ANTES da página pintar (evita flash) -->
    <script>
        (function () {
            try {
                var key = 'sgt-tema';
                var salvo = localStorage.getItem(key);
                var tema = (salvo === 'dark' || salvo === 'light' || salvo === 'misto')
                    ? salvo
                    : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
                        ? 'dark'
                        : 'light');
                document.documentElement.setAttribute('data-theme', tema);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
</head>
<body>