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
            Novo grupo
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            Defina o nome e os modos do grupo. Depois você adiciona membros e diretores.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome do grupo *</label>
    <input type="text" name="nome" class="field" maxlength="150" required autofocus>

    <label class="field-label">Modo do grupo *</label>
    <select name="modo_avaliacao_grupo" class="field" required>
        <?php foreach ($modosGrupo as $val => $label): ?>
            <option value="<?= h($val) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
    </select>

    <label class="field-label">Modo de avaliação padrão *</label>
    <select name="modo_avaliacao_por" class="field" required>
        <?php foreach ($modosPor as $val => $label): ?>
            <option value="<?= h($val) ?>"><?= h($label) ?></option>
        <?php endforeach; ?>
    </select>

    <div class="notice notice-blue">
        <span>i</span>
        <div>
            <strong>Estes modos são apenas padrão de UI</strong>
            <p>
                Cada critério define seu próprio <code>tipo_avaliacao</code> e é ele quem
                determina como o critério é avaliado. O que você escolhe aqui só pré-preenche
                o formulário de novos critérios.
            </p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Criar grupo
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>