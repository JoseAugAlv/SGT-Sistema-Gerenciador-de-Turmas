<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Representantes — <?= h($turma['nome']) ?></h1>
<p>Cada turma pode ter no máximo <strong>2 representantes ativos</strong>.</p>

<p><a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
    <i class="fas fa-arrow-left"></i> Voltar à turma
</a></p>

<hr>

<h2>Representantes atuais (<?= count($representantes) ?>/2)</h2>

<?php if (empty($representantes)): ?>
    <p>Nenhum representante nomeado.</p>
<?php else: ?>
    <ul>
        <?php foreach ($representantes as $r): ?>
            <li>
                <?= h($r['nome']) ?> (<?= h($r['email']) ?>)
                <?php if (count($representantes) > 1): ?>
                    <form method="POST"
                          action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes/remover"
                          style="display:inline">
                        <?= ViewHelper::csrfField() ?>
                        <input type="hidden" name="usuario_id" value="<?= (int) $r['usuario_id'] ?>">
                        <button type="submit" onclick="return confirm('Remover este representante?')">
                            <i class="fas fa-user-minus"></i> Remover
                        </button>
                    </form>
                <?php else: ?>
                    <em>(último representante — não pode ser removido sem nomear outro antes)</em>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<hr>

<?php if (count($representantes) < 2): ?>
    <h2>Nomear novo representante</h2>

    <?php
    $alunosSemRep = array_filter($alunos, fn($a) => $a['papel'] !== 'representante');
    ?>

    <?php if (empty($alunosSemRep)): ?>
        <p>Não há alunos disponíveis para nomear. Primeiro aloque alunos nesta turma.</p>
    <?php else: ?>
        <form method="POST"
              action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes/nomear">
            <?= ViewHelper::csrfField() ?>

            <p>
                <label>Aluno<br>
                    <select name="usuario_id" required>
                        <option value="">Selecione um aluno</option>
                        <?php foreach ($alunosSemRep as $a): ?>
                            <option value="<?= (int) $a['id'] ?>">
                                <?= h($a['nome']) ?> — <?= h($a['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </p>
            <p>
                <button type="submit">
                    <i class="fas fa-user-plus"></i> Nomear representante
                </button>
            </p>
        </form>
    <?php endif; ?>
<?php else: ?>
    <p><strong>Esta turma já possui 2 representantes.</strong></p>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>