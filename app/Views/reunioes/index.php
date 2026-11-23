<?php
// app/Views/reunioes/index.php
// Reuniões da turma/grêmio: marcar nova e lista (próximas e anteriores).
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$ehGremio = $turma['tipo'] === 'gremio';
$agora = time();
$proximas = array_reverse(array_values(array_filter($reunioes, fn($r) => strtotime($r['data']) >= $agora - 3600 && $r['status'] === 'agendada')));
$anteriores = array_values(array_filter($reunioes, fn($r) => !in_array($r, $proximas, true)));
$statusRot = ['agendada' => 'Agendada', 'realizada' => 'Realizada', 'cancelada' => 'Cancelada'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Reuniões</div>
        <h1>Reuniões</h1>
        <p class="hero-subtitle"><?= $ehGremio ? 'Grêmio' : 'Turma' ?> <strong><?= h($turma['nome']) ?></strong> · pauta, ata e presença.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>/calendario"><i class="fas fa-calendar"></i> Calendário</a>
    </div>
</section>

<?php if ($podeMarcar): ?>
    <details class="panel mb-16"<?= !$reunioes ? ' open' : '' ?>>
        <summary><strong><i class="fas fa-plus"></i> Marcar reunião</strong></summary>
        <form method="POST" action="<?= $basePath ?>/turmas/<?= $tid ?>/reunioes" class="cal-form-g">
            <?= ViewHelper::csrfField() ?>
            <label>Título *<input type="text" name="titulo" class="field" maxlength="150" required placeholder="Ex.: Reunião geral do grêmio"></label>
            <label>Data e hora *<input type="datetime-local" name="data" class="field" required min="<?= date('Y-m-d\TH:i') ?>"></label>
            <label>Local<input type="text" name="local" class="field" maxlength="120" placeholder="Sala, pátio ou link"></label>
            <?php if ($ehGremio && $diretorias): ?>
                <label>De qual diretoria?
                    <select name="diretoria_id" class="field">
                        <?php if ($podeGeral): ?><option value="">Reunião geral (todos do grêmio)</option><?php endif; ?>
                        <?php foreach ($diretorias as $d): ?><option value="<?= (int) $d['id'] ?>"><?= h($d['nome']) ?></option><?php endforeach; ?>
                    </select>
                </label>
            <?php endif; ?>
            <label class="cal-larga">Pauta<textarea name="pauta" class="field" rows="4" maxlength="5000" placeholder="Um assunto por linha"></textarea></label>
            <p class="cal-larga mb-0"><button type="submit" class="button button-primary" data-carregando="Marcando…">Marcar e avisar</button></p>
        </form>
    </details>
<?php endif; ?>

<?php foreach (['Próximas' => $proximas, 'Anteriores' => $anteriores] as $titulo => $lista): if (!$lista) continue; ?>
    <div class="bloco-titulo"><h2><?= $titulo ?></h2></div>
    <ul class="lista-tarefas mb-16">
        <?php foreach ($lista as $r): ?>
            <li>
                <span class="tarefa-icone"><i class="fas fa-people-group"></i></span>
                <div class="tarefa-info">
                    <strong><a class="mat-link" href="<?= $basePath ?>/reunioes/<?= (int) $r['id'] ?>"><?= h($r['titulo']) ?></a></strong>
                    <small><?= date('d/m/Y H:i', strtotime($r['data'])) ?><?= $r['local'] ? ' · ' . h($r['local']) : '' ?><?= $r['diretoria_nome'] ? ' · ' . h($r['diretoria_nome']) : '' ?>
                        <?= $r['status'] === 'realizada' ? ' · ' . (int) $r['presentes'] . ' presente(s)' : '' ?></small>
                </div>
                <span class="venda-status <?= $r['status'] === 'realizada' ? 'ativa' : ($r['status'] === 'agendada' ? 'aguardando' : '') ?>"><?= $statusRot[$r['status']] ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endforeach; ?>
<?php if (!$reunioes): ?><div class="lista-vazia"><i class="fas fa-people-group"></i><span>Nenhuma reunião ainda.</span></div><?php endif; ?>

<style>
.cal-form-g { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin-top: 12px; }
.cal-form-g label { display: grid; gap: 4px; font-size: 13px; font-weight: 600; } .cal-form-g .field { margin: 0; }
.cal-larga { grid-column: 1 / -1; }
.venda-status { font-size: 12px; font-weight: 700; color: var(--muted); } .venda-status.ativa { color: var(--green); } .venda-status.aguardando { color: var(--orange); }
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
