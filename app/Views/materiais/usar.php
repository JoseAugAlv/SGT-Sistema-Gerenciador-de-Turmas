<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Retirada — <?= h($material['nome']) ?></h2>

<p>
    Estoque disponível:
    <strong><?= number_format((float) $material['quantidade'], 2, ',', '.') ?></strong>
    <?= h($material['unidade'] ?? '') ?>
</p>

<form method="POST" action="<?= $basePath ?>/materiais/<?= (int) $material['id'] ?>/usar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Quantidade a retirar *<br>
        <input type="number" name="quantidade" min="0.01" step="0.01"
               max="<?= h($material['quantidade']) ?>" required autofocus></label></p>

    <p><label>Observação<br>
        <textarea name="observacao" rows="2" maxlength="1000"></textarea></label></p>

    <p>
        <button type="submit"><i class="fas fa-minus-circle"></i> Retirar</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>