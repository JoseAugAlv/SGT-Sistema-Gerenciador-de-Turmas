<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Avaliação por Diretor</h2>
<p>Em desenvolvimento (chega no próximo bloco).</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>