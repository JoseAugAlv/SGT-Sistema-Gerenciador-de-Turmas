<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Painel</h1>
<p>Olá, <strong><?= h($usuario['nome']) ?></strong>!</p>
<p>Tipo: <strong><?= h($usuario['tipo']) ?></strong></p>
<p>Email: <?= h($usuario['email']) ?></p>

<h2>Próximas funcionalidades</h2>
<ul>
    <li>Turmas (FASE 2)</li>
    <li>Projetos e Etapas (FASE 3)</li>
    <li>Grupos e Diretores (FASE 4)</li>
    <li>Critérios (FASE 5)</li>
    <li>Avaliações (FASE 6)</li>
    <li>...</li>
</ul>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>