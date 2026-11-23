<?php
// app/Views/master/_cupom_regras.php
// Tabela "em quais planos o cupom vale e quanto": um % ou um valor em R$ por plano.
// Espera: $planos (todos), $regrasAtuais ([plano_id => ['tipo_desconto', 'valor']]), $prefixoId (único por formulário).
$regrasAtuais = $regrasAtuais ?? [];
?>
<table class="cupom-regras" data-regras>
    <thead><tr><th>Vale no plano</th><th>Desconto</th><th></th></tr></thead>
    <tbody>
        <?php foreach ($planos as $p):
            $pid = (int) $p['id'];
            $r   = $regrasAtuais[$pid] ?? null;
            $id  = $prefixoId . '-' . $pid;
        ?>
            <tr class="<?= $r ? '' : 'off' ?>">
                <td><label for="<?= $id ?>"><input type="checkbox" id="<?= $id ?>" name="regra[<?= $pid ?>][on]" value="1"<?= $r ? ' checked' : '' ?>>
                    <?= h($p['nome']) ?> <small class="txt-mudo">R$ <?= number_format((float) $p['preco'], 2, ',', '.') ?><?= $p['tipo'] === 'gremio' ? ' · grêmio' : '' ?></small></label></td>
                <td>
                    <select name="regra[<?= $pid ?>][tipo]" class="field" aria-label="Tipo de desconto no <?= h($p['nome']) ?>">
                        <option value="pct"<?= ($r['tipo_desconto'] ?? 'pct') === 'pct' ? ' selected' : '' ?>>%</option>
                        <option value="valor"<?= ($r['tipo_desconto'] ?? '') === 'valor' ? ' selected' : '' ?>>R$</option>
                    </select>
                </td>
                <td><input type="number" name="regra[<?= $pid ?>][valor]" class="field" step="0.01" min="0.01" value="<?= h($r['valor'] ?? '') ?>" placeholder="valor" aria-label="Desconto no <?= h($p['nome']) ?>"></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<small class="field-help">Sem nenhum plano marcado, o cupom vale em todos (conforme “Vale para”) com o desconto geral acima. O desconto nunca passa do piso de cada plano.</small>
<script>
document.querySelectorAll('[data-regras]').forEach(function (t) {
    t.addEventListener('change', function (e) {
        if (e.target.type === 'checkbox') e.target.closest('tr').classList.toggle('off', !e.target.checked);
        if (e.target.type === 'number' && e.target.value) { var cb = e.target.closest('tr').querySelector('[type=checkbox]'); cb.checked = true; cb.closest('tr').classList.remove('off'); }
    });
});
</script>
