<?php
$cssPagina = ['projetos.css', 'conceitos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Faixas
$iMax  = (int) $cfg['limite_i_max'];
$rMin  = (int) $cfg['limite_r_min'];
$rMax  = (int) $cfg['limite_r_max'];
$bMin  = (int) $cfg['limite_b_min'];
$bMax  = (int) $cfg['limite_b_max'];
$mbMin = (int) $cfg['limite_mb_min'];

// Larguras
$wI  = max(0, $iMax - 0);
$wR  = max(0, $rMax - $rMin + 1);
$wB  = max(0, $bMax - $bMin + 1);
$wMB = max(0, 100 - $mbMin + 1);
$total = max(1, $wI + $wR + $wB + $wMB);

$pctI  = round($wI  / $total * 100, 2);
$pctR  = round($wR  / $total * 100, 2);
$pctB  = round($wB  / $total * 100, 2);
$pctMB = round($wMB / $total * 100, 2);
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Pré-visualização
        </div>
        <h1>Como fica o cálculo</h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
            — arraste o slider para ver qual conceito a nota receberia.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/conceitos">
            <i class="fas fa-arrow-left"></i> Voltar à configuração
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<!-- ============ FAIXA VISUAL ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Distribuição das faixas</h2>
            <p>Visualização de como 0–100 é dividido entre os conceitos.</p>
        </div>
    </div>

    <div class="faixa-visual">
        <?php if ($wI > 0): ?>
            <div class="faixa i" style="width: <?= $pctI ?>%;">
                I
                <small>0–<?= $iMax ?></small>
            </div>
        <?php endif; ?>
        <?php if ($wR > 0): ?>
            <div class="faixa r" style="width: <?= $pctR ?>%;">
                R
                <small><?= $rMin ?>–<?= $rMax ?></small>
            </div>
        <?php endif; ?>
        <?php if ($wB > 0): ?>
            <div class="faixa b" style="width: <?= $pctB ?>%;">
                B
                <small><?= $bMin ?>–<?= $bMax ?></small>
            </div>
        <?php endif; ?>
        <?php if ($wMB > 0): ?>
            <div class="faixa mb" style="width: <?= $pctMB ?>%;">
                MB
                <small><?= $mbMin ?>–100</small>
            </div>
        <?php endif; ?>
    </div>

    <div class="faixa-legenda">
        <span>0</span>
        <span>25</span>
        <span>50</span>
        <span>75</span>
        <span>100</span>
    </div>
</section>

<!-- ============ SLIDER INTERATIVO ============ -->
<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Simulador de nota</h2>
            <p>Arraste para escolher uma nota e ver o conceito resultante.</p>
        </div>
    </div>

    <div class="preview-slider">
        <div class="slider-header">
            <span class="titulo">Nota simulada</span>
            <span class="slider-valor">
                <span id="valor-nota">75</span><small>de 100</small>
            </span>
        </div>

        <input type="range" id="slider-nota"
               min="0" max="100" step="1" value="75">

        <div class="slider-resultado">
            <span class="label">Conceito resultante</span>
            <span class="conceito-badge vazio" id="badge-conceito">—</span>
        </div>
    </div>

    <p class="text-muted mb-0" style="font-size:12px;">
        <i class="fas fa-info-circle"></i>
        O conceito é calculado por faixas contínuas. Você pode ajustar os limites na
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/conceitos">configuração de conceitos</a>.
    </p>
</section>

<!-- ============ TABELA DE REFERÊNCIA ============ -->
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Referência rápida</h2>
            <p>Exemplos de notas e seus conceitos.</p>
        </div>
    </div>

    <?php
    // Gera exemplos de notas para mostrar na tabela
    $exemplos = [0, 25, 50, 60, 70, 75, 80, 85, 90, 100];
    ?>
    <div class="rel-wrapper">
        <table class="rel-tabela">
            <thead>
                <tr>
                    <th style="text-align:right;">Nota</th>
                    <th style="text-align:center;">Conceito</th>
                    <th>Faixa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exemplos as $nota): ?>
                    <?php
                    if ($nota <= $iMax)      { $c = 'I';  $classe = 'i';  $faixa = "0 até {$iMax}"; }
                    elseif ($nota <= $rMax)  { $c = 'R';  $classe = 'r';  $faixa = "{$rMin} até {$rMax}"; }
                    elseif ($nota <= $bMax)  { $c = 'B';  $classe = 'b';  $faixa = "{$bMin} até {$bMax}"; }
                    else                     { $c = 'MB'; $classe = 'mb'; $faixa = "{$mbMin} até 100"; }
                    ?>
                    <tr>
                        <td class="td-num"><strong><?= $nota ?></strong></td>
                        <td class="td-center">
                            <span class="conceito-badge <?= $classe ?>"><?= $c ?></span>
                        </td>
                        <td style="color:var(--muted); font-size:12px;"><?= $faixa ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    var faixas = {
        iMax:  <?= $iMax ?>,
        rMin:  <?= $rMin ?>,
        rMax:  <?= $rMax ?>,
        bMin:  <?= $bMin ?>,
        bMax:  <?= $bMax ?>,
        mbMin: <?= $mbMin ?>
    };

    var slider   = document.getElementById('slider-nota');
    var valorEl  = document.getElementById('valor-nota');
    var badgeEl  = document.getElementById('badge-conceito');

    function calcularConceito(v) {
        v = parseFloat(v);
        if (isNaN(v)) return null;
        if (v <= faixas.iMax) return 'I';
        if (v <= faixas.rMax) return 'R';
        if (v <= faixas.bMax) return 'B';
        return 'MB';
    }

    function atualizar() {
        var v = slider.value;
        valorEl.textContent = v;

        var c = calcularConceito(v);
        badgeEl.textContent = c ?? '—';
        badgeEl.className = 'conceito-badge ' + (c ? c.toLowerCase() : 'vazio');
    }

    slider.addEventListener('input', atualizar);
    atualizar();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>