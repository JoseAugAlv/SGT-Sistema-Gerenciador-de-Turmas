<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<section class="panel turma-bloqueada-hero">
    <span class="icon-ban"><i class="fas fa-ban"></i></span>
    <h1>Turma bloqueada</h1>
    <p class="hero-subtitle"><?= h($turma['nome']) ?></p>
</section>

<section class="panel panel-center mt-16">
    <h2>Contate o administrador</h2>
    <p class="text-muted">
        <?= h($turma['bloqueada_motivo'] ?? 'O acesso a esta turma está temporariamente suspenso.') ?>
    </p>

    <?php if (!empty($turma['bloqueada_em'])): ?>
        <p class="text-muted">
            <small>Bloqueada em <?= h($turma['bloqueada_em']) ?></small>
        </p>
    <?php endif; ?>

    <p class="mt-20">
        <a class="button button-primary" href="<?= $basePath ?>/turmas">
            <i class="fas fa-arrow-left"></i> Ver minhas outras turmas
        </a>
    </p>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>