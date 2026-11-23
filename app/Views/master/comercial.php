<?php
require_once __DIR__ . '/../../Controllers/EscolaController.php';
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$pct = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',') . '%';
$tipoCupom = ['todos' => 'Turma e grêmio', 'turma' => 'Só turma', 'gremio' => 'Só grêmio'];
$statusVenda = ['aguardando' => 'Aguardando pagamento', 'ativa' => 'Ativa', 'cancelada' => 'Cancelada', 'trocada' => 'Trocada'];
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

<!-- modo de pagamento: teste (simulado) ou Mercado Pago (Pix real) -->
<?php $temToken = (string) App::get('MP_ACCESS_TOKEN', '') !== ''; ?>
<div class="notice <?= $modo === 'teste' ? 'notice-warning' : 'notice-success' ?> modo-pag">
    <span><i class="fas <?= $modo === 'teste' ? 'fa-flask' : 'fa-bolt' ?>"></i></span>
    <div>
        <strong><?= $modo === 'teste' ? 'Pagamentos em modo de teste' : 'Cobrança real pelo Mercado Pago' ?></strong>
        <p><?= $modo === 'teste' ? 'Os Pix são ilustrativos e os pagamentos são simulados.' : 'Cada parcela gera um Pix de verdade; a confirmação chega sozinha (webhook e consulta automática).' ?>
           <?= !$temToken ? 'Para cobrar de verdade, coloque <code>MP_ACCESS_TOKEN</code> (e <code>MP_WEBHOOK_SECRET</code>) no <code>.env</code> — veja o <code>.env.example</code>.' : '' ?></p>
        <form method="POST" action="<?= $basePath ?>/master/comercial/modo" class="d-inline">
            <?= ViewHelper::csrfField() ?>
            <input type="hidden" name="modo" value="<?= $modo === 'teste' ? 'mercadopago' : 'teste' ?>">
            <button type="submit" class="button button-secondary"<?= $modo === 'teste' && !$temToken ? ' disabled title="Falta o MP_ACCESS_TOKEN no .env"' : '' ?>
                onclick="return confirm('<?= $modo === 'teste' ? 'Ligar a cobrança REAL pelo Mercado Pago? Os próximos Pix serão de verdade.' : 'Voltar para o modo de teste (pagamentos simulados)?' ?>')">
                <?= $modo === 'teste' ? 'Ligar cobrança real (Mercado Pago)' : 'Voltar ao modo de teste' ?>
            </button>
        </form>
    </div>
</div>

<div class="grade-numeros">
    <div class="grade-celula"><span class="num"><?= $ativas ?></span><small>planos ativos</small></div>
    <div class="grade-celula"><span class="num plano-num-valor"><?= $brl($recebido) ?></span><small>recebido<?= $modo === 'teste' ? ' (simulado)' : '' ?></small></div>
    <div class="grade-celula"><span class="num"><?= count(array_filter($cupons, fn($c) => $c['ativo'])) ?></span><small>cupons ativos</small></div>
</div>

<div class="comercial-grade">
    <!-- ============ PERCENTUAIS ============ -->
    <section class="panel">
        <h2 class="comercial-h2">Regras de cobrança</h2>
        <p class="field-help">Cupom sem percentual próprio usa estes valores. Descontos não acumulam e nunca passam do piso de cada plano.</p>
        <form method="POST" action="<?= $basePath ?>/master/comercial/config">
            <?= ViewHelper::csrfField() ?>
            <?php foreach ($editaveis as $k => [$rotulo, $min, $max]): ?>
                <label class="field-label" for="cfg-<?= $k ?>"><?= h($rotulo) ?></label>
                <input id="cfg-<?= $k ?>" type="number" name="<?= $k ?>" class="field" min="<?= $min ?>" max="<?= $max ?>" step="0.01" value="<?= h($config[$k]) ?>">
            <?php endforeach; ?>
            <label class="check-line mt-12"><input type="checkbox" name="exigir_plano" value="1"<?= $exigirPlano ? ' checked' : '' ?>> Exigir plano ativo (turmas sem plano ficam bloqueadas, exceto as isentas)</label>
            <small class="field-help">Indicação: cada turma com plano ativo tem um código na tela do plano. Descontos não somam; vale o maior.</small>
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
            <p class="field-label mt-12">Desconto em cada plano <small class="txt-mudo">(opcional)</small></p>
            <?php $regrasAtuais = []; $prefixoId = 'novo'; require __DIR__ . '/_cupom_regras.php'; ?>
            <p class="mt-16"><button type="submit" class="button button-primary"><i class="fas fa-tag"></i> Criar cupom</button></p>
        </form>
    </section>
</div>

<!-- ============ CUPONS ============ -->
<div class="bloco-titulo" id="cupons"><h2>Cupons</h2></div>
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
                    <?php
                    $resumoRegras = [];
                    foreach ($c['regras'] as $pid => $r) {
                        $nomeP = ''; foreach ($planos as $pp) if ((int) $pp['id'] === (int) $pid) $nomeP = $pp['nome'];
                        $resumoRegras[] = $nomeP . ' ' . ($r['tipo_desconto'] === 'valor' ? $brl($r['valor']) : $pct($r['valor']));
                    }
                    ?>
                    <strong><?= h($c['codigo']) ?> · <?= $resumoRegras ? h(implode(' · ', $resumoRegras)) : ($c['percentual'] !== null ? $pct($c['percentual']) : 'percentual padrão') ?></strong>
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
                <?php if ((int) $c['usos'] === 0): ?>
                    <form method="POST" action="<?= $basePath ?>/master/comercial/cupons/<?= (int) $c['id'] ?>/excluir" class="d-inline">
                        <?= ViewHelper::csrfField() ?>
                        <button type="submit" class="tarefa-acao" onclick="return confirm('Excluir o cupom <?= h($c['codigo']) ?>?')">Excluir</button>
                    </form>
                <?php endif; ?>
                <details class="cupom-editar">
                    <summary class="tarefa-acao">Editar</summary>
                    <form method="POST" action="<?= $basePath ?>/master/comercial/cupons/<?= (int) $c['id'] ?>/editar" class="cupom-editar-form">
                        <?= ViewHelper::csrfField() ?>
                        <label>Vale para
                            <select name="tipo_plano" class="field">
                                <?php foreach ($tipoCupom as $v => $l): ?><option value="<?= $v ?>"<?= $c['tipo_plano'] === $v ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                            </select>
                        </label>
                        <label>Desconto (%)
                            <input type="number" name="percentual" class="field" min="0.01" max="90" step="0.01" value="<?= h($c['percentual'] ?? '') ?>" placeholder="Padrão">
                        </label>
                        <label>Válido até
                            <input type="date" name="valido_ate" class="field" value="<?= h($c['valido_ate'] ?? '') ?>">
                        </label>
                        <label>Limite de usos
                            <input type="number" name="usos_max" class="field" min="<?= max(1, (int) $c['usos']) ?>" value="<?= h($c['usos_max'] ?? '') ?>" placeholder="Sem limite">
                        </label>
                        <div class="cupom-regras-editar"><?php $regrasAtuais = $c['regras']; $prefixoId = 'cp' . (int) $c['id']; require __DIR__ . '/_cupom_regras.php'; ?></div>
                        <button type="submit" class="button button-primary">Salvar cupom</button>
                    </form>
                </details>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- ============ PACOTES (ESCOLAS) ============ -->
<!-- ============ PEDIDOS DE PROPOSTA (página /escolas) ============ -->
<div class="bloco-titulo mt-24" id="escolas"><h2>Escolas interessadas</h2><a href="<?= $basePath ?>/escolas" target="_blank" rel="noopener">Ver a página Para escolas</a></div>
<?php if (empty($escolas)): ?>
    <p class="field-help">Os pedidos de proposta feitos na página <a href="<?= $basePath ?>/escolas" target="_blank" rel="noopener">Para escolas</a> aparecem aqui.</p>
<?php else: ?>
    <ul class="lista-tarefas">
        <?php foreach ($escolas as $c): ?>
            <li>
                <span class="tarefa-icone"><i class="fas fa-school"></i></span>
                <div class="tarefa-info">
                    <strong><?= h($c['escola']) ?><?= $c['cidade'] ? ' · ' . h($c['cidade']) : '' ?> — <?= (int) $c['turmas'] ?> turma(s)<?= $c['plano_nome'] ? ', ' . h($c['plano_nome']) : '' ?></strong>
                    <small><?= h($c['nome']) ?> · <a href="mailto:<?= h($c['email']) ?>"><?= h($c['email']) ?></a><?= $c['telefone'] ? ' · ' . h($c['telefone']) : '' ?> · <?= ViewHelper::data($c['created_at'], true) ?></small>
                    <?php if ($c['mensagem']): ?><small><?= h($c['mensagem']) ?></small><?php endif; ?>
                </div>
                <form method="POST" action="<?= $basePath ?>/master/escolas/<?= (int) $c['id'] ?>/status" class="d-inline">
                    <?= ViewHelper::csrfField() ?>
                    <select name="status" class="field" onchange="this.form.submit()" aria-label="Situação">
                        <?php foreach (EscolaController::STATUS as $k => $v): ?><option value="<?= $k ?>"<?= $c['status'] === $k ? ' selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
                    </select>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<div class="bloco-titulo mt-24" id="pacotes"><h2>Pacotes para escolas</h2></div>
<p class="field-help">Fechou com uma escola? Crie um pacote: o sistema gera um código por turma. A turma usa o código na compra e o plano fica sem custo (a escola já pagou).</p>
<details class="panel mb-16">
    <summary><strong><i class="fas fa-plus"></i> Novo pacote</strong></summary>
    <form method="POST" action="<?= $basePath ?>/master/comercial/pacotes" class="novo-plano-form">
        <?= ViewHelper::csrfField() ?>
        <label>Nome do pacote *<input type="text" name="nome" class="field" maxlength="100" required placeholder="Ex.: ETEC Morato"></label>
        <label>Plano *
            <select name="plano_id" class="field" required>
                <?php foreach ($planos as $p): if ($p['tipo'] !== 'turma') continue; ?>
                    <option value="<?= (int) $p['id'] ?>"<?= $p['codigo'] === 'semestral' ? ' selected' : '' ?>><?= h($p['nome']) ?> · <?= $p['meses'] ? (int) $p['meses'] . ' meses' : 'sem vencimento' ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quantas turmas *<input type="number" name="quantidade" class="field" min="1" max="300" value="12" required></label>
        <label>Códigos valem até<input type="date" name="valido_ate" class="field" min="<?= date('Y-m-d') ?>"></label>
        <p><button type="submit" class="button button-primary">Criar pacote e gerar códigos</button></p>
    </form>
</details>
<?php if (!$pacotes): ?>
    <div class="lista-vazia"><i class="fas fa-school"></i><span>Nenhum pacote criado.</span></div>
<?php else: ?>
    <?php foreach ($pacotes as $pk): ?>
        <details class="panel mb-12<?= $pk['ativo'] ? '' : ' inativo' ?>">
            <summary>
                <strong><?= h($pk['nome']) ?></strong> · <?= h($pk['plano_nome']) ?> ·
                <?= (int) $pk['usados'] ?>/<?= (int) $pk['quantidade'] ?> turma(s) usaram
                <?= $pk['valido_ate'] ? ' · até ' . ViewHelper::data($pk['valido_ate']) : '' ?><?= $pk['ativo'] ? '' : ' · desativado' ?>
            </summary>
            <ul class="pacote-codigos">
                <?php foreach ($pk['codigos'] as $cd): ?>
                    <li><code><?= h($cd['codigo']) ?></code>
                        <?= (int) $cd['usos'] > 0 ? '<span class="usado"><i class="fas fa-check"></i> ' . h($cd['turma_nome'] ?? 'usado') . '</span>' : '<span class="livre">' . ($cd['ativo'] ? 'livre' : 'desativado') . '</span>' ?></li>
                <?php endforeach; ?>
            </ul>
            <div class="plano-aguardando-acoes">
                <button type="button" class="button button-secondary" data-copiar-codigos="<?= h(implode("\n", array_column(array_filter($pk['codigos'], fn($x) => (int) $x['usos'] === 0), 'codigo'))) ?>"><i class="fas fa-copy"></i> Copiar códigos livres</button>
                <form method="POST" action="<?= $basePath ?>/master/comercial/pacotes/<?= (int) $pk['id'] ?>/alternar" class="d-inline">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="button button-ghost"><?= $pk['ativo'] ? 'Desativar códigos livres' : 'Reativar' ?></button>
                </form>
            </div>
        </details>
    <?php endforeach; ?>
<?php endif; ?>

<!-- ============ INDICAÇÕES ============ -->
<div class="bloco-titulo mt-24" id="indicacoes"><h2>Indicações</h2></div>
<p class="field-help">Turmas que indicaram outras e o desconto que ganharam (percentuais em “Regras de cobrança”).</p>
<?php if (!$indicacoes): ?>
    <div class="lista-vazia"><i class="fas fa-share-nodes"></i><span>Nenhuma indicação ainda.</span></div>
<?php else: ?>
    <ul class="lista-tarefas">
        <?php foreach ($indicacoes as $in): ?>
            <li>
                <span class="tarefa-icone"><i class="fas fa-gift"></i></span>
                <div class="tarefa-info">
                    <strong><?= h($in['turma_nome']) ?> indicou <?= h($in['origem_nome'] ?? 'uma turma') ?></strong>
                    <small><?= $pct($in['pct']) ?> de desconto · <?= ViewHelper::data($in['created_at']) ?> · <?= $in['status'] === 'usado' ? 'usado em ' . ViewHelper::data($in['usado_em']) : 'ainda não usado' ?></small>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
<script>
document.querySelectorAll('[data-copiar-codigos]').forEach(function (b) {
    b.addEventListener('click', function () { navigator.clipboard.writeText(b.dataset.copiarCodigos).then(function () { b.textContent = 'Copiado!'; }); });
});
</script>

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
                    <strong><a class="mat-link" href="<?= $basePath ?>/master/comercial/assinaturas/<?= (int) $v['id'] ?>"><?= h($v['turma_nome']) ?> · <?= h($v['plano_nome']) ?></a></strong>
                    <small>
                        <?= ViewHelper::data($v['created_at']) ?> · <?= (int) $v['parcelas'] ?>x · total <?= $brl($v['valor_total']) ?>
                        · pago <?= $brl($v['pago']) ?> (<?= (int) $v['parcelas_pagas'] ?>/<?= (int) $v['parcelas'] ?>)
                        <?= $v['cupom_codigo'] ? ' · cupom ' . h($v['cupom_codigo']) : '' ?>
                        <?= $v['troca_de_nome'] ? ' · troca do ' . h($v['troca_de_nome']) : '' ?>
                        <?= $v['fim'] ? ' · até ' . ViewHelper::data($v['fim']) : '' ?>
                    </small>
                </div>
                <span class="venda-status <?= h($v['status']) ?>"><?= $statusVenda[$v['status']] ?? $v['status'] ?></span>
                <a class="tarefa-acao" href="<?= $basePath ?>/master/comercial/assinaturas/<?= (int) $v['id'] ?>">Gerenciar</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- ============ PLANOS (editáveis) ============ -->
<div class="bloco-titulo mt-24" id="planos"><h2>Planos</h2></div>
<p class="field-help">Mudanças valem para as próximas compras. A tabela de acréscimo das parcelas segue o precos.md (A: desde 2x; B: 6x sem acréscimo).</p>
<div class="mov-wrapper">
    <table class="tabela-planos">
        <thead><tr><th>Plano</th><th>Meses</th><th class="dir">À vista (R$)</th><th class="dir">Piso (R$)</th><th>Parcelas (máx.)</th><th>Projetos</th><th>Pessoas</th><th>Ordem</th><th>Dia a dia</th><th>À venda</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($planos as $p): $fid = 'plano-' . (int) $p['id']; ?>
                <tr class="<?= $p['ativo'] ? '' : 'inativo' ?>">
                    <td><input form="<?= $fid ?>" class="field tp-nome" type="text" name="nome" maxlength="60" required value="<?= h($p['nome']) ?>" aria-label="Nome do plano"><small class="tp-tipo"><?= $p['tipo'] === 'gremio' ? 'Grêmio' : 'Turma' ?> · <?= h($p['codigo']) ?> · tabela <?= h($p['tabela_parcelas'] ?? '—') ?></small></td>
                    <td><input form="<?= $fid ?>" class="field tp-int" type="number" name="meses" min="1" max="120" value="<?= h($p['meses'] ?? '') ?>" placeholder="∞" title="Vazio = sem vencimento" aria-label="Meses de <?= h($p['nome']) ?>"></td>
                    <td class="dir"><input form="<?= $fid ?>" class="field tp-num" type="number" name="preco" step="0.01" min="0.01" value="<?= h($p['preco']) ?>" aria-label="Preço de <?= h($p['nome']) ?>"></td>
                    <td class="dir"><input form="<?= $fid ?>" class="field tp-num" type="number" name="piso" step="0.01" min="0.01" value="<?= h($p['piso']) ?>" aria-label="Piso de <?= h($p['nome']) ?>"></td>
                    <td><input form="<?= $fid ?>" class="field tp-int" type="number" name="max_parcelas" min="1" max="<?= $p['tabela_parcelas'] === 'B' ? 12 : ($p['tabela_parcelas'] === 'A' ? 6 : 1) ?>" value="<?= (int) $p['max_parcelas'] ?>" aria-label="Parcelas de <?= h($p['nome']) ?>"></td>
                    <td><input form="<?= $fid ?>" class="field tp-int" type="number" name="projetos" min="1" value="<?= (int) $p['projetos'] ?>" aria-label="Projetos de <?= h($p['nome']) ?>"></td>
                    <td><input form="<?= $fid ?>" class="field tp-int" type="number" name="pessoas_max" min="1" value="<?= (int) $p['pessoas_max'] ?>" aria-label="Pessoas de <?= h($p['nome']) ?>"></td>
                    <td><input form="<?= $fid ?>" class="field tp-int" type="number" name="ordem" value="<?= (int) $p['ordem'] ?>" aria-label="Ordem de <?= h($p['nome']) ?>"></td>
                    <td class="tp-dia"><label><input form="<?= $fid ?>" type="checkbox" name="inclui_dia_a_dia" value="1"<?= !empty($p['inclui_dia_a_dia']) ? ' checked' : '' ?>> incluso</label>
                        <input form="<?= $fid ?>" class="field tp-num" type="number" name="preco_dia_a_dia" step="0.01" min="0" value="<?= h($p['preco_dia_a_dia'] ?? '') ?>" placeholder="adicional R$" aria-label="Preço do adicional Dia a dia no <?= h($p['nome']) ?>"></td>
                    <td><input form="<?= $fid ?>" type="checkbox" name="ativo" value="1"<?= $p['ativo'] ? ' checked' : '' ?> aria-label="<?= h($p['nome']) ?> à venda"></td>
                    <td>
                        <form id="<?= $fid ?>" method="POST" action="<?= $basePath ?>/master/comercial/planos/<?= (int) $p['id'] ?>">
                            <?= ViewHelper::csrfField() ?>
                            <button type="submit" class="tarefa-acao">Salvar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<small class="field-help">Meses vazio = sem vencimento. Os planos de grêmio ainda não aparecem para compra: falta a lógica de grêmios.</small>

<details class="panel novo-plano mt-16">
    <summary><strong><i class="fas fa-plus"></i> Novo plano</strong></summary>
    <form method="POST" action="<?= $basePath ?>/master/comercial/planos/novo" class="novo-plano-form">
        <?= ViewHelper::csrfField() ?>
        <label>Código *<input type="text" name="codigo" class="field" maxlength="30" required placeholder="ex.: bienal"></label>
        <label>Nome *<input type="text" name="nome" class="field" maxlength="60" required placeholder="ex.: Bienal"></label>
        <label>Tipo
            <select name="tipo" class="field"><option value="turma">Turma</option><option value="gremio">Grêmio</option></select>
        </label>
        <label>Meses<input type="number" name="meses" class="field" min="1" max="120" placeholder="Vazio = sem vencimento"></label>
        <label>À vista (R$) *<input type="number" name="preco" class="field" step="0.01" min="0.01" required></label>
        <label>Piso (R$) *<input type="number" name="piso" class="field" step="0.01" min="0.01" required></label>
        <label>Tabela de parcelas
            <select name="tabela_parcelas" class="field">
                <option value="">Só à vista</option>
                <option value="A">A: acréscimo desde 2x (até 6x)</option>
                <option value="B">B: 6x sem acréscimo (até 12x)</option>
            </select>
        </label>
        <label>Parcelas (máx.)<input type="number" name="max_parcelas" class="field" min="1" max="12" value="1"></label>
        <label>Projetos *<input type="number" name="projetos" class="field" min="1" required></label>
        <label>Pessoas<input type="number" name="pessoas_max" class="field" min="1" value="50"></label>
        <label>Ordem<input type="number" name="ordem" class="field" value="<?= count($planos) + 1 ?>"></label>
        <label class="check-line"><input type="checkbox" name="inclui_dia_a_dia" value="1" checked> Dia a dia incluso</label>
        <label>Adicional Dia a dia (R$)<input type="number" name="preco_dia_a_dia" class="field" step="0.01" min="0" placeholder="Se não incluso"></label>
        <label class="check-line"><input type="checkbox" name="ativo" value="1" checked> À venda</label>
        <p><button type="submit" class="button button-primary">Criar plano</button></p>
    </form>
</details>

<!-- ============ TURMAS E ISENÇÃO ============ -->
<div class="bloco-titulo mt-24" id="turmas"><h2>Turmas que precisam de plano</h2></div>
<p class="field-help">Turmas isentas funcionam sem plano (as que já existiam ficaram isentas). Tire a isenção para passar a cobrar.</p>
<ul class="lista-tarefas lista-isencao">
    <?php foreach ($turmas as $t): ?>
        <li>
            <span class="tarefa-icone"><i class="fas <?= $t['isenta_plano'] ? 'fa-gift' : 'fa-receipt' ?>"></i></span>
            <div class="tarefa-info">
                <strong><a class="mat-link" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>/plano"><?= h($t['nome']) ?></a></strong>
                <small><?= $t['isenta_plano'] ? 'Isenta: não precisa de plano' : 'Precisa de plano ativo' ?></small>
            </div>
            <form method="POST" action="<?= $basePath ?>/master/comercial/turmas/<?= (int) $t['id'] ?>/isencao" class="d-inline">
                <?= ViewHelper::csrfField() ?>
                <button type="submit" class="tarefa-acao"><?= $t['isenta_plano'] ? 'Exigir plano' : 'Isentar' ?></button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
