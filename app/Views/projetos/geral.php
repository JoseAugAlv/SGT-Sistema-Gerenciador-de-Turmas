<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Projetos</h1>
        <p class="hero-subtitle">
            <?= $isMaster ? 'Projetos de todas as turmas.' : 'Projetos das turmas em que você participa.' ?>
        </p>
    </div>
</section>

<?php if (empty($projetosPorTurma)): ?>
    <section class="panel">
        <p style="color:var(--muted); text-align:center; padding:24px 0;">
            Você ainda não está em nenhuma turma.
        </p>
        <p style="text-align:center;">
            <a class="button button-primary" href="<?= $basePath ?>/turmas">
                <i class="fas fa-users"></i> Ver minhas turmas
            </a>
        </p>
    </section>
<?php else: ?>

    <?php foreach ($projetosPorTurma as $bloco): ?>
        <?php $turma = $bloco['turma']; $projetos = $bloco['projetos']; ?>

        <section class="section-heading">
            <div>
                <div class="eyebrow">Turma</div>
                <h2><?= h($turma['nome']) ?></h2>
                <p>
                    Código: <code><?= h($turma['codigo_acesso']) ?></code>
                    — <?= count($projetos) ?> projeto(s)
                </p>
            </div>
            <a class="button button-secondary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
                <i class="fas fa-arrow-right"></i> Abrir turma
            </a>
        </section>

        <?php if (empty($projetos)): ?>
            <section class="panel">
                <p style="color:var(--muted);">Nenhum projeto nesta turma.</p>
            </section>
        <?php else: ?>
            <section class="component-grid">
                <?php foreach ($projetos as $p): ?>
                    <article class="panel">
                        <div class="panel-header">
                            <div>
                                <h2><?= h($p['nome']) ?></h2>
                                <p>
                                    Modo: <code><?= h($p['modo_avaliacao']) ?></code>
                                    — <?= (int) $p['total_etapas'] ?> etapa(s)
                                </p>
                            </div>
                            <?php if ($p['encerrado']): ?>
                                <span class="badge badge-red">Encerrado</span>
                            <?php else: ?>
                                <span class="badge badge-green">Em andamento</span>
                            <?php endif; ?>
                        </div>

                        <div class="status-line">
                            <strong>Prazo:</strong>
                            <span><?= h($p['prazo'] ?? 'sem prazo') ?></span>
                        </div>

                        <a class="button button-primary full-width" href="<?= $basePath ?>/projetos/<?= (int) $p['id'] ?>">
                            <i class="fas fa-arrow-right"></i> Abrir projeto
                        </a>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

    <?php endforeach; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>