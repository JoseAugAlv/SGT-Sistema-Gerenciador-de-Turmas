<?php
// app/Views/operador/atividades.php

$tituloPagina = 'Minhas Atividades - ' . App::getName();
$cssPagina = 'home.css';
$basePath = App::getBasePath();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<section class="section-block animate-in">
    <div class="container">
        <h1><i class="fas fa-clipboard-list"></i> Minhas Atividades</h1>
        <p style="color: var(--color-text);">Aqui você pode visualizar suas atividades e tarefas.</p>

        <div style="margin-top: 2rem; background: white; padding: 2rem; border-radius: 12px; box-shadow: var(--shadow-sm); text-align: center;">
            <i class="fas fa-tasks" style="font-size: 3rem; color: var(--color-text-light);"></i>
            <h3 style="margin-top: 1rem;">Em desenvolvimento</h3>
            <p style="color: var(--color-text-light);">Esta página está em construção.</p>
            <a href="<?= $basePath ?>/" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>