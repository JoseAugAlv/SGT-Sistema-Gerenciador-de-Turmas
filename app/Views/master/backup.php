<?php
$tituloPagina = 'Backup — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$backups = $backups ?? [];
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-database"></i> Backup do Banco</h1>
                <p class="master-subtitle">
                    <i class="fas fa-shield-halved"></i>
                    Gere cópias de segurança completas do banco de dados.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-file-zipper"></i> <?= count($backups) ?> arquivo(s)
            </span>
        </header>

        <!-- ============ AÇÃO GERAR ============ -->
        <div class="master-box box-primary">
            <div class="box-actions">
                <span class="box-info-text">
                    <i class="fas fa-circle-info"></i>
                    O backup inclui todas as tabelas, rotinas e triggers do banco.
                </span>
                <form method="POST" action="<?= $basePath ?>/master/backup/gerar">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="btn-new"
                            onclick="return confirm('Gerar novo backup agora? Isso pode levar alguns segundos.')">
                        <i class="fas fa-download"></i> Gerar novo backup
                    </button>
                </form>
            </div>
        </div>

        <!-- ============ LISTA ============ -->
        <?php if (empty($backups)): ?>
            <div class="empty-state">
                <i class="fas fa-database"></i>
                <h3>Nenhum backup gerado</h3>
                <p>Clique em "Gerar novo backup" acima para criar a primeira cópia de segurança.</p>
            </div>
        <?php else: ?>
            <div class="master-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th>Tamanho</th>
                            <th>Data</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td>
                                    <strong><?= h($b['nome']) ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $kb = $b['tamanho'] / 1024;
                                    echo $kb >= 1024
                                        ? number_format($kb / 1024, 2, ',', '.') . ' MB'
                                        : number_format($kb, 2, ',', '.') . ' KB';
                                    ?>
                                </td>
                                <td><?= h($b['data']) ?></td>
                                <td style="text-align:right; white-space:nowrap;">
                                    <a class="btn btn-sm btn-edit"
                                       href="<?= $basePath ?>/master/backup/<?= urlencode($b['nome']) ?>/baixar">
                                        <i class="fas fa-download"></i> Baixar
                                    </a>
                                    <form method="POST"
                                          action="<?= $basePath ?>/master/backup/<?= urlencode($b['nome']) ?>/excluir"
                                          style="display:inline-block; margin-left:6px;">
                                        <?= ViewHelper::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Excluir este backup permanentemente?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="master-voltar">
            <a class="btn" href="<?= $basePath ?>/master"
               style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700;">
                <i class="fas fa-arrow-left"></i> Voltar ao painel
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>