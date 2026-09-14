<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<h1>Auditoria #<?= (int) $log['id'] ?></h1>
<p><a href="<?= $basePath ?>/master/auditoria">Voltar</a></p>

<table border="1" cellpadding="8">
    <tr><th align="left">Data</th><td><?= h($log['created_at']) ?></td></tr>
    <tr><th align="left">Usuário</th><td><?= h($log['usuario_nome'] ?? '—') ?> (<?= h($log['usuario_email'] ?? '—') ?>)</td></tr>
    <tr><th align="left">Ação</th><td><?= h($log['acao']) ?></td></tr>
    <tr><th align="left">Tabela</th><td><?= h($log['tabela_afetada'] ?? '—') ?></td></tr>
    <tr><th align="left">Registro ID</th><td><?= $log['registro_id'] !== null ? '#' . (int) $log['registro_id'] : '—' ?></td></tr>
    <tr><th align="left">IP</th><td><?= h($log['ip'] ?? '—') ?></td></tr>
    <tr><th align="left">User-Agent</th><td><?= h($log['user_agent'] ?? '—') ?></td></tr>
</table>

<h2>Dados anteriores</h2>
<pre><?= h($log['dados_anteriores'] ?? '(vazio)') ?></pre>

<h2>Dados novos</h2>
<pre><?= h($log['dados_novos'] ?? '(vazio)') ?></pre>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>