<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Relatórios</h2>

<ul>
    <li>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/geral">
            <i class="fas fa-table"></i> Relatório Geral do Projeto
        </a>
        — alunos × critérios com ranking.
    </li>
    <?php if (!empty($meuBoletim)): ?>
        <li>
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/boletim">
                <i class="fas fa-file-lines"></i> Meu Boletim
            </a>
        </li>
    <?php endif; ?>
</ul>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>