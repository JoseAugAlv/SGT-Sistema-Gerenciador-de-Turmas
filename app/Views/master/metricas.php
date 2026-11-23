<?php
// app/Views/master/metricas.php
// Painel de métricas do master.
$cssPagina = ['projetos.css', 'metricas.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
$rot = fn($ym) => $meses[(int) substr($ym, 5, 2) - 1] . '/' . substr($ym, 2, 2);
$var = $m['receitaMesAnt'] > 0 ? round(($m['receitaMes'] - $m['receitaMesAnt']) / $m['receitaMesAnt'] * 100) : null;
$maxS = max(1, max(array_column($m['serie'], 'valor')));
$totTurmas = max(1, array_sum($m['sit']));
$conv = $m['criadas30'] ? round($m['pagaram30'] / $m['criadas30'] * 100) : null;
$renov = ($m['renovadas'] + $m['venceram']) ? round($m['renovadas'] / ($m['renovadas'] + $m['venceram']) * 100) : null;
$maxUso = max(1, max($m['uso']));
$stEsc = ['novo' => 'Novos', 'em_contato' => 'Em contato', 'fechado' => 'Fechados', 'descartado' => 'Descartados'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Master</div>
        <h1>Métricas</h1>
        <p class="hero-subtitle">Como o <?= h(App::getName()) ?> está indo: receita, turmas, uso e crescimento. Atualizado agora (<?= date('d/m/Y H:i') ?>).</p>
    </div>
    <div class="hero-actions"><a class="button button-ghost" href="<?= $basePath ?>/master/comercial"><i class="fas fa-crown"></i> Comercial</a></div>
</section>

<div class="mt-kpis">
    <div class="panel"><span>Receita no mês</span><b><?= $brl($m['receitaMes']) ?></b>
        <small class="<?= $var === null ? '' : ($var >= 0 ? 'sobe' : 'desce') ?>"><?= $var === null ? 'sem base no mês anterior' : ($var >= 0 ? '▲ ' : '▼ ') . abs($var) . '% sobre o mês passado' ?></small></div>
    <div class="panel"><span>A receber (30 dias)</span><b><?= $brl($m['aReceber']) ?></b><small class="<?= $m['atrasado'] > 0 ? 'desce' : '' ?>"><?= $m['atrasado'] > 0 ? $brl($m['atrasado']) . ' em atraso' : 'nada em atraso' ?></small></div>
    <div class="panel"><span>Turmas com plano</span><b><?= $m['sit']['ativa'] + $m['sit']['vencendo'] ?></b><small><?= $m['sit']['sem_plano'] ?> sem plano · <?= $m['sit']['pendencia'] ?> com pendência</small></div>
    <div class="panel"><span>Usuários ativos (7 dias)</span><b><?= $m['usuarios']['semana'] ?></b><small>de <?= $m['usuarios']['total'] ?> · <?= $m['usuarios']['novos'] ?> novos em 30 dias</small></div>
</div>

<section class="panel mt-bloco">
    <h2 class="mt-h2"><i class="fas fa-chart-column"></i> Receita dos últimos 12 meses <small>total desde o início: <?= $brl($m['total']) ?></small></h2>
    <div class="mt-serie">
        <?php foreach ($m['serie'] as $s): ?>
            <div class="mt-col" title="<?= $rot($s['mes']) ?>: <?= $brl($s['valor']) ?>">
                <span class="mt-valor"><?= $s['valor'] > 0 ? number_format($s['valor'] / 1000, 1, ',', '') . 'k' : '' ?></span>
                <i style="height: <?= max(2, round($s['valor'] / $maxS * 100)) ?>%"></i>
                <small><?= $rot($s['mes']) ?></small>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="mt-grade">
    <section class="panel">
        <h2 class="mt-h2"><i class="fas fa-users-rectangle"></i> Turmas por situação</h2>
        <div class="mt-pilha">
            <?php foreach (['ativa' => ['Em dia', 'ok'], 'vencendo' => ['Vencendo', 'atencao'], 'pendencia' => ['Pendência', 'ruim'], 'sem_plano' => ['Sem plano', 'neutro'], 'isenta' => ['Isentas', 'isenta']] as $k => [$n, $c]):
                if (!$m['sit'][$k]) continue; ?>
                <i class="<?= $c ?>" style="width: <?= round($m['sit'][$k] / $totTurmas * 100, 1) ?>%" title="<?= $n ?>: <?= $m['sit'][$k] ?>"></i>
            <?php endforeach; ?>
        </div>
        <ul class="mt-legenda">
            <li><span class="ok"></span> Em dia <b><?= $m['sit']['ativa'] ?></b></li><li><span class="atencao"></span> Vencendo <b><?= $m['sit']['vencendo'] ?></b></li>
            <li><span class="ruim"></span> Pendência <b><?= $m['sit']['pendencia'] ?></b></li><li><span class="neutro"></span> Sem plano <b><?= $m['sit']['sem_plano'] ?></b></li>
            <li><span class="isenta"></span> Isentas <b><?= $m['sit']['isenta'] ?></b></li>
        </ul>
        <div class="mt-taxas">
            <div><b><?= $conv === null ? '—' : $conv . '%' ?></b><span>conversão: turmas criadas em 30 dias que pagaram (<?= $m['pagaram30'] ?>/<?= $m['criadas30'] ?>)</span></div>
            <div><b><?= $renov === null ? '—' : $renov . '%' ?></b><span>renovação: planos renovados × vencidos sem renovar (<?= $m['renovadas'] ?>/<?= $m['renovadas'] + $m['venceram'] ?>)</span></div>
            <div><b><?= $m['testeDia']['ativos'] ?></b><span>testes do Dia a dia em andamento (<?= $m['testeDia']['usados'] ?> no total)</span></div>
        </div>
    </section>

    <section class="panel">
        <h2 class="mt-h2"><i class="fas fa-crown"></i> Planos vendidos</h2>
        <?php if (!$m['planos']): ?><p class="mt-vazio">Nenhum plano pago ainda.</p><?php else: ?>
            <table class="mt-tabela"><thead><tr><th>Plano</th><th>Vendas</th><th>Valor</th></tr></thead><tbody>
                <?php foreach ($m['planos'] as $p): ?><tr><td><?= h($p['nome']) ?><?= $p['tipo'] === 'gremio' ? ' <small>(grêmio)</small>' : '' ?></td><td><?= (int) $p['qtd'] ?></td><td><?= $brl($p['valor']) ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
        <h3 class="mt-h3">Cupons mais usados</h3>
        <?php if (!$m['cupons']): ?><p class="mt-vazio">Nenhum cupom usado ainda.</p><?php else: ?>
            <ul class="mt-lista"><?php foreach ($m['cupons'] as $c): ?><li><code><?= h($c['codigo']) ?></code><small><?= h($c['origem']) ?></small><b><?= (int) $c['usos'] ?><?= $c['usos_max'] ? '/' . (int) $c['usos_max'] : '' ?></b></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2 class="mt-h2"><i class="fas fa-bolt"></i> Uso nos últimos 30 dias</h2>
        <ul class="mt-uso">
            <?php foreach ($m['uso'] as $nome => $n): ?><li><span><?= h($nome) ?></span><div><i style="width: <?= round($n / $maxUso * 100) ?>%"></i></div><b><?= $n ?></b></li><?php endforeach; ?>
        </ul>
    </section>

    <section class="panel">
        <h2 class="mt-h2"><i class="fas fa-user-check"></i> Usuários</h2>
        <div class="mt-usuarios">
            <div><b><?= $m['usuarios']['dia'] ?></b><span>hoje</span></div><div><b><?= $m['usuarios']['semana'] ?></b><span>7 dias</span></div>
            <div><b><?= $m['usuarios']['mes'] ?></b><span>30 dias</span></div><div><b><?= $m['usuarios']['total'] ?></b><span>cadastrados</span></div>
        </div>
        <h3 class="mt-h3">Escolas interessadas</h3>
        <?php if (!$m['escolas']): ?><p class="mt-vazio">Nenhum pedido pela página <a href="<?= $basePath ?>/escolas">Para escolas</a> ainda.</p><?php else: ?>
            <ul class="mt-lista"><?php foreach ($stEsc as $k => $n): if (empty($m['escolas'][$k])) continue; ?><li><span><?= $n ?></span><small><?= (int) $m['escolas'][$k]['t'] ?> turmas</small><b><?= (int) $m['escolas'][$k]['n'] ?></b></li><?php endforeach; ?></ul>
            <p class="mt-vazio"><a href="<?= $basePath ?>/master/comercial#escolas">Ver pedidos</a></p>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
