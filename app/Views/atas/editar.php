<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Editar Etapa</h1>
<p>Projeto: <strong><?= h($projeto['nome']) ?></strong></p>

<form method="POST" action="<?= $basePath ?>/etapas/<?= (int) $etapa['id'] ?>/atualizar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Nome *<br>
        <input type="text" name="nome" maxlength="150" value="<?= h($etapa['nome']) ?>" required></label></p>

    <p><label>Descrição<br>
        <textarea name="descricao" rows="3"><?= h($etapa['descricao'] ?? '') ?></textarea></label></p>

    <p><label>Data início<br>
        <input type="date" name="data_inicio" value="<?= h($etapa['data_inicio'] ?? '') ?>"></label></p>

    <p><label>Data fim<br>
        <input type="date" name="data_fim" value="<?= h($etapa['data_fim'] ?? '') ?>"></label></p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Salvar</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>