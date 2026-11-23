<?php
// app/Views/orcamento/prestacao.php
// Prestação de contas da turma/grêmio: por diretoria e por projeto (previsto, arrecadado, gasto, comprovantes)
// e o caixa geral. Botão de imprimir (ou salvar em PDF pelo navegador).
$cssPagina = ['projetos.css', 'orcamento.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$ehGremio = ($turma['tipo'] ?? 'turma') === 'gremio';
// agrupa os projetos por diretoria
$grupos = [];
foreach ($dados['projetos'] as $p) $grupos[$p['diretoria'] ?? ($ehGremio ? 'Sem diretoria (presidência)' : 'Projetos da turma')][] = $p;
$somar = fn($lista, $k) => array_sum(array_map(fn($p) => (float) $p[$k], $lista));
$g = $dados['geral'];
?>

<section class="hero-section nao-imprimir">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Prestação de contas</div>
        <h1>Prestação de contas</h1>
        <p class="hero-subtitle"><?= $ehGremio ? 'Grêmio' : 'Turma' ?> <strong><?= h($turma['nome']) ?></strong> · gerada em <?= date('d/m/Y H:i') ?>.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>/caixa"><i class="fas fa-arrow-left"></i> Caixa</a>
        <button type="button" class="button button-primary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir / PDF</button>
    </div>
</section>

<article class="panel pc-doc">
    <header class="pc-cab">
        <div><h2>Prestação de contas · <?= h($turma['nome']) ?></h2><p><?= date('d/m/Y') ?> · <?= h(App::getName()) ?></p></div>
        <div class="pc-saldo"><span>Saldo em caixa</span><b><?= $brl($caixa['saldo']) ?></b><small>entrou <?= $brl($caixa['entradas']) ?> · saiu <?= $brl($caixa['saidas']) ?></small></div>
    </header>

    <?php if (!$dados['projetos']): ?><p class="cx2-vazio">Nenhum projeto ainda.</p><?php endif; ?>
    <?php foreach ($grupos as $nome => $lista): ?>
        <section class="pc-bloco">
            <h3><?= $ehGremio ? '<i class="fas fa-sitemap"></i> ' : '' ?><?= h($nome) ?></h3>
            <table class="pc-tabela">
                <thead><tr><th>Projeto</th><th>Previsto</th><th>Arrecadado</th><th>Gasto</th><th>Saldo</th><th>Comprovantes</th></tr></thead>
                <tbody>
                    <?php foreach ($lista as $p): $saldo = $p['arrecadado'] - $p['gasto']; ?>
                        <tr>
                            <td><a href="<?= $basePath ?>/projetos/<?= (int) $p['id'] ?>/orcamento"><?= h($p['nome']) ?></a><?= $p['encerrado'] ? ' <small>(encerrado)</small>' : '' ?></td>
                            <td><?= $brl($p['previsto']) ?></td>
                            <td class="txt-e"><?= $brl($p['arrecadado']) ?></td>
                            <td class="txt-s<?= $p['previsto'] > 0 && $p['gasto'] > $p['previsto'] ? ' pc-estouro' : '' ?>"><?= $brl($p['gasto']) ?></td>
                            <td><?= $brl($saldo) ?></td>
                            <td><?= (int) $p['sem_comprovante'] ? '<span class="pc-alerta">' . (int) $p['sem_comprovante'] . ' sem</span>' : ((int) $p['lancamentos'] ? '<span class="pc-ok">ok</span>' : '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (count($lista) > 1): ?>
                    <tfoot><tr><td>Total</td><td><?= $brl($somar($lista, 'previsto')) ?></td><td><?= $brl($somar($lista, 'arrecadado')) ?></td><td><?= $brl($somar($lista, 'gasto')) ?></td><td><?= $brl($somar($lista, 'arrecadado') - $somar($lista, 'gasto')) ?></td><td></td></tr></tfoot>
                <?php endif; ?>
            </table>
        </section>
    <?php endforeach; ?>

    <section class="pc-bloco">
        <h3><i class="fas fa-sack-dollar"></i> Caixa geral (fora de projetos)</h3>
        <p><?= (int) $g['lancamentos'] ?> lançamento(s) · entrou <b class="txt-e"><?= $brl($g['arrecadado']) ?></b> · saiu <b class="txt-s"><?= $brl($g['gasto']) ?></b>
           <?= (int) $g['sem_comprovante'] ? ' · <span class="pc-alerta">' . (int) $g['sem_comprovante'] . ' gasto(s) sem comprovante</span>' : '' ?></p>
    </section>
    <footer class="pc-rodape">Os comprovantes ficam no Caixa da turma, abertos a todos os membros.</footer>
</article>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
