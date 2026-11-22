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
            Novo projeto
        </div>
        <h1>Novo projeto</h1>
        <p class="hero-subtitle">
            Turma: <strong><?= h($turma['nome']) ?></strong>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/projetos/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>
    <input type="hidden" name="turma_id" value="<?= (int) $turma['id'] ?>">

    <label class="field-label">Nome *</label>
    <input type="text" name="nome" class="field" maxlength="150" required autofocus>

    <label class="field-label">Descrição</label>
    <textarea name="descricao" class="field" rows="3" maxlength="2000"></textarea>

    <label class="field-label">Prazo</label>
    <input type="date" name="prazo" class="field">

    <label class="field-label">Modo de avaliação *</label>
    <select name="modo_avaliacao" class="field" required>
        <?php foreach ($modos as $val => $label): ?>
            <option value="<?= h($val) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
    </select>

    <p class="field-help">Depois de criar, o representante da turma cadastra os critérios de avaliação.</p>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Criar projeto
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
