<?php
// app/Views/reunioes/detalhe.php
// Uma reunião: pauta, e (para quem organiza) ata + lista de presença.
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$statusRot = ['agendada' => 'Agendada', 'realizada' => 'Realizada', 'cancelada' => 'Cancelada'];
$total = count($convocados);
$vieram = count(array_filter($presencas));
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Reunião · <?= $statusRot[$r['status']] ?></div>
        <h1><?= h($r['titulo']) ?></h1>
        <p class="hero-subtitle">
            <?= date('d/m/Y \à\s H:i', strtotime($r['data'])) ?><?= $r['local'] ? ' · ' . h($r['local']) : '' ?>
            · <?= $r['diretoria_nome'] ? 'Diretoria ' . h($r['diretoria_nome']) : 'Reunião geral' ?> · <?= h($r['turma_nome']) ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $r['turma_id'] ?>/reunioes"><i class="fas fa-arrow-left"></i> Reuniões</a>
    </div>
</section>

<div class="reu-grade">
    <section class="panel">
        <h2 class="an-h2"><i class="fas fa-list-ol"></i> Pauta</h2>
        <?php if ($r['pauta']): ?>
            <ol class="reu-pauta"><?php foreach (preg_split('/\R/', $r['pauta']) as $l): if (trim($l) === '') continue; ?><li><?= h(trim($l, " -•\t")) ?></li><?php endforeach; ?></ol>
        <?php else: ?><p class="field-help">Sem pauta.</p><?php endif; ?>

        <h2 class="an-h2 mt-16"><i class="fas fa-file-lines"></i> Ata</h2>
        <?php if ($r['ata']): ?><div class="reu-ata"><?= nl2br(h($r['ata'])) ?></div>
        <?php else: ?><p class="field-help"><?= $r['status'] === 'cancelada' ? 'Reunião cancelada.' : 'A ata é registrada depois da reunião.' ?></p><?php endif; ?>
    </section>

    <section class="panel" id="presenca">
        <h2 class="an-h2"><i class="fas fa-user-check"></i> Presença <?= $r['status'] === 'realizada' ? "({$vieram}/{$total})" : "· {$total} convocado(s)" ?></h2>
        <?php if ($podeGerir && $r['status'] !== 'cancelada'): ?>
            <!-- presença por QR Code: cada convocado marca a própria presença escaneando -->
            <div class="reu-qr">
                <?php if ($qrAberto): ?>
                    <span><i class="fas fa-qrcode"></i> QR de presença aberto</span>
                    <a class="button button-primary" href="<?= $basePath ?>/reunioes/<?= (int) $r['id'] ?>/qr"><i class="fas fa-expand"></i> Mostrar QR</a>
                <?php else: ?>
                    <form method="POST" action="<?= $basePath ?>/reunioes/<?= (int) $r['id'] ?>/qr/abrir" class="reu-qr-form">
                        <?= ViewHelper::csrfField() ?>
                        <span><i class="fas fa-qrcode"></i> Presença por QR Code</span>
                        <select name="minutos" class="field" aria-label="Tempo aberto">
                            <option value="10">10 min</option><option value="20" selected>20 min</option><option value="45">45 min</option><option value="90">1h30</option>
                        </select>
                        <button type="submit" class="button button-secondary">Abrir QR</button>
                    </form>
                <?php endif; ?>
            </div>
            <form method="POST" action="<?= $basePath ?>/reunioes/<?= (int) $r['id'] ?>/registrar">
                <?= ViewHelper::csrfField() ?>
                <ul class="reu-presenca">
                    <?php foreach ($convocados as $c): ?>
                        <li><label><input type="checkbox" name="presentes[]" value="<?= (int) $c['id'] ?>"<?= !empty($presencas[(int) $c['id']]) ? ' checked' : '' ?>> <?= h($c['nome']) ?><?= ($vias[(int) $c['id']] ?? '') === 'qr' ? ' <small class="reu-via"><i class="fas fa-qrcode"></i> QR</small>' : '' ?></label></li>
                    <?php endforeach; ?>
                </ul>
                <label class="field-label" for="reu-ata">Ata (o que foi decidido)</label>
                <textarea id="reu-ata" name="ata" class="field" rows="7" maxlength="20000"><?= h($r['ata'] ?? '') ?></textarea>
                <p class="mt-12"><button type="submit" class="button button-primary">Salvar ata e presença</button></p>
            </form>
            <?php if ($r['status'] === 'agendada'): ?>
                <form method="POST" action="<?= $basePath ?>/reunioes/<?= (int) $r['id'] ?>/cancelar">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="button button-ghost" onclick="return confirm('Cancelar esta reunião?')">Cancelar reunião</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <ul class="reu-presenca">
                <?php foreach ($convocados as $c): $p = $presencas[(int) $c['id']] ?? null; ?>
                    <li><?= $p === 1 ? '<i class="fas fa-check txt-ok"></i>' : ($p === 0 ? '<i class="fas fa-xmark txt-alerta"></i>' : '<i class="fas fa-minus txt-mudo"></i>') ?> <?= h($c['nome']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<style>
.reu-grade { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr); gap: 16px; align-items: start; }
.reu-grade .panel { margin: 0; }
.an-h2 { margin: 0 0 10px; font-size: 16px; } .an-h2 i { color: var(--primary); }
.reu-pauta { margin: 0; padding-left: 20px; line-height: 1.7; }
.reu-ata { white-space: normal; line-height: 1.6; padding: 12px; border-radius: 8px; background: var(--surface-2); }
.reu-presenca { list-style: none; margin: 0 0 12px; padding: 0; display: grid; gap: 6px; max-height: 420px; overflow-y: auto; }
.reu-presenca label { display: flex; gap: 8px; align-items: center; cursor: pointer; }
.txt-ok { color: var(--green); }
.reu-qr { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; padding: 10px 12px; margin-bottom: 12px; border-radius: 10px; background: color-mix(in srgb, var(--primary) 7%, transparent); }
.reu-qr-form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; width: 100%; }
.reu-qr-form span { margin-right: auto; font-weight: 600; }
.reu-qr-form .field { width: auto; }
.reu-qr > span { font-weight: 600; color: var(--primary); }
.reu-via { color: var(--primary); font-weight: 600; font-size: 11px; padding: 1px 6px; border-radius: 6px; background: color-mix(in srgb, var(--primary) 10%, transparent); }
@media (max-width: 860px) { .reu-grade { grid-template-columns: 1fr; } }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
