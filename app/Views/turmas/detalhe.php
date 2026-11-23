<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Helpers/PapelHelper.php';
require_once __DIR__ . '/../../Models/Calendario.php';
$rotulos = PapelHelper::rotulos($turma);
$ehGremio = PapelHelper::ehGremio($turma);
$tt = $basePath . '/turmas/' . (int) $turma['id'];
?>

<section class="turma-faixa" style="<?= h(ViewHelper::estiloTurma($turma)) ?>">
    <div class="turma-logo">
        <?php if (!empty($turma['logo'])): ?>
            <img src="<?= h(ViewHelper::asset($turma['logo'])) ?>" alt="Logo da turma">
        <?php else: ?>
            <i class="fas fa-shield-halved" aria-hidden="true"></i>
        <?php endif; ?>
    </div>
    <div class="turma-sigla num"><?= h(ViewHelper::siglaTurma($turma['nome'])) ?></div>
    <div class="turma-meta">
        <strong><?= h($turma['nome']) ?></strong>
        <span>
            <?= $ehGremio ? '<i class="fas fa-landmark"></i> Grêmio · ' : '' ?>Código <code><?= h($turma['codigo_acesso']) ?></code> ·
            <?= $turma['bloqueada'] ? 'Bloqueada' : 'Ativo' ?>
        </span>
    </div>
</section>

<?php require_once __DIR__ . '/../../Helpers/PlanoGuard.php'; echo PlanoGuard::aviso($turma); ?>

<?php require_once __DIR__ . '/../../Models/Rateio.php';
foreach ((new Rateio())->pendentesDoUsuario((int) $_SESSION['usuario']['id'], (int) $turma['id']) as $parte): ?>
    <div class="notice notice-warning"><span><i class="fas fa-people-arrows"></i></span><div><strong>Sua parte da mensalidade: R$ <?= number_format((float) $parte['valor'], 2, ',', '.') ?></strong>
        <p class="mb-0">O representante dividiu a parcela do plano entre a turma. <a href="<?= $basePath ?>/rateios/<?= (int) $parte['rateio_id'] ?>">Pagar minha parte</a></p></div></div>
<?php endforeach; ?>

<?php require __DIR__ . '/_contagens.php'; ?>

<!-- ============ DIA A DIA: atalhos, próximas datas e aulas de hoje ============ -->
<nav class="dia-atalhos" aria-label="Dia a dia">
    <a href="<?= $tt ?>/alertas"><i class="fas fa-bullhorn"></i><span>Avisos</span></a>
    <a href="<?= $tt ?>/calendario"<?= $temDia ? '' : ' class="bloqueado" title="Incluso a partir do plano Anual"' ?>><i class="fas <?= $temDia ? 'fa-calendar-days' : 'fa-lock' ?>"></i><span>Agenda</span></a>
    <a href="<?= $tt ?>/calendario#horario"<?= $temDia ? '' : ' class="bloqueado" title="Incluso a partir do plano Anual"' ?>><i class="fas <?= $temDia ? 'fa-clock' : 'fa-lock' ?>"></i><span>Horário</span></a>
    <a href="<?= $tt ?>/reunioes"><i class="fas fa-people-group"></i><span>Reuniões</span></a>
    <a href="<?= $tt ?>/enquetes"><i class="fas fa-square-poll-vertical"></i><span>Enquetes</span></a>
    <a href="<?= $tt ?>/estudos"><i class="fas fa-book-open"></i><span>Estudos</span></a>
    <a href="<?= $tt ?>/mural"><i class="fas fa-images"></i><span>Mural</span></a>
    <a href="<?= $tt ?>/caixinha"><i class="fas fa-box-open"></i><span>Caixinha</span></a>
    <a href="<?= $tt ?>/caixa"><i class="fas fa-sack-dollar"></i><span>Caixa</span></a>
    <?php if ($ehGremio): ?><a href="<?= $tt ?>/diretorias"><i class="fas fa-sitemap"></i><span>Diretorias</span></a><a href="<?= $tt ?>/pagina"><i class="fas fa-globe"></i><span>Página pública</span></a><?php endif; ?>
</nav>
<?php if (!empty($proximasDatas) || !empty($aulasHoje)): ?>
    <div class="dia-grade">
        <?php if (!empty($proximasDatas)): ?>
            <section class="panel dia-bloco">
                <div class="dia-bloco-topo"><strong><i class="fas fa-calendar-days"></i> Próximas datas</strong><a href="<?= $tt ?>/calendario">Calendário</a></div>
                <ul>
                    <?php foreach ($proximasDatas as $ev): $dd = (int) floor((strtotime($ev['data']) - strtotime(date('Y-m-d'))) / 86400); ?>
                        <li><span class="dia-pino t-<?= h($ev['tipo']) ?>"></span><span><strong><?= h($ev['titulo']) ?></strong>
                            <small><?= Calendario::TIPOS[$ev['tipo']][0] ?> · <?= date('d/m', strtotime($ev['data'])) ?><?= $ev['hora_inicio'] ? ' ' . substr($ev['hora_inicio'], 0, 5) : '' ?> · <?= $dd === 0 ? 'hoje' : ($dd === 1 ? 'amanhã' : "em {$dd} dias") ?></small></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
        <?php if (!empty($aulasHoje)): ?>
            <section class="panel dia-bloco">
                <div class="dia-bloco-topo"><strong><i class="fas fa-clock"></i> Aulas de hoje</strong><a href="<?= $tt ?>/calendario#horario">Semana</a></div>
                <ul>
                    <?php foreach ($aulasHoje as $au): $agoraH = date('H:i:s'); $ag = $au['inicio'] <= $agoraH && $au['fim'] > $agoraH; ?>
                        <li class="<?= $ag ? 'agora' : '' ?>"><span class="num"><?= substr($au['inicio'], 0, 5) ?>–<?= substr($au['fim'], 0, 5) ?></span>
                            <span><strong><?= h($au['disciplina']) ?></strong><?= $ag ? ' <em>agora</em>' : '' ?><small><?= h(implode(' · ', array_filter([$au['professor'], $au['sala']]))) ?></small></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="hero-actions turma-acoes">
    <?php if ($isMaster || $isRep): ?>
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/identidade">
            <i class="fas fa-palette"></i> Identidade
        </a>
        <?php if ($isMaster || $isPrincipal): // o vice não acessa plano e pagamento ?>
            <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/plano">
                <i class="fas fa-receipt"></i> Plano
            </a>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($isMaster): ?>
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/editar">
            <i class="fas fa-pen"></i> Editar
        </a>
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes">
            <i class="fas fa-user-tie"></i> <?= $rotulos['reps'] ?>
        </a>
        <form method="POST" action="<?= $basePath ?>/master/comercial/turmas/<?= (int) $turma['id'] ?>/isencao" class="d-inline">
            <?= ViewHelper::csrfField() ?>
            <input type="hidden" name="voltar" value="turma">
            <button type="submit" class="button button-secondary">
                <i class="fas <?= !empty($turma['isenta_plano']) ? 'fa-receipt' : 'fa-gift' ?>"></i>
                <?= !empty($turma['isenta_plano']) ? 'Exigir plano' : 'Isentar do plano' ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- PROJETOS DA TURMA                                             -->
<!-- ============================================================ -->
<section class="turma-projetos" aria-labelledby="turma-projetos-titulo">
    <div class="bloco-titulo">
        <h2 id="turma-projetos-titulo">Projetos</h2>
        <?php if ($isMaster || $isRep): ?>
            <a class="bloco-link" href="<?= $basePath ?>/projetos/criar?turma_id=<?= (int) $turma['id'] ?>"><i class="fas fa-plus"></i> Novo projeto</a>
        <?php endif; ?>
    </div>

    <?php if (empty($projetos)): ?>
        <div class="lista-vazia">
            <i class="fas fa-diagram-project"></i>
            <span><?= $ehGremio ? 'O grêmio ainda não tem projetos.' : 'Esta turma ainda não tem projetos.' ?><?= $isMaster ? '' : ' Quando ' . ($ehGremio ? 'a presidência' : 'o representante') . ' criar um, ele aparece aqui.' ?></span>
        </div>
    <?php else: ?>
        <div class="projetos-cartoes">
            <?php foreach ($projetos as $p):
                $pp = $basePath . '/projetos/' . (int) $p['id'];
                $prazo = !empty($p['prazo']) ? date('d/m/Y', strtotime($p['prazo'])) : null;
                $vencido = !$p['encerrado'] && !empty($p['prazo']) && strtotime($p['prazo']) < strtotime('today');
                $atalhos = ($isRep || $isMaster)
                    ? [['fas fa-clipboard-check', 'Avaliar', $pp . '/avaliacoes'], ['fas fa-book', 'Atas', $pp . '/atas'], ['fas fa-list-check', 'Critérios', $pp . '/criterios'], ['fas fa-boxes', 'Materiais', $pp . '/materiais']]
                    : [['fas fa-star-half-stroke', 'Minhas notas', $pp . '/avaliacoes/minhas'], ['fas fa-user-group', 'Avaliar colegas', $pp . '/avaliacoes/pares'], ['fas fa-book', 'Atas', $pp . '/atas'], ['fas fa-boxes', 'Materiais', $pp . '/materiais']];
            ?>
                <article class="projeto-cartao <?= $p['encerrado'] ? 'encerrado' : '' ?>">
                    <a class="projeto-cartao-topo" href="<?= $pp ?>">
                        <span class="tarefa-icone"><i class="fas <?= $p['encerrado'] ? 'fa-lock' : 'fa-diagram-project' ?>"></i></span>
                        <span class="projeto-cartao-titulo">
                            <strong><?= h($p['nome']) ?></strong>
                            <small>
                                <?= $p['encerrado'] ? 'Encerrado' : 'Em andamento' ?>
                                · <?= (int) $p['total_etapas'] ?> etapa(s)
                                <?php if ($prazo): ?> · <span class="<?= $vencido ? 'txt-alerta' : '' ?>">prazo <?= $prazo ?><?= $vencido ? ' (vencido)' : '' ?></span><?php endif; ?>
                            </small>
                        </span>
                        <i class="fas fa-arrow-right projeto-cartao-seta" aria-hidden="true"></i>
                    </a>
                    <nav class="projeto-cartao-atalhos" aria-label="Atalhos de <?= h($p['nome']) ?>">
                        <?php foreach ($atalhos as [$ic, $rot, $url]): ?>
                            <a href="<?= $url ?>"><i class="<?= $ic ?>" aria-hidden="true"></i> <?= $rot ?></a>
                        <?php endforeach; ?>
                    </nav>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($turma['bloqueada'] && $isMaster): ?>
    <div class="notice notice-danger">
        <span>!</span>
        <div>
            <strong>Turma bloqueada</strong>
            <p><?= h($turma['bloqueada_motivo']) ?></p>
        </div>
    </div>
<?php endif; ?>

<!-- ============ AÇÕES DO MASTER ============ -->
<?php if ($isMaster): ?>
    <section class="panel mb-16">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-shield-halved"></i> Ações do Master</h2>
                <p>Bloqueio, código de acesso e exclusão.</p>
            </div>
        </div>

        <div class="turma-acoes">
            <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/regenerar-codigo">
                <?= ViewHelper::csrfField() ?>
                <button type="submit" class="button button-ghost"
                        onclick="return confirm('Regenerar código de acesso? O código atual deixará de funcionar.')">
                    <i class="fas fa-rotate"></i> Regenerar código
                </button>
            </form>

            <?php if ($turma['bloqueada']): ?>
                <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/desbloquear">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="button button-primary">
                        <i class="fas fa-unlock"></i> Desbloquear
                    </button>
                </form>
            <?php else: ?>
                <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/bloquear">
                    <?= ViewHelper::csrfField() ?>
                    <input type="text" name="motivo" class="field" placeholder="Motivo do bloqueio" required>
                    <button type="submit" class="button button-ghost text-red">
                        <i class="fas fa-lock"></i> Bloquear
                    </button>
                </form>
            <?php endif; ?>

            <a class="button btn-outline-danger" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/excluir">
                <i class="fas fa-trash"></i> Excluir turma
            </a>
        </div>
    </section>
<?php elseif ($isRep): ?>
    <section class="panel mb-16">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-user-tie"></i> Ações do Representante</h2>
            </div>
        </div>
        <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/regenerar-codigo">
            <?= ViewHelper::csrfField() ?>
            <button type="submit" class="button button-ghost"
                    onclick="return confirm('Regenerar código de acesso?')">
                <i class="fas fa-rotate"></i> Regenerar código
            </button>
        </form>
    </section>
<?php endif; ?>

<!-- ============ REPRESENTANTES ============ -->
<section class="panel mb-16" id="representantes">
    <div class="panel-header">
        <div>
            <h2><?= $rotulos['reps'] ?> (<?= count($representantes) ?>/2)</h2>
            <p><?= $ehGremio ? 'Presidente e vice-presidente.' : 'Cada turma pode ter até 2 representantes ativos.' ?></p>
        </div>
        <?php if ($isMaster): ?>
            <a class="text-link" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes">
                Gerenciar <span>&rarr;</span>
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($representantes)): ?>
        <p class="text-muted">Nenhum representante nomeado.</p>
    <?php else: ?>
        <div class="turma-membros-lista">
            <?php foreach ($representantes as $r): ?>
                <div class="turma-membro-item">
                    <span class="avatar avatar-indigo"><?= strtoupper(substr($r['nome'], 0, 1)) ?></span>
                    <div class="membro-info">
                        <strong><?= h($r['nome']) ?></strong>
                        <small><?= h($r['email']) ?></small>
                    </div>
                    <?php if (!empty($r['vice'])): ?>
                        <span class="badge badge-blue"><i class="fas fa-user-shield"></i> <?= $ehGremio ? 'Vice-presidente' : 'Vice' ?></span>
                        <?php if ($isMaster || $isPrincipal): ?>
                            <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/vice/remover" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="tarefa-acao" onclick="return confirm('<?= h($r['nome']) ?> deixa de ser vice e volta a ser aluno. Continuar?')">Remover</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-yellow"><i class="fas fa-crown"></i> <?= $rotulos['rep'] ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    $candidatosVice = array_values(array_filter($alunos, fn($a) => ($a['papel'] ?? '') === 'aluno'));
    if (($isMaster || $isPrincipal) && !$vice && count($representantes) < 2):
    ?>
        <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/vice" class="vice-form">
            <?= ViewHelper::csrfField() ?>
            <label class="field-label" for="vice-aluno"><i class="fas fa-user-shield"></i> Escolher <?= $ehGremio ? 'o vice-presidente' : 'um vice-representante' ?></label>
            <p class="field-help">O vice organiza <?= $ehGremio ? 'o grêmio' : 'a turma' ?> com você, mas não vê nem mexe no plano e nos pagamentos.</p>
            <div class="vice-linha">
                <select id="vice-aluno" name="usuario_id" class="field" required>
                    <option value="">Escolha um aluno</option>
                    <?php foreach ($candidatosVice as $a): ?><option value="<?= (int) $a['id'] ?>"><?= h($a['nome']) ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="button button-secondary">Tornar vice</button>
            </div>
        </form>
    <?php elseif ($isVice): ?>
        <p class="field-help mt-12"><i class="fas fa-user-shield"></i> Você é o vice: avalia e organiza a turma; o plano e os pagamentos ficam com o representante principal.</p>
    <?php endif; ?>
</section>

<!-- ============ ALUNOS ============ -->
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Alunos (<?= count($alunos) ?>)</h2>
            <p>Membros ativos da turma.</p>
        </div>
    </div>

    <?php if (empty($alunos)): ?>
        <p class="text-muted">Nenhum aluno na turma.</p>
    <?php else: ?>
        <table class="tabela-lista">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Papel</th>
                    <th>Entrou em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $a): ?>
                    <tr>
                        <td data-label="Nome">
                            <span class="person-cell">
                                <span class="avatar avatar-blue"><?= h(mb_strtoupper(mb_substr($a['nome'], 0, 1))) ?></span>
                                <?= h($a['nome']) ?>
                            </span>
                        </td>
                        <td data-label="E-mail"><?= h($a['email']) ?></td>
                        <td data-label="Papel">
                            <?php if ($a['papel'] === 'representante'): ?>
                                <span class="badge badge-yellow"><i class="fas fa-crown"></i> <?= $rotulos['rep'] ?></span>
                            <?php else: ?>
                                <span class="badge badge-gray">Aluno</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Entrou em"><?= $a['entrou_em'] ? h(date('d/m/Y', strtotime($a['entrou_em']))) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<p class="mt-20">
    <a class="button button-ghost" href="<?= $basePath ?>/turmas">
        <i class="fas fa-arrow-left"></i> Voltar para turmas
    </a>
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>