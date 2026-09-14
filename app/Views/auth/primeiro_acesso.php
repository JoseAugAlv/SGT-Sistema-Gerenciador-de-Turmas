<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Primeiro Acesso</h1>
<p>Bem-vindo(a), <strong><?= h($user['nome']) ?></strong>. Complete as etapas abaixo.</p>

<h2>Etapa 1 — Confirmação de email</h2>
<?php if ($user['email_confirmado']): ?>
    <p><i class="fas fa-check-circle" style="color:#0a0"></i> Email confirmado.</p>
<?php else: ?>
    <p>
        <i class="fas fa-exclamation-triangle" style="color:#c80"></i>
        Seu email ainda <strong>não foi confirmado</strong>. Verifique sua caixa de entrada.
    </p>
    <form method="POST" action="<?= $basePath ?>/auth/reenviar-confirmacao">
        <?= ViewHelper::csrfField() ?>
        <button type="submit"><i class="fas fa-paper-plane"></i> Reenviar email de confirmação</button>
    </form>
<?php endif; ?>

<hr>

<h2>Etapa 2 — Trocar senha</h2>
<h2>Etapa 3 — Aceitar LGPD</h2>

<form method="POST" action="<?= $basePath ?>/primeiro-acesso">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label>Nova senha *<br>
            <input type="password" name="senha" minlength="8" required>
        </label>
    </p>
    <p>
        <label>Confirme a senha *<br>
            <input type="password" name="senha_confirmacao" minlength="8" required>
        </label>
    </p>
    <p>
        <label>
            <input type="checkbox" name="lgpd" value="1" required>
            Li e aceito a <a href="<?= $basePath ?>/lgpd" target="_blank">Política de Privacidade</a> *
        </label>
    </p>
    <p>
        <button type="submit" <?= $user['email_confirmado'] ? '' : 'disabled' ?>>
            <i class="fas fa-check"></i> Concluir
        </button>
        <?php if (!$user['email_confirmado']): ?>
            <small>(Confirme o email primeiro)</small>
        <?php endif; ?>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>