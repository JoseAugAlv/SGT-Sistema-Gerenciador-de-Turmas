<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Notificações <?php if ($naoLidas > 0): ?>(<?= $naoLidas ?> não lida<?= $naoLidas > 1 ? 's' : '' ?>)<?php endif; ?></h1>

<p>
    <a href="<?= $basePath ?>/notificacoes">Todas</a>
    —
    <a href="<?= $basePath ?>/notificacoes?filtro=nao_lidas">Não lidas</a>
    —
    <a href="<?= $basePath ?>/notificacoes/preferencias">Preferências de email</a>

    <?php if ($naoLidas > 0): ?>
        —
        <form method="POST" action="<?= $basePath ?>/notificacoes/marcar-todas-lidas" style="display:inline">
            <?= ViewHelper::csrfField() ?>
            <button type="submit"><i class="fas fa-check-double"></i> Marcar todas como lidas</button>
        </form>
    <?php endif; ?>
</p>

<?php if (empty($notificacoes)): ?>
    <p>Nenhuma notificação.</p>
<?php else: ?>
    <ul>
        <?php foreach ($notificacoes as $n): ?>
            <li style="border:1px solid <?= $n['lida'] ? '#eee' : '#10b981' ?>;padding:10px;margin:6px 0;border-radius:6px;">
                <p>
                    <strong><?= h($n['titulo']) ?></strong>
                    <small>[<?= h($n['tipo']) ?>] — <?= h($n['created_at']) ?></small>
                    <?php if (!$n['lida']): ?>
                        <span style="background:#10b981;color:#fff;padding:2px 8px;border-radius:4px;font-size:0.8em;">nova</span>
                    <?php endif; ?>
                </p>
                <p><?= nl2br(h($n['mensagem'])) ?></p>
                <p>
                    <?php if (!empty($n['link'])): ?>
                        <a href="<?= $basePath . h($n['link']) ?>">Abrir</a> —
                    <?php endif; ?>

                    <?php if (!$n['lida']): ?>
                        <form method="POST" action="<?= $basePath ?>/notificacoes/marcar-lida" style="display:inline">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                            <input type="hidden" name="voltar" value="<?= h($_SERVER['REQUEST_URI'] ?? '/notificacoes') ?>">
                            <button type="submit">Marcar como lida</button>
                        </form>
                    <?php endif; ?>
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>