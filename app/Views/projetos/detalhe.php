<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>

<p>
    Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>"><?= h($turma['nome']) ?></a>
    —
    Modo: <code><?= h($projeto['modo_avaliacao']) ?></code>
    —
    Prazo: <?= h($projeto['prazo'] ?? '—') ?>
    —
    Status:
    <?php if ($projeto['encerrado']): ?>
        <i class="fas fa-lock" style="color:#b00"></i> Encerrado em <?= h($projeto['encerrado_em']) ?>
    <?php else: ?>
        <i class="fas fa-play" style="color:#0a0"></i> Em andamento
    <?php endif; ?>
</p>

<?php if (!empty($projeto['descricao'])): ?>
    <p><?= nl2br(h($projeto['descricao'])) ?></p>
<?php endif; ?>

<?php require __DIR__ . '/_nav.php'; ?>

<?php if ($isMaster): ?>
    <p>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/editar">
            <i class="fas fa-pen"></i> Editar projeto
        </a>
    </p>
<?php endif; ?>

<?php if ($podeEditar && !$projeto['encerrado']): ?>
    <form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/encerrar">
        <?= ViewHelper::csrfField() ?>
        <input type="hidden" name="confirmo" value="1">
        <p>
            <button type="submit" style="background:#b00;color:#fff"
                    onclick="return confirm('Encerrar o projeto é irreversível. Continuar?')">
                <i class="fas fa-lock"></i> Encerrar projeto
            </button>
        </p>
    </form>
<?php endif; ?>

<hr>

<h2>Etapas (<?= count($etapas) ?>)</h2>

<?php if (empty($etapas)): ?>
    <p>Nenhuma etapa cadastrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Ordem</th><th>Nome</th><th>Início</th><th>Fim</th><th>Critérios</th>
                <?php if ($podeEditar && !$projeto['encerrado']): ?><th>Ações</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($etapas as $e): ?>
                <tr>
                    <td><?= (int) $e['ordem'] ?></td>
                    <td><?= h($e['nome']) ?></td>
                    <td><?= h($e['data_inicio'] ?? '—') ?></td>
                    <td><?= h($e['data_fim'] ?? '—') ?></td>
                    <td><?= (int) $e['total_criterios'] ?></td>
                    <?php if ($podeEditar && !$projeto['encerrado']): ?>
                        <td>
                            <form method="POST" action="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/mover" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <input type="hidden" name="direcao" value="subir">
                                <button type="submit" title="Subir"><i class="fas fa-arrow-up"></i></button>
                            </form>
                            <form method="POST" action="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/mover" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <input type="hidden" name="direcao" value="descer">
                                <button type="submit" title="Descer"><i class="fas fa-arrow-down"></i></button>
                            </form>
                            <a href="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/editar" title="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" action="<?= $basePath ?>/etapas/<?= (int) $e['id'] ?>/excluir" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" title="Excluir"
                                        onclick="return confirm('Excluir etapa? Critérios vinculados perdem o vínculo.')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php if ($podeEditar && !$projeto['encerrado']): ?>
    <h3>Adicionar etapa</h3>
    <form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/etapas/criar">
        <?= ViewHelper::csrfField() ?>
        <p><label>Nome * <input type="text" name="nome" maxlength="150" required></label></p>
        <p><label>Descrição <textarea name="descricao" rows="2"></textarea></label></p>
        <p><label>Data início <input type="date" name="data_inicio"></label></p>
        <p><label>Data fim <input type="date" name="data_fim"></label></p>
        <p><button type="submit"><i class="fas fa-plus"></i> Adicionar etapa</button></p>
    </form>
<?php endif; ?>

<p><a href="<?= $basePath ?>/projetos?turma_id=<?= (int) $turma['id'] ?>">
    <i class="fas fa-arrow-left"></i> Voltar aos projetos
</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>