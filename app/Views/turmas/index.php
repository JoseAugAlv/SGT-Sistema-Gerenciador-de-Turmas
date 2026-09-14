<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Turmas</h1>

<?php if ($isMaster): ?>
    <p><a href="<?= $basePath ?>/turmas/criar"><i class="fas fa-plus"></i> Nova Turma</a></p>
<?php endif; ?>

<?php if (empty($turmas)): ?>
    <p>Nenhuma turma <?= $isMaster ? 'cadastrada' : 'encontrada' ?>.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Nome</th><th>Código</th><th>Alunos</th>
                <?php if ($isMaster): ?><th>Reps</th><th>Status</th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($turmas as $t): ?>
                <tr>
                    <td><?= h($t['nome']) ?></td>
                    <td><code><?= h($t['codigo_acesso']) ?></code></td>
                    <td><?= (int) ($t['total_alunos'] ?? 0) ?></td>
                    <?php if ($isMaster): ?>
                        <td><?= (int) ($t['total_reps'] ?? 0) ?>/2</td>
                        <td>
                            <?php if ($t['bloqueada']): ?>
                                <i class="fas fa-ban" style="color:#b00" title="Bloqueada"></i>
                            <?php else: ?>
                                <i class="fas fa-check-circle" style="color:#0a0" title="Ativa"></i>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td><a href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>">Abrir</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<hr>
<p><a href="<?= $basePath ?>/turmas/entrar"><i class="fas fa-right-to-bracket"></i> Entrar em uma turma com código</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>