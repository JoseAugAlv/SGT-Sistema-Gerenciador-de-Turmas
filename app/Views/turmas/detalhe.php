<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($turma['nome']) ?></h1>

<p>
    Código: <code><?= h($turma['codigo_acesso']) ?></code>
    —
    Status:
    <?php if ($turma['bloqueada']): ?>
        <i class="fas fa-ban" style="color:#b00"></i>
        Bloqueada — <?= h($turma['bloqueada_motivo']) ?>
    <?php else: ?>
        <i class="fas fa-check-circle" style="color:#0a0"></i>
        Ativa
    <?php endif; ?>
</p>

<?php if ($isMaster): ?>
    <p>
        <a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/editar">
            <i class="fas fa-pen"></i> Editar turma
        </a>
        —
        <a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/excluir" style="color:#b00">
            <i class="fas fa-trash"></i> Excluir turma
        </a>
        —
        <a href="<?= $basePath ?>/projetos?turma_id=<?= (int) $turma['id'] ?>">
            <i class="fas fa-diagram-project"></i> Ver projetos
        </a>
        —
        <a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes">
            <i class="fas fa-user-tie"></i> <strong>Gerenciar representantes</strong>
        </a>
    </p>
<?php endif; ?>

<?php if ($isMaster): ?>
    <fieldset>
        <legend>Ações do Master</legend>

        <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/regenerar-codigo" style="display:inline">
            <?= ViewHelper::csrfField() ?>
            <button type="submit" onclick="return confirm('Regenerar código?')">
                <i class="fas fa-rotate"></i> Regenerar código
            </button>
        </form>

        <?php if ($turma['bloqueada']): ?>
            <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/desbloquear" style="display:inline">
                <?= ViewHelper::csrfField() ?>
                <button type="submit"><i class="fas fa-unlock"></i> Desbloquear</button>
            </form>
        <?php else: ?>
            <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/bloquear" style="display:inline">
                <?= ViewHelper::csrfField() ?>
                <input type="text" name="motivo" placeholder="Motivo" required>
                <button type="submit"><i class="fas fa-lock"></i> Bloquear</button>
            </form>
        <?php endif; ?>
    </fieldset>
<?php elseif ($isRep): ?>
    <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/regenerar-codigo" style="display:inline">
        <?= ViewHelper::csrfField() ?>
        <button type="submit" onclick="return confirm('Regenerar código?')">
            <i class="fas fa-rotate"></i> Regenerar código
        </button>
    </form>
<?php endif; ?>

<h2>Representantes (<?= count($representantes) ?>/2)</h2>

<?php if (empty($representantes)): ?>
    <p>Nenhum representante nomeado.</p>
<?php else: ?>
    <ul>
        <?php foreach ($representantes as $r): ?>
            <li><?= h($r['nome']) ?> — <?= h($r['email']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($isMaster): ?>
    <p>
        <a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes">
            <i class="fas fa-user-plus"></i> Nomear representantes
        </a>
    </p>
<?php endif; ?>

<h2>Alunos (<?= count($alunos) ?>)</h2>

<table border="1" cellpadding="6">
    <thead>
        <tr><th>Nome</th><th>Email</th><th>Papel</th><th>Entrou em</th></tr>
    </thead>
    <tbody>
        <?php foreach ($alunos as $a): ?>
            <tr>
                <td><?= h($a['nome']) ?></td>
                <td><?= h($a['email']) ?></td>
                <td><?= h($a['papel']) ?></td>
                <td><?= h($a['entrou_em']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><a href="<?= $basePath ?>/turmas"><i class="fas fa-arrow-left"></i> Voltar para turmas</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>