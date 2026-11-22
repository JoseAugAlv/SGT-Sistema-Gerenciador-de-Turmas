<?php
// app/Views/atas/_devolver.php
// Botão "Devolver para correção" com o campo do motivo. Espera: $ata, $basePath.
?>
<details class="acao-motivo">
    <summary class="button button-ghost"><i class="fas fa-rotate-left"></i> Devolver para correção</summary>
    <form method="POST" action="<?= $basePath ?>/atas/<?= (int) $ata['id'] ?>/reabrir">
        <?= ViewHelper::csrfField() ?>
        <label class="field-label" for="motivo-devolver-<?= (int) $ata['id'] ?>">O que o diretor precisa corrigir?</label>
        <textarea id="motivo-devolver-<?= (int) $ata['id'] ?>" name="motivo" class="field" rows="3" maxlength="500" required
                  placeholder="Ex.: faltou registrar a presença da Ana e a atividade de sexta."></textarea>
        <small class="field-help">A ata volta para "Aguardando diretor" e os diretores do grupo recebem este motivo.</small>
        <button type="submit" class="button button-primary">Devolver ao diretor</button>
    </form>
</details>
