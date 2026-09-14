<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<h1>Configurações do Sistema</h1>
<p><a href="<?= $basePath ?>/master">Voltar ao painel</a></p>

<h2>Informações</h2>
<table border="1" cellpadding="8">
    <tr><th align="left">PHP</th><td><?= h($info['php']) ?></td></tr>
    <tr><th align="left">MySQL/MariaDB</th><td><?= h($info['mysql']) ?></td></tr>
    <tr><th align="left">Ambiente</th><td><?= h($info['app_env']) ?></td></tr>
    <tr><th align="left">URL</th><td><?= h($info['app_url']) ?></td></tr>
    <tr><th align="left">Base Path</th><td><?= h($info['base_path']) ?></td></tr>
</table>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>