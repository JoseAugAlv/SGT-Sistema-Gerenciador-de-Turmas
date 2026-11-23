<?php
// app/Views/relatorios/analitico.php
// Painel analítico do projeto: indicadores, distribuição, critérios, grupos, alunos em risco e ranking filtrável/exportável.
$cssPagina = ['projetos.css', 'relatorios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Helpers/EscalaNota.php';

$ex  = fn($v) => $v === null ? '—' : EscalaNota::exibir((float) $v, $cfg);
$num = fn($v) => $v === null ? '—' : number_format(EscalaNota::paraEscala((float) $v, $cfg), EscalaNota::esquema($cfg) === '0a100' ? 0 : 1, ',', '.');
$numerico = EscalaNota::ehNumerico($cfg);
$cor = function ($v) use ($cfg) {
    if ($v === null) return 'sem';
    return strtolower(NotaService::conceitoPorValor((float) $v, $cfg));
};
$maxDist = max(1, max($dist ?: [0]));
$todosGrupos = [];
foreach ($linhas as $l) foreach ($l['grupos'] as $gid => $gn) $todosGrupos[$gid] = $gn;
asort($todosGrupos);
$emRisco = array_values(array_filter($linhas, fn($l) => $l['risco']));
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Relatórios</div>
        <h1>Painel analítico</h1>
        <p class="hero-subtitle"><?= h($projeto['nome']) ?> · <?= h($projeto['turma_nome']) ?> · notas em <?= h(EscalaNota::ESQUEMAS[EscalaNota::esquema($cfg)][0]) ?></p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios"><i class="fas fa-arrow-left"></i> Relatórios</a>
        <a class="button button-secondary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral/excel"><i class="fas fa-file-excel"></i> Excel</a>
        <a class="button button-secondary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral/pdf"><i class="fas fa-file-pdf"></i> PDF</a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<!-- ============ INDICADORES ============ -->
<div class="an-kpis">
    <div class="an-kpi"><small>Média da turma</small><strong class="num an-c-<?= $cor($kpi['media']) ?>"><?= $numerico ? $num($kpi['media']) : $ex($kpi['media']) ?></strong><span><?= $kpi['media'] === null ? 'sem notas ainda' : ($numerico ? 'de 0 a ' . EscalaNota::maximo($cfg) : 'média ' . number_format($kpi['media'], 1, ',', '')) ?></span></div>
    <div class="an-kpi"><small>Avaliações feitas</small><strong class="num"><?= $kpi['progresso'] ?>%</strong><span class="an-progresso"><i style="width: <?= $kpi['progresso'] ?>%"></i></span></div>
    <div class="an-kpi"><small>Alunos em risco</small><strong class="num <?= $kpi['risco'] ? 'an-c-i' : '' ?>"><?= $kpi['risco'] ?></strong><span>média na faixa do I</span></div>
    <div class="an-kpi"><small>Sem nota ainda</small><strong class="num"><?= $kpi['sem_nota'] ?></strong><span>de <?= $kpi['alunos'] ?> aluno(s)</span></div>
</div>

<div class="an-grade">
    <!-- ============ DISTRIBUIÇÃO ============ -->
    <section class="panel">
        <h2 class="an-h2">Distribuição das médias</h2>
        <?php if (!array_sum($dist)): ?>
            <p class="field-help">Aparece quando houver notas lançadas.</p>
        <?php else: ?>
            <div class="an-colunas" role="img" aria-label="Quantidade de alunos por <?= $numerico ? 'faixa de nota' : 'menção' ?>">
                <?php foreach ($dist as $rot => $q): ?>
                    <div class="an-col">
                        <span class="an-col-q num"><?= $q ?></span>
                        <span class="an-col-barra an-bg-<?= $numerico ? 'p' : strtolower($rot) ?>" style="height: <?= round($q / $maxDist * 100) ?>%"></span>
                        <small><?= h($rot) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- ============ GRUPOS ============ -->
    <section class="panel">
        <h2 class="an-h2">Comparação entre grupos</h2>
        <?php if (!$grupos): ?>
            <p class="field-help">O projeto ainda não tem grupos.</p>
        <?php else: ?>
            <ul class="an-barras">
                <?php foreach ($grupos as $g): ?>
                    <li>
                        <span class="an-barras-rot"><?= h($g['nome']) ?> <small><?= $g['membros'] ?> membro(s)</small></span>
                        <span class="an-barras-trilho"><i class="an-bg-<?= $cor($g['media']) ?>" style="width: <?= $g['media'] === null ? 0 : max(2, round($g['media'])) ?>%"></i></span>
                        <b class="num"><?= $numerico ? $num($g['media']) : $ex($g['media']) ?></b>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<!-- ============ CRITÉRIOS ============ -->
<section class="panel mt-16">
    <h2 class="an-h2">Média por critério</h2>
    <p class="field-help">Mostra onde a turma vai bem e onde precisa de atenção. O número ao lado é quantos alunos já foram avaliados.</p>
    <?php if (!$criterios): ?>
        <p class="field-help">Nenhum critério cadastrado.</p>
    <?php else: ?>
        <ul class="an-barras">
            <?php foreach ($criterios as $c): ?>
                <li>
                    <span class="an-barras-rot"><?= h($c['nome']) ?> <small>peso <?= rtrim(rtrim(number_format($c['peso'], 2, ',', ''), '0'), ',') ?></small></span>
                    <span class="an-barras-trilho"><i class="an-bg-<?= $cor($c['media']) ?>" style="width: <?= $c['media'] === null ? 0 : max(2, round($c['media'])) ?>%"></i></span>
                    <b class="num"><?= $numerico ? $num($c['media']) : $ex($c['media']) ?></b>
                    <small class="an-barras-n"><?= $c['avaliados'] ?>/<?= $kpi['alunos'] ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<!-- ============ EM RISCO ============ -->
<?php if ($emRisco): ?>
    <section class="panel mt-16 an-risco">
        <h2 class="an-h2"><i class="fas fa-triangle-exclamation"></i> Precisam de atenção (<?= count($emRisco) ?>)</h2>
        <ul class="lista-tarefas">
            <?php foreach ($emRisco as $l): ?>
                <li>
                    <span class="tarefa-icone"><i class="fas fa-user"></i></span>
                    <div class="tarefa-info">
                        <strong><?= h($l['nome']) ?></strong>
                        <small><?= $l['grupos'] ? h(implode(', ', $l['grupos'])) : 'sem grupo' ?> · média <?= $numerico ? $num($l['media']) : $ex($l['media']) ?> · <?= $l['feitos'] ?>/<?= $l['total'] ?> critérios<?= $l['presenca'] !== null ? ' · presença ' . $l['presenca'] . '%' : '' ?></small>
                    </div>
                    <a class="tarefa-acao" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/boletim?aluno_id=<?= (int) $l['id'] ?>">Boletim</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<!-- ============ RANKING ============ -->
<section class="panel mt-16">
    <div class="an-ranking-topo">
        <h2 class="an-h2">Todos os alunos</h2>
        <div class="an-filtros">
            <input type="search" class="field" id="an-busca" placeholder="Buscar aluno" aria-label="Buscar aluno">
            <select class="field" id="an-grupo" aria-label="Filtrar por grupo">
                <option value="">Todos os grupos</option>
                <option value="-">Sem grupo</option>
                <?php foreach ($todosGrupos as $gid => $gn): ?><option value="<?= (int) $gid ?>"><?= h($gn) ?></option><?php endforeach; ?>
            </select>
            <button type="button" class="button button-secondary" id="an-csv"><i class="fas fa-download"></i> CSV</button>
        </div>
    </div>
    <div class="mov-wrapper">
        <table class="an-tabela" id="an-tabela">
            <thead><tr>
                <th data-ord="n">#</th><th data-ord="t">Aluno</th><th data-ord="t">Grupo</th>
                <th data-ord="n" class="dir">Média</th><th data-ord="n" class="dir">Critérios</th><th data-ord="n" class="dir">Presença nas atas</th>
            </tr></thead>
            <tbody>
                <?php foreach ($linhas as $i => $l): ?>
                    <tr data-grupos="<?= $l['grupos'] ? implode(',', array_keys($l['grupos'])) : '-' ?>" data-nome="<?= h(mb_strtolower($l['nome'])) ?>" class="<?= $l['risco'] ? 'an-linha-risco' : '' ?>">
                        <td data-v="<?= $i + 1 ?>"><?= $i + 1 ?></td>
                        <td data-v="<?= h($l['nome']) ?>"><a class="mat-link" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/boletim?aluno_id=<?= (int) $l['id'] ?>"><?= h($l['nome']) ?></a></td>
                        <td data-v="<?= h(implode(', ', $l['grupos'])) ?>"><?= $l['grupos'] ? h(implode(', ', $l['grupos'])) : '<span class="txt-mudo">—</span>' ?></td>
                        <td class="dir" data-v="<?= $l['media'] ?? -1 ?>">
                            <?php if ($l['media'] === null): ?><span class="txt-mudo">—</span>
                            <?php else: ?><span class="an-chip an-bg-<?= $cor($l['media']) ?>"><?= $numerico ? $num($l['media']) : h($l['conceito']) ?></span><?php if (!$numerico): ?> <small class="num"><?= number_format($l['media'], 1, ',', '') ?></small><?php endif; ?><?php endif; ?>
                        </td>
                        <td class="dir" data-v="<?= $l['total'] ? $l['feitos'] / $l['total'] : 0 ?>"><?= $l['feitos'] ?>/<?= $l['total'] ?></td>
                        <td class="dir" data-v="<?= $l['presenca'] ?? -1 ?>"><?= $l['presenca'] === null ? '<span class="txt-mudo">—</span>' : $l['presenca'] . '%' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
.an-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
.an-kpi { display: grid; gap: 4px; padding: 16px; border: 1px solid var(--border); border-radius: var(--radius, 12px); background: var(--surface); }
.an-kpi small { font-size: 12px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; }
.an-kpi strong { font-size: 32px; line-height: 1.1; }
.an-kpi span { font-size: 13px; color: var(--muted); }
.an-progresso { display: block; height: 8px; border-radius: 4px; background: var(--surface-2); overflow: hidden; }
.an-progresso i { display: block; height: 100%; background: var(--primary); }
.an-grade { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.an-grade .panel { margin: 0; }
.an-h2 { margin: 0 0 12px; font-size: 16px; }
.an-c-i { color: #d63548; } .an-c-r { color: #d48b1c; } .an-c-b { color: #1c6fd6; } .an-c-mb { color: #29946e; }
.an-bg-i { background: #d63548; } .an-bg-r { background: #d48b1c; } .an-bg-b { background: #1c6fd6; } .an-bg-mb { background: #29946e; }
.an-bg-p { background: var(--primary); } .an-bg-sem { background: var(--border); }
.an-colunas { display: flex; align-items: flex-end; gap: 8px; height: 180px; padding-top: 18px; }
.an-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; min-width: 0; }
.an-col-barra { display: block; width: 100%; max-width: 56px; min-height: 2px; border-radius: 6px 6px 0 0; }
.an-col-q { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
.an-col small { margin-top: 6px; font-size: 11px; color: var(--muted); white-space: nowrap; }
.an-barras { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
.an-barras li { display: grid; grid-template-columns: minmax(120px, 1.2fr) 2fr 52px auto; align-items: center; gap: 10px; }
.an-barras-rot { font-size: 14px; font-weight: 600; min-width: 0; overflow: hidden; text-overflow: ellipsis; }
.an-barras-rot small { display: block; font-weight: 400; color: var(--muted); font-size: 12px; }
.an-barras-trilho { height: 12px; border-radius: 6px; background: var(--surface-2); overflow: hidden; }
.an-barras-trilho i { display: block; height: 100%; border-radius: 6px; }
.an-barras b { text-align: right; }
.an-barras-n { color: var(--muted); font-size: 12px; }
.an-risco h2 .fa-triangle-exclamation { color: #d63548; }
.an-ranking-topo { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 10px; }
.an-ranking-topo .an-h2 { margin: 0; }
.an-filtros { display: flex; flex-wrap: wrap; gap: 8px; }
.an-filtros .field { margin: 0; width: auto; min-width: 160px; }
.an-tabela { width: 100%; border-collapse: collapse; font-size: 14px; }
.an-tabela th, .an-tabela td { padding: 9px 10px; border-bottom: 1px solid var(--border); text-align: left; }
.an-tabela th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); cursor: pointer; user-select: none; white-space: nowrap; }
.an-tabela th[aria-sort]::after { content: ' ▲'; font-size: 9px; } .an-tabela th[aria-sort=descending]::after { content: ' ▼'; }
.an-tabela .dir { text-align: right; }
.an-linha-risco td:first-child { box-shadow: inset 3px 0 0 #d63548; }
.an-chip { display: inline-block; min-width: 34px; padding: 2px 8px; border-radius: 999px; color: #fff; font-weight: 700; font-size: 12px; text-align: center; }
@media (max-width: 860px) { .an-kpis { grid-template-columns: 1fr 1fr; } .an-grade { grid-template-columns: 1fr; } }
@media (max-width: 520px) { .an-barras li { grid-template-columns: 1fr 52px; } .an-barras-trilho { grid-column: 1 / -1; grid-row: 2; } .an-barras-n { display: none; } }
</style>
<script>
(function () {
    var tab = document.getElementById('an-tabela'), corpo = tab.tBodies[0];
    var busca = document.getElementById('an-busca'), grupo = document.getElementById('an-grupo');
    function filtrar() {
        var b = busca.value.trim().toLowerCase(), g = grupo.value;
        Array.from(corpo.rows).forEach(function (r) {
            var okB = !b || r.dataset.nome.indexOf(b) >= 0;
            var okG = !g || r.dataset.grupos.split(',').indexOf(g) >= 0;
            r.hidden = !(okB && okG);
        });
    }
    busca.addEventListener('input', filtrar); grupo.addEventListener('change', filtrar);
    tab.tHead.querySelectorAll('th').forEach(function (th, i) {
        th.addEventListener('click', function () {
            var desc = th.getAttribute('aria-sort') !== 'descending';
            tab.tHead.querySelectorAll('th').forEach(function (o) { o.removeAttribute('aria-sort'); });
            th.setAttribute('aria-sort', desc ? 'descending' : 'ascending');
            var numero = th.dataset.ord === 'n';
            Array.from(corpo.rows).sort(function (a, b) {
                var x = a.cells[i].dataset.v, y = b.cells[i].dataset.v;
                var c = numero ? (+x - +y) : x.localeCompare(y, 'pt-BR');
                return desc ? -c : c;
            }).forEach(function (r) { corpo.appendChild(r); });
        });
    });
    document.getElementById('an-csv').addEventListener('click', function () {
        var linhas = [Array.from(tab.tHead.rows[0].cells).map(function (c) { return c.textContent.trim(); })];
        Array.from(corpo.rows).filter(function (r) { return !r.hidden; }).forEach(function (r) {
            linhas.push(Array.from(r.cells).map(function (c) { return c.textContent.replace(/\s+/g, ' ').trim(); }));
        });
        var csv = linhas.map(function (l) { return l.map(function (v) { return '"' + v.replace(/"/g, '""') + '"'; }).join(';'); }).join('\r\n');
        var a = document.createElement('a');
        a.href = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
        a.download = 'painel-<?= (int) $projeto['id'] ?>.csv'; a.click();
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
