<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>">
    <?= h($projeto['turma_nome']) ?>
</a></p>

<?php require __DIR__ . '/_nav.php'; ?>

<h2><?= h($tituloSecao) ?></h2>

<p>
    <i class="fas fa-screwdriver-wrench"></i>
    Esta seção ainda está em desenvolvimento e será entregue em uma fase futura.
</p>

<p><a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
    <i class="fas fa-arrow-left"></i> Voltar às etapas
</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>