<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($appName) ?></h1>
<p>Sistema de Gestão de Turmas.</p>
<p>
    <a href="<?= $basePath ?>/login">Entrar</a> —
    <a href="<?= $basePath ?>/login/cadastrar">Cadastrar-se</a>
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>