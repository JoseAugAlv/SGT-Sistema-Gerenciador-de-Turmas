<?php
// app/Views/orcamento/projeto.php
// Orçamento do projeto: previsto x arrecadado x gasto, itens previstos, lançamentos com comprovante e diretoria (grêmio).
$cssPagina = ['projetos.css', 'caixa.css', 'orcamento.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$pid = (int) $projeto['id'];
$base = $basePath . '/projetos/' . $pid . '/orcamento';
$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$usado = $resumo['previsto'] > 0 ? min(100, round($resumo['gasto'] / $resumo['previsto'] * 100)) : 0;
$estourou = $resumo['previsto'] > 0 && $resumo['gasto'] > $resumo['previsto'];
$dirAtual = null;
foreach ($diretorias as $d) if ((int) $d['id'] === (int) ($projeto['diretoria_id'] ?? 0)) $dirAtual = $d;
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Orçamento</div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">Quanto foi previsto, arrecadado e gasto<?= $dirAtual ? ' · diretoria <strong>' . h($dirAtual['nome']) . '</strong>' : '' ?>.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>/prestacao"><i class="fas fa-file-invoice-dollar"></i> Prestação de contas</a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<div class="cx2-resumo">
    <div class="panel"><span>Previsto</span><b><?= $brl($resumo['previsto']) ?></b><small><?= count($itens) ?> item(ns)</small></div>
    <div class="panel"><span>Arrecadado</span><b class="txt-e"><?= $brl($resumo['arrecadado']) ?></b></div>
    <div class="panel"><span>Gasto</span><b class="txt-s"><?= $brl($resumo['gasto']) ?></b><small><?= $resumo['sem_comprovante'] ? $resumo['sem_comprovante'] . ' gasto(s) sem comprovante' : 'todos com comprovante' ?></small></div>
</div>

<?php if ($resumo['previsto'] > 0): ?>
    <section class="panel or-uso<?= $estourou ? ' estourou' : '' ?>">
        <div class="or-uso-topo"><strong><?= $estourou ? 'Passou do orçamento' : 'Uso do orçamento' ?></strong><span><?= $brl($resumo['gasto']) ?> de <?= $brl($resumo['previsto']) ?> (<?= $resumo['previsto'] > 0 ? round($resumo['gasto'] / $resumo['previsto'] * 100) : 0 ?>%)</span></div>
        <div class="or-barra"><i style="width: <?= $usado ?>%"></i></div>
        <small>Saldo do projeto (arrecadado − gasto): <b class="<?= $resumo['saldo'] < 0 ? 'txt-s' : 'txt-e' ?>"><?= $brl($resumo['saldo']) ?></b></small>
    </section>
<?php endif; ?>

<?php if ($diretorias && $podeDiretoria): ?>
    <form method="POST" action="<?= $base ?>/diretoria" class="panel or-dir">
        <?= ViewHelper::csrfField() ?>
        <label for="or-dir"><i class="fas fa-sitemap"></i> Diretoria responsável</label>
        <select id="or-dir" name="diretoria_id" class="field"><option value="">Nenhuma (o presidente cuida)</option>
            <?php foreach ($diretorias as $d): ?><option value="<?= (int) $d['id'] ?>"<?= $dirAtual && (int) $dirAtual['id'] === (int) $d['id'] ? ' selected' : '' ?>><?= h($d['nome']) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="button button-secondary">Salvar</button>
        <small>O diretor e os coordenadores dela também podem lançar os gastos deste projeto.</small>
    </form>
<?php endif; ?>

<div class="cx2-grade">
    <section class="panel">
        <h2 class="cx2-h2"><i class="fas fa-receipt"></i> Gastos e arrecadação</h2>
        <?php if ($pode && !$projeto['encerrado']): ?>
            <details class="or-lancar"<?= !$lancamentos ? ' open' : '' ?>>
                <summary>Lançar no caixa do projeto</summary>
                <form method="POST" action="<?= $base ?>/lancar" enctype="multipart/form-data" class="cx2-form">
                    <?= ViewHelper::csrfField() ?>
                    <div class="cx2-tipo cx2-larga">
                        <label><input type="radio" name="tipo" value="saida" checked><span class="s"><i class="fas fa-arrow-up"></i> Gasto</span></label>
                        <label><input type="radio" name="tipo" value="entrada"><span class="e"><i class="fas fa-arrow-down"></i> Arrecadação</span></label>
                    </div>
                    <label>Valor (R$) *<input type="text" name="valor" class="field" inputmode="decimal" required placeholder="0,00"></label>
                    <label>Data<input type="date" name="data" class="field" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></label>
                    <label class="cx2-larga">Descrição *<input type="text" name="descricao" class="field" maxlength="200" required placeholder="Ex.: Compra de isopor e tinta"></label>
                    <label class="cx2-larga">Comprovante <small>(foto ou PDF, até 5 MB)</small><input type="file" name="comprovante" class="field" accept=".pdf,image/jpeg,image/png,image/webp"></label>
                    <p class="cx2-larga mb-0"><button type="submit" class="button button-primary" data-carregando="Lançando…">Lançar</button></p>
                </form>
            </details>
        <?php endif; ?>
        <?php if (!$lancamentos): ?>
            <p class="cx2-vazio">Nenhum lançamento neste projeto ainda.</p>
        <?php else: ?>
            <ul class="cx2-lista">
                <?php foreach ($lancamentos as $l): ?>
                    <li class="<?= $l['tipo'] ?>">
                        <span class="cx2-ico"><i class="fas <?= $l['tipo'] === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i></span>
                        <div class="cx2-info"><strong><?= h($l['descricao']) ?></strong><small><?= date('d/m/Y', strtotime($l['data'])) ?> · <?= h($l['registrado'] ?? '') ?></small></div>
                        <?php if ($l['comprovante']): ?><a class="cx2-comp" href="<?= $basePath ?>/caixa/<?= (int) $l['id'] ?>/comprovante" target="_blank" rel="noopener" title="Ver comprovante"><i class="fas fa-paperclip"></i></a>
                        <?php elseif ($l['tipo'] === 'saida'): ?><span class="or-sem" title="Sem comprovante"><i class="fas fa-triangle-exclamation"></i></span><?php endif; ?>
                        <b class="cx2-valor"><?= $l['tipo'] === 'saida' ? '−' : '+' ?> <?= $brl($l['valor']) ?></b>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2 class="cx2-h2"><i class="fas fa-list-check"></i> Itens previstos</h2>
        <?php if ($itens): ?>
            <ul class="or-itens">
                <?php foreach ($itens as $i): ?>
                    <li><span><?= h($i['descricao']) ?></span><b><?= $brl($i['valor_previsto']) ?></b>
                        <?php if ($pode && !$projeto['encerrado']): ?>
                            <form method="POST" action="<?= $base ?>/item" class="d-inline"><?= ViewHelper::csrfField() ?><input type="hidden" name="acao" value="remover"><input type="hidden" name="item_id" value="<?= (int) $i['id'] ?>"><button type="submit" class="cx2-del" aria-label="Remover item"><i class="fas fa-xmark"></i></button></form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                <li class="or-total"><span>Total previsto</span><b><?= $brl($resumo['previsto']) ?></b></li>
            </ul>
        <?php else: ?><p class="cx2-vazio">Liste o que o projeto vai precisar comprar e quanto deve custar.</p><?php endif; ?>
        <?php if ($pode && !$projeto['encerrado']): ?>
            <form method="POST" action="<?= $base ?>/item" class="or-novo-item">
                <?= ViewHelper::csrfField() ?>
                <input type="text" name="descricao" class="field" maxlength="150" required placeholder="Item (ex.: Tinta)">
                <input type="text" name="valor" class="field" inputmode="decimal" required placeholder="R$ 0,00">
                <button type="submit" class="button button-secondary" aria-label="Adicionar item"><i class="fas fa-plus"></i></button>
            </form>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
