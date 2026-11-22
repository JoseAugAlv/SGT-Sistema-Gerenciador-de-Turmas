<?php
$cssPagina = 'landing.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$logado = !empty($_SESSION['usuario']);
?>

<div class="landing-page">

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- INTRO                                                       -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="landing-intro" id="inicio">
        <div>
            <div class="landing-kicker">
                <span class="kicker-dot"></span>
                Organização de turmas e grêmios
            </div>

            <h1>
                Uma visão operacional para
                <span>turmas, projetos e avaliações.</span>
            </h1>

            <p class="landing-copy">
                O <?= h($appName) ?> organiza a rotina acadêmica em um único ambiente:
                papéis por turma e grupo, critérios de avaliação, atas, materiais,
                relatórios e histórico completo das ações.
            </p>
        </div>

        <div class="landing-actions">
            <?php if ($logado): ?>
                <a class="btn btn-primary" href="<?= $basePath ?>/">
                    <i class="fas fa-gauge"></i> Ir para o painel
                </a>
                <a class="btn btn-secondary" href="<?= $basePath ?>/tutorial/exemplos">
                    <i class="fas fa-images"></i> Ver exemplos
                </a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= $basePath ?>/login/cadastrar">
                    <i class="fas fa-user-plus"></i> Criar conta
                </a>
                <a class="btn btn-secondary" href="#modulos">
                    <i class="fas fa-compass"></i> Ver módulos
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- SNAPSHOT + PAPÉIS                                           -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="landing-summary">

        <!-- Painel de exemplo -->
        <article class="landing-card landing-snapshot">
            <div class="landing-card-head">
                <div>
                    <strong class="landing-card-title">Visão geral da turma</strong>
                    <span class="landing-card-sub">Exemplo de painel para representantes e gestores</span>
                </div>
                <span class="landing-chip">3º Informática A</span>
            </div>

            <div class="landing-metrics">
                <div class="landing-metric">
                    <label>Alunos ativos</label>
                    <strong>32</strong>
                    <em>26 alunos comuns</em>
                </div>
                <div class="landing-metric">
                    <label>Projetos abertos</label>
                    <strong>04</strong>
                    <em>02 com prazo próximo</em>
                </div>
                <div class="landing-metric">
                    <label>Avaliações pendentes</label>
                    <strong>07</strong>
                    <em>3 por representante</em>
                </div>
                <div class="landing-metric">
                    <label>Média da turma</label>
                    <strong>8,6</strong>
                    <em>último projeto</em>
                </div>
            </div>
        </article>

        <!-- Papéis -->
        <article class="landing-card landing-roles">
            <div class="landing-card-head">
                <div>
                    <strong class="landing-card-title">Papéis e escopo</strong>
                    <span class="landing-card-sub">Permissões aplicadas por contexto</span>
                </div>
            </div>

            <div class="landing-role-list">
                <div class="landing-role">
                    <span class="role-dot purple"></span>
                    <span class="role-info">
                        <strong>Master</strong>
                        <small>Administração global</small>
                    </span>
                    <span class="role-qtd">01</span>
                </div>
                <div class="landing-role">
                    <span class="role-dot orange"></span>
                    <span class="role-info">
                        <strong>Representantes</strong>
                        <small>Gestão da turma</small>
                    </span>
                    <span class="role-qtd">02</span>
                </div>
                <div class="landing-role">
                    <span class="role-dot blue"></span>
                    <span class="role-info">
                        <strong>Diretores</strong>
                        <small>Atuação por grupo</small>
                    </span>
                    <span class="role-qtd">04</span>
                </div>
            </div>
        </article>

    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- MÓDULOS                                                     -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="landing-section" id="modulos">
        <div class="landing-section-head">
            <div>
                <h2>Módulos do sistema</h2>
                <p>Recursos organizados em torno do ciclo de uma turma.</p>
            </div>
            <a class="landing-section-link" href="#operacao">
                Ver estrutura completa <span>&rarr;</span>
            </a>
        </div>

        <div class="landing-modules">
            <article class="landing-module">
                <span class="module-icon purple"><i class="fas fa-users"></i></span>
                <h3>Turmas e papéis</h3>
                <p>Alocação de alunos, representantes e diretores com permissões por turma e grupo.</p>
                <span class="module-meta">Acesso · histórico · auditoria</span>
            </article>

            <article class="landing-module">
                <span class="module-icon blue"><i class="fas fa-diagram-project"></i></span>
                <h3>Projetos e etapas</h3>
                <p>Projetos, prazos, etapas reordenáveis e encerramento com boletim congelado.</p>
                <span class="module-meta">Cronograma · etapas · snapshot</span>
            </article>

            <article class="landing-module">
                <span class="module-icon green"><i class="fas fa-star"></i></span>
                <h3>Avaliações</h3>
                <p>Critérios com pesos e seis tipos de aplicação, incluindo pares, coletiva e mista.</p>
                <span class="module-meta">Conceito · média · visibilidade</span>
            </article>

            <article class="landing-module">
                <span class="module-icon orange"><i class="fas fa-book"></i></span>
                <h3>Atas e materiais</h3>
                <p>Registro de atividades, validação de atas e movimentação de materiais do projeto.</p>
                <span class="module-meta">Pendências · relatórios · estoque</span>
            </article>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- OPERAÇÃO                                                    -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="landing-section" id="operacao">
        <div class="landing-section-head">
            <div>
                <h2>Como a operação aparece no sistema</h2>
                <p>Exemplos de telas e rotinas que orientam o dia a dia.</p>
            </div>
        </div>

        <div class="landing-operational">

            <!-- Pendências -->
            <article class="landing-card landing-table-card">
                <div class="landing-card-head">
                    <div>
                        <strong class="landing-card-title">Pendências da turma</strong>
                        <span class="landing-card-sub">Itens que pedem ação do usuário</span>
                    </div>
                    <a class="landing-section-link" href="#modulos">
                        Abrir lista <span>&rarr;</span>
                    </a>
                </div>

                <div class="landing-table">
                    <div class="landing-tr landing-th">
                        <span>Item</span>
                        <span>Responsável</span>
                        <span>Status</span>
                    </div>
                    <div class="landing-tr">
                        <span>Avaliação por pares</span>
                        <span>Alunos</span>
                        <span class="status-chip yellow">Pendente</span>
                    </div>
                    <div class="landing-tr">
                        <span>Ata do Grupo 02</span>
                        <span>Diretor</span>
                        <span class="status-chip green">Preenchida</span>
                    </div>
                    <div class="landing-tr">
                        <span>Critério "Entrega"</span>
                        <span>Representante</span>
                        <span class="status-chip yellow">Revisar</span>
                    </div>
                    <div class="landing-tr">
                        <span>Boletim do projeto</span>
                        <span>Master</span>
                        <span class="status-chip green">Disponível</span>
                    </div>
                </div>
            </article>

            <!-- Fluxo -->
            <article class="landing-card landing-process-card">
                <div class="landing-card-head">
                    <div>
                        <strong class="landing-card-title">Fluxo de um projeto</strong>
                        <span class="landing-card-sub">Da configuração ao relatório final</span>
                    </div>
                </div>

                <div class="landing-process-list">
                    <div class="landing-process">
                        <span class="process-num">01</span>
                        <div>
                            <strong>Configurar projeto</strong>
                            <p>Nome, modo de avaliação, prazo e etapas.</p>
                        </div>
                    </div>
                    <div class="landing-process">
                        <span class="process-num">02</span>
                        <div>
                            <strong>Definir grupos e critérios</strong>
                            <p>Membros, diretores, pesos e aplicabilidade.</p>
                        </div>
                    </div>
                    <div class="landing-process">
                        <span class="process-num">03</span>
                        <div>
                            <strong>Consolidar resultados</strong>
                            <p>Avaliações, atas, média ponderada e boletim.</p>
                        </div>
                    </div>
                </div>
            </article>

        </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- INFO BAND — AUDITORIA E SEGURANÇA                           -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="landing-info-band" id="seguranca">
        <div>
            <h2>Controle sem perder o histórico.</h2>
            <p>
                As ações críticas ficam registradas: alterações de papéis,
                reabertura de critérios, troca de diretores, encerramento de
                projetos e solicitações LGPD.
            </p>
        </div>
        <div class="landing-info-actions">
            <a class="btn btn-secondary" href="<?= $basePath ?>/lgpd">
                <i class="fas fa-shield-halved"></i> Conhecer LGPD
            </a>
            <?php if (!$logado): ?>
                <a class="btn btn-primary" href="<?= $basePath ?>/login/cadastrar">
                    <i class="fas fa-rocket"></i> Usar o <?= h($appName) ?>
                </a>
            <?php else: ?>
                <a class="btn btn-primary" href="<?= $basePath ?>/turmas">
                    <i class="fas fa-users"></i> Minhas turmas
                </a>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>