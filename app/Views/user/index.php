<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Minha Conta</h1>

<table border="1" cellpadding="8">
    <tr><th align="left">Nome</th><td><?= h($user['nome']) ?></td></tr>
    <tr><th align="left">Email</th><td><?= h($user['email']) ?></td></tr>
    <tr><th align="left">Tipo</th><td><?= h($user['tipo']) ?></td></tr>
    <tr><th align="left">Telefone</th><td><?= h($user['telefone'] ?? '—') ?></td></tr>
    <tr><th align="left">Data de nascimento</th><td><?= h($user['data_nascimento'] ?? '—') ?></td></tr>
    <tr><th align="left">Último acesso</th><td><?= h($user['ultimo_acesso'] ?? '—') ?></td></tr>
    <tr><th align="left">Conta criada em</th><td><?= h($user['created_at']) ?></td></tr>
</table>

<h2>Preferências de email</h2>
<?php if ($prefs): ?>
    <ul>
        <li>Emails gerais: <strong><?= !empty($prefs['receber_email']) ? 'Ativado' : 'Desativado' ?></strong></li>
        <li>Emails de prazos: <strong><?= !empty($prefs['receber_email_prazos']) ? 'Ativado' : 'Desativado' ?></strong></li>
        <li>Emails de alertas: <strong><?= !empty($prefs['receber_email_alertas']) ? 'Ativado' : 'Desativado' ?></strong></li>
        <li>Emails de avaliações: <strong><?= !empty($prefs['receber_email_avaliacoes']) ? 'Ativado' : 'Desativado' ?></strong></li>
    </ul>
<?php else: ?>
    <p>Nenhuma preferência registrada.</p>
<?php endif; ?>

<hr>

<h2>Ações</h2>
<ul>
    <li><a href="<?= $basePath ?>/user/editar"><i class="fas fa-pen"></i> Editar perfil</a></li>
    <li><a href="<?= $basePath ?>/user/senha"><i class="fas fa-key"></i> Alterar senha</a></li>
    <li><a href="<?= $basePath ?>/notificacoes/preferencias"><i class="fas fa-sliders"></i> Preferências de email</a></li>
    <li><a href="<?= $basePath ?>/lgpd/meus-direitos"><i class="fas fa-shield-halved"></i> Meus direitos (LGPD)</a></li>
</ul>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>