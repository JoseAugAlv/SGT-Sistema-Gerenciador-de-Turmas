<?php
// app/Views/relatorios/geral_projeto_pdf.php
// HTML para TCPDF — paisagem (L), matriz alunos × critérios.

$criterios = [];
foreach ($boletins as $b) {
    foreach ($b['linhas'] as $l) {
        $criterios[(int) $l['criterio']['id']] = $l['criterio'];
    }
}

$ranking = [];
foreach ($alunos as $a) {
    $aid   = (int) $a['id'];
    $media = $boletins[$aid]['media_ponderada'] ?? null;
    $ranking[] = [
        'aluno'    => $a,
        'media'    => $media,
        'conceito' => $boletins[$aid]['conceito_final'] ?? null,
    ];
}
usort($ranking, fn($x, $y) => ($y['media'] ?? -1) <=> ($x['media'] ?? -1));
// o PDF não passa pelo cabeçalho do site, onde h() é definida
if (!function_exists('h')) { function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); } }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório Geral — <?= h($projeto['nome']) ?></title>
<style>
    body { font-family: helvetica, sans-serif; color: #333; font-size: 9px; }
    h1 { font-size: 16px; color: #1d4ed8; margin: 0 0 4px; }
    h2 { font-size: 12px; color: #555; margin: 18px 0 6px; }
    .subtitulo { color: #666; font-size: 10px; margin: 0 0 12px; }
    .badge-encerrado {
        display: inline-block;
        background: #fdecef;
        color: #b13b47;
        padding: 2px 8px;
        border-radius: 3px;
        font-size: 9px;
        font-weight: bold;
        margin-left: 6px;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th {
        background: #f0f0f5;
        color: #555;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: .3px;
        text-align: center;
        padding: 6px 4px;
        border-bottom: 1px solid #ddd;
    }
    th.aluno { text-align: left; min-width: 110px; }
    td {
        padding: 6px 4px;
        border-bottom: 1px solid #eee;
        font-size: 9px;
        color: #222;
        text-align: center;
    }
    td.aluno { text-align: left; font-weight: bold; }
    .num { text-align: right; }
    .media { color: #1d4ed8; font-weight: bold; }
    .conceito { font-weight: bold; }
    .conceito.mb { color: #29946e; }
    .conceito.b  { color: #3c8eea; }
    .conceito.r  { color: #d48b1c; }
    .conceito.i  { color: #e46d78; }
    .peso { color: #999; font-size: 7.5px; font-weight: normal; display: block; }

    /* Ranking */
    .ranking { margin-top: 20px; }
    .ranking table { width: 60%; margin-top: 4px; }
    .ranking th, .ranking td { text-align: left; padding: 5px 8px; font-size: 9px; }
    .ranking .pos { width: 30px; text-align: center; font-weight: bold; color: #1d4ed8; }
    .ranking .media { text-align: right; }
    .ranking .conceito { text-align: center; }
    .rodape {
        margin-top: 20px;
        padding-top: 10px;
        border-top: 1px solid #eee;
        color: #999;
        font-size: 8px;
        text-align: center;
    }
</style>
</head>
<body>

<h1>
    Relatório Geral do Projeto
    <?php if ($projeto['encerrado']): ?>
        <span class="badge-encerrado">ENCERRADO</span>
    <?php endif; ?>
</h1>
<p class="subtitulo">
    <strong><?= h($projeto['nome']) ?></strong> &nbsp;|&nbsp; Turma: <?= h($projeto['turma_nome']) ?>
    &nbsp;|&nbsp; <?= count($alunos) ?> aluno(s) × <?= count($criterios) ?> critério(s)
</p>

<?php if (empty($alunos) || empty($criterios)): ?>
    <p>Sem dados suficientes para gerar o relatório.</p>
<?php else: ?>

    <h2>Matriz de notas</h2>
    <table>
        <thead>
            <tr>
                <th class="aluno">Aluno</th>
                <?php foreach ($criterios as $c): ?>
                    <th>
                        <?= h($c['nome']) ?>
                        <span class="peso">peso <?= number_format((float) $c['peso'], 2, ',', '.') ?></span>
                    </th>
                <?php endforeach; ?>
                <th>Média</th>
                <th>Conceito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alunos as $a): ?>
                <?php
                $aid = (int) $a['id'];
                $b   = $boletins[$aid] ?? null;
                $mapa = [];
                if ($b) {
                    foreach ($b['linhas'] as $l) {
                        $mapa[(int) $l['criterio']['id']] = $l;
                    }
                }
                ?>
                <tr>
                    <td class="aluno"><?= h($a['nome']) ?></td>
                    <?php foreach ($criterios as $cid => $c): ?>
                        <?php $nota = $mapa[$cid]['nota'] ?? null; ?>
                        <td class="num">
                            <?= $nota === null ? '—' : number_format($nota, 2, ',', '.') ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="num media">
                        <?= $b && $b['media_ponderada'] !== null
                            ? number_format($b['media_ponderada'], 2, ',', '.')
                            : '—' ?>
                    </td>
                    <?php $cf = $b['conceito_final'] ?? null; ?>
                    <td class="conceito <?= $cf ? strtolower($cf) : '' ?>">
                        <?= h($cf ?? '—') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="ranking">
        <h2>Ranking por média</h2>
        <table>
            <thead>
                <tr>
                    <th class="pos">#</th>
                    <th>Aluno</th>
                    <th class="media">Média</th>
                    <th class="conceito">Conceito</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ranking as $i => $r): ?>
                    <tr>
                        <td class="pos"><?= $i + 1 ?>º</td>
                        <td><?= h($r['aluno']['nome']) ?></td>
                        <td class="media">
                            <?= $r['media'] === null ? '—' : number_format($r['media'], 2, ',', '.') ?>
                        </td>
                        <td class="conceito <?= $r['conceito'] ? strtolower($r['conceito']) : '' ?>">
                            <?= h($r['conceito'] ?? '—') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<div class="rodape">
    Gerado em <?= date('d/m/Y H:i') ?> — <?= h($appName) ?>
</div>

</body>
</html>