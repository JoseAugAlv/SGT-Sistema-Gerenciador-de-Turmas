<?php
$tituloPagina     = 'Criar conta — ' . App::getName();
$authSideTitle    = 'Crie sua conta em segundos.';
$authSideSubtitle = 'Depois é só entrar em uma turma usando o código de acesso.';

require_once __DIR__ . '/../layouts/auth_header.php';
?>

<div class="auth-header">
    <h1>Criar conta</h1>
    <p>Preencha seus dados para começar a usar o sistema.</p>
</div>

<?php $textoGoogle = 'Criar conta com o Google'; require __DIR__ . '/_google.php'; ?>

<form method="POST" action="<?= $basePath ?>/login/salvar" class="auth-form" autocomplete="on">
    <?= ViewHelper::csrfField() ?>

    <div class="auth-field">
        <label for="cad-nome">Nome completo <span class="obrigatorio">*</span></label>
        <input type="text" id="cad-nome" name="nome"
               minlength="3" maxlength="150"
               placeholder="Ex: João da Silva"
               required autofocus autocomplete="name">
    </div>

    <div class="auth-field">
        <label for="cad-email">Email <span class="obrigatorio">*</span></label>
        <input type="email" id="cad-email" name="email"
               placeholder="seu@email.com"
               required autocomplete="email">
    </div>

    <div class="auth-field">
        <label for="cad-senha">Senha <span class="obrigatorio">*</span></label>
        <div class="auth-password-wrap">
            <input type="password" id="cad-senha" name="senha"
                   minlength="8" placeholder="Mínimo 8 caracteres"
                   required autocomplete="new-password">
            <button type="button" class="auth-password-toggle" aria-label="Mostrar senha">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <small class="field-help">
            Precisa ter maiúscula, minúscula, número e caractere especial.
        </small>
    </div>

    <div class="auth-field">
        <label for="cad-telefone">Telefone (opcional)</label>
        <input type="tel" id="cad-telefone" name="telefone"
               maxlength="20" placeholder="(11) 99999-8888"
               autocomplete="tel">
    </div>

    <div class="auth-field">
        <label for="cad-nasc">Data de nascimento (opcional, mín. 12 anos)</label>
        <input type="date" id="cad-nasc" name="data_nascimento">
    </div>

    <?php require __DIR__ . '/../notificacoes/_nivel_email.php'; ?>

    <label class="auth-check">
        <input type="checkbox" name="lgpd" value="1" required>
        <span>
            Li e aceito a
            <a href="<?= $basePath ?>/lgpd" target="_blank">Política de Privacidade (LGPD)</a>
        </span>
    </label>

    <button type="submit" class="auth-submit">
        <i class="fas fa-user-plus"></i> Criar conta
    </button>
</form>

<div class="auth-links">
    <span>Já tem conta? <a href="<?= $basePath ?>/login">Entrar</a></span>
</div>

<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>