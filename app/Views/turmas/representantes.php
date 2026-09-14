<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Representantes — <?= h($turma['nome']) ?></h1>
<p>Máximo de 2 representantes ativos.</p>

<h2>Atuais (<?= count($representantes) ?>/2)</h2>
<?php if (empty($representantes)): ?>
    <p>Nenhum.</p>
<?php else: ?>
    <ul>
        <?php foreach ($representantes as $r): ?>
            <li>
                <?= h($r['nome']) ?> (<?= h($r['email']) ?>)
                <?php if (count($representantes) > 1): ?>
                    <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes/remover" style="display:inline">
                        <?= ViewHelper::csrfField() ?>
                        <input type="hidden" name="usuario_id" value="<?= (int) $r['usuario_id'] ?>">
                        <button type="submit" onclick="return confirm('Remover representante?')">Remover</button>
                    </form>
                <?php else: ?>
                    <em>(último — não removível)</em>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (count($representantes) < 2): ?>
    <h2>Nomear novo</h2>
    <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes/nomear">
        <?= ViewHelper::csrfField() ?>
        <p><label>Aluno<br>
            <select name="usuario_id" required>
                <option value="">Selecione</option>
                <?php foreach ($alunos as $a): ?>
                    <?php if ($a['papel'] === 'representante') continue; ?>
                    <option value="<?= (int) $a['id'] ?>"><?= h($a['nome']) ?> — <?= h($a['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
        <button type="submit">Nomear</button>
    </form>
<?php else: ?>
    <p><strong>Turma já possui 2 representantes.</strong></p>
<?php endif; ?>

<p><a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">Voltar à turma</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>