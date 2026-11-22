<?php
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$tid  = (int) $turma['id'];
$brl  = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$base = $basePath . '/turmas/' . $tid . '/plano';
$divisor = max($alunos, 1) >= 10 ? $alunos : 40; // turma ainda pequena: mostra a conta com 40 alunos
$statusParcela = ['pendente' => 'A pagar', 'pago' => 'Paga', 'cancelado' => 'Cancelada'];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Plano da turma</div>
        <h1><?= h($turma['nome']) ?></h1>
        <p class="hero-subtitle">O pagamento é por turma, só por Pix. O representante cuida do plano.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar à turma</a>
    </div>
</section>

<div class="notice notice-warning plano-teste">
    <span><i class="fas fa-flask"></i></span>
    <div>
        <strong>Ambiente de teste</strong>
        <p>Nenhum valor é cobrado. O Pix é ilustrativo e o pagamento é simulado por um botão.</p>
    </div>
</div>

<?php if ($atual && $atual['status'] === 'ativa'): ?>
    <?php
    $vencido   = $atual['fim'] && strtotime($atual['fim']) < time();
    $diasFim   = $atual['fim'] ? (int) floor((strtotime($atual['fim']) - time()) / 86400) : null;
    $pagas     = count(array_filter($parcelas, fn($p) => $p['status'] === 'pago'));
    $atrasada  = $proxima && strtotime($proxima['vencimento']) < strtotime('today');
    ?>
    <!-- ============ PLANO ATIVO ============ -->
    <section class="plano-ativo <?= $vencido ? 'vencido' : '' ?>">
        <div class="plano-ativo-topo">
            <div>
                <span class="plano-selo"><i class="fas fa-circle-check"></i> <?= $vencido ? 'Plano vencido' : 'Plano ativo' ?></span>
                <h2><?= h($atual['plano_nome']) ?></h2>
                <p>
                    <?php if (!$atual['fim']): ?>Sem vencimento
                    <?php elseif ($vencido): ?>Venceu em <?= ViewHelper::data($atual['fim']) ?>
                    <?php else: ?>Válido até <?= ViewHelper::data($atual['fim']) ?> · faltam <?= $diasFim ?> dia(s)<?php endif; ?>
                </p>
            </div>
        </div>
        <div class="grade-numeros">
            <div class="grade-celula"><span class="num"><?= $projetosUsados ?>/<?= (int) $atual['projetos_limite'] ?></span><small>projetos usados</small></div>
            <div class="grade-celula"><span class="num"><?= $pagas ?>/<?= (int) $atual['parcelas'] ?></span><small>parcelas pagas</small></div>
            <div class="grade-celula"><span class="num plano-num-valor"><?= $brl($atual['valor_total']) ?></span><small>total<?= (float) $atual['desconto_pct'] > 0 ? ' · ' . rtrim(rtrim(number_format((float) $atual['desconto_pct'], 2, ',', ''), '0'), ',') . '% de desconto' : '' ?></small></div>
        </div>
    </section>

    <?php if ($proxima): ?>
        <div class="notice <?= $atrasada ? 'notice-danger' : 'notice-blue' ?>">
            <span><i class="fas fa-receipt"></i></span>
            <div>
                <strong>Próxima parcela: <?= (int) $proxima['numero'] ?>/<?= (int) $atual['parcelas'] ?> de <?= $brl($proxima['valor']) ?></strong>
                <p><?= $atrasada ? 'Venceu em ' : 'Vence em ' ?><?= ViewHelper::data($proxima['vencimento']) ?>.
                <?= $atrasada ? 'Depois de 5 dias de atraso a turma é bloqueada (regra ainda não aplicada no teste).' : '' ?>
                <a href="<?= $basePath ?>/pagamentos/<?= (int) $proxima['id'] ?>">Pagar agora</a></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="bloco-titulo"><h2>Parcelas</h2></div>
    <ul class="lista-tarefas lista-parcelas">
        <?php foreach ($parcelas as $p): ?>
            <li>
                <span class="tarefa-icone <?= $p['status'] === 'pago' ? 'pago' : '' ?>"><i class="fas <?= $p['status'] === 'pago' ? 'fa-check' : 'fa-receipt' ?>"></i></span>
                <div class="tarefa-info">
                    <strong>Parcela <?= (int) $p['numero'] ?> · <?= $brl($p['valor']) ?></strong>
                    <small><?= $p['status'] === 'pago' ? 'Paga em ' . ViewHelper::data($p['pago_em'], true) : 'Vence em ' . ViewHelper::data($p['vencimento']) ?></small>
                </div>
                <?php if ($proxima && (int) $proxima['id'] === (int) $p['id']): ?>
                    <a class="tarefa-acao primaria" href="<?= $basePath ?>/pagamentos/<?= (int) $p['id'] ?>">Pagar</a>
                <?php else: ?>
                    <span class="parcela-status <?= h($p['status']) ?>"><?= $statusParcela[$p['status']] ?? $p['status'] ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

<?php elseif ($atual && $atual['status'] === 'aguardando'): ?>
    <!-- ============ PEDIDO AGUARDANDO O 1º PAGAMENTO ============ -->
    <section class="panel plano-aguardando">
        <h2>Pedido do plano <?= h($atual['plano_nome']) ?> aguardando pagamento</h2>
        <p>
            <?= (int) $atual['parcelas'] ?>x · total <?= $brl($atual['valor_total']) ?>.
            O plano começa a valer quando a 1ª parcela for paga.
        </p>
        <div class="plano-aguardando-acoes">
            <?php if ($proxima): ?>
                <a class="button button-primary" href="<?= $basePath ?>/pagamentos/<?= (int) $proxima['id'] ?>"><i class="fas fa-qrcode"></i> Pagar 1ª parcela (<?= $brl($proxima['valor']) ?>)</a>
            <?php endif; ?>
            <form method="POST" action="<?= $basePath ?>/assinaturas/<?= (int) $atual['id'] ?>/cancelar" class="d-inline">
                <?= ViewHelper::csrfField() ?>
                <button type="submit" class="button button-ghost" onclick="return confirm('Cancelar este pedido e escolher outro plano?')">Cancelar pedido</button>
            </form>
        </div>
    </section>

<?php else: ?>
    <!-- ============ ESCOLHER PLANO ============ -->
    <form method="GET" action="<?= $base ?>" class="plano-cupom">
        <label class="field-label" for="cupom">Tem um cupom?</label>
        <div class="plano-cupom-linha">
            <input id="cupom" type="text" name="cupom" class="field" maxlength="40" value="<?= h($codigoCupom) ?>" placeholder="Digite o código" autocomplete="off">
            <button type="submit" class="button button-secondary">Aplicar</button>
            <?php if ($cupom): ?><a class="text-link" href="<?= $base ?>">Remover</a><?php endif; ?>
        </div>
        <?php if ($cupom): ?>
            <small class="plano-cupom-ok"><i class="fas fa-tag"></i> Cupom <?= h($cupom['codigo']) ?> aplicado nos preços abaixo.</small>
        <?php elseif ($erroCupom): ?>
            <small class="txt-alerta"><?= h($erroCupom) ?></small>
        <?php endif; ?>
    </form>

    <form method="POST" action="<?= $base ?>/assinar" id="form-plano" class="plano-form">
        <?= ViewHelper::csrfField() ?>
        <input type="hidden" name="cupom" value="<?= h($cupom['codigo'] ?? '') ?>">

        <fieldset class="planos-grade">
            <legend class="bloco-titulo-legend">Escolha o plano</legend>
            <?php foreach ($planos as $i => $p):
                $pid = (int) $p['id'];
                $pr  = $precos[$pid][1];
                $maxParc = max(array_keys($precos[$pid]));
                $semJuros = 1;
                foreach ($precos[$pid] as $n => $x) if ($x['acrescimo_pct'] == 0) $semJuros = $n;
            ?>
                <label class="plano-card<?= $p['codigo'] === 'anual' ? ' destaque' : '' ?>">
                    <input type="radio" name="plano_id" value="<?= $pid ?>" <?= $p['codigo'] === 'anual' ? 'checked' : '' ?> required>
                    <?php if ($p['codigo'] === 'anual'): ?><span class="plano-tag">Mais escolhido</span><?php endif; ?>
                    <strong class="plano-nome"><?= h($p['nome']) ?></strong>
                    <span class="plano-duracao"><?= $p['meses'] ? (int) $p['meses'] . ' meses de acesso' : 'Acesso sem vencimento' ?> · <?= (int) $p['projetos'] ?> projetos</span>
                    <span class="plano-preco">
                        <?php if ($pr['desconto_pct'] > 0): ?><s><?= $brl($pr['lista']) ?></s><?php endif; ?>
                        <b class="num"><?= $brl($pr['avista']) ?></b>
                        <small>à vista</small>
                    </span>
                    <span class="plano-parcela">
                        <?php if ($maxParc > 1): ?>
                            ou até <?= $maxParc ?>x<?= $semJuros > 1 ? ' (' . $semJuros . 'x sem acréscimo)' : '' ?>
                        <?php else: ?>só à vista<?php endif; ?>
                    </span>
                    <span class="plano-por-aluno">≈ <?= $brl($pr['avista'] / $divisor) ?> por aluno, dividindo por <?= $divisor ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <section class="plano-resumo panel">
            <label class="field-label" for="parcelas">Parcelas</label>
            <select id="parcelas" name="parcelas" class="field"></select>
            <small class="field-help">A 1ª parcela libera o acesso. As demais vencem a cada 30 dias.</small>
        </section>

        <!-- Barra fixa: total e botão sempre à mão enquanto rola os planos -->
        <div class="aval-barra-salvar plano-barra">
            <span class="aval-status plano-resumo-total">
                <span><span id="resumo-plano"></span> · <span id="resumo-linha"></span></span>
                <strong id="resumo-total" class="num"></strong>
                <small id="resumo-extra"></small>
            </span>
            <button type="submit" class="button button-primary plano-botao"><i class="fas fa-qrcode"></i> Gerar Pix</button>
        </div>
    </form>

    <script>
    (function () {
        var precos = <?= json_encode($precos, JSON_UNESCAPED_UNICODE) ?>;
        var form = document.getElementById('form-plano');
        var sel  = document.getElementById('parcelas');
        var brl  = function (v) { return 'R$ ' + Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
        function planoAtual() { var r = form.querySelector('[name=plano_id]:checked'); return r ? r.value : null; }
        function montarParcelas() {
            var op = precos[planoAtual()] || {}, antes = sel.value;
            sel.innerHTML = '';
            Object.keys(op).forEach(function (n) {
                var x = op[n], o = document.createElement('option');
                o.value = n;
                o.textContent = n === '1' ? '1x de ' + brl(x.total) + ' (à vista)'
                    : n + 'x de ' + brl(x.parcela) + (x.acrescimo_pct > 0 ? ' (total ' + brl(x.total) + ')' : ' sem acréscimo');
                sel.appendChild(o);
            });
            if (op[antes]) sel.value = antes;
            resumo();
        }
        function resumo() {
            var x = (precos[planoAtual()] || {})[sel.value];
            if (!x) return;
            var card = form.querySelector('[name=plano_id]:checked').closest('.plano-card');
            document.getElementById('resumo-plano').textContent = card.querySelector('.plano-nome').textContent;
            document.getElementById('resumo-linha').textContent = x.parcelas > 1 ? x.parcelas + 'x de ' + brl(x.parcela) : 'à vista';
            document.getElementById('resumo-total').textContent = brl(x.total);
            var extra = [];
            if (x.desconto_pct > 0) extra.push('cupom: ' + x.desconto_pct.toString().replace('.', ',') + '% de desconto');
            if (x.acrescimo_pct > 0) extra.push('inclui ' + x.acrescimo_pct + '% do parcelamento');
            document.getElementById('resumo-extra').textContent = extra.join(' · ');
        }
        form.addEventListener('change', function (e) { if (e.target.name === 'plano_id') montarParcelas(); else resumo(); });
        montarParcelas();
    })();
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
