<?php
$cssPagina = ['projetos.css', 'materiais.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$qtdEstoque = (float) $material['quantidade'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Retirada de material
        </div>
        <h1><?= h($material['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
            — Registre quanto foi retirado do estoque.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<section class="panel mb-16">
    <div class="material-resumo">
        <div class="resumo-linha">
            <span class="label">Estoque disponível</span>
            <span class="valor destaque">
                <?= number_format($qtdEstoque, 2, ',', '.') ?>
                <?= h($material['unidade'] ?? '') ?>
            </span>
        </div>
        <div class="resumo-linha">
            <span class="label">Preço unitário</span>
            <span class="valor">R$ <?= number_format((float) $material['preco'], 2, ',', '.') ?></span>
        </div>
    </div>

    <?php if ($qtdEstoque <= 0): ?>
        <div class="notice notice-danger mt-0">
            <span>!</span>
            <div>
                <strong>Estoque zerado</strong>
                <p class="mb-0">
                    Não há material disponível para retirada. Registre uma compra primeiro.
                </p>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php if ($qtdEstoque > 0): ?>
<form method="POST" action="<?= $basePath ?>/materiais/<?= (int) $material['id'] ?>/usar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Quantidade a retirar *</label>
    <input type="number" name="quantidade" class="field"
           min="0.01" step="0.01"
           max="<?= h($material['quantidade']) ?>"
           required autofocus>
    <small class="field-help">
        Máximo: <?= number_format($qtdEstoque, 2, ',', '.') ?>
        <?= h($material['unidade'] ?? '') ?>
    </small>

    <label class="field-label">Observação</label>
    <textarea name="observacao" class="field" rows="3" maxlength="1000"
              placeholder="Para que foi usado, quem retirou, etc."></textarea>

    <p class="mt-20">
        <button type="submit" class="button button-primary"
                onclick="return confirm('Registrar retirada?')">
            <i class="fas fa-minus-circle"></i> Registrar retirada
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
            Cancelar
        </a>
    </p>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>