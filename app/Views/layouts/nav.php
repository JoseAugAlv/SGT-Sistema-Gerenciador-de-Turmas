<?php
// app/Views/layouts/nav.php
require_once __DIR__ . '/../../Helpers/menuHelper.php';

$basePath = App::getBasePath();
$logado   = !empty($_SESSION['usuario']);
$usuario  = $_SESSION['usuario'] ?? null;
?>
<nav>
    <strong><?= h(App::getName()) ?></strong>
    —
    <?= MenuHelper::render() ?>
    <?php if ($logado): ?>
        —
        <span>Olá, <?= h($usuario['nome']) ?> (<?= h($usuario['tipo']) ?>)</span>
        —
        <a href="<?= $basePath ?>/logout">Sair</a>
    <?php else: ?>
        —
        <a href="<?= $basePath ?>/login">Entrar</a>
        —
        <a href="<?= $basePath ?>/login/cadastrar">Cadastrar</a>
    <?php endif; ?>
</nav>
<hr>