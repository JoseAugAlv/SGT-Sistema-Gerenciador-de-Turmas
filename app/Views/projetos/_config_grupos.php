<?php
// app/Views/projetos/_config_grupos.php
// Regras dos grupos do projeto: alunos pedem para entrar? quem define diretores? quantos diretores por grupo?
// Espera opcional: $regras (projeto atual) com alunos_pedem_grupo, alunos_pedem_diretor, diretores_por_grupo.
$rg = $regras ?? ['alunos_pedem_grupo' => 0, 'alunos_pedem_diretor' => 0, 'diretores_por_grupo' => 1];
$opcao = function (string $nome, string $valor, bool $marcado, string $ico, string $titulo, string $desc) {
    return '<label class="cg-op"><input type="radio" name="' . $nome . '" value="' . $valor . '"' . ($marcado ? ' checked' : '') . '>'
         . '<span class="cg-card"><i class="fas ' . $ico . '"></i><strong>' . $titulo . '</strong><small>' . $desc . '</small></span></label>';
};
?>
<div class="cg">
    <div class="cg-bloco">
        <p class="cg-pergunta">Como os alunos entram nos grupos?</p>
        <div class="cg-ops">
            <?= $opcao('alunos_pedem_grupo', '0', !$rg['alunos_pedem_grupo'], 'fa-user-tie', 'Eu monto os grupos', 'Você coloca cada aluno no grupo.') ?>
            <?= $opcao('alunos_pedem_grupo', '1', (bool) $rg['alunos_pedem_grupo'], 'fa-hand', 'Alunos pedem para entrar', 'Cada aluno escolhe um grupo e você aprova.') ?>
        </div>
    </div>
    <div class="cg-bloco">
        <p class="cg-pergunta">Quem define os diretores?</p>
        <div class="cg-ops">
            <?= $opcao('alunos_pedem_diretor', '0', !$rg['alunos_pedem_diretor'], 'fa-user-check', 'Eu escolho', 'Você nomeia os diretores de cada grupo.') ?>
            <?= $opcao('alunos_pedem_diretor', '1', (bool) $rg['alunos_pedem_diretor'], 'fa-crown', 'Alunos pedem para ser', 'Membros pedem e você aprova.') ?>
        </div>
    </div>
    <div class="cg-bloco">
        <p class="cg-pergunta"><label for="cg-dir">Diretores por grupo</label></p>
        <div class="cg-stepper">
            <button type="button" data-passo="-1" aria-label="Menos um diretor">−</button>
            <input id="cg-dir" type="number" name="diretores_por_grupo" min="1" max="5" value="<?= (int) $rg['diretores_por_grupo'] ?>" inputmode="numeric">
            <button type="button" data-passo="1" aria-label="Mais um diretor">+</button>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.cg-stepper').forEach(function (s) {
    var inp = s.querySelector('input');
    s.querySelectorAll('[data-passo]').forEach(function (b) {
        b.addEventListener('click', function () { inp.value = Math.min(5, Math.max(1, (+inp.value || 1) + +b.dataset.passo)); });
    });
});
</script>
