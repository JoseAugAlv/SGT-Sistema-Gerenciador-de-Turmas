<?php
$cssPagina = ['projetos.css', 'criterios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// $podeEditar (representante/master, projeto aberto) e $podeReabrir vêm do controller.
$tipoLabel = [
    'diretor'       => 'Diretor avalia',
    'representante' => 'Representante avalia',
    'pares'         => 'Colegas avaliam',
    'autoavaliacao' => 'Autoavaliação',
    'coletiva'      => 'Nota do grupo',
    'misto'         => 'Diretor + representante',
];
$aplicLabel = Criterio::APLICAVEIS;

$pesoFmt   = fn($v) => number_format((float) $v, 2, ',', '.');
$abertos   = count(array_filter($criterios, fn($c) => !$c['bloqueado']));
$bloqueados = count($criterios) - $abertos;
$falta     = 10 - $somaPesos;

$statusPesoTxt = [
    'completo'   => 'Soma completa',
    'incompleto' => 'Faltam ' . $pesoFmt($falta) . ' para 10',
    'excedido'   => 'Passou ' . $pesoFmt(-$falta) . ' de 10',
][$statusPeso];
$pid = (int) $projeto['id'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Critérios
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
        </p>
    </div>
    <div class="hero-actions">
        <?php if ($podeEditar): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= $pid ?>/criterios/criar">
                <i class="fas fa-plus"></i> Novo critério
            </a>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php if (empty($criterios)): ?>
    <section class="panel empty-state">
        <span class="icon-large text-muted"><i class="fas fa-clipboard-check"></i></span>
        <h2>Nenhum critério cadastrado</h2>
        <p>
            <?= $podeEditar
                ? 'Os critérios definem como os alunos são avaliados. A soma dos pesos deve dar 10.'
                : 'Aguarde o representante criar os critérios deste projeto.' ?>
        </p>
        <?php if ($podeEditar): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= $pid ?>/criterios/criar">
                <i class="fas fa-plus"></i> Criar critério
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>

    <!-- Resumo: soma dos pesos + situação -->
    <div class="grade-numeros crit-numeros">
        <div class="grade-celula crit-soma <?= h($statusPeso) ?>">
            <span class="num"><?= $pesoFmt($somaPesos) ?><small class="crit-de"> / 10</small></span>
            <span class="crit-barra" aria-hidden="true"><span style="width: <?= min(100, max(0, $somaPesos * 10)) ?>%"></span></span>
            <small class="crit-soma-txt"><?= h($statusPesoTxt) ?></small>
        </div>
        <div class="grade-celula">
            <span class="num"><?= count($criterios) ?></span>
            <small>critério(s)</small>
        </div>
        <div class="grade-celula">
            <span class="num"><?= $abertos ?></span>
            <small>abertos para nota<?= $bloqueados ? ' · ' . $bloqueados . ' encerrado(s)' : '' ?></small>
        </div>
    </div>

    <ul class="lista-tarefas lista-criterios">
        <?php foreach ($criterios as $c):
            $cid   = (int) $c['id'];
            $total = (int) $c['total_avaliacoes'] + (int) $c['total_coletivas'];
            $prazoTxt = '';
            $prazoCls = '';
            if (!empty($c['prazo_avaliacao'])) {
                $prazoTxt = ($c['bloqueado'] ? 'encerrou ' : 'até ') . ViewHelper::data($c['prazo_avaliacao'], true);
                $restante = strtotime($c['prazo_avaliacao']) - time();
                if (!$c['bloqueado'] && $restante < 3 * 86400) $prazoCls = 'txt-alerta';
            }
        ?>
            <li class="<?= $c['bloqueado'] ? 'bloqueado' : '' ?>">
                <span class="crit-peso num" title="Peso"><?= $pesoFmt($c['peso']) ?><small>peso</small></span>
                <div class="tarefa-info">
                    <strong><?= h($c['nome']) ?></strong>
                    <small>
                        <span class="crit-tipo t-<?= h($c['tipo_avaliacao']) ?>"><?= h($tipoLabel[$c['tipo_avaliacao']] ?? $c['tipo_avaliacao']) ?></span>
                        <?php if ($c['aplicavel_a'] !== 'todos'): ?> · <?= h($aplicLabel[$c['aplicavel_a']] ?? $c['aplicavel_a']) ?><?php endif; ?>
                        <?php if (!empty($c['etapa_nome'])): ?> · etapa <?= h($c['etapa_nome']) ?><?php endif; ?>
                        <?php if ($prazoTxt): ?> · <span class="<?= $prazoCls ?>"><?= h($prazoTxt) ?></span><?php endif; ?>
                        · <?= $total ?> nota(s)
                    </small>
                    <?php if (!empty($c['obrigatorio'])): $faltam = $faltamPreencher[$cid] ?? null; ?>
                        <div class="crit-obrig-linha">
                            <span class="crit-obrig-tag"><i class="fas fa-asterisk"></i> Obrigatório</span>
                            <?php if ($faltam !== null): ?>
                                <?php $jaDesc = $descontos[$cid] ?? []; $semDesc = array_diff_key($faltam, $jaDesc); ?>
                                <?php if (empty($faltam)): ?>
                                    <span class="crit-obrig-ok">todos já preencheram</span>
                                <?php elseif (!$c['bloqueado']): ?>
                                    <details class="crit-faltam">
                                        <summary class="txt-alerta"><?= count($faltam) ?> aluno(s) ainda não preencheram</summary>
                                        <p><?= h(implode(', ', $faltam)) ?></p>
                                    </details>
                                <?php else: ?>
                                    <details class="crit-faltam"<?= $semDesc ? ' open' : '' ?>>
                                        <summary class="<?= $semDesc ? 'txt-alerta' : '' ?>">
                                            <?= count($faltam) ?> aluno(s) não preencheram<?= $jaDesc ? ' · ' . count($jaDesc) . ' com desconto' : '' ?>
                                        </summary>

                                        <?php if ($semDesc && $podeEditar): ?>
                                            <form method="POST" action="<?= $basePath ?>/criterios/<?= $cid ?>/descontar" class="crit-desc-form">
                                                <?= ViewHelper::csrfField() ?>
                                                <div class="crit-desc-alunos">
                                                    <?php foreach ($semDesc as $aid => $nomeAluno): ?>
                                                        <label class="participante"><input type="checkbox" name="alunos[]" value="<?= (int) $aid ?>" checked> <?= h($nomeAluno) ?></label>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="crit-desc-acao">
                                                    <label>Descontar
                                                        <input type="number" name="pontos" class="field" min="0.1" max="100" step="0.1" value="5" required>
                                                        ponto(s) da média final <span class="txt-mudo">(escala 0–100)</span>
                                                    </label>
                                                    <button type="submit" class="button small button-primary"
                                                            onclick="return confirm('Aplicar o desconto aos alunos marcados? Eles serão avisados.')">Aplicar desconto</button>
                                                </div>
                                            </form>
                                        <?php elseif ($semDesc): ?>
                                            <p><?= h(implode(', ', $semDesc)) ?></p>
                                        <?php endif; ?>

                                        <?php if ($jaDesc): ?>
                                            <ul class="crit-desc-lista">
                                                <?php foreach ($jaDesc as $aid => $d): ?>
                                                    <li>
                                                        <span><?= h($d['aluno_nome']) ?> · <strong>−<?= number_format((float) $d['pontos'], 1, ',', '.') ?></strong></span>
                                                        <?php if ($podeEditar): ?>
                                                            <form method="POST" action="<?= $basePath ?>/criterios/<?= $cid ?>/remover-desconto" class="d-inline">
                                                                <?= ViewHelper::csrfField() ?>
                                                                <input type="hidden" name="aluno_id" value="<?= (int) $aid ?>">
                                                                <button type="submit" class="crit-desc-remover">remover</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($c['descricao'])): ?>
                        <details class="crit-descricao">
                            <summary>Ver descrição</summary>
                            <p><?= nl2br(h($c['descricao'])) ?></p>
                        </details>
                    <?php endif; ?>
                </div>
                <span class="crit-status <?= $c['bloqueado'] ? 'fechado' : 'aberto' ?>">
                    <i class="fas <?= $c['bloqueado'] ? 'fa-lock' : 'fa-lock-open' ?>"></i>
                    <?= $c['bloqueado'] ? 'Encerrado' : 'Aberto' ?>
                </span>
                <?php if ($podeEditar || ($podeReabrir && $c['bloqueado'])): ?>
                    <span class="crit-acoes-linha">
                        <?php if ($c['bloqueado'] && $podeReabrir): ?>
                            <a class="tarefa-acao primaria" href="<?= $basePath ?>/criterios/<?= $cid ?>/reabrir">Reabrir</a>
                        <?php endif; ?>
                        <?php if ($podeEditar): ?>
                            <a class="tarefa-acao" href="<?= $basePath ?>/criterios/<?= $cid ?>/editar">Editar</a>
                            <form method="POST" action="<?= $basePath ?>/criterios/<?= $cid ?>/excluir" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="tarefa-acao perigo" aria-label="Excluir <?= h($c['nome']) ?>"
                                        onclick="return confirm('<?= $total
                                            ? 'Este critério tem ' . $total . ' nota(s). Elas ficam arquivadas por 30 dias e só o master pode restaurar. Excluir mesmo assim?'
                                            : 'Excluir este critério?' ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
