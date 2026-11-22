<?php
$cssPagina = 'projetos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Projeto
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            Turma:
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>">
                <?= h($projeto['turma_nome']) ?>
            </a>
        </p>
    </div>
</section>

<?php require __DIR__ . '/_nav.php'; ?>

<section class="panel empty-state">
    <span class="icon-large text-muted"><i class="fas fa-screwdriver-wrench"></i></span>
    <h2><?= h($tituloSecao) ?></h2>
    <p>
        Esta seção ainda está em desenvolvimento e será entregue em uma fase futura.
    </p>
    <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
        <i class="fas fa-arrow-left"></i> Voltar às etapas
    </a>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>