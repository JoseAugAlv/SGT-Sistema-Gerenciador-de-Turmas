<?php
// app/Views/atas/_preenchimento.php
// Escolha de quem preenche a ata. Espera: $modoPreenchimento ('diretor' | 'grupo').
$modoPreenchimento = $modoPreenchimento ?? 'diretor';
?>
<fieldset class="identidade-bloco escolha-preenchimento">
    <legend>Quem preenche esta ata?</legend>
    <label class="identidade-opcao">
        <input type="radio" name="preenchimento" value="diretor" <?= $modoPreenchimento !== 'grupo' ? 'checked' : '' ?>>
        <span><strong>Só o diretor do grupo</strong><small>O diretor registra as atividades, os relatórios e as presenças.</small></span>
    </label>
    <label class="identidade-opcao">
        <input type="radio" name="preenchimento" value="grupo" <?= $modoPreenchimento === 'grupo' ? 'checked' : '' ?>>
        <span><strong>Todo o grupo</strong><small>Cada membro registra o que fez e pode escrever relatórios. O diretor confere as presenças e envia a ata.</small></span>
    </label>
</fieldset>
