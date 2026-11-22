<?php
$cssPagina = ['projetos.css', 'relatorios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

function classeConceitoGeral(?string $c): string {
    return match ($c) {
        'I'  => 'i',
        'R'  => 'r',
        'B'  => 'b',
        'MB' => 'mb',
        default => 'vazio',
    };
}

// Coleta todos os critérios (união dos boletins)
$criterios = [];
foreach ($boletins as $b) {
    foreach ($b['linhas'] as $l) {
        $criterios[(int) $l['criterio']['id']] = $l['criterio'];
    }
}

// Ranking ordenado
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
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Relatório geral
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
            — <?= count($alunos) ?> aluno(s) × <?= count($criterios) ?> critério(s)
            <?php if ($projeto['encerrado']): ?>
                — <span class="badge badge-red"><i class="fas fa-lock"></i> Encerrado</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios">
            <i class="fas fa-arrow-left"></i> Voltar aos relatórios
        </a>
        <a class="button button-secondary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
            <i class="fas fa-diagram-project"></i> Abrir projeto
        </a>
    </div>

    
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php if (empty($alunos)): ?>
    <section class="panel rel-empty">
        <span class="icon-large text-muted"><i class="fas fa-users"></i></span>
        <h2>Nenhum aluno na turma</h2>
        <p>Cadastre alunos na turma para gerar o relatório.</p>
    </section>
<?php elseif (empty($criterios)): ?>
    <section class="panel rel-empty">
        <span class="icon-large text-muted"><i class="fas fa-clipboard-check"></i></span>
        <h2>Nenhum critério definido</h2>
        <p>Cadastre critérios no projeto para gerar o relatório geral.</p>
    </section>
<?php else: ?>

    <!-- ============ TOOLBAR ============ -->
    <div class="relatorio-toolbar">
        <span class="toolbar-label"><i class="fas fa-file-export"></i> Exportar</span>
        <div class="toolbar-actions">
            <a class="button small button-ghost"
               href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral/pdf">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a class="button small button-ghost"
               href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral/excel">
                <i class="fas fa-file-excel"></i> Excel
            </a>
        </div>
    </div>

    <!-- ============ LEGENDA ============ -->
    <div class="rel-legenda">
        <strong>Conceitos:</strong>
        <span class="legenda-item">
            <span class="conceito-badge mb">MB</span> Muito Bom
        </span>
        <span class="legenda-item">
            <span class="conceito-badge b">B</span> Bom
        </span>
        <span class="legenda-item">
            <span class="conceito-badge r">R</span> Regular
        </span>
        <span class="legenda-item">
            <span class="conceito-badge i">I</span> Insuficiente
        </span>
    </div>

    <!-- ============ MATRIZ ============ -->
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Matriz de notas</h2>
                <p>Cada linha é um aluno, cada coluna é um critério do projeto.</p>
            </div>
        </div>

        <div class="rel-wrapper">
            <table class="rel-tabela">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <?php foreach ($criterios as $c): ?>
                            <th class="td-center">
                                <div class="th-criterio">
                                    <span><?= h($c['nome']) ?></span>
                                    <small>peso <?= number_format((float) $c['peso'], 2, ',', '.') ?></small>
                                </div>
                            </th>
                        <?php endforeach; ?>
                        <th style="text-align:right;">Média</th>
                        <th style="text-align:center;">Conceito</th>
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
                            <td>
                                <div class="td-aluno">
                                    <span class="avatar avatar-blue">
                                        <?= strtoupper(substr($a['nome'], 0, 1)) ?>
                                    </span>
                                    <div class="td-aluno-info">
                                        <strong><?= h($a['nome']) ?></strong>
                                        <small><?= h($a['papel'] ?? 'aluno') ?></small>
                                    </div>
                                </div>
                            </td>

                            <?php foreach ($criterios as $cid => $c): ?>
                                <?php $nota = $mapa[$cid]['nota'] ?? null; ?>
                                <td class="td-num">
                                    <?php if ($nota === null): ?>
                                        <span class="nota-vazia">—</span>
                                    <?php else: ?>
                                        <?= number_format($nota, 2, ',', '.') ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>

                            <td class="td-num">
                                <strong style="color:var(--primary); font-size:13.5px;">
                                    <?= $b && $b['media_ponderada'] !== null
                                        ? number_format($b['media_ponderada'], 2, ',', '.')
                                        : '—' ?>
                                </strong>
                            </td>

                            <td class="td-center">
                                <?php $cf = $b['conceito_final'] ?? null; ?>
                                <?php if ($cf): ?>
                                    <span class="conceito-badge <?= classeConceitoGeral($cf) ?>">
                                        <?= h($cf) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="conceito-badge vazio">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ============ RANKING ============ -->
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-trophy" style="color:#d48b1c;"></i> Ranking por média</h2>
                <p>Ordenado da maior para a menor média ponderada.</p>
            </div>
        </div>

        <div class="ranking-lista">
            <?php foreach ($ranking as $i => $r): ?>
                <div class="ranking-item">
                    <span class="ranking-pos"><?= $i + 1 ?>º</span>

                    <div class="ranking-info">
                        <strong><?= h($r['aluno']['nome']) ?></strong>
                        <small><?= h($r['aluno']['papel'] ?? 'aluno') ?></small>
                    </div>

                    <span class="ranking-media <?= $r['media'] === null ? 'vazio' : '' ?>">
                        <?= $r['media'] === null ? '—' : number_format($r['media'], 2, ',', '.') ?>
                    </span>

                    <?php if (!empty($r['conceito'])): ?>
                        <span class="conceito-badge <?= classeConceitoGeral($r['conceito']) ?>">
                            <?= h($r['conceito']) ?>
                        </span>
                    <?php else: ?>
                        <span class="conceito-badge vazio">—</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>