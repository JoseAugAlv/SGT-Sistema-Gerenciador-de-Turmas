<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Novo Material</h2>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/salvar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Nome *<br>
        <input type="text" name="nome" maxlength="150" required autofocus></label></p>

    <p><label>Unidade *<br>
        <select name="unidade" required>
            <option value="">Selecione</option>
            <?php foreach ($unidades as $chave => $label): ?>
                <option value="<?= h($chave) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select></label></p>

    <p><label>Quantidade inicial *<br>
        <input type="number" name="quantidade" min="0" step="0.01" value="0" required></label></p>

    <p><label>Preço unitário (R$)<br>
        <input type="text" name="preco" class="campo-moeda"
               inputmode="numeric" placeholder="0,00" value="0,00"></label></p>

    <p>
        <button type="submit"><i class="fas fa-check"></i> Cadastrar</button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">Cancelar</a>
    </p>
</form>

<?php require __DIR__ . '/../layouts/footer.php'; ?>

<script>
// Máscara de moeda BRL — aplica em qualquer input com .campo-moeda
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.campo-moeda').forEach(function(input) {
        // Formata o valor inicial se já vier preenchido
        if (input.value !== '') formatarMoeda(input);

        input.addEventListener('input', function() { formatarMoeda(this); });
    });
});

function formatarMoeda(input) {
    // Remove tudo que não é dígito
    var v = input.value.replace(/\D/g, '');
    if (v === '') { input.value = ''; return; }

    // Trata como centavos
    v = (parseInt(v, 10) / 100).toFixed(2);       // "1234.56"
    v = v.replace('.', ',');                      // "1234,56"
    v = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');  // "1.234,56"
    input.value = v;
}
</script>