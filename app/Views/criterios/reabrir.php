<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Reabrir Critério</h2>

<p>Critério: <strong><?= h($criterio['nome']) ?></strong></p>
<p>Prazo anterior: <?= h($criterio['prazo_avaliacao'] ?? '—') ?></p>
<p>Reaberto anteriormente em: <?= h($criterio['reaberto_em'] ?? '—') ?></p>

<p>
    <i class="fas fa-info-circle"></i>
    É obrigatório informar um novo prazo no futuro. Sem ele, o critério voltaria a ser bloqueado na próxima leitura.
</p>

<form method="POST" action="<?= $basePath ?>/criterios/<?= (int) $criterio['id'] ?>/reabrir">
    <?= ViewHelper::csrfField() ?>

    <p><label>Novo prazo de avaliação *<br>
        <input type="datetime-local" name="novo_prazo" required autofocus></label></p>

    <p>
        <button type="submit"><i class="fas fa-rotate"></i> Reabrir</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>