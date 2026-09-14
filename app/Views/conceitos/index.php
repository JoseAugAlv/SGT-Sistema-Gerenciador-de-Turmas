<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>">
    <?= h($projeto['turma_nome']) ?>
</a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Configuração de Conceito</h2>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/conceitos/salvar">
    <?= ViewHelper::csrfField() ?>

    <fieldset>
        <legend>Faixas de nota</legend>
        <p><label>I — de 0 até <input type="number" name="limite_i_max" min="0" max="100" value="<?= (int) $cfg['limite_i_max'] ?>" required></label></p>
        <p><label>R — de <input type="number" name="limite_r_min" min="0" max="100" value="<?= (int) $cfg['limite_r_min'] ?>" required>
                  até <input type="number" name="limite_r_max" min="0" max="100" value="<?= (int) $cfg['limite_r_max'] ?>" required></label></p>
        <p><label>B — de <input type="number" name="limite_b_min" min="0" max="100" value="<?= (int) $cfg['limite_b_min'] ?>" required>
                  até <input type="number" name="limite_b_max" min="0" max="100" value="<?= (int) $cfg['limite_b_max'] ?>" required></label></p>
        <p><label>MB — de <input type="number" name="limite_mb_min" min="0" max="100" value="<?= (int) $cfg['limite_mb_min'] ?>" required>
                   até 100</label></p>
    </fieldset>

    <fieldset>
        <legend>Visibilidade de boletins</legend>
        <p><label>Diretor vê boletim de:<br>
            <select name="visibilidade_diretor">
                <?php foreach ($visibilidades as $val => $label): ?>
                    <option value="<?= h($val) ?>" <?= $val === $cfg['visibilidade_diretor'] ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select></label></p>
        <p><label>Aluno vê boletim de:<br>
            <select name="visibilidade_aluno">
                <?php foreach ($visibilidades as $val => $label): ?>
                    <option value="<?= h($val) ?>" <?= $val === $cfg['visibilidade_aluno'] ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select></label></p>
    </fieldset>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Salvar</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>