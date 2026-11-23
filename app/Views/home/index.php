<?php
$cssPagina = 'home.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$primeiroNome = explode(' ', trim($usuario['nome']))[0];

// ---------- Turma em destaque (faixa "camiseta") ----------
$turmaFoco = null;
if (!$isMaster && !empty($minhasTurmas)) {
    foreach ($minhasTurmas as $t) {
        if (($t['meu_papel'] ?? '') === 'representante') { $turmaFoco = $t; break; }
    }
    $turmaFoco = $turmaFoco ?? $minhasTurmas[0];
}

$papelLabel = [
    'representante' => 'você é representante',
    'aluno'         => 'você é aluno',
];

// ---------- Item mais urgente (uma única placa por tela) ----------
$urgente = null;
foreach ($prazos as $p) {
    if ($p['dias'] <= 3) { $urgente = $p; break; }
}
$alertaUrgente = null;
if (!$urgente) {
    foreach ($alertas as $a) {
        if (!empty($a['urgente'])) { $alertaUrgente = $a; break; }
    }
}

$mesesCurtos = ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'];

// ---------- Projeto em foco e central de ações (por papel) ----------
$projetoFoco = null;
foreach ($projetos as $p) {
    if ($turmaFoco && (int) $p['turma_id'] === (int) $turmaFoco['id']) { $projetoFoco = $p; break; }
}
$souRepFoco = $turmaFoco && ($turmaFoco['meu_papel'] ?? '') === 'representante';

$acoes = [];
if ($projetoFoco) {
    $pp = $basePath . '/projetos/' . (int) $projetoFoco['id'];
    if ($souRepFoco) {
        $acoes = [
            ['fas fa-clipboard-check', 'Avaliar turma',  $pp . '/avaliacoes/representante', 'Lançar notas dos critérios'],
            ['fas fa-file-signature',  'Atas',           $pp . '/atas',                     'Criar e validar reuniões'],
            ['fas fa-list-check',      'Critérios',      $pp . '/criterios',                'Pesos, prazos e tipos'],
            ['fas fa-people-group',    'Grupos',         $pp . '/grupos',                   'Membros e diretores'],
            ['fas fa-box-open',        'Materiais',      $pp . '/materiais',                'Estoque e solicitações'],
            ['fas fa-chart-column',    'Relatórios',     $pp . '/relatorios',               'Boletins, PDF e Excel'],
        ];
    } else {
        $acoes = [
            ['fas fa-star-half-stroke', 'Minhas notas',    $pp . '/avaliacoes/minhas',   'Como estou sendo avaliado'],
            ['fas fa-user-group',       'Avaliar colegas', $pp . '/avaliacoes/pares',    'Avaliação por pares'],
            ['fas fa-user-check',       'Autoavaliação',   $pp . '/avaliacoes/auto',     'Avalie o seu trabalho'],
            ['fas fa-file-lines',       'Atas',            $pp . '/atas',                'Reuniões do seu grupo'],
            ['fas fa-box-open',         'Pedir material',  $pp . '/materiais/solicitar', 'Solicitar ao representante'],
            ['fas fa-file-pdf',         'Meu boletim',     $pp . '/relatorios/boletim',  'Ver e baixar em PDF'],
        ];
        if ($souDiretorEmAlgumGrupo) {
            array_unshift($acoes, ['fas fa-crown', 'Avaliar meu grupo', $pp . '/avaliacoes/diretor', 'Você é diretor']);
            array_pop($acoes);
        }
    }
}
$acoesTurma = [];
if ($turmaFoco && $souRepFoco) {
    $tt = $basePath . '/turmas/' . (int) $turmaFoco['id'];
    $acoesTurma = [
        ['fas fa-bullhorn',  'Novo aviso', $tt . '/alertas/criar'],
        ['fas fa-user-plus', 'Alunos',     $basePath . '/alunos'],
        ['fas fa-palette',   'Identidade', $tt . '/identidade'],
    ];
    if (empty($turmaFoco['sou_vice'])) $acoesTurma[] = ['fas fa-receipt', 'Plano', $tt . '/plano']; // o vice não cuida do plano
}
?>

<?php if (!$isMaster && !empty($_SESSION['plano_desejado'])): ?>
    <div class="notice notice-blue">
        <span><i class="fas fa-tags"></i></span>
        <div>
            <strong>Continue de onde parou</strong>
            <?php $ehG = str_starts_with((string) $_SESSION['plano_desejado'], 'g_'); ?>
            <p>Você escolheu um plano na página de preços. <a href="<?= $basePath ?><?= $ehG ? '/gremios/novo' : '/turmas/nova' ?>"><?= $ehG ? 'Criar o grêmio e pagar' : 'Criar a turma e pagar' ?></a></p>
        </div>
    </div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- FAIXA DA TURMA                                                -->
<!-- ============================================================ -->
<section class="turma-faixa" style="<?= $turmaFoco ? h(ViewHelper::estiloTurma($turmaFoco)) : '' ?>">
    <?php if ($turmaFoco): ?>
        <div class="turma-logo">
            <?php if (!empty($turmaFoco['logo'])): ?>
                <img src="<?= h(ViewHelper::asset($turmaFoco['logo'])) ?>" alt="Logo da turma">
            <?php else: ?>
                <i class="fas fa-shield-halved" aria-hidden="true"></i>
            <?php endif; ?>
        </div>
        <div class="turma-sigla num"><?= h(ViewHelper::siglaTurma($turmaFoco['nome'])) ?></div>
        <div class="turma-meta">
            <strong><?= h($turmaFoco['nome']) ?></strong>
            <span>
                <?= !empty($turmaFoco['sou_vice']) ? 'você é vice-representante' : h($papelLabel[$turmaFoco['meu_papel'] ?? 'aluno'] ?? 'você participa') ?>
                <?php if (count($minhasTurmas) > 1): ?> · <?= count($minhasTurmas) ?> turmas<?php endif; ?>
            </span>
        </div>
        <a class="turma-abrir" href="<?= $basePath ?>/turmas/<?= (int) $turmaFoco['id'] ?>" aria-label="Abrir turma <?= h($turmaFoco['nome']) ?>">
            <i class="fas fa-chevron-right"></i>
        </a>
    <?php else: ?>
        <div class="turma-meta">
            <strong class="turma-ola">Olá, <?= h($primeiroNome) ?></strong>
            <span><?= $isMaster ? 'Administração do sistema · ' . count($minhasTurmas) . ' turmas' : 'Você ainda não está em nenhuma turma' ?></span>
        </div>
    <?php endif; ?>
</section>

<?php
// ---------- Planos das turmas que eu represento (sempre à vista, mesmo sendo aluno em outras) ----------
$turmasRep = $isMaster ? [] : array_values(array_filter($minhasTurmas, fn($t) => ($t['meu_papel'] ?? '') === 'representante' && empty($t['sou_vice'])));
if ($turmasRep):
    require_once __DIR__ . '/../../Helpers/PlanoGuard.php';
    $seloHome = ['isenta' => ['Cortesia', 'ok'], 'ativa' => ['Ativo', 'ok'], 'vencendo' => ['Vence em breve', 'aviso'],
                 'tolerancia' => ['Vencido', 'perigo'], 'atraso' => ['Parcela atrasada', 'perigo'], 'sem_plano' => ['Sem plano', 'perigo'],
                 'aguardando' => ['Aguardando pagamento', 'aviso'], 'vencido' => ['Vencido', 'perigo'], 'atraso_bloqueio' => ['Bloqueada', 'perigo']];
?>
<section class="panel home-planos">
    <div class="home-planos-topo">
        <h2><i class="fas fa-crown"></i> Planos das suas turmas</h2>
        <a class="text-link" href="<?= $basePath ?>/planos">Ver todos os planos</a>
    </div>
    <ul class="home-planos-lista">
        <?php foreach ($turmasRep as $t):
            $sit = PlanoGuard::situacao($t);
            [$rot, $cls] = $seloHome[$sit['codigo']] ?? [$sit['codigo'], 'aviso'];
            $ass = $sit['assinatura'];
        ?>
            <li>
                <span>
                    <strong><?= h($t['nome']) ?></strong>
                    <small class="home-planos-info"><?= $ass ? h($ass['plano_nome']) . ($ass['fim'] ? ' · até ' . ViewHelper::data($ass['fim']) : '') : '' ?></small>
                </span>
                <span class="home-planos-acoes">
                    <span class="home-selo <?= $cls ?>"><?= h($rot) ?></span>
                    <a class="button <?= $sit['bloqueia'] ? 'button-primary' : 'button-ghost' ?>" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>/plano">
                        <?= $sit['bloqueia'] ? 'Regularizar' : ($ass && $ass['status'] === 'ativa' ? 'Plano e upgrade' : 'Ver plano') ?>
                    </a>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<div class="painel-grid">
<div class="painel-main">

<!-- ============================================================ -->
<!-- ITEM URGENTE                                                  -->
<!-- ============================================================ -->
<?php if ($urgente): $dtU = new DateTime($urgente['prazo']); ?>
    <a class="placa-urgente" href="<?= $basePath ?>/projetos/<?= (int) $urgente['projeto_id'] ?>">
        <div>
            <strong><?= $urgente['tipo'] === 'criterio' ? 'Critério' : 'Projeto' ?> “<?= h($urgente['titulo']) ?>” <?= $urgente['dias'] <= 0 ? 'fecha hoje' : 'fecha em ' . (int) $urgente['dias'] . ' dia(s)' ?></strong>
            <span><?= h($urgente['contexto']) ?></span>
        </div>
        <div class="placa-prazo">
            <span class="num"><?= $urgente['dias'] <= 0 ? h($dtU->format('H:i')) : h($dtU->format('d/m')) ?></span>
            <small>prazo</small>
        </div>
    </a>
<?php elseif ($alertaUrgente): ?>
    <a class="placa-urgente" href="<?= $basePath ?>/notificacoes">
        <div>
            <strong><?= h($alertaUrgente['titulo']) ?></strong>
            <span><?= h($alertaUrgente['turma_nome_ctx']) ?> · aviso urgente</span>
        </div>
        <div class="placa-prazo"><i class="fas fa-bullhorn"></i></div>
    </a>
<?php endif; ?>

<!-- ============================================================ -->
<!-- NÚMEROS                                                       -->
<!-- ============================================================ -->
<section class="grade-numeros" aria-label="Resumo">
    <?php foreach (array_slice($stats, 0, 4) as $s): ?>
        <div class="grade-celula">
            <span class="num"><?= str_pad((string) (int) $s['valor'], 2, '0', STR_PAD_LEFT) ?></span>
            <small><?= h(mb_strtolower($s['label'])) ?></small>
        </div>
    <?php endforeach; ?>
</section>

<!-- ============================================================ -->
<!-- AGENDA (Dia a dia)                                            -->
<!-- ============================================================ -->
<section class="home-agenda" aria-labelledby="agenda-titulo">
    <div class="bloco-titulo">
        <h2 id="agenda-titulo">Agenda</h2>
        <a href="<?= $basePath ?>/agenda" class="bloco-link">Abrir agenda <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php $ctx = $agendaCtx; $modo = 'mini'; $agId = 'agenda-home'; $soTurma = null; require __DIR__ . '/../agenda/_componente.php'; ?>
</section>

<!-- ============================================================ -->
<!-- CENTRAL DE AÇÕES                                              -->
<!-- ============================================================ -->
<?php if ($acoes): ?>
    <section class="central-acoes" aria-labelledby="acoes-titulo">
        <div class="bloco-titulo">
            <h2 id="acoes-titulo">Ações</h2>
            <a href="<?= $basePath ?>/projetos/<?= (int) $projetoFoco['id'] ?>" class="bloco-link">
                <?= h($projetoFoco['nome']) ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="acoes-grade">
            <?php foreach ($acoes as [$icone, $rotulo, $url, $dica]): ?>
                <a class="acao" href="<?= h($url) ?>">
                    <span class="acao-icone"><i class="<?= $icone ?>"></i></span>
                    <span class="acao-texto"><strong><?= h($rotulo) ?></strong><small><?= h($dica) ?></small></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($acoesTurma): ?>
            <div class="acoes-turma">
                <?php foreach ($acoesTurma as [$icone, $rotulo, $url]): ?>
                    <a href="<?= h($url) ?>"><i class="<?= $icone ?>"></i> <?= h($rotulo) ?></a>
                <?php endforeach; ?>
                <button type="button" class="copiar-codigo" data-codigo="<?= h($turmaFoco['codigo_acesso']) ?>">
                    <i class="fas fa-copy"></i> Código <code><?= h($turmaFoco['codigo_acesso']) ?></code>
                </button>
            </div>
        <?php endif; ?>
    </section>
<?php elseif ($turmaFoco): ?>
    <section class="central-acoes">
        <div class="lista-vazia">
            <i class="fas fa-diagram-project"></i>
            <span>Nenhum projeto aberto nesta turma. <a href="<?= $basePath ?>/projetos?turma_id=<?= (int) $turmaFoco['id'] ?>">Ver projetos</a></span>
        </div>
    </section>
<?php endif; ?>

<!-- ============================================================ -->
<!-- PARA FAZER                                                    -->
<!-- ============================================================ -->
<section class="para-fazer">
    <div class="bloco-titulo"><h2>Para fazer</h2></div>
    <?php if (!empty($criteriosPendentes)): ?>
        <ul class="lista-tarefas mb-12">
            <?php foreach ($criteriosPendentes as $cp): $semNenhum = (int) $cp['total'] === 0; ?>
                <li>
                    <span class="tarefa-icone urgente"><i class="fas fa-list-check"></i></span>
                    <div class="tarefa-info">
                        <strong><?= $semNenhum ? 'Crie os critérios de “' . h($cp['nome']) . '”' : 'Ajuste os pesos de “' . h($cp['nome']) . '”' ?></strong>
                        <small><?= h($cp['turma_nome']) ?> · <?= $semNenhum
                            ? 'sem critérios, a turma não pode ser avaliada'
                            : 'os pesos somam ' . number_format((float) $cp['soma'], 2, ',', '.') . ' de 10' ?></small>
                    </div>
                    <a class="tarefa-acao" href="<?= $basePath ?>/projetos/<?= (int) $cp['id'] ?>/criterios<?= $semNenhum ? '/criar' : '' ?>"><?= $semNenhum ? 'Criar' : 'Ajustar' ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (empty($prazos) && empty($criteriosPendentes)): ?>
        <div class="lista-vazia">
            <i class="fas fa-circle-check"></i>
            <span>Nada vencendo nos próximos 30 dias.</span>
        </div>
    <?php elseif (!empty($prazos)): ?>
        <ul class="lista-tarefas">
            <?php foreach (array_slice($prazos, 0, 5) as $p): $dtP = new DateTime($p['prazo']); ?>
                <li>
                    <span class="tarefa-data num"><?= h($dtP->format('d')) ?><small><?= $mesesCurtos[(int) $dtP->format('n') - 1] ?></small></span>
                    <div class="tarefa-info">
                        <strong><?= h($p['titulo']) ?></strong>
                        <small><?= $p['tipo'] === 'criterio' ? (!empty($p['obrigatorio']) ? '<span class="txt-alerta">Critério obrigatório</span>' : 'Critério') : 'Projeto' ?> · <?= h($p['contexto']) ?> · <?= $p['dias'] <= 0 ? 'hoje' : 'em ' . (int) $p['dias'] . ' dia(s)' ?></small>
                    </div>
                    <a class="tarefa-acao" href="<?= $basePath ?>/projetos/<?= (int) $p['projeto_id'] ?>">Ver</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<!-- ============================================================ -->
<!-- PROJETOS                                                      -->
<!-- ============================================================ -->
<section class="para-fazer">
    <div class="bloco-titulo">
        <h2>Projetos em andamento</h2>
        <a class="bloco-link" href="<?= $basePath ?>/projetos">Todos <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php if (empty($projetos)): ?>
        <div class="lista-vazia"><i class="fas fa-diagram-project"></i><span>Nenhum projeto em andamento.</span></div>
    <?php else: ?>
        <ul class="lista-tarefas">
            <?php foreach ($projetos as $p): ?>
                <li>
                    <span class="tarefa-icone"><i class="fas fa-diagram-project"></i></span>
                    <div class="tarefa-info">
                        <strong><?= h($p['nome']) ?></strong>
                        <small>
                            <?= h($p['turma_nome']) ?> · <?= (int) $p['total_grupos'] ?> grupo(s) · <?= (int) $p['total_criterios'] ?> critério(s) ·
                            <?php if ($p['dias_restantes'] === null): ?>sem prazo
                            <?php elseif ($p['dias_restantes'] < 0): ?><span class="txt-alerta">prazo vencido</span>
                            <?php elseif ($p['dias_restantes'] === 0): ?>vence hoje
                            <?php else: ?>vence em <?= (int) $p['dias_restantes'] ?> dia(s)<?php endif; ?>
                        </small>
                    </div>
                    <a class="tarefa-acao" href="<?= $basePath ?>/projetos/<?= (int) $p['id'] ?>">Abrir</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

</div><!-- /.painel-main -->

<aside class="painel-lado">

    <!-- AVISOS -->
    <section class="para-fazer">
        <div class="bloco-titulo">
            <h2>Avisos</h2>
            <a class="bloco-link" href="<?= $basePath ?>/notificacoes">
                <?= (int) $naoLidas > 0 ? (int) $naoLidas . ' não lida(s)' : 'Ver todos' ?> <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php if (empty($alertas) && empty($atividade)): ?>
            <div class="lista-vazia"><i class="fas fa-bell-slash"></i><span>Nenhum aviso por enquanto.</span></div>
        <?php else: ?>
            <ul class="lista-tarefas lista-compacta">
                <?php foreach ($alertas as $a): ?>
                    <li>
                        <span class="tarefa-icone <?= $a['urgente'] ? 'urgente' : '' ?>"><i class="fas fa-bullhorn"></i></span>
                        <div class="tarefa-info">
                            <strong><?= h($a['titulo']) ?></strong>
                            <small><?= h($a['turma_nome_ctx']) ?> · por <?= h($a['autor_nome'] ?? '—') ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
                <?php foreach (array_slice($atividade, 0, max(0, 5 - count($alertas))) as $a): ?>
                    <li>
                        <span class="tarefa-icone"><i class="<?= h($a['icon']) ?>"></i></span>
                        <div class="tarefa-info">
                            <strong><?= h($a['titulo']) ?></strong>
                            <small><?= h($a['texto']) ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- MINHAS TURMAS -->
    <section class="para-fazer">
        <div class="bloco-titulo">
            <h2><?= $isMaster ? 'Turmas' : 'Minhas turmas' ?></h2>
            <?php if ($isMaster): ?>
                <a class="bloco-link" href="<?= $basePath ?>/turmas/criar"><i class="fas fa-plus"></i> Nova</a>
            <?php else: ?>
                <a class="bloco-link" href="<?= $basePath ?>/turmas/entrar"><i class="fas fa-right-to-bracket"></i> Entrar com código</a>
            <?php endif; ?>
        </div>
        <?php if (empty($minhasTurmas) && !$isMaster): ?>
            <div class="lista-vazia sem-turma">
                <i class="fas fa-users"></i>
                <span>
                    <strong>Você ainda não está em nenhuma turma.</strong>
                    Sua turma já usa o Prumo? Peça o código ao representante e <a href="<?= $basePath ?>/turmas/entrar">entre com o código</a>.
                    É o representante? <a href="<?= $basePath ?>/turmas/nova">Crie a turma</a> e escolha um plano.
                </span>
            </div>
        <?php elseif (empty($minhasTurmas)): ?>
            <div class="lista-vazia"><i class="fas fa-users"></i><span>Nenhuma turma cadastrada.</span></div>
        <?php else: ?>
            <ul class="lista-tarefas lista-compacta">
                <?php foreach (array_slice($minhasTurmas, 0, 6) as $t): ?>
                    <li>
                        <span class="mini-sigla num" style="<?= h(ViewHelper::estiloTurma($t)) ?>"><?= h(ViewHelper::siglaTurma($t['nome'])) ?></span>
                        <div class="tarefa-info">
                            <strong><?= h($t['nome']) ?></strong>
                            <small>
                                <?php if ($isMaster): ?><?= (int) ($t['total_alunos'] ?? 0) ?> aluno(s) · <?= (int) ($t['total_reps'] ?? 0) ?>/2 rep.
                                <?php else: ?><?= h($t['meu_papel'] ?? 'aluno') ?><?php endif; ?>
                                <?php if (!empty($t['bloqueada'])): ?> · <span class="txt-alerta">bloqueada</span><?php endif; ?>
                            </small>
                        </div>
                        <a class="tarefa-acao" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>">Abrir</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($minhasTurmas) > 6): ?>
                <a class="bloco-link mais" href="<?= $basePath ?>/turmas">Ver as <?= count($minhasTurmas) ?> turmas <i class="fas fa-arrow-right"></i></a>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if ((int) $papeis['total'] > 0): ?>
        <!-- PESSOAS -->
        <section class="para-fazer">
            <div class="bloco-titulo"><h2>Pessoas</h2></div>
            <div class="grade-numeros grade-pessoas">
                <div class="grade-celula"><span class="num"><?= (int) $papeis['alunos'] ?></span><small>alunos</small></div>
                <div class="grade-celula"><span class="num"><?= (int) $papeis['diretores'] ?></span><small>diretores</small></div>
                <div class="grade-celula"><span class="num"><?= (int) $papeis['reps'] ?></span><small>representantes</small></div>
            </div>
        </section>
    <?php endif; ?>

</aside>
</div><!-- /.painel-grid -->

<script>
document.querySelectorAll('.copiar-codigo').forEach(function (b) {
    b.addEventListener('click', function () {
        var original = b.innerHTML;
        var feito = function () {
            b.innerHTML = '<i class="fas fa-check"></i> Código copiado';
            setTimeout(function () { b.innerHTML = original; }, 1800);
        };
        if (navigator.clipboard) navigator.clipboard.writeText(b.dataset.codigo).then(feito); else feito();
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
