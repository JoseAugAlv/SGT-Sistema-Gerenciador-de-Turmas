<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Nova turma
        </div>
        <h1>Entrar em uma Turma</h1>
        <p class="hero-subtitle">
            Informe o código de acesso fornecido pelo professor ou representante.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas">
            <i class="fas fa-arrow-left"></i> Minhas turmas
        </a>
    </div>
</section>

<section class="panel form-codigo-acesso">
    <form method="POST" action="<?= $basePath ?>/turmas/entrar">
        <?= ViewHelper::csrfField() ?>

        <label class="field-label">Código de acesso *</label>
        <input type="text" name="codigo_acesso" class="field" required autofocus
               maxlength="30" placeholder="Ex: Info2026">

        <p class="mt-20">
            <button type="submit" class="button button-primary full-width">
                <i class="fas fa-right-to-bracket"></i> Entrar na turma
            </button>
        </p>
    </form>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>