<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
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
            Código <code><?= h($turma['codigo_acesso']) ?></code> ·
            <?= $turma['bloqueada'] ? 'Bloqueada' : 'Ativa' ?>
        </span>
    </div>
</section>

<div class="hero-actions turma-acoes">
    <?php if ($isMaster || $isRep): ?>
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/identidade">
            <i class="fas fa-palette"></i> Identidade
        </a>
    <?php endif; ?>
    <?php if ($isMaster): ?>
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/editar">
            <i class="fas fa-pen"></i> Editar
        </a>
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/representantes">
            <i class="fas fa-user-tie"></i> Representantes
        </a>
    <?php endif; ?>
</div>

<!-- ============================================================ -->
<!-- PROJETOS DA TURMA                                             -->
<!-- ============================================================ -->
<section class="turma-projetos" aria-labelledby="turma-projetos-titulo">
    <div class="bloco-titulo">
        <h2 id="turma-projetos-titulo">Projetos</h2>
        <?php if ($isMaster): ?>
            <a class="bloco-link" href="<?= $basePath ?>/projetos/criar?turma_id=<?= (int) $turma['id'] ?>"><i class="fas fa-plus"></i> Novo projeto</a>
        <?php endif; ?>
    </div>

    <?php if (empty($projetos)): ?>
        <div class="lista-vazia">
            <i class="fas fa-diagram-project"></i>
            <span>Esta turma ainda não tem projetos.<?= $isMaster ? '' : ' Quando o professor criar um, ele aparece aqui.' ?></span>
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
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Representantes (<?= count($representantes) ?>/2)</h2>
            <p>Cada turma pode ter até 2 representantes ativos.</p>
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
                    <span class="badge badge-yellow"><i class="fas fa-crown"></i> Representante</span>
                </div>
            <?php endforeach; ?>
        </div>
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
                                <span class="badge badge-yellow"><i class="fas fa-crown"></i> Representante</span>
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