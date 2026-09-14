<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Recuperar Senha</h1>

<form method="POST" action="<?= $basePath ?>/auth/enviar-token">
    <?= ViewHelper::csrfField() ?>
    <p>
        <label>Informe seu email<br>
            <input type="email" name="email" required autofocus>
        </label>
    </p>
    <p><button type="submit">Enviar instruções</button></p>
</form>

<p><a href="<?= $basePath ?>/login">Voltar ao login</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>