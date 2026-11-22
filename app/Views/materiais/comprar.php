<?php
$cssPagina = ['projetos.css', 'materiais.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$qtdAtual   = (float) $material['quantidade'];
$precoAtual = (float) $material['preco'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Registro de compra
        </div>
        <h1><?= h($material['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
            — O preço médio será recalculado automaticamente.
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
    <div class="panel-header">
        <div>
            <h2>Estoque atual</h2>
            <p>Estado antes da nova compra.</p>
        </div>
    </div>

    <div class="material-resumo">
        <div class="resumo-linha">
            <span class="label">Quantidade em estoque</span>
            <span class="valor destaque">
                <?= number_format($qtdAtual, 2, ',', '.') ?>
                <?= h($material['unidade'] ?? '') ?>
            </span>
        </div>
        <div class="resumo-linha">
            <span class="label">Preço médio atual</span>
            <span class="valor">R$ <?= number_format($precoAtual, 2, ',', '.') ?></span>
        </div>
        <div class="resumo-linha">
            <span class="label">Valor total em estoque</span>
            <span class="valor">
                R$ <?= number_format($qtdAtual * $precoAtual, 2, ',', '.') ?>
            </span>
        </div>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/materiais/<?= (int) $material['id'] ?>/comprar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <div class="field-row">
        <div>
            <label class="field-label">Quantidade comprada *</label>
            <input type="number" name="quantidade" id="input-qtd"
                   class="field" min="0.01" step="0.01" required autofocus>
        </div>
        <div>
            <label class="field-label">Preço unitário (R$) *</label>
            <input type="number" name="preco_unitario" id="input-preco"
                   class="field" min="0" step="0.01" required>
        </div>
    </div>

    <label class="field-label">Observação</label>
    <textarea name="observacao" class="field" rows="3" maxlength="1000"
              placeholder="Fornecedor, nota fiscal, etc."></textarea>

    <div class="notice notice-blue mt-16">
        <span>i</span>
        <div>
            <strong>Como o preço médio é calculado</strong>
            <p class="mb-0">
                <code>novo preço = (estoque × preço atual + comprado × preço compra) ÷ (estoque + comprado)</code>
            </p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary"
                onclick="return confirm('Registrar esta compra?')">
            <i class="fas fa-cart-plus"></i> Registrar compra
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>