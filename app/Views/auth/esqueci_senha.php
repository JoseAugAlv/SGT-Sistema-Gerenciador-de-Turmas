<?php
$tituloPagina     = 'Recuperar senha — ' . App::getName();
$authSideTitle    = 'Esqueceu sua senha?';
$authSideSubtitle = 'Informe seu email e enviaremos um link para redefini-la.';
$authSideNoList   = true;

require_once __DIR__ . '/../layouts/auth_header.php';
?>

<div class="auth-header">
    <h1>Recuperar senha</h1>
    <p>Informe o email cadastrado na sua conta.</p>
</div>

<div class="auth-alert auth-alert-info">
    <i class="fas fa-circle-info"></i>
    <div>
        <strong>Verifique sua caixa de entrada</strong>
        Se o email existir, você receberá um link para redefinir a senha.
        O link é válido por 2 horas.
    </div>
</div>

<form method="POST" action="<?= $basePath ?>/auth/enviar-token" class="auth-form">
    <?= ViewHelper::csrfField() ?>

    <div class="auth-field">
        <label for="rec-email">Email <span class="obrigatorio">*</span></label>
        <input type="email" id="rec-email" name="email"
               placeholder="seu@email.com"
               required autofocus autocomplete="email">
    </div>

    <button type="submit" class="auth-submit">
        <i class="fas fa-paper-plane"></i> Enviar instruções
    </button>
</form>

<div class="auth-links">
    <a href="<?= $basePath ?>/login">
        <i class="fas fa-arrow-left"></i> Voltar ao login
    </a>
</div>

<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>