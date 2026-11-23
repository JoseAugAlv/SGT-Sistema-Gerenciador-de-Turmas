<?php
$tituloPagina    = 'Entrar — ' . App::getName();
$authSideTitle   = 'Bem-vindo de volta.';
$authSideSubtitle = 'Acesse sua conta para continuar.';

require_once __DIR__ . '/../layouts/auth_header.php';
?>

<div class="auth-header">
    <h1>Entrar</h1>
    <p>Informe suas credenciais para acessar o sistema.</p>
</div>

<?php require __DIR__ . '/_google.php'; ?>

<form method="POST" action="<?= $basePath ?>/login" class="auth-form" autocomplete="on">
    <?= ViewHelper::csrfField() ?>

    <div class="auth-field">
        <label for="login-email">Email <span class="obrigatorio">*</span></label>
        <input type="email" id="login-email" name="email"
               placeholder="seu@email.com" required autofocus
               autocomplete="email">
    </div>

    <div class="auth-field">
        <label for="login-senha">Senha <span class="obrigatorio">*</span></label>
        <div class="auth-password-wrap">
            <input type="password" id="login-senha" name="senha"
                   placeholder="Sua senha" required
                   autocomplete="current-password">
            <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="auth-submit">
        <i class="fas fa-right-to-bracket"></i> Entrar
    </button>
</form>

<div class="auth-links">
    <a href="<?= $basePath ?>/auth/esqueci-senha">
        <i class="fas fa-key"></i> Esqueci minha senha
    </a>
    <span>Não tem conta? <a href="<?= $basePath ?>/login/cadastrar">Cadastre-se</a></span>
</div>

<?php if (App::get('APP_ENV') === 'development'): ?>
    <div class="auth-dev">
        <div class="auth-dev-header">
            <i class="fas fa-flask"></i> Dev — preenchimento rápido
        </div>
        <p>
            Senha de todos: <code>Teste@1234</code><br>
            <small style="opacity:.75;">Usuários reais da turma 231-3ANO-INFO-M.</small>
        </p>

        <div class="auth-dev-buttons">
            <button type="button" class="auth-dev-btn" onclick="devFill('jose@adm.com')">
                <i class="fas fa-user-shield"></i> Master
            </button>

            <button type="button" class="auth-dev-btn" onclick="devFill('joseaugusto@email.com')">
                <i class="fas fa-user-tie"></i> Representante
            </button>

            <button type="button" class="auth-dev-btn" onclick="devFill('ana@email.com')">
                <i class="fas fa-crown"></i> Diretor
            </button>

            <button type="button" class="auth-dev-btn" onclick="devFill('andrey@email.com')">
                <i class="fas fa-user-graduate"></i> Aluno (grupo)
            </button>

            <button type="button" class="auth-dev-btn" onclick="devFill('melissa@email.com')">
                <i class="fas fa-user"></i> Aluno (turma)
            </button>

            <button type="button" class="auth-dev-btn" onclick="devFill('carol@aluno.edu')">
                <i class="fas fa-user-slash"></i> Aluno (sem turma)
            </button>
        </div>
    </div>

    <script>
        function devFill(email) {
            var emailInput = document.getElementById('login-email');
            var senhaInput = document.getElementById('login-senha');
            emailInput.value = email;
            senhaInput.value = 'Teste@1234';
            senhaInput.focus();
        }
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>