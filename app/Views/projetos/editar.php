<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h1>Editar Projeto</h1>
<p>Turma: <strong><?= h($projeto['turma_nome']) ?></strong></p>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atualizar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Nome *<br>
        <input type="text" name="nome" maxlength="150" value="<?= h($projeto['nome']) ?>" required></label></p>

    <p><label>Descrição<br>
        <textarea name="descricao" rows="3" maxlength="2000"><?= h($projeto['descricao'] ?? '') ?></textarea></label></p>

    <p><label>Prazo<br>
        <input type="date" name="prazo" value="<?= h($projeto['prazo'] ?? '') ?>"></label></p>

    <p><label>Modo de avaliação *<br>
        <select name="modo_avaliacao" required>
            <?php foreach ($modos as $val => $label): ?>
                <option value="<?= h($val) ?>" <?= $val === $projeto['modo_avaliacao'] ? 'selected' : '' ?>>
                    <?= h($label) ?>
                </option>
            <?php endforeach; ?>
        </select></label></p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Salvar</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>