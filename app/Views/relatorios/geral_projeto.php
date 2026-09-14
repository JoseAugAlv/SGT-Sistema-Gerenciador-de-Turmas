<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Coleta todos os critérios (união dos boletins)
$criterios = [];
foreach ($boletins as $b) {
    foreach ($b['linhas'] as $l) {
        $criterios[(int) $l['criterio']['id']] = $l['criterio'];
    }
}

// Prepara ranking
$ranking = [];
foreach ($alunos as $a) {
    $aid = (int) $a['id'];
    $media = $boletins[$aid]['media_ponderada'] ?? null;
    $ranking[] = ['aluno' => $a, 'media' => $media, 'conceito' => $boletins[$aid]['conceito_final'] ?? null];
}
usort($ranking, fn($x, $y) => ($y['media'] ?? -1) <=> ($x['media'] ?? -1));
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
   — <?= $projeto['encerrado'] ? 'Encerrado' : 'Em andamento' ?>
</p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Relatório Geral do Projeto</h2>

<p>
    <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral/pdf">
        <i class="fas fa-file-pdf"></i> Exportar PDF
    </a>
    —
    <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral/excel">
        <i class="fas fa-file-excel"></i> Exportar Excel
    </a>
</p>

<?php if (empty($alunos)): ?>
    <p>Nenhum aluno na turma.</p>
<?php else: ?>
    <table border="1" cellpadding="4">
        <thead>
            <tr>
                <th>Aluno</th>
                <?php foreach ($criterios as $c): ?>
                    <th><?= h($c['nome']) ?><br>
                        <small>peso <?= number_format((float) $c['peso'], 2, ',', '.') ?></small>
                    </th>
                <?php endforeach; ?>
                <th>Média</th>
                <th>Conceito</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alunos as $a): $aid = (int) $a['id']; ?>
                <?php $b = $boletins[$aid] ?? null; ?>
                <?php
                $mapa = [];
                if ($b) {
                    foreach ($b['linhas'] as $l) {
                        $mapa[(int) $l['criterio']['id']] = $l;
                    }
                }
                ?>
                <tr>
                    <td><?= h($a['nome']) ?> <small>(<?= h($a['papel']) ?>)</small></td>
                    <?php foreach ($criterios as $cid => $c): ?>
                        <?php $nota = $mapa[$cid]['nota'] ?? null; ?>
                        <td><?= $nota === null ? '—' : number_format($nota, 2, ',', '.') ?></td>
                    <?php endforeach; ?>
                    <td>
                        <strong><?= $b && $b['media_ponderada'] !== null ? number_format($b['media_ponderada'], 2, ',', '.') : '—' ?></strong>
                    </td>
                    <td><?= h($b['conceito_final'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Ranking (por média)</h3>
    <ol>
        <?php foreach ($ranking as $r): ?>
            <li>
                <?= h($r['aluno']['nome']) ?>
                — <strong><?= $r['media'] === null ? '—' : number_format($r['media'], 2, ',', '.') ?></strong>
                (<?= h($r['conceito'] ?? '—') ?>)
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>