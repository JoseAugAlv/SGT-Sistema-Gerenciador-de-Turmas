<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Editar Perfil</h1>

<form method="POST" action="<?= $basePath ?>/user/atualizar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Nome *<br>
        <input type="text" name="nome" maxlength="150"
               value="<?= h($user['nome']) ?>" required></label></p>

    <p><label>Email (não editável)<br>
        <input type="email" value="<?= h($user['email']) ?>" disabled></label></p>

    <p><label>Telefone<br>
        <input type="text" name="telefone" maxlength="20"
               value="<?= h($user['telefone'] ?? '') ?>"></label></p>

    <p><label>Data de nascimento<br>
        <input type="date" name="data_nascimento"
               value="<?= h($user['data_nascimento'] ?? '') ?>"></label></p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Salvar</button>
        <a href="<?= $basePath ?>/user">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>