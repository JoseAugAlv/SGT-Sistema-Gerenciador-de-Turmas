<?php
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$valorFmt = 'R$ ' . number_format((float) $pagamento['valor'], 2, ',', '.');
$tid      = (int) $turma['id'];
$pago     = $pagamento['status'] === 'pago';
$pixReal  = $pixReal ?? false;
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Pagamento por Pix</div>
        <h1><?= $valorFmt ?></h1>
        <p class="hero-subtitle">
            Plano <?= h($assinatura['plano_nome']) ?> · <?= h($turma['nome']) ?> ·
            parcela <?= (int) $pagamento['numero'] ?> de <?= (int) $assinatura['parcelas'] ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>/plano"><i class="fas fa-arrow-left"></i> Voltar ao plano</a>
    </div>
</section>

<?php if ($pago): ?>
    <div class="notice notice-success">
        <span><i class="fas fa-circle-check"></i></span>
        <div><strong>Esta parcela já foi paga</strong><p>Em <?= ViewHelper::data($pagamento['pago_em'], true) ?>.</p></div>
    </div>
<?php elseif ($pixReal && !empty($erroPix)): ?>
    <div class="notice notice-danger"><span><i class="fas fa-triangle-exclamation"></i></span><div><strong>Não deu para gerar o Pix</strong><p><?= h($erroPix) ?></p>
        <p class="mb-0"><a href="">Tentar de novo</a></p></div></div>
<?php else: ?>
    <section class="pix-caixa panel">
        <?php if ($pixReal && !empty($pagamento['pix_qr'])): ?>
            <div class="pix-qr real"><img src="data:image/png;base64,<?= h($pagamento['pix_qr']) ?>" alt="QR Code do Pix" width="220" height="220"></div>
        <?php else: ?>
        <div class="pix-qr" aria-hidden="true">
            <i class="fas fa-qrcode"></i>
            <span>QR Code de teste</span>
        </div>
        <?php endif; ?>
        <div class="pix-dados">
            <ol class="pix-passos">
                <li>Abra o app do seu banco e escolha <strong>Pix</strong>.</li>
                <li>Leia o QR Code ou use o <strong>Pix copia e cola</strong>.</li>
                <li>Confira o valor de <strong><?= $valorFmt ?></strong> e confirme.</li>
            </ol>
            <label class="field-label" for="pix-copia">Pix copia e cola</label>
            <div class="pix-copia-linha">
                <input id="pix-copia" class="field" type="text" readonly value="<?= h($pixCopia) ?>">
                <button type="button" class="button button-secondary" id="pix-copiar"><i class="fas fa-copy"></i> Copiar</button>
            </div>
            <?php if ($pixReal): ?>
                <small class="field-help">Pix válido até <?= ViewHelper::data($pagamento['pix_expira'], true) ?> · pagamento <?= h($pagamento['mp_id']) ?> no Mercado Pago</small>
                <p class="pix-esperando" id="pix-esperando"><span class="pix-pulso"></span> Aguardando o pagamento… esta tela atualiza sozinha.</p>
            <?php else: ?>
                <small class="field-help">Vencimento: <?= ViewHelper::data($pagamento['vencimento']) ?> · identificador <?= h($pagamento['txid']) ?></small>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($pixReal): ?>
    <script>
    (function () {
        var url = <?= json_encode($basePath . '/pagamentos/' . (int) $pagamento['id'] . '/status') ?>, voltas = 0;
        var t = setInterval(function () {
            if (++voltas > 360) { clearInterval(t); return; }          // 30 min
            fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
                if (d.pago) { clearInterval(t); document.getElementById('pix-esperando').innerHTML = '<i class="fas fa-circle-check"></i> Pagamento recebido! Abrindo o plano…';
                    setTimeout(function () { location.href = <?= json_encode($basePath . '/turmas/' . $tid . '/plano') ?>; }, 1500); }
            }).catch(function () {});
        }, 5000);
    })();
    </script>
    <?php endif; ?>

    <?php if (!$pixReal): ?>
    <section class="pix-teste">
        <div>
            <strong><i class="fas fa-flask"></i> Ambiente de teste</strong>
            <p>Este Pix não cobra nada. Para testar o fluxo, simule que o pagamento foi feito.</p>
        </div>
        <form method="POST" action="<?= $basePath ?>/pagamentos/<?= (int) $pagamento['id'] ?>/simular" id="form-simular">
            <?= ViewHelper::csrfField() ?>
            <button type="submit" class="button button-primary"><i class="fas fa-check"></i> Simular pagamento</button>
        </form>
    </section>
    <?php endif; ?>

    <script>
    (function () {
        var copiar = document.getElementById('pix-copiar');
        copiar.addEventListener('click', function () {
            var campo = document.getElementById('pix-copia');
            campo.select();
            (navigator.clipboard ? navigator.clipboard.writeText(campo.value) : Promise.reject()).catch(function () { document.execCommand('copy'); });
            copiar.innerHTML = '<i class="fas fa-check"></i> Copiado';
        });
        var fs = document.getElementById('form-simular');
        if (fs) fs.addEventListener('submit', function (e) {
            var ok = confirm('SIMULAÇÃO DE PAGAMENTO\n\n'
                + 'Valor: <?= $valorFmt ?>\n'
                + 'Plano: <?= h(addslashes($assinatura['plano_nome'])) ?> · parcela <?= (int) $pagamento['numero'] ?>/<?= (int) $assinatura['parcelas'] ?>\n\n'
                + 'Ambiente de teste: nenhum valor será cobrado.\n'
                + 'Confirmar como se o Pix tivesse sido pago?');
            if (!ok) e.preventDefault();
        });
    })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
