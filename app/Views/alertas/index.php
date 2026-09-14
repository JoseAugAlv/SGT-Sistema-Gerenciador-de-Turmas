<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Alertas da Turma</h1>

<?php if ($isRep || $isMaster): ?>
    <p>
        <a href="<?= $basePath ?>/turmas/<?= (int) $turmaId ?>/alertas/criar">
            <i class="fas fa-plus"></i> Novo alerta
        </a>
    </p>
<?php endif; ?>

<?php if (empty($alertas)): ?>
    <p>Nenhum alerta ativo.</p>
<?php else: ?>
    <?php foreach ($alertas as $a): ?>
        <div style="border:1px solid <?= $a['urgente'] ? '#b00' : '#ccc' ?>;padding:12px;margin:8px 0;border-radius:6px;">
            <p>
                <?php if ($a['urgente']): ?>
                    <strong style="color:#b00;"><i class="fas fa-exclamation-triangle"></i> URGENTE</strong>
                <?php endif; ?>
                <strong><?= h($a['titulo']) ?></strong>
                <br>
                <small>
                    Por <?= h($a['autor_nome'] ?? '—') ?>
                    em <?= h($a['created_at']) ?>
                    <?php if ($a['expira_em']): ?>
                        — expira em <?= h($a['expira_em']) ?>
                    <?php endif; ?>
                </small>
            </p>
            <p><?= nl2br(h($a['mensagem'])) ?></p>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<p><a href="<?= $basePath ?>/turmas/<?= (int) $turmaId ?>">
    <i class="fas fa-arrow-left"></i> Voltar à turma
</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>