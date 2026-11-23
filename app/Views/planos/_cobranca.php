<?php
// app/Views/planos/_cobranca.php
// Campos de dados de cobrança (sem <form>): nome completo, CPF e endereço com preenchimento pelo CEP.
// Espera: $cobranca (array|null) com os dados salvos ou digitados antes. Opcional: $cobrancaTitulo.
require_once __DIR__ . '/../../Services/CobrancaService.php';
$cb = $cobranca ?? [];
$v  = fn($c) => h($cb[$c] ?? '');
?>
<fieldset class="cobranca panel">
    <legend class="bloco-titulo-legend"><?= h($cobrancaTitulo ?? 'Dados de cobrança') ?></legend>
    <p class="field-help">Necessários para emitir a cobrança. Ficam salvos para as próximas compras.</p>
    <div class="cobranca-campos">
        <div class="cb-largo">
            <label class="field-label" for="cob-nome">Nome completo *</label>
            <input id="cob-nome" type="text" name="cob_nome_completo" class="field" maxlength="120" required autocomplete="name" value="<?= $v('nome_completo') ?>">
        </div>
        <div>
            <label class="field-label" for="cob-cpf">CPF *</label>
            <input id="cob-cpf" type="text" name="cob_cpf" class="field" inputmode="numeric" maxlength="14" required placeholder="000.000.000-00"
                   value="<?= !empty($cb['cpf']) ? h(CobrancaService::formatarCpf($cb['cpf'])) : '' ?>">
            <small class="cb-msg" id="cob-cpf-msg" aria-live="polite"></small>
        </div>
        <div>
            <label class="field-label" for="cob-cep">CEP *</label>
            <input id="cob-cep" type="text" name="cob_cep" class="field" inputmode="numeric" maxlength="9" required placeholder="00000-000" autocomplete="postal-code"
                   value="<?= !empty($cb['cep']) ? h(CobrancaService::formatarCep($cb['cep'])) : '' ?>">
            <small class="cb-msg" id="cob-cep-msg" aria-live="polite"></small>
        </div>
        <div class="cb-largo">
            <label class="field-label" for="cob-logradouro">Rua *</label>
            <input id="cob-logradouro" type="text" name="cob_logradouro" class="field" maxlength="150" required autocomplete="address-line1" value="<?= $v('logradouro') ?>">
        </div>
        <div>
            <label class="field-label" for="cob-numero">Número *</label>
            <input id="cob-numero" type="text" name="cob_numero" class="field" maxlength="20" required placeholder="Ou S/N" value="<?= $v('numero') ?>">
        </div>
        <div>
            <label class="field-label" for="cob-complemento">Complemento</label>
            <input id="cob-complemento" type="text" name="cob_complemento" class="field" maxlength="80" autocomplete="address-line2" value="<?= $v('complemento') ?>">
        </div>
        <div>
            <label class="field-label" for="cob-bairro">Bairro *</label>
            <input id="cob-bairro" type="text" name="cob_bairro" class="field" maxlength="80" required value="<?= $v('bairro') ?>">
        </div>
        <div>
            <label class="field-label" for="cob-cidade">Cidade *</label>
            <input id="cob-cidade" type="text" name="cob_cidade" class="field" maxlength="80" required readonly value="<?= $v('cidade') ?>">
        </div>
        <div>
            <label class="field-label" for="cob-uf">UF *</label>
            <select id="cob-uf" name="cob_uf" class="field" required>
                <option value="">—</option>
                <?php foreach (CobrancaService::UFS as $uf): ?><option<?= ($cb['uf'] ?? '') === $uf ? ' selected' : '' ?>><?= $uf ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
</fieldset>

<script>
(function () {
    var base = <?= json_encode($basePath) ?>;
    var cpf = document.getElementById('cob-cpf'), cpfMsg = document.getElementById('cob-cpf-msg');
    var cep = document.getElementById('cob-cep'), cepMsg = document.getElementById('cob-cep-msg');
    var el = function (id) { return document.getElementById('cob-' + id); };
    var digitos = function (s) { return (s || '').replace(/\D/g, ''); };

    function cpfValido(c) {
        c = digitos(c);
        if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;
        for (var t = 9; t < 11; t++) {
            var soma = 0;
            for (var i = 0; i < t; i++) soma += +c[i] * (t + 1 - i);
            if (+c[t] !== ((10 * soma) % 11) % 10) return false;
        }
        return true;
    }
    function msg(alvo, texto, ok) { alvo.textContent = texto; alvo.className = 'cb-msg ' + (ok ? 'ok' : 'erro'); }

    cpf.addEventListener('input', function () {
        var d = digitos(cpf.value).slice(0, 11);
        cpf.value = d.replace(/^(\d{3})(\d)/, '$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1-$2');
        if (d.length < 11) { msg(cpfMsg, '', true); cpf.setCustomValidity(''); return; }
        var ok = cpfValido(d);
        msg(cpfMsg, ok ? 'CPF válido' : 'CPF inválido', ok);
        cpf.setCustomValidity(ok ? '' : 'CPF inválido');
    });

    var ultimo = '';
    function buscarCep() {
        var d = digitos(cep.value);
        if (d.length !== 8 || d === ultimo) return;
        ultimo = d;
        msg(cepMsg, 'Buscando endereço…', true);
        fetch(base + '/api/cep/' + d, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (j.ok) {
                    if (j.logradouro) el('logradouro').value = j.logradouro;
                    if (j.bairro) el('bairro').value = j.bairro;
                    el('cidade').value = j.cidade || '';
                    el('uf').value = j.uf || '';
                    el('cidade').readOnly = true;
                    msg(cepMsg, j.cidade + ' / ' + j.uf, true);
                    cep.setCustomValidity('');
                    (j.logradouro ? el('numero') : el('logradouro')).focus();
                } else if (j.erro === 'nao_encontrado') {
                    msg(cepMsg, 'CEP não encontrado', false);
                    cep.setCustomValidity('CEP não encontrado');
                } else {
                    msg(cepMsg, 'Busca indisponível: preencha o endereço', false);
                    el('cidade').readOnly = false;
                    cep.setCustomValidity('');
                }
            })
            .catch(function () { msg(cepMsg, 'Busca indisponível: preencha o endereço', false); el('cidade').readOnly = false; });
    }
    cep.addEventListener('input', function () {
        var d = digitos(cep.value).slice(0, 8);
        cep.value = d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
        if (d.length === 8) buscarCep(); else { ultimo = ''; msg(cepMsg, '', true); }
    });
    if (cpf.value) cpf.dispatchEvent(new Event('input'));
    if (!el('cidade').value) el('cidade').readOnly = false;
})();
</script>
