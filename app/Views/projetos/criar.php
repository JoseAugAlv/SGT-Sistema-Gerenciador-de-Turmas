<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Novo Projeto — <?= h($turma['nome']) ?></h1>

<form method="POST" action="<?= $basePath ?>/projetos/salvar">
    <?= ViewHelper::csrfField() ?>
    <input type="hidden" name="turma_id" value="<?= (int) $turma['id'] ?>">

    <p><label>Nome *<br>
        <input type="text" name="nome" maxlength="150" required autofocus></label></p>

    <p><label>Descrição<br>
        <textarea name="descricao" rows="3" maxlength="2000"></textarea></label></p>

    <p><label>Prazo<br>
        <input type="date" name="prazo"></label></p>

    <p><label>Modo de avaliação *<br>
        <select name="modo_avaliacao" required>
            <?php foreach ($modos as $val => $label): ?>
                <option value="<?= h($val) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label></p>

    <p><button type="submit"><i class="fas fa-check"></i> Criar projeto</button></p>
</form>

<p><a href="<?= $basePath ?>/projetos?turma_id=<?= (int) $turma['id'] ?>">
    <i class="fas fa-arrow-left"></i> Voltar
</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>