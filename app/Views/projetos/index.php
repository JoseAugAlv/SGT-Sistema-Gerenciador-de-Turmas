<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Projetos — <?= h($turma['nome']) ?></h1>

<?php if ($isMaster): ?>
    <p>
        <a href="<?= $basePath ?>/projetos/criar?turma_id=<?= (int) $turma['id'] ?>">
            <i class="fas fa-plus"></i> Novo Projeto
        </a>
    </p>
<?php endif; ?>

<?php if (empty($projetos)): ?>
    <p>Nenhum projeto cadastrado nesta turma.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Modo</th>
                <th>Prazo</th>
                <th>Etapas</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projetos as $p): ?>
                <tr>
                    <td><?= h($p['nome']) ?></td>
                    <td><?= h($p['modo_avaliacao']) ?></td>
                    <td><?= h($p['prazo'] ?? '—') ?></td>
                    <td><?= (int) $p['total_etapas'] ?></td>
                    <td>
                        <?php if ($p['encerrado']): ?>
                            <i class="fas fa-lock" style="color:#b00"></i> Encerrado
                        <?php else: ?>
                            <i class="fas fa-play" style="color:#0a0"></i> Em andamento
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= $basePath ?>/projetos/<?= (int) $p['id'] ?>">Abrir</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<p><a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
    <i class="fas fa-arrow-left"></i> Voltar à turma
</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>