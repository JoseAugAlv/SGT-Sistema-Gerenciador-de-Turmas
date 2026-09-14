<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Compra — <?= h($material['nome']) ?></h2>

<p>
    Estoque atual:
    <strong><?= number_format((float) $material['quantidade'], 2, ',', '.') ?></strong>
    <?= h($material['unidade'] ?? '') ?>
    — Preço médio atual: <strong>R$ <?= number_format((float) $material['preco'], 2, ',', '.') ?></strong>
</p>

<form method="POST" action="<?= $basePath ?>/materiais/<?= (int) $material['id'] ?>/comprar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Quantidade comprada *<br>
        <input type="number" name="quantidade" min="0.01" step="0.01" required autofocus></label></p>

    <p><label>Preço unitário da compra (R$) *<br>
        <input type="number" name="preco_unitario" min="0" step="0.01" required></label></p>

    <p><label>Observação<br>
        <textarea name="observacao" rows="2" maxlength="1000"></textarea></label></p>

    <p><em>O preço médio ponderado será recalculado automaticamente.</em></p>

    <p>
        <button type="submit"><i class="fas fa-plus-circle"></i> Registrar compra</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>