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
            Solicitar material
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
            — Descreva o material que você precisa.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<div class="notice notice-blue mt-0 mb-16">
    <span>i</span>
    <div>
        <strong>Sua solicitação é vista por todos os alunos da turma</strong>
        <p class="mb-0">
            Representante e diretores recebem uma notificação. Eles podem
            <strong>aprovar</strong> (vai para a lista de compras) ou <strong>recusar</strong>
            (com um motivo).
        </p>
    </div>
</div>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/solicitar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome do material *</label>
    <input type="text" name="nome_material" class="field" maxlength="150" required autofocus
           placeholder="Ex: Papel sulfite A4">

    <div class="field-row">
        <div>
            <label class="field-label">Quantidade *</label>
            <input type="number" name="quantidade" class="field"
                   min="0.01" step="0.01" required placeholder="Ex: 5">
        </div>
        <div>
            <label class="field-label">Unidade</label>
            <select name="unidade" class="field">
                <option value="">Selecione</option>
                <?php foreach ($unidades as $chave => $label): ?>
                    <option value="<?= h($chave) ?>"><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <label class="field-label">Por que você precisa? *</label>
    <textarea name="justificativa" class="field" rows="4" maxlength="2000" required
              placeholder="Explique para que vai usar, em qual atividade, etc."></textarea>

    <label class="field-label">Preço estimado (opcional)</label>
    <input type="text" name="preco_estimado" class="field campo-moeda"
           inputmode="numeric" placeholder="0,00">
    <small class="field-help">Se souber mais ou menos quanto custa, ajuda o representante.</small>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-paper-plane"></i> Enviar solicitação
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
            Cancelar
        </a>
    </p>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.campo-moeda').forEach(function(input) {
        input.addEventListener('input', function() { formatarMoeda(this); });
    });
});
function formatarMoeda(input) {
    var v = input.value.replace(/\D/g, '');
    if (v === '') { input.value = ''; return; }
    v = (parseInt(v, 10) / 100).toFixed(2);
    v = v.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = v;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>