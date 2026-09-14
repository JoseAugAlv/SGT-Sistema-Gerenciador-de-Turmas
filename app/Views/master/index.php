<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Painel Master</h1>

<h2>Estatísticas</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Turmas</th>
        <th>Turmas ativas</th>
        <th>Usuários</th>
        <th>Alunos ativos</th>
    </tr>
    <tr>
        <td><?= (int) $stats['turmas'] ?></td>
        <td><?= (int) $stats['turmas_ativas'] ?></td>
        <td><?= (int) $stats['usuarios'] ?></td>
        <td><?= (int) $stats['alunos_ativos'] ?></td>
    </tr>
    <tr>
        <th>Projetos</th>
        <th>Projetos abertos</th>
        <th>Grupos</th>
        <th>Crit. arquivados</th>
    </tr>
    <tr>
        <td><?= (int) $stats['projetos'] ?></td>
        <td><?= (int) $stats['projetos_abertos'] ?></td>
        <td><?= (int) $stats['grupos'] ?></td>
        <td><?= (int) $stats['crit_arquivados'] ?></td>
    </tr>
</table>

<?php if ($stats['lgpd_pendentes'] > 0): ?>
    <p>
        <i class="fas fa-shield-halved"></i>
        <strong><?= (int) $stats['lgpd_pendentes'] ?> solicitação(ões) LGPD pendente(s).</strong>
        <a href="<?= $basePath ?>/master/lgpd">Ver</a>
    </p>
<?php endif; ?>

<h2>Atalhos</h2>
<ul>
    <li><a href="<?= $basePath ?>/master/auditoria"><i class="fas fa-clipboard-list"></i> Auditoria</a></li>
    <li><a href="<?= $basePath ?>/master/criterios-arquivados"><i class="fas fa-archive"></i> Critérios arquivados</a></li>
    <li><a href="<?= $basePath ?>/master/lgpd"><i class="fas fa-shield-halved"></i> Solicitações LGPD</a></li>
    <li><a href="<?= $basePath ?>/master/backup"><i class="fas fa-database"></i> Backup</a></li>
    <li><a href="<?= $basePath ?>/master/configuracoes"><i class="fas fa-gear"></i> Configurações do sistema</a></li>
    <li><a href="<?= $basePath ?>/configuracoes/turmas"><i class="fas fa-cog"></i> Cursos e períodos</a></li>
    <li><a href="<?= $basePath ?>/turmas"><i class="fas fa-users"></i> Gerenciar turmas</a></li>
</ul>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>