<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>">
    <?= h($projeto['turma_nome']) ?>
</a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Grupos (<?= count($grupos) ?>)</h2>

<?php if ($podeEditar && !$projeto['encerrado']): ?>
    <p>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos/criar">
            <i class="fas fa-plus"></i> Novo grupo
        </a>
    </p>
<?php endif; ?>

<?php if (empty($grupos)): ?>
    <p>Nenhum grupo cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Modo grupo</th>
                <th>Modo avaliação</th>
                <th>Membros</th>
                <th>Diretores</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grupos as $g): ?>
                <tr>
                    <td><?= h($g['nome']) ?></td>
                    <td><code><?= h($g['modo_avaliacao_grupo']) ?></code></td>
                    <td><code><?= h($g['modo_avaliacao_por']) ?></code></td>
                    <td><?= (int) $g['total_membros'] ?></td>
                    <td><?= (int) $g['total_diretores'] ?></td>
                    <td><a href="<?= $basePath ?>/grupos/<?= (int) $g['id'] ?>">Abrir</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>