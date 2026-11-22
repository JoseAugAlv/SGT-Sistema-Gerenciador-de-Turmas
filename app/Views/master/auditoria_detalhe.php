<?php
$tituloPagina = 'Detalhe da Auditoria #' . (int) $log['id'] . ' — ' . App::getName();
$cssPagina    = 'master.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

/**
 * Formata um JSON de auditoria para leitura humana.
 */
function formatarJsonAuditoria(?string $json): string
{
    if (empty($json)) return '(vazio)';

    $dados = json_decode($json, true);
    if ($dados === null) return $json;

    return json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>

<section class="master-section">
    <div class="master-content">

        <header class="master-hero">
            <div>
                <h1><i class="fas fa-clipboard-list"></i> Registro #<?= (int) $log['id'] ?></h1>
                <p class="master-subtitle">
                    <i class="far fa-clock"></i>
                    <?= h($log['created_at']) ?>
                </p>
            </div>
            <span class="badge-count">
                <i class="fas fa-shield-halved"></i>
                <?= h($log['acao']) ?>
            </span>
        </header>

        <!-- ============ INFO GERAL ============ -->
        <div class="master-info-grid">
            <div class="master-info-item">
                <span class="label">Usuário</span>
                <span class="valor">
                    <?= h($log['usuario_nome'] ?? '—') ?>
                    <?php if (!empty($log['usuario_email'])): ?>
                        <small style="display:block; font-size:11.5px; color:var(--muted); font-weight:400; margin-top:2px;">
                            <?= h($log['usuario_email']) ?>
                        </small>
                    <?php endif; ?>
                </span>
            </div>

            <div class="master-info-item">
                <span class="label">Ação</span>
                <span class="valor"><?= h($log['acao']) ?></span>
            </div>

            <div class="master-info-item">
                <span class="label">Tabela afetada</span>
                <span class="valor"><?= h($log['tabela_afetada'] ?? '—') ?></span>
            </div>

            <div class="master-info-item">
                <span class="label">Registro ID</span>
                <span class="valor">
                    <?= $log['registro_id'] !== null ? '#' . (int) $log['registro_id'] : '—' ?>
                </span>
            </div>

            <div class="master-info-item">
                <span class="label">IP</span>
                <span class="valor"><?= h($log['ip'] ?? '—') ?></span>
            </div>

            <div class="master-info-item">
                <span class="label">Data / hora</span>
                <span class="valor"><?= h($log['created_at']) ?></span>
            </div>
        </div>

        <!-- ============ USER-AGENT ============ -->
        <?php if (!empty($log['user_agent'])): ?>
            <div class="master-info-item" style="padding:14px 16px;">
                <span class="label">User-Agent</span>
                <span class="valor" style="font-size:12px; font-weight:400; line-height:1.5; word-break:break-all;">
                    <?= h($log['user_agent']) ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- ============ DADOS ANTERIORES ============ -->
        <h2 style="font-size:14px; font-weight:750; margin:8px 0 0; color: var(--text);">
            <i class="fas fa-clock-rotate-left" style="color: var(--orange);"></i>
            Dados anteriores
        </h2>
        <pre class="master-pre"><?= h(formatarJsonAuditoria($log['dados_anteriores'] ?? null)) ?></pre>

        <!-- ============ DADOS NOVOS ============ -->
        <h2 style="font-size:14px; font-weight:750; margin:8px 0 0; color: var(--text);">
            <i class="fas fa-circle-check" style="color: var(--green);"></i>
            Dados novos
        </h2>
        <pre class="master-pre"><?= h(formatarJsonAuditoria($log['dados_novos'] ?? null)) ?></pre>

        <div class="master-voltar">
            <a class="btn" href="<?= $basePath ?>/master/auditoria"
               style="padding:9px 14px; border-radius:9px; border:1px solid var(--border); color:var(--muted); text-decoration:none; font-size:13px; font-weight:700;">
                <i class="fas fa-arrow-left"></i> Voltar à auditoria
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>