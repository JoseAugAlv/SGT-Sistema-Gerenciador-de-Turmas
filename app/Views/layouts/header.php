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
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= h(CsrfMiddleware::generateToken()) ?>">
    <title><?= h($tituloPagina) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>