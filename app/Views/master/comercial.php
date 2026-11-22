<?php
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$pct = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',') . '%';
$tipoCupom = ['todos' => 'Turma e grêmio', 'turma' => 'Só turma', 'gremio' => 'Só grêmio'];
$statusVenda = ['aguardando' => 'Aguardando pagamento', 'ativa' => 'Ativa', 'cancelada' => 'Cancelada'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Master</div>
        <h1>Comercial</h1>
        <p class="hero-subtitle">Planos, descontos, cupons e vendas. Preços de referência em precos.md.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/master"><i class="fas fa-arrow-left"></i> Painel do master</a>
    </div>
</section>

<?php if ($modo === 'teste'): ?>
    <div class="notice notice-warning">
        <span><i class="fas fa-flask"></i></span>
        <div><strong>Pagamentos em modo de teste</strong><p>Os Pix são ilustrativos e os pagamentos são simulados. A integração com o Mercado Pago entra numa etapa futura.</p></div>
    </div>
<?php endif; ?>

<div class="grade-numeros">
    <div class="grade-celula"><span class="num"><?= $ativas ?></span><small>planos ativos</small></div>
    <div class="grade-celula"><span class="num plano-num-valor"><?= $brl($recebido) ?></span><small>recebido<?= $modo === 'teste' ? ' (simulado)' : '' ?></small></div>
    <div class="grade-celula"><span class="num"><?= count(array_filter($cupons, fn($c) => $c['ativo'])) ?></span><small>cupons ativos</small></div>
</div>

<div class="comercial-grade">
    <!-- ============ PERCENTUAIS ============ -->
    <section class="panel">
        <h2 class="comercial-h2">Descontos padrão</h2>
        <p class="field-help">Cupom sem percentual próprio usa estes valores. Descontos não acumulam e nunca passam do piso de cada plano.</p>
        <form method="POST" action="<?= $basePath ?>/master/comercial/config">
            <?= ViewHelper::csrfField() ?>
            <?php foreach ($editaveis as $k => [$rotulo, $min, $max]): ?>
                <label class="field-label" for="cfg-<?= $k ?>"><?= h($rotulo) ?></label>
                <input id="cfg-<?= $k ?>" type="number" name="<?= $k ?>" class="field" min="<?= $min ?>" max="<?= $max ?>" step="0.01" value="<?= h($config[$k]) ?>">
            <?php endforeach; ?>
            <small class="field-help">A indicação fica guardada aqui, mas o fluxo de indicação ainda não foi construído.</small>
            <p class="mt-16"><button type="submit" class="button button-primary">Salvar percentuais</button></p>
        </form>
    </section>

    <!-- ============ NOVO CUPOM ============ -->
    <section class="panel">
        <h2 class="comercial-h2">Novo cupom</h2>
        <form method="POST" action="<?= $basePath ?>/master/comercial/cupons">
            <?= ViewHelper::csrfField() ?>
            <label class="field-label" for="cp-codigo">Código *</label>
            <input id="cp-codigo" type="text" name="codigo" class="field" maxlength="40" required placeholder="Ex.: VOLTAASAULAS">
            <div class="field-row">
                <div>
                    <label class="field-label" for="cp-tipo">Vale para</label>
                    <select id="cp-tipo" name="tipo_plano" class="field">
                        <?php foreach ($tipoCupom as $v => $l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="cp-pct">Desconto (%)</label>
                    <input id="cp-pct" type="number" name="percentual" class="field" min="0.01" max="90" step="0.01" placeholder="Padrão">
                </div>
            </div>
            <div class="field-row">
                <div>
                    <label class="field-label" for="cp-ate">Válido até</label>
                    <input id="cp-ate" type="date" name="valido_ate" class="field" min="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="field-label" for="cp-usos">Limite de usos</label>
                    <input id="cp-usos" type="number" name="usos_max" class="field" min="1" placeholder="Sem limite">
                </div>
            </div>
            <p class="mt-16"><button type="submit" class="button button-primary"><i class="fas fa-tag"></i> Criar cupom</button></p>
        </form>
    </section>
</div>

<!-- ============ CUPONS ============ -->
<div class="bloco-titulo"><h2>Cupons</h2></div>
<?php if (!$cupons): ?>
    <div class="lista-vazia"><i class="fas fa-tag"></i><span>Nenhum cupom criado.</span></div>
<?php else: ?>
    <ul class="lista-tarefas lista-cupons">
        <?php foreach ($cupons as $c):
            $vencido = $c['valido_ate'] && $c['valido_ate'] < date('Y-m-d');
            $esgotado = $c['usos_max'] !== null && (int) $c['usos'] >= (int) $c['usos_max'];
        ?>
            <li class="<?= !$c['ativo'] || $vencido || $esgotado ? 'inativo' : '' ?>">
                <span class="tarefa-icone"><i class="fas fa-tag"></i></span>
                <div class="tarefa-info">
                    <strong><?= h($c['codigo']) ?> · <?= $c['percentual'] !== null ? $pct($c['percentual']) : 'percentual padrão' ?></strong>
                    <small>
                        <?= $tipoCupom[$c['tipo_plano']] ?> ·
                        <?= $c['valido_ate'] ? ($vencido ? 'venceu em ' : 'até ') . ViewHelper::data($c['valido_ate']) : 'sem validade' ?> ·
                        <?= (int) $c['usos'] ?><?= $c['usos_max'] !== null ? '/' . (int) $c['usos_max'] : '' ?> uso(s)
                        <?= !$c['ativo'] ? ' · desativado' : ($esgotado ? ' · esgotado' : '') ?>
                    </small>
                </div>
                <form method="POST" action="<?= $basePath ?>/master/comercial/cupons/<?= (int) $c['id'] ?>/alternar" class="d-inline">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="tarefa-acao"><?= $c['ativo'] ? 'Desativar' : 'Reativar' ?></button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- ============ VENDAS ============ -->
<div class="bloco-titulo mt-24"><h2>Vendas</h2></div>
<?php if (!$vendas): ?>
    <div class="lista-vazia"><i class="fas fa-receipt"></i><span>Nenhuma compra ainda.</span></div>
<?php else: ?>
    <ul class="lista-tarefas lista-vendas">
        <?php foreach ($vendas as $v): ?>
            <li>
                <span class="tarefa-icone"><i class="fas fa-receipt"></i></span>
                <div class="tarefa-info">
                    <strong><?= h($v['turma_nome']) ?> · <?= h($v['plano_nome']) ?></strong>
                    <small>
                        <?= ViewHelper::data($v['created_at']) ?> · <?= (int) $v['parcelas'] ?>x · total <?= $brl($v['valor_total']) ?>
                        · pago <?= $brl($v['pago']) ?> (<?= (int) $v['parcelas_pagas'] ?>/<?= (int) $v['parcelas'] ?>)
                        <?= $v['cupom_codigo'] ? ' · cupom ' . h($v['cupom_codigo']) : '' ?>
                        <?= $v['fim'] ? ' · até ' . ViewHelper::data($v['fim']) : '' ?>
                    </small>
                </div>
                <span class="venda-status <?= h($v['status']) ?>"><?= $statusVenda[$v['status']] ?? $v['status'] ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- ============ PLANOS ============ -->
<details class="comercial-planos mt-24">
    <summary>Tabela de planos (<?= count($planos) ?>)</summary>
    <div class="mov-wrapper">
        <table class="tabela-planos">
            <thead><tr><th>Plano</th><th>Tipo</th><th>Acesso</th><th>Projetos</th><th>Pessoas</th><th class="dir">À vista</th><th class="dir">Piso</th><th>Parcelas</th></tr></thead>
            <tbody>
                <?php foreach ($planos as $p): ?>
                    <tr>
                        <td><strong><?= h($p['nome']) ?></strong></td>
                        <td><?= $p['tipo'] === 'gremio' ? 'Grêmio' : 'Turma' ?></td>
                        <td><?= $p['meses'] ? (int) $p['meses'] . ' meses' : 'Sem vencimento' ?></td>
                        <td><?= (int) $p['projetos'] ?></td>
                        <td>até <?= (int) $p['pessoas_max'] ?></td>
                        <td class="dir"><?= $brl($p['preco']) ?></td>
                        <td class="dir"><?= $brl($p['piso']) ?></td>
                        <td>até <?= (int) $p['max_parcelas'] ?>x</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <small class="field-help">Os planos de grêmio ainda não aparecem para compra: falta a lógica de grêmios.</small>
</details>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
