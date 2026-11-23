<?php
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$logado = !empty($_SESSION['usuario']);
?>

<section class="precos-hero">
    <span class="eyebrow"><span class="status-dot"></span> Planos e preços</span>
    <h1>Um preço pela turma inteira</h1>
    <p>O representante paga uma vez pela turma, por Pix. Todos os colegas usam sem pagar nada a mais.</p>
</section>

<section class="precos-grade" aria-label="Planos de turma">
    <?php foreach ($planos as $p):
        $pid = (int) $p['id'];
        $pr  = $precos[$pid][1];
        $maxParc = max(array_keys($precos[$pid]));
        $menorParcela = end($precos[$pid]);
        $semJuros = 1;
        foreach ($precos[$pid] as $n => $x) if ($x['acrescimo_pct'] == 0) $semJuros = $n;
        $destaque = $p['codigo'] === 'anual';
    ?>
        <article class="plano-card precos-card<?= $destaque ? ' destaque' : '' ?>">
            <?php if ($destaque): ?><span class="plano-tag">Mais escolhido</span><?php endif; ?>
            <h2 class="plano-nome"><?= h($p['nome']) ?></h2>
            <span class="plano-duracao"><?= $p['meses'] ? (int) $p['meses'] . ' meses de acesso' : 'Acesso sem vencimento' ?></span>
            <span class="plano-preco"><b class="num"><?= $brl($pr['avista']) ?></b><small>à vista</small></span>
            <span class="plano-parcela">
                <?php if ($maxParc > 1): ?>
                    ou <?= $semJuros > 1 ? $semJuros . 'x de ' . $brl($precos[$pid][$semJuros]['parcela']) . ' sem acréscimo' : 'até ' . $maxParc . 'x de ' . $brl($menorParcela['parcela']) ?>
                <?php else: ?>só à vista<?php endif; ?>
            </span>
            <ul class="precos-itens">
                <li><i class="fas fa-check"></i> <?= (int) $p['projetos'] ?> projetos</li>
                <li><i class="fas fa-check"></i> Até <?= (int) $p['pessoas_max'] ?> alunos</li>
                <li><i class="fas fa-check"></i> Avaliações, atas, materiais e boletim</li>
                <li><i class="fas <?= !empty($p['inclui_dia_a_dia']) ? 'fa-check' : 'fa-plus' ?>"></i> <?= !empty($p['inclui_dia_a_dia']) ? '<strong>Agenda e Dia a dia inclusos</strong>' : 'Agenda e Dia a dia: + ' . $brl($p['preco_dia_a_dia'] ?? 0) ?></li>
            </ul>
            <span class="plano-por-aluno">≈ <?= $brl($pr['avista'] / 40) ?> por aluno numa turma de 40</span>
            <a class="button <?= $destaque ? 'button-primary' : 'button-secondary' ?> precos-botao" href="<?= $basePath ?>/precos/escolher/<?= h($p['codigo']) ?>">Escolher <?= h($p['nome']) ?></a>
        </article>
    <?php endforeach; ?>
</section>

<section class="precos-hero precos-hero-gremio" id="gremios">
    <h2>Para grêmios estudantis</h2>
    <p>Reuniões com ata e presença, diretorias criadas pelo presidente, calendário, avisos e projetos do grêmio.
       <strong>Quem já tem plano de turma ganha <?= rtrim(rtrim(number_format((float) PrecoService::config('desconto_cruzado_pct', '10'), 2, ',', ''), '0'), ',') ?>% no grêmio</strong>, e vice-versa.</p>
</section>
<section class="precos-grade" aria-label="Planos de grêmio">
    <?php foreach ($gremios as $p):
        $pid = (int) $p['id'];
        $pr  = $precosGremio[$pid][1];
        $maxParc = max(array_keys($precosGremio[$pid]));
        $menorParcela = end($precosGremio[$pid]);
        $semJuros = 1;
        foreach ($precosGremio[$pid] as $n => $x) if ($x['acrescimo_pct'] == 0) $semJuros = $n;
        $destaque = $p['codigo'] === 'g_anual';
    ?>
        <article class="plano-card precos-card<?= $destaque ? ' destaque' : '' ?>">
            <?php if ($destaque): ?><span class="plano-tag">Mais escolhido</span><?php endif; ?>
            <h2 class="plano-nome"><?= h($p['nome']) ?></h2>
            <span class="plano-duracao"><?= $p['meses'] ? (int) $p['meses'] . ' meses de acesso' : 'Acesso sem vencimento' ?></span>
            <span class="plano-preco"><b class="num"><?= $brl($pr['avista']) ?></b><small>à vista</small></span>
            <span class="plano-parcela">
                <?php if ($maxParc > 1): ?>
                    ou <?= $semJuros > 1 ? $semJuros . 'x de ' . $brl($precosGremio[$pid][$semJuros]['parcela']) . ' sem acréscimo' : 'até ' . $maxParc . 'x de ' . $brl($menorParcela['parcela']) ?>
                <?php else: ?>só à vista<?php endif; ?>
            </span>
            <ul class="precos-itens">
                <li><i class="fas fa-check"></i> <?= (int) $p['projetos'] ?> projetos</li>
                <li><i class="fas fa-check"></i> Até <?= (int) $p['pessoas_max'] ?> pessoas</li>
                <li><i class="fas fa-check"></i> Diretorias e reuniões com ata</li>
                <li><i class="fas <?= !empty($p['inclui_dia_a_dia']) ? 'fa-check' : 'fa-plus' ?>"></i> <?= !empty($p['inclui_dia_a_dia']) ? '<strong>Agenda e Dia a dia inclusos</strong>' : 'Agenda e Dia a dia: + ' . $brl($p['preco_dia_a_dia'] ?? 0) ?></li>
            </ul>
            <a class="button <?= $destaque ? 'button-primary' : 'button-secondary' ?> precos-botao" href="<?= $basePath ?>/precos/escolher/<?= h($p['codigo']) ?>">Escolher <?= h($p['nome']) ?></a>
        </article>
    <?php endforeach; ?>
</section>

<section class="precos-condicoes">
    <h2>Como funciona</h2>
    <ul>
        <li><i class="fas fa-qrcode"></i><div><strong>Só Pix</strong><span>À vista ou parcelado, conforme o plano.</span></div></li>
        <li><i class="fas fa-unlock"></i><div><strong>Liberação na hora</strong><span>A 1ª parcela libera a turma. As demais vencem a cada 30 dias.</span></div></li>
        <li><i class="fas fa-tag"></i><div><strong>Cupom de desconto</strong><span>Tem um cupom? Use na hora de escolher o plano. Descontos não se somam.</span></div></li>
        <li><i class="fas fa-clock"></i><div><strong>Atraso</strong><span>Há <?= (int) PrecoService::config('tolerancia_dias', '5') ?> dias de tolerância antes de a turma ser bloqueada.</span></div></li>
    </ul>
    <?php if (!$logado): ?>
        <p class="precos-cta">Já tem conta? <a href="<?= $basePath ?>/login">Entre</a> e escolha o plano. Sua turma já usa o Prumo? Peça o código ao representante.</p>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
