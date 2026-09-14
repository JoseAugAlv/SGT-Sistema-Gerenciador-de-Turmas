<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$primeiroNome = explode(' ', trim($usuario['nome']))[0];

$diasSemana = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
$meses      = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
$agora      = new DateTime();
$dataLonga  = $diasSemana[(int) $agora->format('w')] . ', ' . $agora->format('d') . ' de ' . $meses[(int) $agora->format('n') - 1] . ' de ' . $agora->format('Y');
?>

<!-- ============================================================ -->
<!-- HERO                                                          -->
<!-- ============================================================ -->
<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            <?= h($dataLonga) ?>
        </div>
        <h1>Olá, <?= h($primeiroNome) ?></h1>
        <p class="hero-subtitle">
            <?php if ($isMaster): ?>
                Acompanhe o sistema em um só lugar.
            <?php elseif ($souRepEmAlgumaTurma): ?>
                Aqui está o que está acontecendo nas suas turmas hoje.
            <?php elseif ($souDiretorEmAlgumGrupo): ?>
                Veja as pendências e o desempenho dos seus grupos.
            <?php else: ?>
                Veja suas turmas, avaliações e alertas.
            <?php endif; ?>
        </p>
    </div>

    <div class="hero-actions">
        <?php if ($isMaster): ?>
            <a class="button button-secondary" href="<?= $basePath ?>/master">
                <i class="fas fa-gauge"></i> Painel Master
            </a>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/criar">
                <i class="fas fa-plus"></i> Nova turma
            </a>
        <?php elseif ($souRepEmAlgumaTurma): ?>
            <a class="button button-secondary" href="<?= $basePath ?>/turmas">
                <i class="fas fa-users"></i> Minhas turmas
            </a>
            <a class="button button-primary" href="<?= $basePath ?>/notificacoes">
                <i class="fas fa-bell"></i> Notificações
                <?php if ($naoLidas > 0): ?>(<?= $naoLidas ?>)<?php endif; ?>
            </a>
        <?php else: ?>
            <a class="button button-secondary" href="<?= $basePath ?>/turmas">
                <i class="fas fa-users"></i> Minhas turmas
            </a>
            <a class="button button-primary" href="<?= $basePath ?>/notificacoes">
                <i class="fas fa-bell"></i> Notificações
                <?php if ($naoLidas > 0): ?>(<?= $naoLidas ?>)<?php endif; ?>
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- ALERTAS (destaque)                                            -->
<!-- ============================================================ -->
<?php if (!empty($alertas)): ?>
    <section class="panel" style="margin-bottom:16px; border-left:4px solid <?= $alertas[0]['urgente'] ? 'var(--red)' : 'var(--primary)' ?>;">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-bullhorn"></i> Alertas recentes</h2>
                <p>Avisos enviados para as suas turmas.</p>
            </div>
            <?php foreach ($minhasTurmas as $t): ?>
                <a class="text-link" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>/alertas">Ver todos <span>→</span></a>
                <?php break; ?>
            <?php endforeach; ?>
        </div>

        <?php foreach ($alertas as $a): ?>
            <div class="activity-item">
                <div class="activity-icon <?= $a['urgente'] ? 'orange' : 'purple' ?>">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div style="flex:1;">
                    <strong>
                        <?php if ($a['urgente']): ?>
                            <span class="badge badge-red">URGENTE</span>
                        <?php endif; ?>
                        <?= h($a['titulo']) ?>
                    </strong>
                    <span>
                        <?= h($a['turma_nome_ctx']) ?>
                        • por <?= h($a['autor_nome'] ?? '—') ?>
                        • <?= h($a['created_at']) ?>
                    </span>
                    <p style="margin:6px 0 0; color:var(--text); font-size:12px;">
                        <?= nl2br(h($a['mensagem'])) ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<!-- ============================================================ -->
<!-- ESTATÍSTICAS                                                  -->
<!-- ============================================================ -->
<section class="stats-grid" aria-label="Resumo">
    <?php foreach ($stats as $s): ?>
        <article class="stat-card">
            <div class="stat-top">
                <span class="stat-label"><?= h($s['label']) ?></span>
                <span class="stat-icon <?= h($s['cor']) ?>"><i class="<?= h($s['icon']) ?>"></i></span>
            </div>
            <div class="stat-value"><?= (int) $s['valor'] ?></div>
            <div class="stat-foot"><span><?= h($s['foot']) ?></span></div>
        </article>
    <?php endforeach; ?>
</section>

<!-- ============================================================ -->
<!-- PROJETOS EM ANDAMENTO + ATIVIDADE                             -->
<!-- ============================================================ -->
<section class="content-grid two-thirds">

    <article class="panel">
        <div class="panel-header">
            <div>
                <h2>Projetos em andamento</h2>
                <p>Acompanhe o progresso dos projetos das suas turmas.</p>
            </div>
        </div>

        <?php if (empty($projetos)): ?>
            <p style="color:var(--muted);">Nenhum projeto em andamento.</p>
        <?php else: ?>
            <div class="project-list">
                <?php
                $cores = ['coral', 'lilac', 'mint'];
                foreach ($projetos as $i => $p):
                    $cor = $cores[$i % 3];
                ?>
                    <a class="project-row" href="<?= $basePath ?>/projetos/<?= (int) $p['id'] ?>" style="text-decoration:none;">
                        <div class="project-logo <?= $cor ?>"><?= h($p['sigla']) ?></div>
                        <div class="project-info">
                            <strong><?= h($p['nome']) ?></strong>
                            <span><?= h($p['turma_nome']) ?> • <?= (int) $p['total_grupos'] ?> grupo(s) • <?= (int) $p['total_criterios'] ?> critério(s)</span>
                            <div class="progress-line"><i style="width:<?= (int) $p['progresso'] ?>%"></i></div>
                        </div>
                        <div class="project-meta">
                            <strong><?= (int) $p['progresso'] ?>%</strong>
                            <span>
                                <?php if ($p['dias_restantes'] === null): ?>
                                    sem prazo
                                <?php elseif ($p['dias_restantes'] < 0): ?>
                                    atrasado
                                <?php elseif ($p['dias_restantes'] === 0): ?>
                                    hoje
                                <?php else: ?>
                                    em <?= (int) $p['dias_restantes'] ?> dia(s)
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="row-arrow"><i class="fas fa-chevron-right"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="panel-header">
            <div>
                <h2>Atividade recente</h2>
                <p><?= $isMaster ? 'Últimas ações no sistema.' : 'Suas notificações recentes.' ?></p>
            </div>
        </div>

        <?php if (empty($atividade)): ?>
            <p style="color:var(--muted);">Nada por aqui ainda.</p>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($atividade as $a): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?= h($a['cor']) ?>">
                            <i class="<?= h($a['icon']) ?>"></i>
                        </div>
                        <div>
                            <strong><?= h($a['titulo']) ?></strong>
                            <span><?= h($a['texto']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a class="activity-footer" href="<?= $basePath ?>/notificacoes">
            Ver histórico completo <span>→</span>
        </a>
    </article>

</section>

<!-- ============================================================ -->
<!-- PRAZOS + PAPÉIS                                               -->
<!-- ============================================================ -->
<section class="content-grid two-thirds">

    <article class="panel">
        <div class="panel-header">
            <div>
                <h2>Próximos prazos</h2>
                <p>Critérios e projetos que vencem em até 30 dias.</p>
            </div>
        </div>

        <?php if (empty($prazos)): ?>
            <p style="color:var(--muted);">Nenhum prazo nos próximos 30 dias.</p>
        <?php else: ?>
            <div class="deadline-list">
                <?php foreach ($prazos as $p): ?>
                    <div class="deadline-item">
                        <div class="date-box <?= $p['dias'] <= 3 ? 'urgent' : '' ?>">
                            <strong><?= h($p['dia']) ?></strong>
                            <span><?= h($p['mes']) ?></span>
                        </div>
                        <div>
                            <strong><?= h($p['titulo']) ?></strong>
                            <span>
                                <?= $p['tipo'] === 'criterio' ? 'Critério' : 'Projeto' ?>
                                • <?= h($p['contexto']) ?>
                            </span>
                        </div>
                        <span class="badge <?= h($p['badge']) ?>">
                            <?php if ($p['dias'] <= 0): ?>
                                hoje
                            <?php else: ?>
                                <?= (int) $p['dias'] ?> dia(s)
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="panel">
        <div class="panel-header">
            <div>
                <h2>Distribuição de papéis</h2>
                <p>Nas suas turmas.</p>
            </div>
        </div>

        <?php
        $total = max(1, (int) $papeis['total']); // evita divisão por zero
        $pctAlunos    = round($papeis['alunos']    / $total * 100);
        $pctDiretores = round($papeis['diretores'] / $total * 100);
        $pctReps      = round($papeis['reps']      / $total * 100);

        // Conic gradient dinâmico
        $c1 = $pctAlunos;
        $c2 = $pctAlunos + $pctDiretores;
        $c3 = 100;
        ?>
        <div class="donut-area">
            <div class="donut" style="background: conic-gradient(
                var(--blue) 0 <?= $c1 ?>%,
                var(--primary) <?= $c1 ?>% <?= $c2 ?>%,
                var(--orange) <?= $c2 ?>% 100%
            );">
                <div>
                    <strong><?= (int) $papeis['total'] ?></strong>
                    <span>pessoas</span>
                </div>
            </div>
            <div class="role-legend">
                <span><i class="legend-dot blue-dot"></i><strong><?= (int) $papeis['alunos'] ?></strong> Alunos</span>
                <span><i class="legend-dot purple-dot"></i><strong><?= (int) $papeis['diretores'] ?></strong> Diretores</span>
                <span><i class="legend-dot orange-dot"></i><strong><?= (int) $papeis['reps'] ?></strong> Representantes</span>
            </div>
        </div>
    </article>

</section>

<!-- ============================================================ -->
<!-- MINHAS TURMAS                                                 -->
<!-- ============================================================ -->
<section class="section-heading">
    <div>
        <div class="eyebrow">
            <?= $isMaster ? 'Visão geral' : 'Onde você participa' ?>
        </div>
        <h2>Minhas turmas</h2>
        <p>
            <?php if (empty($minhasTurmas)): ?>
                Você ainda não está em nenhuma turma.
            <?php else: ?>
                Você participa de <?= count($minhasTurmas) ?> turma(s).
            <?php endif; ?>
        </p>
    </div>

    <?php if (!$isMaster): ?>
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/entrar">
            <i class="fas fa-right-to-bracket"></i> Entrar com código
        </a>
    <?php else: ?>
        <a class="button button-primary" href="<?= $basePath ?>/turmas/criar">
            <i class="fas fa-plus"></i> Nova turma
        </a>
    <?php endif; ?>
</section>

<?php if (!empty($minhasTurmas)): ?>
    <section class="component-grid">
        <?php foreach (array_slice($minhasTurmas, 0, 6) as $t): ?>
            <article class="panel">
                <div class="panel-header">
                    <div>
                        <h2><?= h($t['nome']) ?></h2>
                        <p>Código: <code><?= h($t['codigo_acesso']) ?></code></p>
                    </div>
                    <?php if (!empty($t['bloqueada'])): ?>
                        <span class="badge badge-red">Bloqueada</span>
                    <?php else: ?>
                        <span class="badge badge-green">Ativa</span>
                    <?php endif; ?>
                </div>

                <?php if ($isMaster): ?>
                    <div class="status-line">
                        <strong><?= (int) ($t['total_alunos'] ?? 0) ?></strong>
                        <span>aluno(s) ativo(s)</span>
                    </div>
                    <div class="status-line">
                        <strong><?= (int) ($t['total_reps'] ?? 0) ?>/2</strong>
                        <span>representante(s)</span>
                    </div>
                <?php else: ?>
                    <div class="status-line">
                        <span class="badge badge-gray"><?= h($t['meu_papel'] ?? 'aluno') ?></span>
                        <span>seu papel nesta turma</span>
                    </div>
                <?php endif; ?>

                <a class="button button-primary full-width" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>">
                    <i class="fas fa-arrow-right"></i> Abrir turma
                </a>
            </article>
        <?php endforeach; ?>
    </section>
<?php else: ?>
    <section class="panel" style="text-align:center; padding:36px 20px;">
        <h2 style="margin-bottom:8px;">Você ainda não está em nenhuma turma</h2>
        <p style="color:var(--muted); max-width:480px; margin:0 auto 18px;">
            Peça o código de acesso da turma para o professor ou representante e entre
            usando o botão abaixo.
        </p>
        <a class="button button-primary" href="<?= $basePath ?>/turmas/entrar">
            <i class="fas fa-right-to-bracket"></i> Entrar em uma turma
        </a>
    </section>
<?php endif; ?>

<!-- ============================================================ -->
<!-- ATALHOS RÁPIDOS                                               -->
<!-- ============================================================ -->
<section class="section-heading" style="margin-top:32px;">
    <div>
        <h2>Atalhos rápidos</h2>
        <p>Ações frequentes.</p>
    </div>
</section>

<section class="panel">
    <div class="shortcut-grid">
        <?php if ($isMaster): ?>
            <a href="<?= $basePath ?>/turmas/criar">
                <span class="shortcut-icon blue"><i class="fas fa-plus"></i></span>
                <strong>Nova turma</strong>
                <small>Criar turma com código de acesso</small>
            </a>
            <a href="<?= $basePath ?>/configuracoes/turmas">
                <span class="shortcut-icon purple"><i class="fas fa-cog"></i></span>
                <strong>Cursos e períodos</strong>
                <small>Configurações de turma</small>
            </a>
            <a href="<?= $basePath ?>/master/auditoria">
                <span class="shortcut-icon orange"><i class="fas fa-clipboard-list"></i></span>
                <strong>Auditoria</strong>
                <small>Histórico de ações</small>
            </a>
            <a href="<?= $basePath ?>/master/backup">
                <span class="shortcut-icon green"><i class="fas fa-database"></i></span>
                <strong>Backup</strong>
                <small>Gerar cópia do banco</small>
            </a>
        <?php elseif ($souRepEmAlgumaTurma): ?>
            <a href="<?= $basePath ?>/turmas/entrar">
                <span class="shortcut-icon blue"><i class="fas fa-right-to-bracket"></i></span>
                <strong>Entrar em turma</strong>
                <small>Usar um código de acesso</small>
            </a>
            <a href="<?= $basePath ?>/notificacoes">
                <span class="shortcut-icon purple"><i class="fas fa-bell"></i></span>
                <strong>Notificações</strong>
                <small><?= $naoLidas ?> não lida(s)</small>
            </a>
            <a href="<?= $basePath ?>/user">
                <span class="shortcut-icon orange"><i class="fas fa-user"></i></span>
                <strong>Minha conta</strong>
                <small>Perfil e senha</small>
            </a>
            <a href="<?= $basePath ?>/lgpd/meus-direitos">
                <span class="shortcut-icon green"><i class="fas fa-shield-halved"></i></span>
                <strong>LGPD</strong>
                <small>Exportar ou excluir dados</small>
            </a>
        <?php else: ?>
            <a href="<?= $basePath ?>/turmas">
                <span class="shortcut-icon blue"><i class="fas fa-users"></i></span>
                <strong>Minhas turmas</strong>
                <small>Ver onde participo</small>
            </a>
            <a href="<?= $basePath ?>/notificacoes">
                <span class="shortcut-icon purple"><i class="fas fa-bell"></i></span>
                <strong>Notificações</strong>
                <small><?= $naoLidas ?> não lida(s)</small>
            </a>
            <a href="<?= $basePath ?>/user">
                <span class="shortcut-icon orange"><i class="fas fa-user"></i></span>
                <strong>Minha conta</strong>
                <small>Perfil e senha</small>
            </a>
            <a href="<?= $basePath ?>/lgpd/meus-direitos">
                <span class="shortcut-icon green"><i class="fas fa-shield-halved"></i></span>
                <strong>LGPD</strong>
                <small>Exportar ou excluir dados</small>
            </a>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>