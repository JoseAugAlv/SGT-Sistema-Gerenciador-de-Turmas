<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$mapa = [];
foreach ($minhasAvaliacoes as $cid => $linhas) {
    foreach ($linhas as $l) {
        $mapa[(int) $cid][(int) $l['aluno_id']] = $l;
    }
}
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<h2>Avaliação por Diretor</h2>

<?php if (empty($criterios)): ?>
    <p>Nenhum critério do tipo <strong>diretor</strong> ou <strong>misto</strong> está aberto.</p>
    <?php require_once __DIR__ . '/../layouts/footer.php'; return; ?>
<?php endif; ?>

<?php if (count($meusGrupos) > 1): ?>
    <p>
        <strong>Grupo:</strong>
        <?php foreach ($meusGrupos as $g): ?>
            <?php if ((int) $g['id'] === $grupoAtual): ?>
                <strong><?= h($g['nome']) ?></strong>
            <?php else: ?>
                <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/diretor?grupo_id=<?= (int) $g['id'] ?>">
                    <?= h($g['nome']) ?>
                </a>
            <?php endif; ?>
            <?= $g !== end($meusGrupos) ? '—' : '' ?>
        <?php endforeach; ?>
    </p>
<?php else: ?>
    <p>Grupo: <strong><?= h($meusGrupos[0]['nome']) ?></strong></p>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/diretor/salvar">
    <?= ViewHelper::csrfField() ?>
    <input type="hidden" name="grupo_id" value="<?= (int) $grupoAtual ?>">

    <fieldset>
        <legend><i class="fas fa-bolt"></i> Preenchimento rápido</legend>
        <p>
            <strong>Conceito:</strong>
            <button type="button" onclick="preencherTodosConceito('MB')">Todos MB</button>
            <button type="button" onclick="preencherTodosConceito('B')">Todos B</button>
            <button type="button" onclick="preencherTodosConceito('R')">Todos R</button>
            <button type="button" onclick="preencherTodosConceito('I')">Todos I</button>
        </p>
        <p>
            <strong>Valor:</strong>
            <?php foreach ([100,95,90,85,80,70,60,50,25,0] as $v): ?>
                <button type="button" onclick="preencherTodosValor(<?= $v ?>)"><?= $v ?></button>
            <?php endforeach; ?>
        </p>
    </fieldset>

    <table border="1" cellpadding="4">
        <thead>
            <tr>
                <th>Aluno</th>
                <?php foreach ($criterios as $c): ?>
                    <th><?= h($c['nome']) ?><br>
                        <small><?= h($c['tipo_avaliacao']) ?> — peso <?= number_format((float) $c['peso'], 2, ',', '.') ?></small>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($membros as $m): $aid = (int) $m['usuario_id']; ?>
                <?php $souEu = $aid === $meuId; ?>
                <tr <?= $souEu ? 'style="background:#f5f5f5"' : '' ?>>
                    <td>
                        <?= h($m['nome']) ?>
                        <?php if ($souEu): ?><small>(você — não pode se autoavaliar)</small><?php endif; ?>
                    </td>
                    <?php foreach ($criterios as $c): $cid = (int) $c['id']; ?>
                        <?php
                        $reg = $mapa[$cid][$aid] ?? null;
                        $conc = $reg['conceito'] ?? '';
                        $val  = $reg['valor_numerico'] ?? '';

                        // Avaliações de outros diretores
                        $outros = [];
                        if (!empty($avaliacoesOutros[$cid])) {
                            foreach ($avaliacoesOutros[$cid] as $outroId => $linhas) {
                                foreach ($linhas as $l) {
                                    if ((int) $l['aluno_id'] === $aid) $outros[] = $l;
                                }
                            }
                        }
                        ?>
                        <td>
                            <?php if ($souEu): ?>
                                <em>bloqueado</em>
                            <?php else: ?>
                                <select data-campo="conceito" name="aval[<?= $cid ?>][<?= $aid ?>][conceito]">
                                    <option value="">—</option>
                                    <?php foreach (['I','R','B','MB'] as $op): ?>
                                        <option value="<?= $op ?>" <?= $conc === $op ? 'selected' : '' ?>><?= $op ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" step="0.01" min="0" max="100"
                                       data-campo="valor"
                                       name="aval[<?= $cid ?>][<?= $aid ?>][valor]"
                                       value="<?= $val !== '' ? number_format((float) $val, 2, '.', '') : '' ?>"
                                       style="width:70px">
                                <?php if (!empty($outros)): ?>
                                    <br>
                                    <small>
                                        Outros diretores:
                                        <?php foreach ($outros as $o): ?>
                                            <code><?= h($o['conceito']) ?> (<?= number_format((float) $o['valor_numerico'], 2, ',', '.') ?>)</code>
                                        <?php endforeach; ?>
                                    </small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><button type="submit"><i class="fas fa-save"></i> Salvar avaliações</button></p>
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
function aplicarConceito(sel) {
    const td = sel.closest('td');
    const inp = td.querySelector('[data-campo="valor"]');
    if (!inp) return;
    if (sel.value === '') { inp.value = ''; return; }
    inp.value = valorPorConceito(sel.value);
}
function aplicarValor(inp) {
    const td = inp.closest('td');
    const sel = td.querySelector('[data-campo="conceito"]');
    if (!sel) return;
    const v = inp.value.trim();
    if (v === '') { sel.value = ''; return; }
    sel.value = conceitoPorValor(v);
}
document.addEventListener('change', e => {
    if (e.target.matches('[data-campo="conceito"]')) aplicarConceito(e.target);
});
document.addEventListener('input', e => {
    if (e.target.matches('[data-campo="valor"]')) aplicarValor(e.target);
});
function preencherTodosConceito(c) {
    document.querySelectorAll('[data-campo="conceito"]').forEach(s => { s.value = c; aplicarConceito(s); });
}
function preencherTodosValor(v) {
    document.querySelectorAll('[data-campo="valor"]').forEach(i => { i.value = v; aplicarValor(i); });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>