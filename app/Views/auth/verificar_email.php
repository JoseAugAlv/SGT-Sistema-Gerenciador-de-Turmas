<?php
$tituloPagina     = 'Confirme seu email — ' . App::getName();
$authSideTitle    = 'Falta pouco!';
$authSideSubtitle = 'Confirme seu email para liberar o acesso ao sistema.';
$authSideNoList   = true;

require_once __DIR__ . '/../layouts/auth_header.php';
?>

<div class="auth-header">
    <h1>Confirme seu email</h1>
    <p>
        Enviamos um link de confirmação para
        <strong><?= h($_SESSION['usuario']['email'] ?? '') ?></strong>.
    </p>
</div>

<div class="auth-alert auth-alert-info">
    <i class="fas fa-envelope-circle-check"></i>
    <div>
        <strong>Verifique sua caixa de entrada</strong>
        Abra o email, clique no link de confirmação e depois faça login novamente.
        Se não encontrar, verifique a pasta de spam.
    </div>
</div>

<form method="POST" action="<?= $basePath ?>/auth/reenviar-confirmacao" class="auth-form">
    <?= ViewHelper::csrfField() ?>
    <button type="submit" class="auth-submit">
        <i class="fas fa-paper-plane"></i> Reenviar email de confirmação
    </button>
</form>

<div class="auth-links">
    <a href="<?= $basePath ?>/logout">
        <i class="fas fa-right-from-bracket"></i> Sair
    </a>
</div>

<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>