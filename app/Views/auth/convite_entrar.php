<?php
// app/Views/auth/convite_entrar.php
// Link do convite para quem já tem conta: confirma com um botão e só então entra na turma.
$tituloPagina     = 'Convite — ' . App::getName();
$authSideTitle    = 'Você foi convidado.';
$authSideSubtitle = ($convite['turma_tipo'] ?? '') === 'gremio' ? 'Confirme para entrar no grêmio.' : 'Confirme para entrar na turma.';

require_once __DIR__ . '/../layouts/auth_header.php';
?>

<div class="auth-header">
    <h1><?= ($convite['turma_tipo'] ?? '') === 'gremio' ? 'Grêmio' : 'Turma' ?> <?= h($convite['turma_nome']) ?></h1>
    <p>Convite para <strong><?= h($convite['email']) ?></strong>. Você já tem conta: é só confirmar.</p>
</div>

<form method="POST" action="<?= $basePath ?>/convite/<?= h($token) ?>" class="auth-form">
    <?= ViewHelper::csrfField() ?>
    <button type="submit" class="auth-submit"><i class="fas fa-right-to-bracket"></i> Entrar <?= ($convite['turma_tipo'] ?? '') === 'gremio' ? 'no grêmio' : 'na turma' ?></button>
</form>

<p class="auth-footer-text">Não quer entrar? É só ignorar este convite.</p>

<?php require_once __DIR__ . '/../layouts/auth_footer.php'; ?>
