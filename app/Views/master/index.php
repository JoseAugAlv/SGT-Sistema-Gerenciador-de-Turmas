<?php
$tituloPagina = 'Painel Master — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="master-section">
    <div class="master-content">

        <!-- ============ HERO ============ -->
        <header class="master-hero">
            <div>
                <h1><i class="fas fa-gauge"></i> Painel Master</h1>
                <p class="master-subtitle">
                    <i class="fas fa-shield-halved"></i>
                    Controle total do sistema — estatísticas, auditoria e configurações.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-circle-check"></i> Acesso master
            </span>
        </header>

        <!-- ============ ALERTA LGPD ============ -->
        <?php if (!empty($stats['lgpd_pendentes'])): ?>
            <div class="master-box box-primary" style="border-left-color: var(--orange);">
                <div class="box-actions">
                    <span class="box-info-text" style="color: var(--text);">
                        <i class="fas fa-shield-halved" style="color: var(--orange);"></i>
                        <strong><?= (int) $stats['lgpd_pendentes'] ?></strong>
                        solicitação(ões) LGPD pendente(s) aguardando análise.
                    </span>
                    <a class="btn-new" href="<?= $basePath ?>/master/lgpd">
                        <i class="fas fa-arrow-right"></i> Analisar agora
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============ ESTATÍSTICAS ============ -->
        <h2 style="font-size:15px; font-weight:750; margin:8px 0 0; color: var(--text);">
            <i class="fas fa-chart-simple" style="color: var(--primary);"></i> Visão geral
        </h2>

        <div class="master-stats">
            <article class="master-stat-card">
                <span class="label">Turmas</span>
                <span class="valor"><?= (int) $stats['turmas'] ?></span>
                <span class="foot"><?= (int) $stats['turmas_ativas'] ?> ativa(s)</span>
            </article>

            <article class="master-stat-card accent-blue">
                <span class="label">Usuários</span>
                <span class="valor"><?= (int) $stats['usuarios'] ?></span>
                <span class="foot"><?= (int) $stats['alunos_ativos'] ?> aluno(s) ativo(s)</span>
            </article>

            <article class="master-stat-card accent-orange">
                <span class="label">Projetos</span>
                <span class="valor"><?= (int) $stats['projetos'] ?></span>
                <span class="foot"><?= (int) $stats['projetos_abertos'] ?> em andamento</span>
            </article>

            <article class="master-stat-card accent-green">
                <span class="label">Grupos</span>
                <span class="valor"><?= (int) $stats['grupos'] ?></span>
                <span class="foot">cadastrados no sistema</span>
            </article>

            <article class="master-stat-card">
                <span class="label">Critérios arquivados</span>
                <span class="valor"><?= (int) $stats['crit_arquivados'] ?></span>
                <span class="foot">disponíveis para restauração</span>
            </article>

            <article class="master-stat-card accent-red">
                <span class="label">LGPD pendentes</span>
                <span class="valor"><?= (int) $stats['lgpd_pendentes'] ?></span>
                <span class="foot">aguardando análise</span>
            </article>
        </div>

        <!-- ============ ATALHOS ============ -->
        <h2 style="font-size:15px; font-weight:750; margin:16px 0 0; color: var(--text);">
            <i class="fas fa-bolt" style="color: var(--primary);"></i> Atalhos rápidos
        </h2>

        <div class="master-shortcuts">
            <a class="master-shortcut blue" href="<?= $basePath ?>/turmas">
                <i class="icon fas fa-users"></i>
                <div class="info">
                    <strong>Gerenciar turmas</strong>
                    <small>Criar, editar e bloquear turmas</small>
                </div>
            </a>

            <a class="master-shortcut green" href="<?= $basePath ?>/master/comercial">
                <i class="icon fas fa-receipt"></i>
                <div class="info">
                    <strong>Comercial</strong>
                    <small>Planos, cupons, descontos e vendas</small>
                </div>
            </a>

            <a class="master-shortcut" href="<?= $basePath ?>/configuracoes/turmas">
                <i class="icon fas fa-gear"></i>
                <div class="info">
                    <strong>Cursos e períodos</strong>
                    <small>Configurações de turma</small>
                </div>
            </a>

            <a class="master-shortcut orange" href="<?= $basePath ?>/master/auditoria">
                <i class="icon fas fa-clipboard-list"></i>
                <div class="info">
                    <strong>Auditoria</strong>
                    <small>Histórico completo de ações</small>
                </div>
            </a>

            <a class="master-shortcut" href="<?= $basePath ?>/master/criterios-arquivados">
                <i class="icon fas fa-archive"></i>
                <div class="info">
                    <strong>Critérios arquivados</strong>
                    <small>Restaurar avaliações apagadas</small>
                </div>
            </a>

            <a class="master-shortcut red" href="<?= $basePath ?>/master/lgpd">
                <i class="icon fas fa-shield-halved"></i>
                <div class="info">
                    <strong>Solicitações LGPD</strong>
                    <small>Exportações e exclusões de conta</small>
                </div>
            </a>

            <a class="master-shortcut green" href="<?= $basePath ?>/master/backup">
                <i class="icon fas fa-database"></i>
                <div class="info">
                    <strong>Backup</strong>
                    <small>Gerar e baixar cópias do banco</small>
                </div>
            </a>

            <a class="master-shortcut" href="<?= $basePath ?>/usuarios">
                <i class="icon fas fa-users-cog"></i>
                <div class="info">
                    <strong>Gerenciar usuários</strong>
                    <small>Perfis e permissões</small>
                </div>
            </a>

            <a class="master-shortcut" href="<?= $basePath ?>/master/configuracoes">
                <i class="icon fas fa-sliders"></i>
                <div class="info">
                    <strong>Configurações do sistema</strong>
                    <small>Informações técnicas</small>
                </div>
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>