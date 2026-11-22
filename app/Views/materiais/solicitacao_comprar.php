<?php
$cssPagina = ['projetos.css', 'materiais.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Confirmar compra
        </div>
        <h1><?= h($solicitacao['nome_material']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
            — Solicitação de <?= h($solicitacao['solicitante_nome']) ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/solicitacoes">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<!-- ============ RESUMO DA SOLICITAÇÃO ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Solicitação original</h2>
        </div>
    </div>

    <div class="material-resumo">
        <div class="resumo-linha">
            <span class="label">Solicitado por</span>
            <span class="valor"><?= h($solicitacao['solicitante_nome']) ?></span>
        </div>
        <div class="resumo-linha">
            <span class="label">Quantidade pedida</span>
            <span class="valor destaque">
                <?= number_format((float) $solicitacao['quantidade'], 2, ',', '.') ?>
                <?= h($solicitacao['unidade'] ?? '') ?>
            </span>
        </div>
        <?php if (!empty($solicitacao['preco_estimado'])): ?>
            <div class="resumo-linha">
                <span class="label">Preço estimado</span>
                <span class="valor">R$ <?= number_format((float) $solicitacao['preco_estimado'], 2, ',', '.') ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($solicitacao['justificativa'])): ?>
            <div class="resumo-linha">
                <span class="label">Justificativa</span>
                <span class="valor"><?= nl2br(h($solicitacao['justificativa'])) ?></span>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ INFO: MATERIAL EXISTENTE? ============ -->
<?php if ($materialExistente): ?>
    <div class="notice notice-blue mb-16">
        <span>i</span>
        <div>
            <strong>Material já existe no estoque</strong>
            <p class="mb-0">
                Já existe um material com este nome: <strong><?= h($materialExistente['nome']) ?></strong>
                — estoque atual de
                <strong><?= number_format((float) $materialExistente['quantidade'], 2, ',', '.') ?>
                <?= h($materialExistente['unidade'] ?? '') ?></strong>.
                A compra será somada ao estoque e o preço médio recalculado.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="notice notice-warning mb-16">
        <span>!</span>
        <div>
            <strong>Material novo</strong>
            <p class="mb-0">
                Não existe um material com este nome no projeto. Um novo será criado
                automaticamente ao confirmar a compra, com a quantidade e o preço informados.
            </p>
        </div>
    </div>
<?php endif; ?>

<!-- ============ FORM DE COMPRA ============ -->
<form method="POST" action="<?= $basePath ?>/solicitacoes/<?= (int) $solicitacao['id'] ?>/comprar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <div class="field-row">
        <div>
            <label class="field-label">Quantidade comprada *</label>
            <input type="number" name="quantidade" class="field"
                   min="0.01" step="0.01" required autofocus
                   value="<?= h($solicitacao['quantidade']) ?>">
        </div>
        <div>
            <label class="field-label">Preço unitário (R$) *</label>
            <input type="number" name="preco_unitario" class="field"
                   min="0" step="0.01" required
                   value="<?= h($solicitacao['preco_estimado'] ?? '0') ?>">
        </div>
    </div>

    <label class="field-label">Observação</label>
    <textarea name="observacao" class="field" rows="3" maxlength="1000"
              placeholder="Fornecedor, nota fiscal, etc."></textarea>

    <p class="mt-20">
        <button type="submit" class="button button-primary"
                onclick="return confirm('Confirmar compra e marcar solicitação como comprada?')">
            <i class="fas fa-check"></i> Confirmar compra
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/solicitacoes">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>