<?php
// app/Views/alertas/index.php
// Mural de avisos da turma/grêmio: aviso do dia, contagens e avisos comuns.
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$hoje = date('Y-m-d');
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Avisos</div>
        <h1>Avisos</h1>
        <p class="hero-subtitle"><?= h($turma['nome'] ?? '') ?> · <?= count($alertas) ?> aviso(s) ativo(s)</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turmaId ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
        <?php if ($isRep || $isMaster): ?>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/<?= (int) $turmaId ?>/alertas/criar"><i class="fas fa-plus"></i> Novo aviso</a>
        <?php endif; ?>
    </div>
</section>

<?php if (empty($alertas)): ?>
    <div class="lista-vazia"><i class="fas fa-bullhorn"></i><span>Nenhum aviso ativo.</span></div>
<?php else: ?>
    <div class="contagens">
        <?php foreach ($alertas as $a):
            $ehDia = ($a['do_dia'] ?? null) !== null;
            $temContagem = !empty($a['contagem_ate']);
        ?>
            <article class="<?= $temContagem ? 'contagem' : ($ehDia ? 'aviso-dia' : 'mural') ?><?= $a['urgente'] ? ' urgente' : '' ?>">
                <div class="contagem-texto">
                    <span class="contagem-rotulo">
                        <?php if ($a['urgente']): ?><i class="fas fa-triangle-exclamation"></i> Urgente ·<?php endif; ?>
                        <?= $temContagem ? '<i class="fas fa-hourglass-half"></i> Contagem regressiva' : ($ehDia ? '<i class="fas fa-sun"></i> Aviso do dia ' . ($a['do_dia'] === $hoje ? '(hoje)' : ViewHelper::data($a['do_dia'])) : '<i class="fas fa-bullhorn"></i> Aviso') ?>
                    </span>
                    <strong><?= h($a['titulo']) ?></strong>
                    <p class="mb-0"><?= nl2br(h($a['mensagem'])) ?></p>
                    <small>Por <?= h($a['autor_nome'] ?? '—') ?> · <?= ViewHelper::data($a['created_at'], true) ?><?= $a['expira_em'] && !$ehDia ? ' · some em ' . ViewHelper::data($a['expira_em'], true) : '' ?></small>
                </div>
                <?php if ($temContagem) { $alvoMs = strtotime($a['contagem_ate']) * 1000; require __DIR__ . '/_relogio.php'; } ?>
            </article>
        <?php endforeach; ?>
    </div>
    <script src="<?= ViewHelper::asset('js/contagem.js') ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
