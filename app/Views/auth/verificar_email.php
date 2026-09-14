<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Confirme seu email</h1>
<p>Enviamos um link de confirmação para <strong><?= h($_SESSION['usuario']['email'] ?? '') ?></strong>.</p>
<p>Abra o email, clique no link e faça login novamente.</p>

<form method="POST" action="<?= $basePath ?>/auth/reenviar-confirmacao">
    <?= ViewHelper::csrfField() ?>
    <button type="submit">Reenviar email de confirmação</button>
</form>

<p><a href="<?= $basePath ?>/logout">Sair</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>