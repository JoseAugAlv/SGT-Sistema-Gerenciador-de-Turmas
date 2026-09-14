<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Entrar em uma Turma</h1>

<form method="POST" action="<?= $basePath ?>/turmas/entrar">
    <?= ViewHelper::csrfField() ?>
    <p><label>Código de acesso *<br>
        <input type="text" name="codigo_acesso" required autofocus></label></p>
    <p><button type="submit">Entrar</button></p>
</form>

<p><a href="<?= $basePath ?>/turmas">Minhas turmas</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>