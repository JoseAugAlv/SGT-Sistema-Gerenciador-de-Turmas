<?php
// app/Views/auth/convite.php
// Link do email de convite (sem conta ainda): cria a conta com senha ou Google e só então entra na turma.
$tituloPagina     = 'Convite — ' . App::getName();
$authSideTitle    = 'Você foi convidado.';
$authSideSubtitle = ($convite['turma_tipo'] ?? '') === 'gremio' ? 'Crie sua conta para entrar no grêmio.' : 'Crie sua conta para entrar na turma.';

require_once __DIR__ . '/../layouts/auth_header.php';
$a = $antigo ?? [];
?>

<div class="auth-header">
    <h1><?= ($convite['turma_tipo'] ?? '') === 'gremio' ? 'Grêmio' : 'Turma' ?> <?= h($convite['turma_nome']) ?></h1>
    <p>Convite para <strong><?= h($convite['email']) ?></strong>. Escolha como quer entrar.</p>
</div>

<?php $urlGoogle = $basePath . '/convite/' . $token . '/google'; $textoGoogle = 'Entrar com o Google'; require __DIR__ . '/_google.php'; ?>

<form method="POST" action="<?= $basePath ?>/convite/<?= h($token) ?>" class="auth-form" autocomplete="on">
    <?= ViewHelper::csrfField() ?>

    <div class="auth-field">
        <label for="cv-nome">Nome completo <span class="obrigatorio">*</span></label>
        <input type="text" id="cv-nome" name="nome" minlength="5" maxlength="150" required autocomplete="name"
               value="<?= h($a['nome'] ?? '') ?>" placeholder="Nome e sobrenome">
    </div>

    <div class="auth-field">
        <label for="cv-tel">Telefone <span class="obrigatorio">*</span></label>
        <input type="tel" id="cv-tel" name="telefone" maxlength="16" required autocomplete="tel" inputmode="tel"
               value="<?= h($a['telefone'] ?? '') ?>" placeholder="(11) 99999-8888" data-mascara-tel>
    </div>

    <div class="auth-field">
        <label for="cv-senha">Crie uma senha <span class="obrigatorio">*</span></label>
        <div class="auth-password-wrap">
            <input type="password" id="cv-senha" name="senha" minlength="8" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
            <button type="button" class="auth-password-toggle" aria-label="Mostrar senha"><i class="fas fa-eye"></i></button>
        </div>
        <small class="field-help">Precisa ter maiúscula, minúscula, número e caractere especial.</small>
    </div>

    <div class="auth-field">
        <label for="cv-senha2">Repita a senha <span class="obrigatorio">*</span></label>
        <input type="password" id="cv-senha2" name="senha_confirmacao" minlength="8" required autocomplete="new-password">
    </div>

    <?php $nivelEmail = $a['nivel_email'] ?? 'importantes'; require __DIR__ . '/../notificacoes/_nivel_email.php'; ?>

    <label class="auth-check">
        <input type="checkbox" name="lgpd" value="1" required>
        <span>Li e aceito a <a href="<?= $basePath ?>/lgpd" target="_blank">Política de Privacidade (LGPD)</a></span>
    </label>

    <button type="submit" class="auth-submit"><i class="fas fa-check"></i> Concluir cadastro</button>
</form>

<?php require __DIR__ . '/_mascara_tel.php'; ?>
<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>
