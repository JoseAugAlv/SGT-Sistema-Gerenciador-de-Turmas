<?php
$tituloPagina     = 'Primeiro acesso — ' . App::getName();
$authSideTitle    = 'Bem-vindo(a)!';
$authSideSubtitle = 'Complete as etapas abaixo para liberar o acesso completo.';
$authSideNoList   = true;

require_once __DIR__ . '/../layouts/auth_header.php';

$emailOk  = !empty($user['email_confirmado']);
$lgpdOk   = !empty($user['lgpd_aceito']);
$etapa1   = $emailOk ? 'concluido' : 'pendente';
?>

<div class="auth-header">
    <h1>Primeiro acesso</h1>
    <p>
        Olá, <strong><?= h($user['nome']) ?></strong>. Complete as etapas para liberar seu acesso.
    </p>
</div>

<div class="auth-steps">
    <!-- Etapa 1 — Confirmação de email -->
    <div class="auth-step <?= $etapa1 ?>">
        <span class="auth-step-num">
            <?php if ($emailOk): ?>
                <i class="fas fa-check"></i>
            <?php else: ?>
                1
            <?php endif; ?>
        </span>
        <div class="auth-step-info">
            <strong>Confirmação de email</strong>
            <?php if ($emailOk): ?>
                <small>Email confirmado com sucesso.</small>
            <?php else: ?>
                <small>
                    Verifique sua caixa de entrada.
                    Se não recebeu o email,
                    <a href="#" onclick="document.getElementById('form-reenviar').submit(); return false;"
                       style="color:var(--primary); font-weight:600;">clique aqui para reenviar</a>.
                </small>
            <?php endif; ?>
        </div>
    </div>

    <!-- Etapa 2 — Trocar senha -->
    <div class="auth-step <?= $emailOk ? 'pendente' : 'pendente' ?>">
        <span class="auth-step-num">2</span>
        <div class="auth-step-info">
            <strong>Definir nova senha</strong>
            <small>Escolha uma senha forte para substituir a temporária.</small>
        </div>
    </div>

    <!-- Etapa 3 — LGPD -->
    <div class="auth-step <?= $lgpdOk ? 'concluido' : 'pendente' ?>">
        <span class="auth-step-num">
            <?php if ($lgpdOk): ?>
                <i class="fas fa-check"></i>
            <?php else: ?>
                3
            <?php endif; ?>
        </span>
        <div class="auth-step-info">
            <strong>Aceitar os termos (LGPD)</strong>
            <?php if ($lgpdOk): ?>
                <small>Termos já aceitos.</small>
            <?php else: ?>
                <small>Aceite a política de privacidade para continuar.</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$emailOk): ?>
    <div class="auth-alert auth-alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Confirme seu email primeiro</strong>
            Você precisa confirmar o email antes de definir a nova senha.
            Verifique sua caixa de entrada ou clique em "reenviar" acima.
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/primeiro-acesso" class="auth-form">
    <?= ViewHelper::csrfField() ?>

    <div class="auth-field">
        <label for="pa-senha">Nova senha <span class="obrigatorio">*</span></label>
        <div class="auth-password-wrap">
            <input type="password" id="pa-senha" name="senha"
                   minlength="8" placeholder="Mínimo 8 caracteres"
                   required autocomplete="new-password"
                   <?= !$emailOk ? 'disabled' : '' ?>>
            <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
                <i class="fas fa-eye"></i>
            </button>
        </div>
    </div>

    <div class="auth-field">
        <label for="pa-senha2">Confirme a senha <span class="obrigatorio">*</span></label>
        <div class="auth-password-wrap">
            <input type="password" id="pa-senha2" name="senha_confirmacao"
                   minlength="8" placeholder="Repita a senha"
                   required autocomplete="new-password"
                   <?= !$emailOk ? 'disabled' : '' ?>>
            <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <small class="field-help">
            Mínimo 8 caracteres, com maiúscula, minúscula, número e símbolo.
        </small>
    </div>

    <label class="auth-check">
        <input type="checkbox" name="lgpd" value="1"
               required <?= !$emailOk ? 'disabled' : '' ?>>
        <span>
            Li e aceito a
            <a href="<?= $basePath ?>/lgpd" target="_blank">Política de Privacidade (LGPD)</a>
        </span>
    </label>

    <button type="submit" class="auth-submit" <?= !$emailOk ? 'disabled' : '' ?>>
        <i class="fas fa-check"></i> Concluir primeiro acesso
    </button>
</form>

<div class="auth-links">
    <span><a href="<?= $basePath ?>/logout">Sair</a></span>
</div>

<!-- Form escondido para reenviar confirmação -->
<form method="POST" action="<?= $basePath ?>/auth/reenviar-confirmacao" id="form-reenviar" style="display:none;">
    <?= ViewHelper::csrfField() ?>
</form>

<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>