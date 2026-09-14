<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Mapa de valores já salvos: [cid][alunoId] => linha
$mapa = [];
foreach ($minhasAvaliacoes as $cid => $linhas) {
    foreach ($linhas as $l) {
        $mapa[(int) $cid][(int) $l['aluno_id']] = $l;
    }
}

// Remove critérios bloqueados da exibição
$critTodos     = array_values(array_filter($critTodos,     fn($c) => !$c['bloqueado']));
$critDiretores = array_values(array_filter($critDiretores, fn($c) => !$c['bloqueado']));
$critRepresent = array_values(array_filter($critRepresent, fn($c) => !$c['bloqueado']));

$temCrit = !empty($critTodos) || !empty($critDiretores) || !empty($critRepresent);
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
   — <?= $projeto['encerrado'] ? 'Encerrado' : 'Em andamento' ?>
</p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<h2>Avaliação em massa — Representante</h2>

<p><small>
    <i class="fas fa-info-circle"></i>
    <span style="background:#dbeafe;padding:2px 6px">Azul</span>: todos os alunos.
    <span style="background:#fed7aa;padding:2px 6px">Laranja</span>: apenas diretores.
    <span style="background:#d1fae5;padding:2px 6px">Verde</span>: apenas representantes.
</small></p>

<?php if (!$temCrit): ?>
    <p><strong>Nenhum critério aberto para avaliação.</strong></p>
    <?php require_once __DIR__ . '/../layouts/footer.php'; return; ?>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/avaliacoes/representante/salvar" id="form-bulk">
    <?= ViewHelper::csrfField() ?>

    <fieldset>
        <legend><i class="fas fa-bolt"></i> Preenchimento rápido</legend>

        <p>
            <strong>Por conceito:</strong>
            <button type="button" onclick="preencherTodosConceito('MB')">Todos MB</button>
            <button type="button" onclick="preencherTodosConceito('B')">Todos B</button>
            <button type="button" onclick="preencherTodosConceito('R')">Todos R</button>
            <button type="button" onclick="preencherTodosConceito('I')">Todos I</button>
            <button type="button" onclick="limparTudo()">Limpar tudo</button>
        </p>

        <p>
            <strong>Por valor:</strong>
            <?php foreach ([100, 95, 90, 85, 80, 70, 60, 50, 25, 0] as $v): ?>
                <button type="button" onclick="preencherTodosValor(<?= $v ?>)"><?= $v ?></button>
            <?php endforeach; ?>
        </p>

        <p>
            <label>
                <input type="checkbox" name="limpar_antes" value="1">
                <strong>Limpar e Salvar</strong> — apaga todas as suas avaliações deste projeto antes de salvar
            </label>
        </p>
    </fieldset>

    <table border="1" cellpadding="4" id="tabela-aval">
        <thead>
            <tr>
                <th>Aluno</th>
                <?php foreach ($critTodos     as $c): ?>
                    <th style="background:#dbeafe"><?= h($c['nome']) ?><br>
                        <small>peso <?= number_format((float) $c['peso'], 2, ',', '.') ?></small>
                    </th>
                <?php endforeach; ?>
                <?php foreach ($critDiretores as $c): ?>
                    <th style="background:#fed7aa"><?= h($c['nome']) ?><br>
                        <small>peso <?= number_format((float) $c['peso'], 2, ',', '.') ?></small>
                    </th>
                <?php endforeach; ?>
                <?php foreach ($critRepresent as $c): ?>
                    <th style="background:#d1fae5"><?= h($c['nome']) ?><br>
                        <small>peso <?= number_format((float) $c['peso'], 2, ',', '.') ?></small>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alunos as $a): $aid = (int) $a['id']; ?>
                <tr>
                    <td><?= h($a['nome']) ?> <small>(<?= h($a['papel']) ?>)</small></td>

                    <?php foreach (array_merge($critTodos, $critDiretores, $critRepresent) as $c): ?>
                        <?php
                        $cid = (int) $c['id'];
                        $reg = $mapa[$cid][$aid] ?? null;
                        $conc = $reg['conceito'] ?? '';
                        $val  = $reg['valor_numerico'] ?? '';
                        ?>
                        <td>
                            <select data-campo="conceito"
                                    name="aval[<?= $cid ?>][<?= $aid ?>][conceito]">
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
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><button type="submit"><i class="fas fa-save"></i> Salvar avaliações</button></p>
</form>

<script>
// ============================================================
// Configuração do projeto (limites de conceito)
// ============================================================
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

// ============================================================
// Sincronização bidirecional
// ============================================================
function aplicarConceito(select) {
    const td = select.closest('td');
    const input = td.querySelector('[data-campo="valor"]');
    if (!input) return;

    if (select.value === '') {
        input.value = '';
        return;
    }
    input.value = valorPorConceito(select.value);
}

function aplicarValor(input) {
    const td = input.closest('td');
    const select = td.querySelector('[data-campo="conceito"]');
    if (!select) return;

    const v = input.value.trim();
    if (v === '') {
        select.value = '';
        return;
    }
    select.value = conceitoPorValor(v);
}

document.addEventListener('change', function(e) {
    if (e.target.matches('[data-campo="conceito"]')) aplicarConceito(e.target);
});
document.addEventListener('input', function(e) {
    if (e.target.matches('[data-campo="valor"]')) aplicarValor(e.target);
});

// ============================================================
// Preenchimento rápido
// ============================================================
function preencherTodosConceito(c) {
    document.querySelectorAll('[data-campo="conceito"]').forEach(function(sel) {
        sel.value = c;
        aplicarConceito(sel);
    });
}

function preencherTodosValor(v) {
    document.querySelectorAll('[data-campo="valor"]').forEach(function(inp) {
        inp.value = v;
        aplicarValor(inp);
    });
}

function limparTudo() {
    if (!confirm('Limpar todas as células do formulário? (só limpa a tela, sem salvar)')) return;
    document.querySelectorAll('[data-campo="conceito"]').forEach(function(sel) { sel.value = ''; });
    document.querySelectorAll('[data-campo="valor"]').forEach(function(inp) { inp.value = ''; });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>