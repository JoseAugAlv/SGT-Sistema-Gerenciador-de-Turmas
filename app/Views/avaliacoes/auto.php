<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<h2>Autoavaliação</h2>

<?php if (empty($criterios)): ?>
    <p>Nenhum critério de autoavaliação aberto.</p>
    <?php require_once __DIR__ . '/../layouts/footer.php'; return; ?>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/auto/salvar">
    <?= ViewHelper::csrfField() ?>

    <table border="1" cellpadding="4">
        <thead>
            <tr><th>Critério</th><th>Peso</th><th>Conceito</th><th>Valor</th></tr>
        </thead>
        <tbody>
            <?php foreach ($criterios as $c): $cid = (int) $c['id']; ?>
                <?php $reg = $minhas[$cid] ?? null;
                      $conc = $reg['conceito'] ?? '';
                      $val  = $reg['valor_numerico'] ?? ''; ?>
                <tr>
                    <td><?= h($c['nome']) ?></td>
                    <td><?= number_format((float) $c['peso'], 2, ',', '.') ?></td>
                    <td>
                        <select data-campo="conceito" name="aval[<?= $cid ?>][conceito]">
                            <option value="">—</option>
                            <?php foreach (['I','R','B','MB'] as $op): ?>
                                <option value="<?= $op ?>" <?= $conc === $op ? 'selected' : '' ?>><?= $op ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" step="0.01" min="0" max="100"
                               data-campo="valor" name="aval[<?= $cid ?>][valor]"
                               value="<?= $val !== '' ? number_format((float) $val, 2, '.', '') : '' ?>"
                               style="width:80px">
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><button type="submit"><i class="fas fa-save"></i> Salvar</button></p>
</form>

<script>
const CFG = {
    iMax:  <?= (int) $cfg['limite_i_max'] ?>,
    rMin:  <?= (int) $cfg['limite_r_min'] ?>,
    rMax:  <?= (int) $cfg['limite_r_max'] ?>,
    bMin:  <?= (int) $cfg['limite_b_min'] ?>,
    bMax:  <?= (int) $cfg['limite_b_max'] ?>,
    mbMin: <?= (int) $cfg['limite_mb_min'] ?>,
};
function valorPorConceito(c) {
    switch (c) {
        case 'I':  return CFG.iMax;
        case 'R':  return Math.round((CFG.rMin + CFG.rMax) / 2);
        case 'B':  return Math.round((CFG.bMin + CFG.bMax) / 2);
        case 'MB': return CFG.mbMin;
    }
    return '';
}
function conceitoPorValor(v) {
    v = parseFloat(v);
    if (isNaN(v)) return '';
    if (v <= CFG.iMax) return 'I';
    if (v <= CFG.rMax) return 'R';
    if (v <= CFG.bMax) return 'B';
    return 'MB';
}
document.addEventListener('change', e => {
    if (e.target.matches('[data-campo="conceito"]')) {
        const td = e.target.closest('td');
        const inp = td.parentElement.querySelector('[data-campo="valor"]');
        if (inp) inp.value = e.target.value ? valorPorConceito(e.target.value) : '';
    }
});
document.addEventListener('input', e => {
    if (e.target.matches('[data-campo="valor"]')) {
        const td = e.target.closest('td');
        const sel = td.parentElement.querySelector('[data-campo="conceito"]');
        if (sel) sel.value = e.target.value ? conceitoPorValor(e.target.value) : '';
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>