<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h1>Novo Grupo — <?= h($projeto['nome']) ?></h1>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos/salvar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Nome do grupo *<br>
        <input type="text" name="nome" maxlength="150" required autofocus></label></p>

    <p><label>Modo do grupo *<br>
        <select name="modo_avaliacao_grupo" required>
            <?php foreach ($modosGrupo as $val => $label): ?>
                <option value="<?= h($val) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label></p>

    <p><label>Modo de avaliação padrão *<br>
        <select name="modo_avaliacao_por" required>
            <?php foreach ($modosPor as $val => $label): ?>
                <option value="<?= h($val) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label></p>

    <p><em>Observação: estes modos são apenas valores padrão de UI. Cada critério
    define seu próprio <code>tipo_avaliacao</code>.</em></p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Criar grupo</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/grupos">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>