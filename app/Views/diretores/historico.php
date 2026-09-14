<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h1>Histórico de Diretores — <?= h($grupo['nome']) ?></h1>
<p>Projeto: <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a></p>

<p><a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores">
    <i class="fas fa-arrow-left"></i> Voltar ao gerenciamento
</a></p>

<hr>

<?php if (empty($historico)): ?>
    <p>Nenhum diretor nomeado ainda neste grupo.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Diretor</th>
                <th>Status</th>
                <th>Nomeado em</th>
                <th>Nomeado por</th>
                <th>Removido em</th>
                <th>Removido por</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historico as $h): ?>
                <tr>
                    <td><?= h($h['diretor_nome'] ?? '—') ?> (<?= h($h['diretor_email'] ?? '—') ?>)</td>
                    <td>
                        <?php if ($h['ativo']): ?>
                            <i class="fas fa-check-circle" style="color:#0a0"></i> Ativo
                        <?php else: ?>
                            <i class="fas fa-ban" style="color:#b00"></i> Removido
                        <?php endif; ?>
                    </td>
                    <td><?= h($h['nomeado_em']) ?></td>
                    <td><?= h($h['nomeado_por_nome'] ?? '—') ?></td>
                    <td><?= h($h['removido_em'] ?? '—') ?></td>
                    <td><?= h($h['removido_por_nome'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>