<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Entrar</h1>

<form method="POST" action="<?= $basePath ?>/login">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label>Email<br>
            <input type="email" name="email" required autofocus>
        </label>
    </p>
    <p>
        <label>Senha<br>
            <input type="password" name="senha" required>
        </label>
    </p>
    <p>
        <button type="submit"><i class="fas fa-right-to-bracket"></i> Entrar</button>
    </p>
</form>

<p><a href="<?= $basePath ?>/auth/esqueci-senha"><i class="fas fa-key"></i> Esqueci minha senha</a></p>
<p>Não tem conta? <a href="<?= $basePath ?>/login/cadastrar">Cadastre-se</a></p>

<?php if (\App::get('APP_ENV') === 'development'): ?>
    <hr>
    <fieldset>
        <legend><i class="fas fa-flask"></i> Dev — preenchimento rápido</legend>
        <p>Clique para preencher o formulário. Estes botões só aparecem em <code>APP_ENV=development</code>.</p>

        <button type="button" onclick="devFill('master@adm.com', 'Teste@1234')">
            <i class="fas fa-user-shield"></i> Master
        </button>

        <button type="button" onclick="devFill('aluno.sem@teste.com', 'Teste@1234')">
            <i class="fas fa-user"></i> Aluno sem turma
        </button>

        <button type="button" onclick="devFill('aluno.com@teste.com', 'Teste@1234')">
            <i class="fas fa-user-graduate"></i> Aluno com turma (Info2026)
        </button>
    </fieldset>

    <script>
        function devFill(email, senha) {
            document.querySelector('input[name="email"]').value = email;
            document.querySelector('input[name="senha"]').value = senha;
            document.querySelector('input[name="senha"]').focus();
        }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>