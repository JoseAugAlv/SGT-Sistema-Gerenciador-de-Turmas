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
            Configuração de avaliação
        </div>
        <h1><?= h($grupo['nome']) ?></h1>
        <p class="hero-subtitle">
            Projeto: <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar ao grupo
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/config-avaliacao" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Modo do grupo *</label>
    <select name="modo_avaliacao_grupo" class="field" required>
        <?php foreach ($modosGrupo as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $grupo['modo_avaliacao_grupo'] ? 'selected' : '' ?>>
                <?= h($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small class="field-help">
        <i class="fas fa-info-circle"></i>
        <strong>Individual</strong>: cada aluno recebe nota própria.
        <strong>Coletiva</strong>: o grupo recebe uma nota única.
    </small>

    <label class="field-label mt-16">Modo de avaliação padrão *</label>
    <select name="modo_avaliacao_por" class="field" required>
        <?php foreach ($modosPor as $val => $label): ?>
            <option value="<?= h($val) ?>" <?= $val === $grupo['modo_avaliacao_por'] ? 'selected' : '' ?>>
                <?= h($label) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <div class="notice notice-warning mt-16">
        <span>!</span>
        <div>
            <strong>Estes modos são apenas valores padrão</strong>
            <p>
                Eles pré-preenchem o formulário quando um novo critério é criado neste grupo.
                Não há herança em tempo de execução — cada critério tem seu próprio
                <code>tipo_avaliacao</code> obrigatório, e é ele quem manda.
            </p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar configuração
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>