<?php
// app/Views/master/erros.php
// Master: erros registrados no servidor (o usuário só vê a página amigável).
$cssPagina = ['projetos.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Master</div>
        <h1>Erros do sistema</h1>
        <p class="hero-subtitle">O que deu errado no servidor, do mais recente para o mais antigo. Repetições aparecem agrupadas.</p>
    </div>
    <?php if ($erros): ?>
    <div class="hero-actions">
        <form method="POST" action="<?= $basePath ?>/master/erros/limpar" onsubmit="return confirm('Limpar o registro de erros?')">
            <?= ViewHelper::csrfField() ?>
            <button type="submit" class="button button-ghost"><i class="fas fa-broom"></i> Limpar registro</button>
        </form>
    </div>
    <?php endif; ?>
</section>

<?php if (!$erros): ?>
    <section class="panel empty-state"><i class="fas fa-circle-check"></i><p>Nenhum erro registrado. Tudo certo por aqui.</p></section>
<?php else: ?>
    <section class="panel">
        <ul class="erros-lista">
            <?php foreach ($erros as $er): ?>
                <li>
                    <div class="erros-topo">
                        <span class="erros-vezes"><?= (int) $er['vezes'] ?>×</span>
                        <small>última vez: <?= h($er['ultima']) ?></small>
                    </div>
                    <p class="erros-msg"><?= h($er['msg']) ?></p>
                    <?php if ($er['detalhe']): ?>
                        <details><summary>Detalhes</summary><pre><?= h(mb_strimwidth($er['detalhe'], 0, 4000, '…')) ?></pre></details>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<style>
.erros-lista { list-style: none; margin: 0; padding: 0; }
.erros-lista li { padding: 14px 0; border-bottom: 1px solid var(--border, #e5e7eb); }
.erros-lista li:last-child { border-bottom: 0; }
.erros-topo { display: flex; align-items: center; gap: 10px; }
.erros-vezes { min-width: 40px; text-align: center; padding: 2px 8px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-weight: 700; font-size: 13px; }
.erros-msg { margin: 6px 0 0; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: 13px; word-break: break-word; }
.erros-lista pre { white-space: pre-wrap; word-break: break-word; font-size: 12px; max-height: 320px; overflow: auto; background: var(--surface-2, #f5f7fb); padding: 10px; border-radius: 8px; }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
