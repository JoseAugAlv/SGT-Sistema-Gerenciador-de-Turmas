<?php
// app/Views/alertas/_relogio.php
// Relógio da contagem regressiva (anos, meses, dias, horas, min, seg). Espera: $alvoMs (timestamp em ms).
// O ritmo fica em public/js/contagem.js; o estilo em components.css (.relogio).
?>
<div class="relogio" data-contagem-alvo="<?= (int) $alvoMs ?>">
    <div class="relogio-unidades" data-relogio role="timer" aria-live="off">
        <span data-unidade hidden><b data-u="a">0</b><small>anos</small></span>
        <span data-unidade hidden><b data-u="me">0</b><small>meses</small></span>
        <span data-unidade><b data-u="d">0</b><small>dias</small></span>
        <span data-unidade><b data-u="h">00</b><small>horas</small></span>
        <span data-unidade><b data-u="m">00</b><small>min</small></span>
        <span data-unidade><b data-u="s">00</b><small>seg</small></span>
    </div>
    <p class="relogio-fim" data-fim hidden><i class="fas fa-flag-checkered"></i> Chegou o dia!</p>
</div>
