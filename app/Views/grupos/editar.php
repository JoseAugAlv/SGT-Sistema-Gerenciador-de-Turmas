<?php
$cssPagina = ['projetos.css', 'grupos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Editar grupo
        </div>
        <h1><?= h($grupo['nome']) ?></h1>
        <p class="hero-subtitle">
            Projeto: <strong><?= h($projeto['nome']) ?></strong>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/atualizar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome do grupo *</label>
    <input type="text" name="nome" class="field" maxlength="150"
           value="<?= h($grupo['nome']) ?>" required>

    <label class="field-label">Modo do grupo *</label>
    <select name="modo_avaliacao_grupo" class="field" required>
        <?php foreach ($modosGrupo as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $grupo['modo_avaliacao_grupo'] ? 'selected' : '' ?>>
                <?= h($label) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="field-label">Modo de avaliação padrão *</label>
    <select name="modo_avaliacao_por" class="field" required>
        <?php foreach ($modosPor as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $grupo['modo_avaliacao_por'] ? 'selected' : '' ?>>
                <?= h($label) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar alterações
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>