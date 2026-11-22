<?php
$tituloPagina     = 'Definir nova senha — ' . App::getName();
$authSideTitle    = 'Defina uma nova senha.';
$authSideSubtitle = 'Escolha uma senha forte — mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.';
$authSideNoList   = true;

require_once __DIR__ . '/../layouts/auth_header.php';
?>

<div class="auth-header">
    <h1>Nova senha</h1>
    <p>Escolha uma nova senha para sua conta.</p>
</div>

<form method="POST" action="<?= $basePath ?>/auth/redefinir-senha" class="auth-form">
    <?= ViewHelper::csrfField() ?>
    <input type="hidden" name="token" value="<?= h($token) ?>">

    <div class="auth-field">
        <label for="rd-senha">Nova senha <span class="obrigatorio">*</span></label>
        <div class="auth-password-wrap">
            <input type="password" id="rd-senha" name="senha"
                   minlength="8" placeholder="Mínimo 8 caracteres"
                   required autofocus autocomplete="new-password">
            <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>

    <div class="auth-field">
        <label for="rd-senha2">Confirme a nova senha <span class="obrigatorio">*</span></label>
        <div class="auth-password-wrap">
            <input type="password" id="rd-senha2" name="senha_confirmacao"
                   minlength="8" placeholder="Repita a senha"
                   required autocomplete="new-password">
            <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>

    <div class="auth-alert auth-alert-info">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Requisitos da senha</strong>
            Mínimo 8 caracteres, com maiúscula, minúscula, número e caractere especial.
        </div>
    </div>

    <button type="submit" class="auth-submit">
        <i class="fas fa-check"></i> Salvar nova senha
    </button>
</form>

<div class="auth-links">
    <a href="<?= $basePath ?>/login">
        <i class="fas fa-arrow-left"></i> Voltar ao login
    </a>
</div>

<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>