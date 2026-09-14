<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Novo Critério</h2>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios/salvar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Nome *<br>
        <input type="text" name="nome" maxlength="200" required autofocus></label></p>

    <p><label>Descrição<br>
        <textarea name="descricao" rows="3" maxlength="2000"></textarea></label></p>

    <p><label>Peso * (0.01 a 99.99 — ideal: soma dos critérios = 10)<br>
        <input type="number" name="peso" min="0.01" max="99.99" step="0.01" value="1.00" required></label></p>

    <p><label>Tipo de avaliação *<br>
        <select name="tipo_avaliacao" required>
            <?php foreach ($tipos as $val => $label): ?>
                <option value="<?= h($val) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label></p>

    <details>
        <summary><i class="fas fa-circle-info"></i> Como funciona cada tipo de avaliação</summary>

        <ul>
            <li><strong>Diretor</strong> — os <em>diretores ativos do grupo</em> avaliam o aluno. Se houver mais de um diretor, a nota final é a média entre eles. Se o próprio aluno avaliado for o único diretor do grupo, essa avaliação passa automaticamente para o representante da turma.</li>
            <li><strong>Representante</strong> — o(s) <em>representante(s) ativo(s) da turma</em> avaliam. Se houver 2 representantes, ambos avaliam e a nota final é a média entre eles.</li>
            <li><strong>Pares</strong> — cada aluno do grupo avalia cada um dos colegas (menos ele mesmo). <strong>Justificativa é obrigatória</strong> e é de uso interno. A nota final é a média das notas dadas pelos pares.</li>
            <li><strong>Autoavaliação</strong> — o próprio aluno avalia a si mesmo.</li>
            <li><strong>Coletiva</strong> — uma nota única para <em>todo o grupo</em>. Todos os membros recebem essa mesma nota no critério.</li>
            <li><strong>Misto</strong> — combina diretor e representante: <code>nota = média(diretores) × 0.5 + média(representantes) × 0.5</code>.</li>
        </ul>

        <p><small>
            <i class="fas fa-info-circle"></i>
            <em>Aplicável a</em> define <strong>quem recebe</strong> a nota.
            <em>Tipo</em> define <strong>quem dá</strong> a nota.
        </small></p>
    </details>

    <p><label>Aplicável a *<br>
        <select name="aplicavel_a" required>
            <?php foreach ($aplicaveis as $val => $label): ?>
                <option value="<?= h($val) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label></p>

    <?php if (!empty($etapas)): ?>
        <p><label>Etapa vinculada (opcional)<br>
            <select name="etapa_id">
                <option value="">Nenhuma</option>
                <?php foreach ($etapas as $e): ?>
                    <option value="<?= (int) $e['id'] ?>"><?= h($e['nome']) ?></option>
                <?php endforeach; ?>
            </select></label></p>
    <?php endif; ?>

    <p><label>Prazo de avaliação (opcional)<br>
        <input type="datetime-local" name="prazo_avaliacao"></label></p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Criar critério</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/criterios">Cancelar</a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>