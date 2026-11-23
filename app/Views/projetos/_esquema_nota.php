<?php
// app/Views/projetos/_esquema_nota.php
// Escolha do esquema de notas (menção, 0–10, 0–100) e das faixas das menções com botões prontos e barra visual.
// Espera: $cfg (configuração atual ou padrão). Envia: esquema_nota, faixa_preset, faixa_i, faixa_r, faixa_b.
require_once __DIR__ . '/../../Helpers/EscalaNota.php';
$cfgE   = $cfg ?? ['esquema_nota' => 'mencao', 'limite_i_max' => 49, 'limite_r_max' => 69, 'limite_b_max' => 84];
$esqAt  = EscalaNota::esquema($cfgE);
$preAt  = EscalaNota::presetAtual($cfgE) ?? 'personalizado';
$iAt = (int) $cfgE['limite_i_max']; $rAt = (int) $cfgE['limite_r_max']; $bAt = (int) $cfgE['limite_b_max'];
$barra = fn($i, $r, $b) => '<span class="en-mini"><i class="i" style="flex:' . ($i + 1) . '"></i><i class="r" style="flex:' . ($r - $i) . '"></i><i class="b" style="flex:' . ($b - $r) . '"></i><i class="mb" style="flex:' . (100 - $b) . '"></i></span>';
?>
<div class="en" data-esquema-nota>
    <p class="en-titulo">Como as notas aparecem</p>
    <div class="en-esquemas" role="radiogroup" aria-label="Esquema de notas">
        <?php foreach (EscalaNota::ESQUEMAS as $v => [$rot, $ex, $ico]): ?>
            <label class="en-op">
                <input type="radio" name="esquema_nota" value="<?= $v ?>"<?= $esqAt === $v ? ' checked' : '' ?>>
                <span class="en-card"><i class="fas <?= $ico ?>"></i><strong><?= $rot ?></strong><small><?= $ex ?></small></span>
            </label>
        <?php endforeach; ?>
    </div>
    <p class="en-ajuda" data-so-numero hidden>Quem avalia digita a nota de 0 a <span data-max>10</span>. A média ponderada pelos pesos dos critérios continua igual.</p>

    <div class="en-faixas" data-so-mencao>
        <p class="en-titulo">Faixas das menções <small>(a média vai de 0 a 100; escolha onde começa cada menção)</small></p>
        <div class="en-presets" role="radiogroup" aria-label="Faixas das menções">
            <?php foreach (EscalaNota::PRESETS as $k => [$rot, $desc, $i, $r, $b]): ?>
                <label class="en-preset">
                    <input type="radio" name="faixa_preset" value="<?= $k ?>" data-i="<?= $i ?>" data-r="<?= $r ?>" data-b="<?= $b ?>"<?= $preAt === $k ? ' checked' : '' ?>>
                    <span class="en-preset-card"><strong><?= $rot ?></strong><?= $barra($i, $r, $b) ?><small><?= $desc ?></small></span>
                </label>
            <?php endforeach; ?>
            <label class="en-preset">
                <input type="radio" name="faixa_preset" value="personalizado"<?= $preAt === 'personalizado' ? ' checked' : '' ?>>
                <span class="en-preset-card"><strong>Personalizado</strong><span class="en-mini en-mini-vazia"><i></i></span><small>Ajuste cada limite</small></span>
            </label>
        </div>

        <div class="en-barra" aria-hidden="true">
            <div class="i" data-faixa="i"><b>I</b><small></small></div>
            <div class="r" data-faixa="r"><b>R</b><small></small></div>
            <div class="b" data-faixa="b"><b>B</b><small></small></div>
            <div class="mb" data-faixa="mb"><b>MB</b><small></small></div>
        </div>
        <div class="en-escala"><span>0</span><span>25</span><span>50</span><span>75</span><span>100</span></div>

        <div class="en-custom" data-custom<?= $preAt === 'personalizado' ? '' : ' hidden' ?>>
            <label>I vai até <output data-out="i"><?= $iAt ?></output><input type="range" name="faixa_i" min="0" max="97" value="<?= $iAt ?>"></label>
            <label>R vai até <output data-out="r"><?= $rAt ?></output><input type="range" name="faixa_r" min="1" max="98" value="<?= $rAt ?>"></label>
            <label>B vai até <output data-out="b"><?= $bAt ?></output><input type="range" name="faixa_b" min="2" max="99" value="<?= $bAt ?>"></label>
        </div>
    </div>
</div>
<style>
.en { display: grid; gap: 12px; }
.en-titulo { margin: 0; font-weight: 700; font-size: 14px; }
.en-titulo small { font-weight: 400; color: var(--muted); }
.en-esquemas { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
.en-op, .en-preset { position: relative; cursor: pointer; }
.en-op input, .en-preset input { position: absolute; opacity: 0; pointer-events: none; }
.en-card, .en-preset-card { display: grid; gap: 4px; height: 100%; padding: 12px; border: 1.5px solid var(--border); border-radius: 10px; background: var(--surface); transition: border-color .15s, background .15s; }
.en-card i { color: var(--muted); font-size: 18px; }
.en-card small, .en-preset-card small { color: var(--muted); font-size: 12px; }
.en-op input:checked + .en-card, .en-preset input:checked + .en-preset-card { border-color: var(--primary); background: var(--primary-soft, color-mix(in srgb, var(--primary) 8%, transparent)); }
.en-op input:checked + .en-card i { color: var(--primary); }
.en-op input:focus-visible + .en-card, .en-preset input:focus-visible + .en-preset-card { outline: 2px solid var(--primary); outline-offset: 2px; }
.en-ajuda { margin: 0; font-size: 13px; color: var(--muted); }
.en-faixas { display: grid; gap: 10px; }
.en-presets { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 8px; }
.en-mini { display: flex; height: 8px; border-radius: 4px; overflow: hidden; margin: 2px 0; }
.en-mini i { display: block; }
.en-mini-vazia i { flex: 1; background: repeating-linear-gradient(90deg, var(--border) 0 6px, transparent 6px 10px); }
.en-mini .i, .en-barra .i { background: #d63548; } .en-mini .r, .en-barra .r { background: #d48b1c; }
.en-mini .b, .en-barra .b { background: #1c6fd6; } .en-mini .mb, .en-barra .mb { background: #29946e; }
.en-barra { display: flex; height: 52px; border-radius: 10px; overflow: hidden; }
.en-barra > div { display: grid; place-content: center; text-align: center; color: #fff; min-width: 0; transition: flex-grow .25s ease; }
.en-barra b { font-size: 16px; line-height: 1; } .en-barra small { font-size: 11px; opacity: .9; }
.en-escala { display: flex; justify-content: space-between; font-size: 11px; color: var(--muted); margin-top: -6px; }
.en-custom { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.en-custom label { display: grid; gap: 4px; font-size: 13px; font-weight: 600; }
.en-custom output { font-weight: 700; color: var(--primary); }
@media (max-width: 640px) { .en-esquemas, .en-custom { grid-template-columns: 1fr; } }
@media (prefers-reduced-motion: reduce) { .en-barra > div { transition: none; } }
</style>
<script>
(function () {
    document.querySelectorAll('[data-esquema-nota]').forEach(function (raiz) {
        var q = function (s) { return raiz.querySelector(s); };
        var rng = { i: q('[name=faixa_i]'), r: q('[name=faixa_r]'), b: q('[name=faixa_b]') };
        function esquema() { var e = q('[name=esquema_nota]:checked'); return e ? e.value : 'mencao'; }
        function desenhar() {
            var i = +rng.i.value, r = +rng.r.value, b = +rng.b.value;
            var larg = { i: i + 1, r: r - i, b: b - r, mb: 100 - b };
            var txt  = { i: '0–' + i, r: (i + 1) + '–' + r, b: (r + 1) + '–' + b, mb: (b + 1) + '–100' };
            ['i', 'r', 'b', 'mb'].forEach(function (k) {
                var el = q('[data-faixa=' + k + ']');
                el.style.flex = Math.max(larg[k], 0) + ' 1 0';
                el.querySelector('small').textContent = txt[k];
            });
            ['i', 'r', 'b'].forEach(function (k) { q('[data-out=' + k + ']').textContent = rng[k].value; });
        }
        function aplicarEsquema() {
            var num = esquema() !== 'mencao';
            q('[data-so-mencao]').hidden = num;
            q('[data-so-numero]').hidden = !num;
            q('[data-max]').textContent = esquema() === '0a10' ? '10' : '100';
        }
        raiz.addEventListener('change', function (e) {
            if (e.target.name === 'esquema_nota') aplicarEsquema();
            if (e.target.name === 'faixa_preset') {
                var p = e.target;
                q('[data-custom]').hidden = p.value !== 'personalizado';
                if (p.dataset.i) { rng.i.value = p.dataset.i; rng.r.value = p.dataset.r; rng.b.value = p.dataset.b; }
                desenhar();
            }
        });
        Object.keys(rng).forEach(function (k) {
            rng[k].addEventListener('input', function () {
                // mantém a ordem I < R < B
                var i = +rng.i.value, r = +rng.r.value, b = +rng.b.value;
                if (k === 'i' && i >= r) rng.r.value = Math.min(i + 1, 98);
                if (k !== 'b' && +rng.r.value >= b) rng.b.value = Math.min(+rng.r.value + 1, 99);
                if (k === 'b' && b <= r) rng.r.value = Math.max(b - 1, 1);
                if (+rng.r.value <= +rng.i.value) rng.i.value = Math.max(+rng.r.value - 1, 0);
                var pers = q('[name=faixa_preset][value=personalizado]'); pers.checked = true; q('[data-custom]').hidden = false;
                desenhar();
            });
        });
        aplicarEsquema(); desenhar();
    });
})();
</script>
