<?php
$cssPagina = ['master.css', 'relatorios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$total    = count($usuarios ?? []);
$ativos   = count(array_filter($usuarios ?? [], fn($u) => !empty($u['ativo'])));
$inativos = $total - $ativos;
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Relatório
        </div>
        <h1>Usuários do Sistema</h1>
        <p class="hero-subtitle">
            <?= $total ?> usuário(s) cadastrado(s) — <?= $ativos ?> ativo(s)
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<div class="relatorio-toolbar">
    <span class="toolbar-label"><i class="fas fa-file-export"></i> Exportar</span>
    <div class="toolbar-actions">
        <a class="button small button-ghost" href="<?= $basePath ?>/relatorios/pdf">
            <i class="fas fa-file-pdf"></i> PDF
        </a>
        <a class="button small button-ghost" href="<?= $basePath ?>/relatorios/exportar">
            <i class="fas fa-file-excel"></i> Excel
        </a>
    </div>
</div>

<section class="projeto-resumo-grid">
    <div class="projeto-resumo-card">
        <span class="label">Total</span>
        <span class="valor"><?= $total ?></span>
        <span class="foot">usuários</span>
    </div>
    <div class="projeto-resumo-card" style="border-left-color:#29946e;">
        <span class="label">Ativos</span>
        <span class="valor" style="color:#29946e;"><?= $ativos ?></span>
        <span class="foot">acesso liberado</span>
    </div>
    <div class="projeto-resumo-card" style="border-left-color:var(--red);">
        <span class="label">Inativos</span>
        <span class="valor" style="color:var(--red);"><?= $inativos ?></span>
        <span class="foot">acesso bloqueado</span>
    </div>
</section>

<?php if (empty($usuarios)): ?>
    <section class="panel rel-empty">
        <span class="icon-large text-muted"><i class="fas fa-users"></i></span>
        <h2>Nenhum usuário</h2>
        <p>Não há usuários cadastrados no sistema.</p>
    </section>
<?php else: ?>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Lista completa</h2>
                <p>Todos os usuários cadastrados.</p>
            </div>
        </div>

        <div class="rel-wrapper">
            <table class="rel-tabela">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Perfil</th>
                        <th style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><strong>#<?= (int) $u['id_usuario'] ?></strong></td>
                            <td><?= h($u['nome']) ?></td>
                            <td><?= h($u['email']) ?></td>
                            <td><?= h($u['nome_perfil'] ?? 'Usuario') ?></td>
                            <td class="td-center">
                                <?php if (!empty($u['ativo'])): ?>
                                    <span class="badge badge-green"><i class="fas fa-check"></i> Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-red"><i class="fas fa-ban"></i> Inativo</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>