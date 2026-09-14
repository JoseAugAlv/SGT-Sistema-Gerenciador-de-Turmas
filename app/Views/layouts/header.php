<?php
// app/Views/layouts/header.php
require_once __DIR__ . '/../../Core/App.php';
require_once __DIR__ . '/../../Middleware/CsrfMiddleware.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

$basePath     = App::getBasePath();
$appName      = App::getName();
$tituloPagina = $tituloPagina ?? $appName;
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= h(CsrfMiddleware::generateToken()) ?>">
    <title><?= h($tituloPagina) ?></title>

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">

    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- CSS do sistema -->
    <link rel="stylesheet" href="<?= $basePath ?>/public/css/base.css">
    <link rel="stylesheet" href="<?= $basePath ?>/public/css/layout.css">
    <link rel="stylesheet" href="<?= $basePath ?>/public/css/components.css">
    <link rel="stylesheet" href="<?= $basePath ?>/public/css/responsive.css">

    <!-- Aplica o tema ANTES da página pintar (evita flash) -->
    <script>
        (function () {
            try {
                var key = 'sgt-tema';
                var salvo = localStorage.getItem(key);
                var tema = (salvo === 'dark' || salvo === 'light')
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