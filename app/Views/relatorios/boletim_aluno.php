<?php
$cssPagina = ['projetos.css', 'relatorios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

function classeConceitoRel(?string $c): string {
    return match ($c) {
        'I'  => 'i',
        'R'  => 'r',
        'B'  => 'b',
        'MB' => 'mb',
        default => 'vazio',
    };
}

function classePresencaAta(?string $p): string {
    return match ($p) {
        'sim'         => 'presente',
        'nao'         => 'ausente',
        'justificado' => 'justificado',
        default       => '',
    };
}

function badgePresencaAta(?string $p): string {
    return match ($p) {
        'sim'         => '<span class="badge badge-green"><i class="fas fa-check"></i> Presente</span>',
        'nao'         => '<span class="badge badge-red"><i class="fas fa-times"></i> Ausente</span>',
        'justificado' => '<span class="badge badge-yellow"><i class="fas fa-info-circle"></i> Justificado</span>',
        default       => '<span class="badge badge-gray">—</span>',
    };
}

function badgeStatusAta(string $s): string {
    return match ($s) {
        'pendente'   => '<span class="ata-status pendente"><i class="fas fa-hourglass-half"></i> Pendente</span>',
        'preenchida' => '<span class="ata-status preenchida"><i class="fas fa-pen"></i> Preenchida</span>',
        'revisada'   => '<span class="ata-status revisada"><i class="fas fa-check-circle"></i> Revisada</span>',
        default      => h($s),
    };
}

$totalAtas     = $totalAtas     ?? 0;
$totalPresente = $totalPresente ?? 0;
$totalAusente  = $totalAusente  ?? 0;
$totalJust     = $totalJust     ?? 0;
$atasDetalhes  = $atasDetalhes  ?? [];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Boletim individual
        </div>
        <h1><?= h($aluno['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
            — <?= h($projeto['turma_nome']) ?>
            <?php if (!empty($souEu)): ?>
                — <span class="badge badge-gray"><i class="fas fa-user"></i> você</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios">
            <i class="fas fa-arrow-left"></i> Voltar aos relatórios
        </a>
        <a class="button button-primary"
           href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/boletim/pdf?aluno_id=<?= (int) $aluno['id'] ?>">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php if (!empty($boletim['congelado'])): ?>
    <div class="boletim-congelado">
        <i class="fas fa-lock"></i>
        <div>
            <strong>Boletim congelado</strong>
            Este projeto foi encerrado em <?= h($boletim['congelado_em'] ?? '') ?>.
            As notas abaixo não mudam mais.
        </div>
    </div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- NOTAS                                                        -->
<!-- ============================================================ -->
<?php if (empty($boletim['linhas'])): ?>
    <section class="panel rel-empty">
        <span class="icon-large text-muted"><i class="fas fa-graduation-cap"></i></span>
        <h2>Nenhum critério definido</h2>
        <p>Este projeto ainda não possui critérios de avaliação cadastrados.</p>
    </section>
<?php else: ?>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Detalhamento por critério</h2>
                <p>Cada linha é um critério avaliado neste projeto.</p>
            </div>
        </div>

        <div class="rel-wrapper">
            <table class="rel-tabela">
                <thead>
                    <tr>
                        <th>Critério</th>
                        <th style="text-align:center;">Tipo</th>
                        <th style="text-align:right;">Peso</th>
                        <th style="text-align:right;">Nota</th>
                        <th style="text-align:center;">Conceito</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($boletim['linhas'] as $l): ?>
                        <tr>
                            <td>
                                <strong><?= h($l['criterio']['nome']) ?></strong>
                                <?php if (!empty($l['criterio']['descricao'])): ?>
                                    <br>
                                    <small style="color:var(--muted); font-size:11px; white-space:normal;">
                                        <?= h($l['criterio']['descricao']) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="td-center">
                                <code class="code-inline"><?= h($l['criterio']['tipo_avaliacao']) ?></code>
                            </td>
                            <td class="td-num">
                                <?= number_format((float) $l['criterio']['peso'], 2, ',', '.') ?>
                            </td>
                            <td class="td-num">
                                <?php if ($l['nota'] === null): ?>
                                    <span class="nota-vazia">—</span>
                                <?php else: ?>
                                    <strong><?= number_format($l['nota'], 2, ',', '.') ?></strong>
                                <?php endif; ?>
                            </td>
                            <td class="td-center">
                                <?php if ($l['conceito'] !== null): ?>
                                    <span class="conceito-badge <?= classeConceitoRel($l['conceito']) ?>">
                                        <?= h($l['conceito']) ?>
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

        <?php require __DIR__ . '/../avaliacoes/_descontos.php'; ?>

        <div class="boletim-footer">
            <div>
                <div class="media-label">Média ponderada</div>
                <div class="media-valor">
                    <span class="media-numero <?= $boletim['media_ponderada'] === null ? 'vazio' : '' ?>">
                        <?= $boletim['media_ponderada'] === null
                            ? '—'
                            : number_format($boletim['media_ponderada'], 2, ',', '.') ?>
                    </span>
                    <small style="color:var(--muted); font-size:11.5px;">de 100</small>
                </div>
            </div>

            <div class="media-conceito">
                <span class="media-label">Conceito final</span>
                <?php if (!empty($boletim['conceito_final'])): ?>
                    <span class="conceito-badge <?= classeConceitoRel($boletim['conceito_final']) ?>"
                          style="font-size:15px; padding:6px 16px; min-width:44px;">
                        <?= h($boletim['conceito_final']) ?>
                    </span>
                <?php else: ?>
                    <span class="conceito-badge vazio" style="font-size:15px; padding:6px 16px;">—</span>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php endif; ?>

<!-- ============================================================ -->
<!-- PARTICIPAÇÃO EM ATAS                                         -->
<!-- ============================================================ -->
<section class="section-heading mt-24">
    <div>
        <h2><i class="fas fa-book" style="color:var(--primary);"></i> Participação em atas</h2>
        <p>Reuniões registradas dos grupos em que o aluno participou.</p>
    </div>
</section>

<?php if ($totalAtas > 0): ?>
    <section class="presenca-stats">
        <article class="presenca-stat total">
            <span class="label">Total de atas</span>
            <span class="valor"><?= $totalAtas ?></span>
        </article>
        <article class="presenca-stat presente">
            <span class="label">Presenças</span>
            <span class="valor"><?= $totalPresente ?></span>
        </article>
        <article class="presenca-stat justificado">
            <span class="label">Justificadas</span>
            <span class="valor"><?= $totalJust ?></span>
        </article>
        <article class="presenca-stat ausente">
            <span class="label">Ausências</span>
            <span class="valor"><?= $totalAusente ?></span>
        </article>
    </section>
<?php endif; ?>

<?php if (empty($atasDetalhes)): ?>
    <section class="panel">
        <div class="ata-boletim-vazio">
            <i class="fas fa-book"></i>
            <strong>Nenhuma ata registrada</strong>
            <p class="mb-0" style="margin-top:6px;">
                Este aluno ainda não participou de nenhuma ata neste projeto.
            </p>
        </div>
    </section>
<?php else: ?>
    <section class="atas-boletim-lista">
        <?php foreach ($atasDetalhes as $bloco): ?>
            <?php
            $a         = $bloco['ata'];
            $presenca  = $a['presente'] ?? null;
            $corClasse = classePresencaAta($presenca);
            ?>
            <article class="ata-boletim <?= $corClasse ?>">
                <div class="ata-boletim-header">
                    <div style="flex:1; min-width:0;">
                        <h3>
                            <a href="<?= $basePath ?>/atas/<?= (int) $a['id'] ?>">
                                <?= h($a['titulo']) ?>
                            </a>
                        </h3>
                        <div class="meta">
                            <span>
                                <i class="fas fa-users"></i>
                                <?= h($a['grupo_nome']) ?>
                            </span>
                            <span>
                                <i class="far fa-calendar"></i>
                                <?= h($a['data_ata']) ?>
                                <?php if ($a['horario_inicio']): ?>
                                    — <?= h($a['horario_inicio']) ?>
                                    <?= $a['horario_fim'] ? ' a ' . h($a['horario_fim']) : '' ?>
                                <?php endif; ?>
                            </span>
                            <?php if (!empty($a['diretor_nome'])): ?>
                                <span>
                                    <i class="fas fa-user-tie"></i>
                                    <?= h($a['diretor_nome']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                        <?= badgeStatusAta($a['status']) ?>
                        <?= badgePresencaAta($presenca) ?>
                    </div>
                </div>

                <div class="ata-boletim-body">
                    <?php if (!empty($a['justificativa'])): ?>
                        <div>
                            <div class="ata-boletim-section-title">
                                <i class="fas fa-info-circle"></i>
                                Justificativa
                            </div>
                            <div class="ata-boletim-atividade">
                                <?= nl2br(h($a['justificativa'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($bloco['atividades'])): ?>
                        <div>
                            <div class="ata-boletim-section-title">
                                <i class="fas fa-list-check"></i>
                                Atividades (<?= count($bloco['atividades']) ?>)
                            </div>
                            <div class="ata-boletim-atividades">
                                <?php foreach ($bloco['atividades'] as $ativ): ?>
                                    <div class="ata-boletim-atividade">
                                        <strong><?= h($ativ['nome']) ?></strong>
                                        <?php if (!empty($ativ['descricao'])): ?>
                                            <span><?= h($ativ['descricao']) ?></span>
                                        <?php endif; ?>
                                        <?php if ((int) $ativ['total_participantes'] > 0): ?>
                                            <br>
                                            <small>
                                                <i class="fas fa-users"></i>
                                                <?= (int) $ativ['total_participantes'] ?> participante(s)
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($bloco['relatorios'])): ?>
                        <div>
                            <div class="ata-boletim-section-title">
                                <i class="fas fa-file-lines"></i>
                                Relatórios internos (<?= count($bloco['relatorios']) ?>)
                            </div>
                            <div class="ata-boletim-relatorios">
                                <?php foreach ($bloco['relatorios'] as $rel): ?>
                                    <div class="ata-boletim-relatorio <?= h($rel['tipo_relatorio']) ?>">
                                        <div class="rel-head">
                                            <strong><?= h($rel['titulo'] ?: '(sem título)') ?></strong>
                                            <span class="rel-tipo"><?= h($rel['tipo_relatorio']) ?></span>
                                        </div>
                                        <div class="rel-conteudo"><?= nl2br(h($rel['conteudo'])) ?></div>
                                        <div class="rel-foot">
                                            <span>
                                                <i class="fas fa-user"></i>
                                                <?= h($rel['autor_nome']) ?>
                                            </span>
                                            <span>
                                                <i class="far fa-clock"></i>
                                                <?= h($rel['created_at']) ?>
                                            </span>
                                            <?php if ((int) $rel['mencionado'] > 0): ?>
                                                <span class="mencao-badge">
                                                    <i class="fas fa-at"></i> aluno mencionado
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($bloco['atividades']) && empty($bloco['relatorios']) && empty($a['justificativa'])): ?>
                        <p class="mb-0 text-muted" style="font-size:12px;">
                            <i class="fas fa-info-circle"></i>
                            Nenhuma atividade ou relatório registrado nesta ata ainda.
                        </p>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<!-- ============================================================ -->
<!-- AVISO                                                        -->
<!-- ============================================================ -->
<div class="notice notice-blue mt-16">
    <span>i</span>
    <div>
        <strong>Sobre as atas e relatórios</strong>
        <p class="mb-0">
            Esta seção mostra as atas em que o aluno participou (ou foi mencionado)
            e os relatórios internos onde ele é o autor ou foi marcado como participante.
            Relatórios internos são visíveis apenas para alunos mencionados, representantes,
            diretores e master.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>