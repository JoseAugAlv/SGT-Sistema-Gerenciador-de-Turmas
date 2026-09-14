<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Cadastro de Aluno</h1>

<form method="POST" action="<?= $basePath ?>/login/salvar">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label>Nome completo *<br>
            <input type="text" name="nome" minlength="3" maxlength="150" required>
        </label>
    </p>
    <p>
        <label>Email *<br>
            <input type="email" name="email" required>
        </label>
    </p>
    <p>
        <label>Senha * (mín. 8 chars, maiúscula, minúscula, número, especial)<br>
            <input type="password" name="senha" minlength="8" required>
        </label>
    </p>
    <p>
        <label>Telefone (opcional)<br>
            <input type="text" name="telefone" maxlength="20">
        </label>
    </p>
    <p>
        <label>Data de nascimento (opcional, mín. 12 anos)<br>
            <input type="date" name="data_nascimento">
        </label>
    </p>
    <p>
        <label>
            <input type="checkbox" name="lgpd" value="1" required>
            Li e aceito a <a href="<?= $basePath ?>/lgpd" target="_blank">Política de Privacidade (LGPD)</a>
        </label>
    </p>
    <p><button type="submit">Cadastrar</button></p>
</form>

<p>Já tem conta? <a href="<?= $basePath ?>/login">Entrar</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>