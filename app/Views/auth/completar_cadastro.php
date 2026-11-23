<?php
// app/Views/auth/completar_cadastro.php
// Depois de entrar com o Google (ou pelo convite com Google): nome completo, telefone, LGPD e emails.
$tituloPagina     = 'Completar cadastro — ' . App::getName();
$authSideTitle    = 'Quase lá.';
$authSideSubtitle = 'Só faltam seu nome completo e telefone.';

require_once __DIR__ . '/../layouts/auth_header.php';
$a = $antigo ?? [];
?>

<div class="auth-header">
    <h1>Completar cadastro</h1>
    <p>Você entrou como <strong><?= h($user['email']) ?></strong>. Seu email já está confirmado.</p>
</div>

<form method="POST" action="<?= $basePath ?>/completar-cadastro" class="auth-form" autocomplete="on">
    <?= ViewHelper::csrfField() ?>

    <div class="auth-field">
        <label for="cc-nome">Nome completo <span class="obrigatorio">*</span></label>
        <input type="text" id="cc-nome" name="nome" minlength="5" maxlength="150" required autofocus autocomplete="name"
               value="<?= h($a['nome'] ?? $user['nome']) ?>" placeholder="Nome e sobrenome">
    </div>

    <div class="auth-field">
        <label for="cc-tel">Telefone <span class="obrigatorio">*</span></label>
        <input type="tel" id="cc-tel" name="telefone" maxlength="16" required autocomplete="tel" inputmode="tel"
               value="<?= h($a['telefone'] ?? ($user['telefone'] ?? '')) ?>" placeholder="(11) 99999-8888" data-mascara-tel>
    </div>

    <?php $nivelEmail = $a['nivel_email'] ?? 'importantes'; require __DIR__ . '/../notificacoes/_nivel_email.php'; ?>

    <label class="auth-check">
        <input type="checkbox" name="lgpd" value="1" required>
        <span>Li e aceito a <a href="<?= $basePath ?>/lgpd" target="_blank">Política de Privacidade (LGPD)</a></span>
    </label>

    <button type="submit" class="auth-submit"><i class="fas fa-check"></i> Concluir</button>
</form>

<div class="auth-links">
    <a href="<?= $basePath ?>/logout"><i class="fas fa-right-from-bracket"></i> Sair</a>
</div>

<?php require __DIR__ . '/_mascara_tel.php'; ?>
<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>
