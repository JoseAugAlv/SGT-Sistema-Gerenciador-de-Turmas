<?php
$cssPagina = ['projetos.css', 'criterios.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Reabrir critério
        </div>
        <h1><?= h($criterio['nome']) ?></h1>
        <p class="hero-subtitle">
            Projeto: <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<div class="crit-reabrir-info">
    <i class="fas fa-info-circle"></i>
    <div>
        <strong>Novo prazo é obrigatório</strong>
        <p class="mb-0">
            É preciso informar uma nova data/hora futura. Sem isso, o critério voltaria a ficar bloqueado no próximo acesso.
        </p>
    </div>
</div>

<section class="panel mb-16">
    <div class="crit-detalhes">
        <div class="crit-detalhe-linha">
            <span class="label">Status atual</span>
            <span class="valor"><?= $criterio['bloqueado'] ? 'Bloqueado' : 'Aberto' ?></span>
        </div>
        <div class="crit-detalhe-linha">
            <span class="label">Prazo anterior</span>
            <span class="valor"><?= ViewHelper::data($criterio['prazo_avaliacao'] ?? null, true) ?></span>
        </div>
        <div class="crit-detalhe-linha">
            <span class="label">Reaberto anteriormente em</span>
            <span class="valor"><?= ViewHelper::data($criterio['reaberto_em'] ?? null, true) ?></span>
        </div>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/criterios/<?= (int) $criterio['id'] ?>/reabrir" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Novo prazo de avaliação *</label>
    <input type="datetime-local" name="novo_prazo" class="field" min="<?= date('Y-m-d\TH:i') ?>" required autofocus>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-rotate"></i> Reabrir critério
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>