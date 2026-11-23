<?php
// app/Views/projetos/_avaliacao_reps.php
// Como o representante e o vice avaliam: a mesma nota compartilhada ou cada um a sua (nota final = média).
// Espera opcional: $avaliacaoReps ('compartilhada' | 'media').
$avr = $avaliacaoReps ?? 'compartilhada';
?>
<div class="cg-bloco">
    <p class="cg-pergunta">Quando a turma tem vice, como os dois representantes avaliam?</p>
    <div class="cg-ops">
        <label class="cg-op">
            <input type="radio" name="avaliacao_reps" value="compartilhada"<?= $avr === 'compartilhada' ? ' checked' : '' ?>>
            <span class="cg-card"><i class="fas fa-handshake"></i><strong>Dividem as mesmas notas</strong><small>Os dois lançam na mesma nota de cada aluno; vale a última lançada.</small></span>
        </label>
        <label class="cg-op">
            <input type="radio" name="avaliacao_reps" value="media"<?= $avr === 'media' ? ' checked' : '' ?>>
            <span class="cg-card"><i class="fas fa-scale-balanced"></i><strong>Média dos dois</strong><small>Cada um dá a sua nota; a nota final é a média das duas.</small></span>
        </label>
    </div>
</div>
