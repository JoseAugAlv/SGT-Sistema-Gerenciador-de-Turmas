<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<h2>Avaliação por Pares</h2>

<?php if (empty($dados)): ?>
    <p>Nenhum critério de pares aberto no momento.</p>
    <?php require_once __DIR__ . '/../layouts/footer.php'; return; ?>
<?php endif; ?>

<p><small><i class="fas fa-info-circle"></i> Justificativa é obrigatória. Só é visível para representante e diretor.</small></p>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/pares/salvar">
    <?= ViewHelper::csrfField() ?>

    <?php foreach ($dados as $cid => $bloco): ?>
        <h3>
            <?= h($bloco['criterio']['nome']) ?>
            <small>(peso <?= number_format((float) $bloco['criterio']['peso'], 2, ',', '.') ?>)</small>
        </h3>

        <?php if (empty($bloco['colegas'])): ?>
            <p><em>Nenhum colega disponível para avaliar neste critério.</em></p>
        <?php else: ?>
            <table border="1" cellpadding="4">
                <thead>
                    <tr>
                        <th>Colega</th>
                        <th>Conceito</th>
                        <th>Valor</th>
                        <th>Justificativa *</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bloco['colegas'] as $col): $aid = (int) $col['id']; ?>
                        <?php
                        $reg  = $bloco['minhas'][$aid] ?? null;
                        $conc = $reg['conceito'] ?? '';
                        $val  = $reg['valor_numerico'] ?? '';
                        $just = $reg['justificativa'] ?? '';
                        ?>
                        <tr>
                            <td>
                                <?= h($col['nome']) ?>

                                <?php if (!empty($col['is_diretor'])): ?>
                                    <small style="background:#fef3c7;padding:1px 6px;border-radius:4px;margin-left:4px;white-space:nowrap;">
                                        <i class="fas fa-crown"></i> Diretor
                                    </small>
                                <?php endif; ?>

                                <?php if (!empty($col['is_representante'])): ?>
                                    <small style="background:#dbeafe;padding:1px 6px;border-radius:4px;margin-left:4px;white-space:nowrap;">
                                        <i class="fas fa-user-tie"></i> Representante
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select data-campo="conceito"
                                        name="aval[<?= (int) $cid ?>][<?= $aid ?>][conceito]">
                                    <option value="">—</option>
                                    <?php foreach (['I','R','B','MB'] as $op): ?>
                                        <option value="<?= $op ?>" <?= $conc === $op ? 'selected' : '' ?>><?= $op ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100"
                                       data-campo="valor"
                                       name="aval[<?= (int) $cid ?>][<?= $aid ?>][valor]"
                                       value="<?= $val !== '' ? number_format((float) $val, 2, '.', '') : '' ?>"
                                       style="width:70px">
                            </td>
                            <td>
                                <textarea name="aval[<?= (int) $cid ?>][<?= $aid ?>][justificativa]"
                                          rows="2" cols="40" maxlength="1000"><?= h($just) ?></textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endforeach; ?>

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
        const td  = e.target.closest('td');
        const inp = td.parentElement.querySelector('[data-campo="valor"]');
        if (inp) inp.value = e.target.value ? valorPorConceito(e.target.value) : '';
    }
});

document.addEventListener('input', e => {
    if (e.target.matches('[data-campo="valor"]')) {
        const td  = e.target.closest('td');
        const sel = td.parentElement.querySelector('[data-campo="conceito"]');
        if (sel) sel.value = e.target.value ? conceitoPorValor(e.target.value) : '';
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>