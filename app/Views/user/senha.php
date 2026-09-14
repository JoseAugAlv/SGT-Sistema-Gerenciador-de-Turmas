<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Alterar Senha</h1>

<form method="POST" action="<?= $basePath ?>/user/senha">
    <?= ViewHelper::csrfField() ?>

    <p><label>Senha atual *<br>
        <input type="password" name="senha_atual" required autofocus></label></p>

    <p><label>Nova senha *<br>
        <input type="password" name="senha_nova" minlength="8" required></label></p>

    <p><label>Confirme a nova senha *<br>
        <input type="password" name="senha_confirmacao" minlength="8" required></label></p>

    <p>
        <small>Requisitos: mínimo 8 caracteres, com maiúscula, minúscula, número e caractere especial.</small>
    </p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Alterar senha</button>
        <a href="<?= $basePath ?>/user">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>