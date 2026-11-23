<?php
// app/Views/rateio/novo.php
// Dividir a parcela do plano: escolher quem entra; o valor por pessoa aparece na hora.
$cssPagina = ['projetos.css', 'rateio.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$valor = (float) $pag['valor'];
$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Dividir mensalidade</div>
        <h1>Dividir a <?= (int) $pag['numero'] ?>ª parcela</h1>
        <p class="hero-subtitle">Plano <?= h($assin['plano_nome'] ?? '') ?> da <strong><?= h($turma['nome']) ?></strong> · <?= $brl($valor) ?>. Cada um paga a sua parte pelo Pix.</p>
    </div>
    <div class="hero-actions"><a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/plano"><i class="fas fa-arrow-left"></i> Plano</a></div>
</section>

<form method="POST" action="<?= $basePath ?>/pagamentos/<?= (int) $pag['id'] ?>/rateio" class="panel rt-form" data-valor="<?= $valor ?>">
    <?= ViewHelper::csrfField() ?>
    <div class="rt-topo">
        <div><strong>Quem entra na divisão</strong><small>Desmarque quem não vai participar (ex.: quem já pagou por fora).</small></div>
        <div class="rt-calc"><span>Cada um paga</span><b id="rt-parte">—</b><small id="rt-qtd"></small></div>
    </div>
    <div class="rt-acoes-lista"><button type="button" class="button button-ghost" data-todos="1">Marcar todos</button><button type="button" class="button button-ghost" data-todos="0">Desmarcar todos</button></div>
    <ul class="rt-pessoas">
        <?php foreach ($pessoas as $id => $p): ?>
            <li><label><input type="checkbox" name="usuarios[]" value="<?= (int) $id ?>" checked> <?= h($p['nome']) ?><?= $p['papel'] === 'representante' ? ' <small>(representante)</small>' : '' ?></label></li>
        <?php endforeach; ?>
    </ul>
    <p class="field-help">Os centavos que não dividem exato vão em algumas partes (diferença de R$ 0,01). Quando todos pagarem, a parcela é quitada sozinha;
       se faltar alguém, você pode pagar o restante.</p>
    <button type="submit" class="button button-primary" data-carregando="Dividindo…"><i class="fas fa-people-arrows"></i> Dividir e avisar a turma</button>
</form>

<script>
(function () {
    var f = document.querySelector('.rt-form'), total = parseFloat(f.dataset.valor);
    function calc() {
        var n = f.querySelectorAll('[name="usuarios[]"]:checked').length;
        document.getElementById('rt-parte').textContent = n ? (total / n).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—';
        document.getElementById('rt-qtd').textContent = n + ' pessoa' + (n === 1 ? '' : 's');
    }
    f.addEventListener('change', calc);
    f.querySelectorAll('[data-todos]').forEach(function (b) { b.addEventListener('click', function () { f.querySelectorAll('[name="usuarios[]"]').forEach(function (c) { c.checked = b.dataset.todos === '1'; }); calc(); }); });
    calc();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
