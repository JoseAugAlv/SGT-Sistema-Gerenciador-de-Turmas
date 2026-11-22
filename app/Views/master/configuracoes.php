<?php
$tituloPagina = 'Configurações do Sistema — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$info = $info ?? [];
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-sliders"></i> Configurações do Sistema</h1>
                <p class="master-subtitle">
                    <i class="fas fa-circle-info"></i>
                    Informações técnicas do ambiente em execução.
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-gear"></i> Ambiente
            </span>
        </header>

        <!-- ============ INFO TÉCNICA ============ -->
        <h2 style="font-size:15px; font-weight:750; margin:8px 0 0; color: var(--text);">
            <i class="fas fa-server" style="color: var(--primary);"></i> Informações técnicas
        </h2>

        <div class="master-info-grid">
            <div class="master-info-item">
                <span class="label">Versão do PHP</span>
                <span class="valor"><?= h($info['php'] ?? '—') ?></span>
            </div>

            <div class="master-info-item">
                <span class="label">MySQL / MariaDB</span>
                <span class="valor"><?= h($info['mysql'] ?? '—') ?></span>
            </div>

            <div class="master-info-item">
                <span class="label">Ambiente</span>
                <span class="valor">
                    <?php
                    $env = strtolower($info['app_env'] ?? 'production');
                    $cor = $env === 'production' ? 'var(--green)' : 'var(--orange)';
                    ?>
                    <span style="color: <?= $cor ?>; font-weight:750;">
                        <?= h(ucfirst($env)) ?>
                    </span>
                </span>
            </div>

            <div class="master-info-item">
                <span class="label">URL</span>
                <span class="valor" style="font-size:12.5px;"><?= h($info['app_url'] ?? '—') ?></span>
            </div>

            <div class="master-info-item">
                <span class="label">Base Path</span>
                <span class="valor" style="font-family:monospace;"><?= h($info['base_path'] ?? '—') ?></span>
            </div>
        </div>

        <!-- ============ ALERTAS DE AMBIENTE ============ -->
        <?php if (($info['app_env'] ?? '') !== 'production'): ?>
            <div class="master-box" style="border-left:4px solid var(--orange);">
                <div class="box-actions">
                    <span class="box-info-text" style="color:var(--text);">
                        <i class="fas fa-triangle-exclamation" style="color: var(--orange);"></i>
                        <strong>Modo desenvolvimento.</strong>
                        Erros detalhados estão visíveis nas páginas. Em produção isso deve ser desativado.
                    </span>
                </div>
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