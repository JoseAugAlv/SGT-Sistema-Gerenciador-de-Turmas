<?php
// app/Views/caixa/index.php
// Caixa da turma/grêmio: saldo, mês, gráfico dos últimos 6 meses, lançar (gestor), lançamentos e contribuições.
$cssPagina = ['projetos.css', 'caixa.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$base = $basePath . '/turmas/' . $tid . '/caixa';
$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$meses = ['01' => 'jan', '02' => 'fev', '03' => 'mar', '04' => 'abr', '05' => 'mai', '06' => 'jun', '07' => 'jul', '08' => 'ago', '09' => 'set', '10' => 'out', '11' => 'nov', '12' => 'dez'];
$rotMes = fn($m) => $meses[substr($m, 5, 2)] . '/' . substr($m, 2, 2);
$maxG = max(1, max(array_map(fn($g) => max($g['entradas'], $g['saidas']), $grafico)));
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Caixa</div>
        <h1>Caixa da <?= ($turma['tipo'] ?? 'turma') === 'gremio' ? 'grêmio' : 'turma' ?></h1>
        <p class="hero-subtitle">Entradas, saídas e comprovantes da <strong><?= h($turma['nome']) ?></strong>, à vista de todos.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>/prestacao"><i class="fas fa-file-invoice-dollar"></i> Prestação de contas</a>
        <a class="button button-ghost" href="<?= $base ?>/csv"><i class="fas fa-file-csv"></i> Exportar</a>
    </div>
</section>

<div id="cx2-topo">
<div class="cx2-resumo">
    <div class="panel cx2-saldo<?= $geral['saldo'] < 0 ? ' negativo' : '' ?>"><span>Saldo em caixa</span><b><?= $brl($geral['saldo']) ?></b><small><?= $brl($geral['entradas']) ?> entraram · <?= $brl($geral['saidas']) ?> saíram</small></div>
    <div class="panel"><span><?= $mes ? 'Entradas em ' . $rotMes($mes) : 'Entradas neste mês' ?></span><b class="txt-e"><?= $brl($doMes['entradas']) ?></b></div>
    <div class="panel"><span><?= $mes ? 'Saídas em ' . $rotMes($mes) : 'Saídas neste mês' ?></span><b class="txt-s"><?= $brl($doMes['saidas']) ?></b></div>
</div>

<section class="panel cx2-grafico" aria-label="Entradas e saídas dos últimos 6 meses">
    <?php foreach ($grafico as $g): ?>
        <a class="cx2-mes<?= $mes === $g['mes'] ? ' ativo' : '' ?>" href="<?= $base ?>?mes=<?= $g['mes'] ?>" title="<?= $rotMes($g['mes']) ?>: entrou <?= $brl($g['entradas']) ?>, saiu <?= $brl($g['saidas']) ?>">
            <div class="cx2-barras"><i class="e" style="height: <?= round($g['entradas'] / $maxG * 100) ?>%"></i><i class="s" style="height: <?= round($g['saidas'] / $maxG * 100) ?>%"></i></div>
            <small><?= $rotMes($g['mes']) ?></small>
        </a>
    <?php endforeach; ?>
    <p class="cx2-legenda"><span class="e"></span> Entradas <span class="s"></span> Saídas · clique num mês para filtrar</p>
</section>
</div>

<?php if ($gestor): ?>
    <details class="panel mb-16"<?= !$lancamentos ? ' open' : '' ?>>
        <summary><strong><i class="fas fa-plus"></i> Novo lançamento</strong></summary>
        <form method="POST" action="<?= $base ?>" enctype="multipart/form-data" class="cx2-form" data-parcial="#cx2-topo,#cx2-lista" data-parcial-limpar>
            <?= ViewHelper::csrfField() ?>
            <div class="cx2-tipo cx2-larga">
                <label><input type="radio" name="tipo" value="entrada" checked><span class="e"><i class="fas fa-arrow-down"></i> Entrada</span></label>
                <label><input type="radio" name="tipo" value="saida"><span class="s"><i class="fas fa-arrow-up"></i> Saída</span></label>
            </div>
            <label>Valor (R$) *<input type="text" name="valor" class="field" inputmode="decimal" required placeholder="0,00"></label>
            <label>Data *<input type="date" name="data" class="field" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></label>
            <label class="cx2-larga">Descrição *<input type="text" name="descricao" class="field" maxlength="200" required placeholder="Ex.: Contribuição para a formatura"></label>
            <label>Categoria
                <select name="categoria" class="field" data-cat>
                    <?php foreach (Caixa::CATEGORIAS as $t => $cats): foreach ($cats as $c): ?><option value="<?= h($c) ?>" data-tipo="<?= $t ?>"><?= h($c) ?></option><?php endforeach; endforeach; ?>
                </select>
            </label>
            <label data-so="entrada">Quem pagou <small>(opcional)</small>
                <select name="pessoa_id" class="field"><option value="">—</option><?php foreach ($pessoas as $id => $n): ?><option value="<?= (int) $id ?>"><?= h($n) ?></option><?php endforeach; ?></select>
            </label>
            <?php if ($diretorias): ?>
                <label>Diretoria <small>(opcional)</small>
                    <select name="diretoria_id" class="field"><option value="">Geral</option><?php foreach ($diretorias as $d): ?><option value="<?= (int) $d['id'] ?>"><?= h($d['nome']) ?></option><?php endforeach; ?></select>
                </label>
            <?php endif; ?>
            <label class="cx2-larga">Comprovante <small>(foto ou PDF, até 5 MB)</small><input type="file" name="comprovante" class="field" accept=".pdf,image/jpeg,image/png,image/webp"></label>
            <p class="cx2-larga mb-0"><button type="submit" class="button button-primary" data-carregando="Lançando…">Lançar</button></p>
        </form>
    </details>
<?php endif; ?>

<div class="cx2-grade" id="cx2-lista">
    <section class="panel">
        <h2 class="cx2-h2"><i class="fas fa-list"></i> Lançamentos<?= $mes ? ' de ' . $rotMes($mes) . ' · <a href="' . $base . '">ver todos</a>' : '' ?></h2>
        <?php if (!$lancamentos): ?>
            <p class="cx2-vazio">Nenhum lançamento<?= $mes ? ' neste mês' : ' ainda' ?>.</p>
        <?php else: ?>
            <ul class="cx2-lista">
                <?php foreach ($lancamentos as $l): ?>
                    <li class="<?= $l['tipo'] ?>">
                        <span class="cx2-ico"><i class="fas <?= $l['tipo'] === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i></span>
                        <div class="cx2-info">
                            <strong><?= h($l['descricao']) ?></strong>
                            <small><?= date('d/m/Y', strtotime($l['data'])) ?> · <?= h($l['categoria']) ?><?= $l['pessoa'] ? ' · ' . h($l['pessoa']) : '' ?><?= $l['diretoria'] ? ' · ' . h($l['diretoria']) : '' ?><?= !empty($l['projeto']) ? ' · projeto ' . h($l['projeto']) : '' ?></small>
                        </div>
                        <?php if ($l['comprovante']): ?><a class="cx2-comp" href="<?= $basePath ?>/caixa/<?= (int) $l['id'] ?>/comprovante" target="_blank" rel="noopener" title="Ver comprovante"><i class="fas fa-paperclip"></i></a><?php endif; ?>
                        <b class="cx2-valor"><?= $l['tipo'] === 'saida' ? '−' : '+' ?> <?= $brl($l['valor']) ?></b>
                        <?php if ($gestor): ?>
                            <form method="POST" action="<?= $basePath ?>/caixa/<?= (int) $l['id'] ?>/excluir" class="d-inline" data-parcial="#cx2-topo,#cx2-lista"><?= ViewHelper::csrfField() ?><button type="submit" class="cx2-del" aria-label="Remover" onclick="return confirm('Remover este lançamento?')"><i class="fas fa-trash"></i></button></form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="panel">
        <h2 class="cx2-h2"><i class="fas fa-hand-holding-dollar"></i> Contribuições</h2>
        <?php if (!$contribuicoes): ?>
            <p class="cx2-vazio">Ao lançar uma entrada com "Quem pagou", a contribuição aparece aqui.</p>
        <?php else: ?>
            <ul class="cx2-contrib">
                <?php foreach ($contribuicoes as $c): ?><li><span><?= h($c['nome']) ?></span><b><?= $brl($c['total']) ?></b><small><?= (int) $c['vezes'] ?>x · última em <?= date('d/m', strtotime($c['ultima'])) ?></small></li><?php endforeach; ?>
            </ul>
            <?php $faltam = array_diff_key($pessoas, array_flip(array_column($contribuicoes, 'id'))); if ($faltam && $gestor): ?>
                <details class="cx2-faltam"><summary><?= count($faltam) ?> ainda não contribuíram</summary><p><?= h(implode(', ', $faltam)) ?></p></details>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<script>
(function () {
    var f = document.querySelector('.cx2-form'); if (!f) return;
    function trocar() {
        var t = f.querySelector('[name=tipo]:checked').value, sel = f.querySelector('[data-cat]'), primeiro = null;
        sel.querySelectorAll('option').forEach(function (o) { var ok = o.dataset.tipo === t; o.hidden = !ok; o.disabled = !ok; if (ok && !primeiro) primeiro = o; });
        if (sel.selectedOptions[0].disabled) primeiro.selected = true;
        f.querySelectorAll('[data-so]').forEach(function (c) { c.hidden = c.dataset.so !== t; });
    }
    f.addEventListener('change', function (e) { if (e.target.name === 'tipo') trocar(); });
    trocar();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
