<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Solicitações LGPD</h1>
<p><a href="<?= $basePath ?>/master">Voltar ao painel</a></p>

<?php if (empty($solicitacoes)): ?>
    <p>Nenhuma solicitação registrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Data</th>
                <th>Usuário</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Motivo</th>
                <th>Processado em</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($solicitacoes as $s): ?>
                <tr>
                    <td><?= h($s['created_at']) ?></td>
                    <td><?= h($s['usuario_nome']) ?><br><small><?= h($s['usuario_email']) ?></small></td>
                    <td><?= h($s['tipo']) ?></td>
                    <td><?= h($s['status']) ?></td>
                    <td><?= h($s['motivo_negacao'] ?? '—') ?></td>
                    <td><?= h($s['processado_em'] ?? '—') ?></td>
                    <td>
                        <?php if ($s['status'] === 'pendente'): ?>
                            <form method="POST" action="<?= $basePath ?>/master/lgpd/<?= (int) $s['id'] ?>/aprovar" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" onclick="return confirm('Aprovar e executar? Para exclusão, o usuário será anonimizado.')">
                                    <i class="fas fa-check"></i> Aprovar
                                </button>
                            </form>
                            —
                            <form method="POST" action="<?= $basePath ?>/master/lgpd/<?= (int) $s['id'] ?>/negar" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <input type="text" name="motivo" placeholder="Motivo" required>
                                <button type="submit"><i class="fas fa-xmark"></i> Negar</button>
                            </form>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>