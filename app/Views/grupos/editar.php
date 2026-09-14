<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h1>Editar Grupo</h1>
<p>Projeto: <strong><?= h($projeto['nome']) ?></strong></p>

<form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/atualizar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Nome do grupo *<br>
        <input type="text" name="nome" maxlength="150" value="<?= h($grupo['nome']) ?>" required></label></p>

    <p><label>Modo do grupo *<br>
        <select name="modo_avaliacao_grupo" required>
            <?php foreach ($modosGrupo as $val => $label): ?>
                <option value="<?= h($val) ?>" <?= $val === $grupo['modo_avaliacao_grupo'] ? 'selected' : '' ?>>
                    <?= h($label) ?>
                </option>
            <?php endforeach; ?>
        </select></label></p>

    <p><label>Modo de avaliação padrão *<br>
        <select name="modo_avaliacao_por" required>
            <?php foreach ($modosPor as $val => $label): ?>
                <option value="<?= h($val) ?>" <?= $val === $grupo['modo_avaliacao_por'] ? 'selected' : '' ?>>
                    <?= h($label) ?>
                </option>
            <?php endforeach; ?>
        </select></label></p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Salvar</button>
        <a href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>