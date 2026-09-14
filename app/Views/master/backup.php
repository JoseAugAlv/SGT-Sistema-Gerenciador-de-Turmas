<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Backup do Banco</h1>
<p><a href="<?= $basePath ?>/master">Voltar ao painel</a></p>

<form method="POST" action="<?= $basePath ?>/master/backup/gerar">
    <?= ViewHelper::csrfField() ?>
    <p>
        <button type="submit"><i class="fas fa-database"></i> Gerar novo backup</button>
    </p>
</form>

<?php if (empty($backups)): ?>
    <p>Nenhum backup gerado ainda.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr><th>Arquivo</th><th>Tamanho</th><th>Data</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $b): ?>
                <tr>
                    <td><?= h($b['nome']) ?></td>
                    <td><?= number_format($b['tamanho'] / 1024, 2, ',', '.') ?> KB</td>
                    <td><?= h($b['data']) ?></td>
                    <td>
                        <a href="<?= $basePath ?>/master/backup/<?= h($b['nome']) ?>/baixar">
                            <i class="fas fa-download"></i> Baixar
                        </a>
                        —
                        <form method="POST" action="<?= $basePath ?>/master/backup/<?= h($b['nome']) ?>/excluir" style="display:inline">
                            <?= ViewHelper::csrfField() ?>
                            <button type="submit" onclick="return confirm('Excluir este backup?')">
                                <i class="fas fa-trash"></i> Excluir
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>