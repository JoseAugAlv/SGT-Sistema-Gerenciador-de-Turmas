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
            Editar projeto
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            Turma: <strong><?= h($projeto['turma_nome']) ?></strong>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atualizar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome *</label>
    <input type="text" name="nome" class="field" maxlength="150"
           value="<?= h($projeto['nome']) ?>" required>

    <label class="field-label">Descrição</label>
    <textarea name="descricao" class="field" rows="3" maxlength="2000"><?= h($projeto['descricao'] ?? '') ?></textarea>

    <label class="field-label">Prazo</label>
    <input type="date" name="prazo" class="field" value="<?= h($projeto['prazo'] ?? '') ?>">

    <label class="field-label">Modo de avaliação *</label>
    <select name="modo_avaliacao" class="field" required>
        <?php foreach ($modos as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $projeto['modo_avaliacao'] ? 'selected' : '' ?>>
                <?= h($label) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <p style="margin-top:20px;">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar alterações
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>