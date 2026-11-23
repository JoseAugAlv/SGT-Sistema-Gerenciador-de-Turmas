<?php
// app/Views/planos/_escolher.php
// Escolha de plano: cupom, cartões dos planos, parcelas e barra com o total.
// Espera: $planos, $precos, $cupom, $codigoCupom, $erroCupom, $divisor, $urlCupom (GET do cupom),
// $acaoForm (POST), opcionais $camposExtras (HTML dentro do formulário), $manterNaUrl, $planoInicial, $textoBotao,
// $semCupom (troca de plano: sem campo de cupom).
$planoInicial = $planoInicial ?? 'anual';
$brl = $brl ?? fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$semCupom = $semCupom ?? false;
?>
<!-- ============ ESCOLHER PLANO ============ -->
<?php if (!$semCupom): ?>
<form method="GET" action="<?= h($urlCupom) ?>" class="plano-cupom">
    <?php foreach ($manterNaUrl ?? [] as $k => $v): ?><input type="hidden" name="<?= h($k) ?>" value="<?= h($v) ?>"><?php endforeach; ?>
    <label class="field-label" for="cupom">Tem um cupom?</label>
    <div class="plano-cupom-linha">
        <input id="cupom" type="text" name="cupom" class="field" maxlength="40" value="<?= h($codigoCupom) ?>" placeholder="Digite o código" autocomplete="off">
        <button type="submit" class="button button-secondary">Aplicar</button>
        <?php if ($cupom): ?><a class="text-link" href="<?= h($urlCupom) ?>">Remover</a><?php endif; ?>
    </div>
    <?php if ($cupom): ?>
        <small class="plano-cupom-ok"><i class="fas fa-tag"></i> Cupom <?= h($cupom['codigo']) ?> aplicado nos preços abaixo.</small>
    <?php elseif ($erroCupom): ?>
        <small class="txt-alerta"><?= h($erroCupom) ?></small>
    <?php endif; ?>
</form>
<?php endif; ?>

<?php if (!empty($cruzado) && empty($credito) && !$semCupom && empty($ehGremio) && isset($turma)): ?>
    <div class="notice notice-blue">
        <span><i class="fas fa-gift"></i></span>
        <div><strong>Desconto de <?= rtrim(rtrim(number_format((float) $cruzado['percentual'], 2, ',', ''), '0'), ',') ?>% por já ter outro plano</strong>
            <p class="mb-0">Quem tem plano de turma paga menos no grêmio, e vice-versa. Já está nos preços abaixo; com cupom, vale o maior.</p></div>
    </div>
<?php endif; ?>
<?php if (!empty($credito) && !$semCupom): ?>
    <div class="notice notice-blue">
        <span><i class="fas fa-gift"></i></span>
        <div><strong>Vocês têm um crédito de indicação de <?= rtrim(rtrim(number_format((float) $credito['percentual'], 2, ',', ''), '0'), ',') ?>%</strong>
            <p class="mb-0">Ele já está nos preços abaixo. Se vocês usarem um cupom, vale o desconto maior (eles não somam).</p></div>
    </div>
<?php endif; ?>

<form method="POST" action="<?= h($acaoForm) ?>" id="form-plano" class="plano-form">
    <?= ViewHelper::csrfField() ?>
    <?= $camposExtras ?? '' ?>
    <input type="hidden" name="cupom" value="<?= h($cupom['codigo'] ?? '') ?>">

    <fieldset class="planos-grade">
        <legend class="bloco-titulo-legend">Escolha o plano</legend>
        <?php foreach ($planos as $i => $p):
            $pid = (int) $p['id'];
            $pr  = $precos[$pid][1];
            $maxParc = max(array_keys($precos[$pid]));
            $semJuros = 1;
            foreach ($precos[$pid] as $n => $x) if ($x['acrescimo_pct'] == 0) $semJuros = $n;
        ?>
            <label class="plano-card<?= $p['codigo'] === $planoInicial ? ' destaque' : '' ?>">
                <input type="radio" name="plano_id" value="<?= $pid ?>" <?= $p['codigo'] === $planoInicial ? 'checked' : '' ?> required>
                <?php if (in_array($p['codigo'], ['anual', 'g_anual'], true) && !$semCupom): ?><span class="plano-tag">Mais escolhido</span><?php endif; ?>
                <strong class="plano-nome"><?= h($p['nome']) ?></strong>
                <span class="plano-duracao"><?= $p['meses'] ? (int) $p['meses'] . ' meses de acesso' : 'Acesso sem vencimento' ?> · <?= (int) $p['projetos'] ?> projetos</span>
                <?php if (!$semCupom): ?>
                    <span class="plano-dia<?= !empty($p['inclui_dia_a_dia']) ? ' incluso' : '' ?>"><?= !empty($p['inclui_dia_a_dia'])
                        ? '<i class="fas fa-calendar-check"></i> Agenda e Dia a dia inclusos'
                        : '<i class="fas fa-calendar-plus"></i> Agenda: adicional de ' . $brl($p['preco_dia_a_dia'] ?? 0) ?></span>
                <?php endif; ?>
                <span class="plano-preco">
                    <?php if ($pr['desconto_pct'] > 0): ?><s><?= $brl($pr['lista']) ?></s><?php endif; ?>
                    <b class="num"><?= $brl($pr['avista']) ?></b>
                    <small><?= $semCupom ? 'de diferença' : 'à vista' ?></small>
                </span>
                <span class="plano-parcela">
                    <?php if ($maxParc > 1): ?>
                        ou até <?= $maxParc ?>x<?= $semJuros > 1 ? ' (' . $semJuros . 'x sem acréscimo)' : '' ?>
                    <?php else: ?>só à vista<?php endif; ?>
                </span>
                <span class="plano-por-aluno">≈ <?= $brl($pr['avista'] / $divisor) ?> por aluno, dividindo por <?= $divisor ?></span>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <?php if (!empty($pedirCobranca)) require __DIR__ . '/_cobranca.php'; ?>

    <section class="plano-resumo panel">
        <label class="field-label" for="parcelas">Parcelas</label>
        <select id="parcelas" name="parcelas" class="field"></select>
        <?php if (!empty($precosDia) && !$semCupom): ?>
            <label class="dia-addon" id="dia-addon" hidden>
                <input type="checkbox" name="dia_a_dia" value="1">
                <span><strong>Adicionar o Dia a dia <b id="dia-addon-preco"></b></strong>
                    <small>Agenda com lembretes e prioridades, horário das aulas, aviso do dia e contagem regressiva.</small>
                    <small class="dia-addon-dica" id="dia-addon-dica"></small></span>
            </label>
        <?php endif; ?>
        <small class="field-help">A 1ª parcela libera o acesso. As demais vencem a cada 30 dias.</small>
    </section>

    <!-- Barra fixa: total e botão sempre à mão enquanto rola os planos -->
    <div class="aval-barra-salvar plano-barra">
        <span class="aval-status plano-resumo-total">
            <span><span id="resumo-plano"></span> · <span id="resumo-linha"></span></span>
            <strong id="resumo-total" class="num"></strong>
            <small id="resumo-extra"></small>
        </span>
        <button type="submit" class="button button-primary plano-botao"><i class="fas fa-qrcode"></i> <?= h($textoBotao ?? 'Gerar Pix') ?></button>
    </div>
</form>

<script>
(function () {
    var precosBase = <?= json_encode($precos, JSON_UNESCAPED_UNICODE) ?>;
    var precosDia = <?= json_encode((object) ($precosDia ?? []), JSON_UNESCAPED_UNICODE) ?>;
    var planosInfo = <?= json_encode(array_map(fn($p) => ['id' => (int) $p['id'], 'codigo' => $p['codigo'], 'nome' => $p['nome'], 'meses' => $p['meses']], $planos), JSON_UNESCAPED_UNICODE) ?>;
    var addon = document.getElementById('dia-addon');
    function comDia() { return addon && !addon.hidden && addon.querySelector('input').checked; }
    var precos = new Proxy({}, { get: function (_, pid) { return comDia() && precosDia[pid] ? precosDia[pid] : precosBase[pid]; } });
    function atualizarAddon() {
        if (!addon) return;
        var pid = planoAtual(), temAddon = !!precosDia[pid];
        addon.hidden = !temAddon;
        if (!temAddon) return;
        var base = precosBase[pid][1], cDia = precosDia[pid][1];
        document.getElementById('dia-addon-preco').textContent = '+ ' + brl(cDia.dia_a_dia);
        var anual = planosInfo.filter(function (p) { return p.codigo === 'anual' || p.codigo === 'g_anual'; })[0];
        var dica = document.getElementById('dia-addon-dica');
        if (anual && precosBase[anual.id]) {
            var dif = precosBase[anual.id][1].total - cDia.total;
            var atual = planosInfo.filter(function (p) { return String(p.id) === String(pid); })[0];
            dica.textContent = dif > 0
                ? 'Dica: por mais ' + brl(dif) + ' você leva o ' + anual.nome + ' — ' + anual.meses + ' meses (não ' + atual.meses + ') com o Dia a dia incluso.'
                : 'Dica: o ' + anual.nome + ' sai mais barato e já inclui o Dia a dia.';
        }
    }
    var form = document.getElementById('form-plano');
    var sel  = document.getElementById('parcelas');
    var brl  = function (v) { return 'R$ ' + Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    function planoAtual() { var r = form.querySelector('[name=plano_id]:checked'); return r ? r.value : null; }
    function montarParcelas() {
        atualizarAddon();
        var op = precos[planoAtual()] || {}, antes = sel.value;
        sel.innerHTML = '';
        Object.keys(op).forEach(function (n) {
            var x = op[n], o = document.createElement('option');
            o.value = n;
            o.textContent = n === '1' ? '1x de ' + brl(x.total) + ' (à vista)'
                : n + 'x de ' + brl(x.parcela) + (x.acrescimo_pct > 0 ? ' (total ' + brl(x.total) + ')' : ' sem acréscimo');
            sel.appendChild(o);
        });
        if (op[antes]) sel.value = antes;
        resumo();
    }
    function resumo() {
        var x = (precos[planoAtual()] || {})[sel.value];
        if (!x) return;
        var card = form.querySelector('[name=plano_id]:checked').closest('.plano-card');
        document.getElementById('resumo-plano').textContent = card.querySelector('.plano-nome').textContent;
        document.getElementById('resumo-linha').textContent = x.parcelas > 1 ? x.parcelas + 'x de ' + brl(x.parcela) : 'à vista';
        document.getElementById('resumo-total').textContent = brl(x.total);
        var extra = [];
        if (x.desconto_pct > 0) extra.push(x.total <= 0 ? 'cupom: plano sem custo' : 'desconto de ' + (+x.desconto_pct).toFixed(2).replace(/\.?0+$/, '').replace('.', ',') + '%');
        if (x.acrescimo_pct > 0) extra.push('inclui ' + x.acrescimo_pct + '% do parcelamento');
        document.getElementById('resumo-extra').textContent = extra.join(' · ');
        // Plano gratuito (cupom de pacote): sem Pix e sem dados de cobrança
        var gratis = x.total <= 0, cob = form.querySelector('.cobranca'), btn = form.querySelector('.plano-botao');
        if (cob) {
            cob.hidden = gratis;
            cob.querySelectorAll('input, select').forEach(function (el) {
                if (el.dataset.req === undefined) el.dataset.req = el.required ? '1' : '0';
                el.required = !gratis && el.dataset.req === '1';
            });
        }
        if (btn) {
            if (btn.dataset.texto === undefined) btn.dataset.texto = btn.innerHTML;
            btn.innerHTML = gratis ? '<i class="fas fa-check"></i> Ativar sem custo' : btn.dataset.texto;
        }
    }
    form.addEventListener('change', function (e) { if (e.target.name === 'plano_id' || e.target.name === 'dia_a_dia') montarParcelas(); else resumo(); });
    montarParcelas();
})();
</script>
