<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Criar Turma</h1>
<p><em>Nome será montado como: <code>{codigo_escola}-{ano_modulo}-{sigla_curso}-{sigla_periodo}</code></em></p>

<form method="POST" action="<?= $basePath ?>/turmas/salvar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Código da escola (numérico) *<br>
        <input type="text" name="codigo_escola" pattern="\d+" required></label></p>

    <p><label>Ano/Módulo *<br>
        <select name="ano_modulo" required>
            <option value="">Selecione</option>
            <?php foreach ($anosModulo as $val => $label): ?>
                <option value="<?= h($val) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label></p>

    <p><label>Curso *<br>
        <select name="curso_id" required>
            <option value="">Selecione</option>
            <?php foreach ($cursos as $c): ?>
                <option value="<?= (int) $c['id'] ?>"><?= h($c['nome']) ?> (<?= h($c['sigla']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <?php if (empty($cursos)): ?>
            <br><small>⚠️ Nenhum curso ativo. <a href="<?= $basePath ?>/configuracoes/turmas">Cadastre um curso</a>.</small>
        <?php endif; ?>
    </label></p>

    <p><label>Período *<br>
        <select name="periodo_id" required>
            <option value="">Selecione</option>
            <?php foreach ($periodos as $p): ?>
                <option value="<?= (int) $p['id'] ?>"><?= h($p['nome']) ?> (<?= h($p['sigla']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <?php if (empty($periodos)): ?>
            <br><small>⚠️ Nenhum período ativo. <a href="<?= $basePath ?>/configuracoes/turmas">Cadastre um período</a>.</small>
        <?php endif; ?>
    </label></p>

    <p><label>Código de acesso (o que o aluno digita) *<br>
        <input type="text" name="codigo_acesso" maxlength="30" required></label></p>

    <p><label>Cor primária<br>
        <input type="color" name="cor_primaria" value="#3498db"></label></p>

    <p><label>Cor secundária<br>
        <input type="color" name="cor_secundaria" value="#2ecc71"></label></p>

    <p><button type="submit" <?= (empty($cursos) || empty($periodos)) ? 'disabled' : '' ?>>Criar turma</button></p>
</form>

<p><a href="<?= $basePath ?>/turmas">Voltar</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>