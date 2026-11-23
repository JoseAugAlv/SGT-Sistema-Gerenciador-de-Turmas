<?php
// app/Views/reunioes/presenca.php
// Resultado de quem escaneou o QR de presença.
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
$msgs = [
    'marcada'       => ['fa-circle-check', 'ok',     'Presença confirmada!', 'Sua presença na reunião foi registrada.'],
    'ja_marcada'    => ['fa-circle-check', 'ok',     'Você já está na lista', 'Sua presença já tinha sido registrada.'],
    'nao_convocado' => ['fa-circle-xmark', 'erro',   'Esta reunião não é para você', 'Só os convocados (a turma ou a diretoria da reunião) marcam presença.'],
    'fechado'       => ['fa-lock',         'aviso',  'O QR de presença foi fechado', 'Fale com quem organiza a reunião para marcar a sua presença.'],
    'invalido'      => ['fa-qrcode',       'aviso',  'Código expirado', 'O QR muda a cada 30 segundos. Escaneie de novo o código que está na tela.'],
];
[$ico, $tom, $titulo, $texto] = $msgs[$situacao] ?? $msgs['invalido'];
?>

<section class="pq-caixa pq-<?= $tom ?>">
    <i class="fas <?= $ico ?> pq-ico"></i>
    <h1><?= $titulo ?></h1>
    <p><?= $texto ?></p>
    <?php if ($r): ?>
        <p class="pq-reuniao"><strong><?= h($r['titulo']) ?></strong><br><?= date('d/m/Y \à\s H:i', strtotime($r['data'])) ?> · <?= h($r['turma_nome']) ?></p>
        <a class="button button-primary" href="<?= $basePath ?>/reunioes/<?= (int) $r['id'] ?>">Ver a reunião</a>
    <?php endif; ?>
</section>

<style>
.pq-caixa { max-width: 460px; margin: 6vh auto; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 36px 24px; }
.pq-ico { font-size: 64px; margin-bottom: 12px; }
.pq-ok .pq-ico { color: var(--green); animation: pq-pop .6s cubic-bezier(.34, 1.56, .64, 1); }
.pq-erro .pq-ico { color: var(--red); } .pq-aviso .pq-ico { color: var(--orange); }
.pq-caixa h1 { margin: 0 0 8px; font-size: 24px; }
.pq-caixa p { color: var(--muted); }
.pq-reuniao { margin: 18px 0; color: var(--text) !important; }
@keyframes pq-pop { from { transform: scale(.4); opacity: 0; } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
