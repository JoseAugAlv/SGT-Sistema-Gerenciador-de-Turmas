<?php
$cssPagina = ['projetos.css', 'grupos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Diretor
        </div>
        <h1>Meus Grupos</h1>
        <p class="hero-subtitle">
            Grupos que você dirige atualmente.
        </p>
    </div>
</section>

<?php if (empty($grupos)): ?>
    <section class="panel empty-state">
        <span class="icon-large text-muted"><i class="fas fa-crown"></i></span>
        <h2>Você não é diretor de nenhum grupo</h2>
        <p>
            Quando o representante da turma nomear você como diretor de um grupo,
            ele aparece aqui.
        </p>
        <a class="button button-primary" href="<?= $basePath ?>/turmas">
            <i class="fas fa-users"></i> Ver minhas turmas
        </a>
    </section>
<?php else: ?>
    <section class="grupos-grid">
        <?php foreach ($grupos as $g): ?>
            <article class="panel grupo-card">
                <div class="panel-header">
                    <div>
                        <h2><?= h($g['grupo_nome']) ?></h2>
                        <p>
                            <a href="<?= $basePath ?>/projetos/<?= (int) $g['projeto_id'] ?>">
                                <?= h($g['projeto_nome']) ?>
                            </a>
                        </p>
                    </div>
                    <?php if ($g['encerrado']): ?>
                        <span class="badge badge-red"><i class="fas fa-lock"></i> Encerrado</span>
                    <?php else: ?>
                        <span class="badge badge-green"><i class="fas fa-play"></i> Em andamento</span>
                    <?php endif; ?>
                </div>

                <div class="status-line">
                    <strong>Turma:</strong>
                    <span><?= h($g['turma_nome']) ?></span>
                </div>

                <div class="grupo-acoes">
                    <a class="button button-primary full-width" href="<?= $basePath ?>/grupos/<?= (int) $g['id'] ?>">
                        <i class="fas fa-arrow-right"></i> Abrir grupo
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>