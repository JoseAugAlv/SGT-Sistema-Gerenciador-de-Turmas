<?php
// app/Views/turmas/_contagens.php
// Painel da turma: contagens regressivas e mural de avisos (com o aviso do dia em destaque).
// Espera: $contagens, $avisos, $turma, $isMaster, $isRep.
$hoje = date('Y-m-d');
$doDia = array_values(array_filter($avisos ?? [], fn($a) => ($a['do_dia'] ?? null) === $hoje));
$outros = array_values(array_filter($avisos ?? [], fn($a) => empty($a['do_dia']) && empty($a['contagem_ate'])));
$base = $basePath . '/turmas/' . (int) $turma['id'] . '/alertas';
if (empty($contagens) && empty($doDia) && empty($outros) && !($isMaster || $isRep)) return;
?>
<section class="contagens" aria-label="Avisos e contagens">
    <?php foreach ($doDia as $a): ?>
        <article class="aviso-dia<?= $a['urgente'] ? ' urgente' : '' ?>">
            <span class="aviso-dia-selo"><i class="fas fa-sun"></i> Aviso do dia</span>
            <strong><?= h($a['titulo']) ?></strong>
            <p><?= nl2br(h($a['mensagem'])) ?></p>
        </article>
    <?php endforeach; ?>

    <?php foreach ($contagens as $c): $alvo = strtotime($c['contagem_ate']); ?>
        <article class="contagem<?= $c['urgente'] ? ' urgente' : '' ?>">
            <div class="contagem-texto">
                <span class="contagem-rotulo"><i class="fas fa-hourglass-half"></i> Contagem regressiva</span>
                <strong><?= h($c['titulo']) ?></strong>
                <small><?= h(mb_strimwidth($c['mensagem'], 0, 120, '…')) ?> · até <?= date('d/m/Y H:i', $alvo) ?></small>
            </div>
            <?php $alvoMs = $alvo * 1000; require __DIR__ . '/../alertas/_relogio.php'; ?>
        </article>
    <?php endforeach; ?>

    <?php if ($outros): ?>
        <div class="mural">
            <div class="mural-topo"><strong><i class="fas fa-bullhorn"></i> Avisos</strong><a href="<?= $base ?>">Ver todos</a></div>
            <ul>
                <?php foreach (array_slice($outros, 0, 3) as $a): ?>
                    <li class="<?= $a['urgente'] ? 'urgente' : '' ?>"><strong><?= h($a['titulo']) ?></strong> <span><?= h(mb_strimwidth($a['mensagem'], 0, 140, '…')) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (($isMaster || $isRep) && empty($contagens) && empty($doDia)): ?>
        <a class="contagem-nova" href="<?= $base ?>/criar">
            <i class="fas fa-plus"></i> Novo aviso: aviso do dia, contagem regressiva para uma entrega, prova ou evento…
        </a>
    <?php endif; ?>
</section>
<script src="<?= ViewHelper::asset('js/contagem.js') ?>"></script>
