<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Definir Nova Senha</h1>

<form method="POST" action="<?= $basePath ?>/auth/redefinir-senha">
    <?= ViewHelper::csrfField() ?>
    <input type="hidden" name="token" value="<?= h($token) ?>">

    <p>
        <label>Nova senha *<br>
            <input type="password" name="senha" minlength="8" required>
        </label>
    </p>
    <p>
        <label>Confirme a nova senha *<br>
            <input type="password" name="senha_confirmacao" minlength="8" required>
        </label>
    </p>
    <p><button type="submit">Salvar</button></p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>