<?php
// app/Views/rateio/ver.php
// Divisão da parcela: o aluno paga a sua parte; o representante acompanha, lembra, cobre o restante ou cancela.
$cssPagina = ['projetos.css', 'rateio.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$total = (float) $rateio['valor_parcela'];
$pct = $total > 0 ? min(100, round($pagoTotal / $total * 100)) : 0;
$pagos = count(array_filter($partesRateio, fn($p) => $p['status'] !== 'pendente'));
$base = $basePath . '/rateios/' . (int) $rateio['id'];
$statusRot = ['aberto' => 'Em andamento', 'quitado' => 'Quitada', 'cancelado' => 'Cancelada'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Mensalidade dividida · <?= $statusRot[$rateio['status']] ?></div>
        <h1><?= (int) $rateio['numero'] ?>ª parcela da <?= h($turma['nome']) ?></h1>
        <p class="hero-subtitle"><?= $brl($total) ?> dividida entre <?= count($partesRateio) ?> pessoas.</p>
    </div>
    <div class="hero-actions"><a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?><?= $gestor ? '/plano' : '' ?>"><i class="fas fa-arrow-left"></i> Voltar</a></div>
</section>

<?php if ($minha): ?>
    <section class="panel rt-minha <?= $minha['status'] ?>" id="rt-minha">
        <div>
            <span>Sua parte</span>
            <b><?= $brl($minha['valor']) ?></b>
            <small><?= $minha['status'] === 'pendente' ? 'Ainda não paga' : ($minha['status'] === 'pago' ? 'Paga em ' . date('d/m/Y', strtotime($minha['pago_em'])) : 'Coberta pelo representante') ?></small>
        </div>
        <?php if ($minha['status'] === 'pendente' && $rateio['status'] === 'aberto'): ?>
            <div class="rt-pix">
                <div class="rt-qr" aria-hidden="true"></div>
                <div>
                    <p>Pague pelo Pix<?= $modoTeste ? ' (modo de teste: o pagamento é simulado)' : '' ?>.</p>
                    <?php if ($modoTeste): ?>
                        <form method="POST" action="<?= $base ?>/pagar" data-parcial="#rt-minha,#rt-prog"><?= ViewHelper::csrfField() ?><button type="submit" class="button button-primary" data-carregando="Confirmando…"><i class="fas fa-check"></i> Simular pagamento da minha parte</button></form>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <i class="fas fa-circle-check rt-ok"></i>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="panel" id="rt-prog"<?= $rateio['status'] === 'aberto' ? ' data-atualizar="15"' : '' ?>>
    <div class="rt-prog-topo"><strong><?= $pagos ?> de <?= count($partesRateio) ?> pagaram</strong><span><?= $brl($pagoTotal) ?> de <?= $brl($total) ?></span></div>
    <div class="rt-barra"><i style="width: <?= $pct ?>%"></i></div>
    <?php if ($gestor): ?>
        <ul class="rt-lista">
            <?php foreach ($partesRateio as $p): ?>
                <li class="<?= $p['status'] ?>"><span><?= h($p['nome']) ?></span><b><?= $brl($p['valor']) ?></b>
                    <small><?= $p['status'] === 'pendente' ? 'pendente' : ($p['status'] === 'pago' ? 'pagou ' . date('d/m', strtotime($p['pago_em'])) : 'coberto') ?></small></li>
            <?php endforeach; ?>
        </ul>
        <?php if ($rateio['status'] === 'aberto'): ?>
            <div class="rt-acoes">
                <form method="POST" action="<?= $base ?>/lembrar" data-parcial="#rt-minha,#rt-prog"><?= ViewHelper::csrfField() ?><button type="submit" class="button button-secondary"><i class="fas fa-bell"></i> Lembrar quem falta</button></form>
                <?php if ($modoTeste): ?>
                    <form method="POST" action="<?= $base ?>/cobrir" data-parcial="#rt-minha,#rt-prog"><?= ViewHelper::csrfField() ?><button type="submit" class="button button-secondary" onclick="return confirm('Pagar o restante e quitar a parcela?')"><i class="fas fa-hand-holding-dollar"></i> Pagar o restante (<?= $brl($total - $pagoTotal) ?>)</button></form>
                <?php endif; ?>
                <?php if (!$pagos): ?><form method="POST" action="<?= $base ?>/cancelar"><?= ViewHelper::csrfField() ?><button type="submit" class="button button-ghost" onclick="return confirm('Cancelar a divisão?')">Cancelar divisão</button></form><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="field-help">Só o representante vê quem já pagou.</p>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
