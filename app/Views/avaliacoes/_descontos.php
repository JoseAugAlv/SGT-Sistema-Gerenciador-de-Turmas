<?php
// app/Views/avaliacoes/_descontos.php
// Explica os descontos aplicados na média. Espera: $boletim (com 'descontos').
$listaDescontos = $boletim['descontos'] ?? [];
if (!$listaDescontos) return;
?>
<div class="notice notice-danger boletim-descontos">
    <span><i class="fas fa-circle-minus"></i></span>
    <div>
        <strong>Desconto na média final</strong>
        <?php foreach ($listaDescontos as $dsc): ?>
            <p>−<?= number_format((float) $dsc['pontos'], 1, ',', '.') ?> ponto(s): <?= h($dsc['motivo']) ?>.</p>
        <?php endforeach; ?>
        <?php if (isset($boletim['media_sem_desconto']) && $boletim['media_sem_desconto'] !== null): ?>
            <p>Média antes do desconto: <?= number_format((float) $boletim['media_sem_desconto'], 1, ',', '.') ?>.</p>
        <?php endif; ?>
    </div>
</div>
