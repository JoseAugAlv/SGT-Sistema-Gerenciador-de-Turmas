<?php if (empty($ultimas)): ?>
    <p>Nenhuma notificação.</p>
<?php else: ?>
    <ul style="list-style:none;padding:0;margin:0;max-height:400px;overflow:auto;">
        <?php foreach ($ultimas as $n): ?>
            <li style="border-bottom:1px solid #eee;padding:8px;">
                <p style="margin:0 0 4px;">
                    <strong><?= h($n['titulo']) ?></strong>
                    <?php if (!$n['lida']): ?>
                        <span style="background:#10b981;color:#fff;padding:1px 6px;border-radius:3px;font-size:0.7em;">nova</span>
                    <?php endif; ?>
                </p>
                <small><?= h(substr($n['mensagem'], 0, 80)) ?><?= strlen($n['mensagem']) > 80 ? '…' : '' ?></small>
                <br>
                <small><?= h($n['created_at']) ?></small>
            </li>
        <?php endforeach; ?>
    </ul>
    <p style="text-align:center;padding:8px;">
        <a href="<?= $basePath ?>/notificacoes">Ver todas</a>
    </p>
<?php endif; ?>