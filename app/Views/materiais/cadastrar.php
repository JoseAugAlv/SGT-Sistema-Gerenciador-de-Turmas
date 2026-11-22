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
            Novo material
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
            — Cadastre um novo material para o projeto.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome do material *</label>
    <input type="text" name="nome" class="field" maxlength="150" required autofocus
           placeholder="Ex: Papel sulfite A4">

    <label class="field-label">Unidade *</label>
    <select name="unidade" class="field" required>
        <option value="">Selecione uma unidade</option>
        <?php foreach ($unidades as $chave => $label): ?>
            <option value="<?= h($chave) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
    </select>

    <div class="field-row">
        <div>
            <label class="field-label">Quantidade inicial *</label>
            <input type="number" name="quantidade" class="field"
                   min="0" step="0.01" value="0" required>
            <small class="field-help">Quantidade disponível no estoque.</small>
        </div>
        <div>
            <label class="field-label">Preço unitário (R$)</label>
            <input type="text" name="preco" class="field campo-moeda"
                   inputmode="numeric" placeholder="0,00" value="0,00">
            <small class="field-help">Preço médio pago por unidade.</small>
        </div>
    </div>

    <div class="notice notice-blue mt-16">
        <span>i</span>
        <div>
            <strong>Preço médio ponderado</strong>
            <p class="mb-0">
                A cada nova compra, o preço médio é recalculado automaticamente
                com base no estoque atual e no valor da compra.
            </p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Cadastrar material
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
            Cancelar
        </a>
    </p>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.campo-moeda').forEach(function(input) {
        if (input.value !== '') formatarMoeda(input);
        input.addEventListener('input', function() { formatarMoeda(this); });
    });
});

function formatarMoeda(input) {
    var v = input.value.replace(/\D/g, '');
    if (v === '') { input.value = ''; return; }
    v = (parseInt(v, 10) / 100).toFixed(2);
    v = v.replace('.', ',');
    v = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = v;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>