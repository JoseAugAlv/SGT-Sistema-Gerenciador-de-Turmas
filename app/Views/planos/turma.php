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
                    <strong><?= ($p['tipo'] ?? 'parcela') === 'adicional' ? 'Dia a dia (adicional)' : 'Parcela ' . (int) $p['numero'] ?> · <?= $brl($p['valor']) ?></strong>
                    <small><?= $p['status'] === 'pago' ? 'Paga em ' . ViewHelper::data($p['pago_em'], true) : 'Vence em ' . ViewHelper::data($p['vencimento']) ?></small>
                </div>
                <?php if ($proxima && (int) $proxima['id'] === (int) $p['id']):
                    require_once __DIR__ . '/../../Models/Rateio.php';
                    $rateioAberto = (new Rateio())->abertoDoPagamento((int) $p['id']); ?>
                    <?php if ($rateioAberto): ?>
                        <a class="tarefa-acao primaria" href="<?= $basePath ?>/rateios/<?= (int) $rateioAberto['id'] ?>"><i class="fas fa-people-arrows"></i> Ver divisão</a>
                    <?php else: ?>
                        <a class="tarefa-acao" href="<?= $basePath ?>/pagamentos/<?= (int) $p['id'] ?>/rateio" title="Cada aluno paga a sua parte pelo Pix"><i class="fas fa-people-arrows"></i> Dividir</a>
                        <a class="tarefa-acao primaria" href="<?= $basePath ?>/pagamentos/<?= (int) $p['id'] ?>">Pagar</a>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="parcela-status <?= h($p['status']) ?>"><?= $statusParcela[$p['status']] ?? $p['status'] ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- ============ DIA A DIA (adicional nos planos que não incluem) ============ -->
    <?php if ($diaPreco > 0 && empty($atual['dia_a_dia'])):
        $teste = PlanoGuard::testeDia($turma); ?>
        <section class="panel dia-oferta mt-24" id="dia-a-dia">
            <div class="dia-oferta-ico"><i class="fas fa-calendar-days"></i></div>
            <div>
                <h2>Libere o Dia a dia</h2>
                <?php if ($teste['ativo']): ?>
                    <p class="dia-teste-ativo"><i class="fas fa-hourglass-half"></i> Teste grátis ativo até <?= date('d/m/Y', strtotime($teste['ate'])) ?>
                       (falta<?= $teste['faltam'] > 1 ? 'm' : '' ?> <?= $teste['faltam'] ?> dia<?= $teste['faltam'] > 1 ? 's' : '' ?>). Para continuar depois, libere abaixo.</p>
                <?php endif; ?>
                <p>Agenda interativa com lembretes na véspera e prioridades por cor, horário das aulas, aviso do dia e contagem regressiva.
                   Para a turma toda e na agenda pessoal de cada aluno.</p>
                <div class="dia-oferta-acoes">
                    <?php if ($diaPendente): ?>
                        <a class="button button-primary" href="<?= $basePath ?>/pagamentos/<?= (int) $diaPendente['id'] ?>"><i class="fas fa-qrcode"></i> Pagar <?= $brl($diaPendente['valor']) ?></a>
                        <form method="POST" action="<?= $basePath ?>/pagamentos/<?= (int) $diaPendente['id'] ?>/cancelar-adicional" class="d-inline">
                            <?= ViewHelper::csrfField() ?><button type="submit" class="button button-ghost">Cancelar pedido</button>
                        </form>
                    <?php else: ?>
                        <?php if ($teste['pode']): ?>
                            <form method="POST" action="<?= $base ?>/dia-a-dia/teste" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="button button-primary"><i class="fas fa-gift"></i> Testar grátis por <?= (int) $teste['dias'] ?> dias</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= $base ?>/dia-a-dia" class="d-inline">
                            <?= ViewHelper::csrfField() ?>
                            <button type="submit" class="button <?= $teste['pode'] ? 'button-secondary' : 'button-primary' ?>" data-carregando="Gerando Pix…"><i class="fas fa-unlock"></i> Liberar por <?= $brl($diaPreco) ?></button>
                        </form>
                    <?php endif; ?>
                    <a class="button button-ghost" href="#trocar">Ou troque para o Anual (já inclui)</a>
                </div>
                <small class="field-help">Pagamento único, vale até o fim do plano atual. No Anual, o Dia a dia vem incluso.<?= $teste['pode'] ? ' O teste grátis não pede pagamento e pode ser usado uma vez por turma.' : '' ?></small>
            </div>
        </section>
    <?php elseif ($atual && ($atual['dia_a_dia'] || !empty($atual['inclui_dia_a_dia']))): ?>
        <p class="dia-ok mt-24" id="dia-a-dia"><i class="fas fa-calendar-check"></i> Dia a dia liberado: <a href="<?= $basePath ?>/turmas/<?= $tid ?>/calendario">agenda e horário</a>, aviso do dia e contagem regressiva.</p>
    <?php endif; ?>

    <!-- ============ INDIQUE OUTRA TURMA ============ -->
    <?php if (!empty($codigoIndicacao)):
        $pctTxt = fn($p) => rtrim(rtrim(number_format((float) $p, 2, ',', ''), '0'), ',') . '%';
        $disp = array_filter($creditos, fn($c) => $c['status'] === 'disponivel');
    ?>
        <section class="panel indicacao mt-24">
            <div class="indicacao-topo">
                <div>
                    <h2><i class="fas fa-share-nodes"></i> Indique outra turma</h2>
                    <p>A turma indicada ganha <strong><?= $pctTxt($pctIndicada) ?></strong> na primeira compra. Quando ela pagar, vocês ganham
                       <strong><?= $pctTxt($pctRecompensa) ?></strong> de desconto nas parcelas que faltam ou na próxima compra.</p>
                </div>
                <div class="indicacao-codigo">
                    <code id="cod-indicacao"><?= h($codigoIndicacao) ?></code>
                    <button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText('<?= h($codigoIndicacao) ?>').then(function(){ this.textContent='Copiado!'; }.bind(this))">Copiar</button>
                </div>
            </div>
            <?php if ($creditos): ?>
                <ul class="lista-tarefas mt-12">
                    <?php foreach ($creditos as $c): ?>
                        <li>
                            <span class="tarefa-icone"><i class="fas fa-gift"></i></span>
                            <div class="tarefa-info">
                                <strong><?= $pctTxt($c['pct']) ?> de desconto · <?= h($c['descricao'] ?? 'Indicação') ?></strong>
                                <small><?= $c['status'] === 'usado' ? 'Usado em ' . ViewHelper::data($c['usado_em']) : 'Disponível: entra na próxima compra' ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- ============ RENOVAR (perto do fim do plano) ============ -->
    <?php if ($renov['pendente']): ?>
        <div class="bloco-titulo mt-24" id="renovar"><h2>Renovação</h2></div>
        <section class="panel plano-aguardando">
            <h2>Renovação para o plano <?= h($renov['pendente']['plano_nome']) ?> aguardando pagamento</h2>
            <p><?= (int) $renov['pendente']['parcelas'] ?>x · total <?= $brl($renov['pendente']['valor_total']) ?>.
               O novo período começa em <?= h($renov['inicio']) ?>, sem perder os dias que faltam do plano atual.</p>
            <div class="plano-aguardando-acoes">
                <?php if ($renov['proxima']): ?>
                    <a class="button button-primary" href="<?= $basePath ?>/pagamentos/<?= (int) $renov['proxima']['id'] ?>"><i class="fas fa-qrcode"></i> Pagar 1ª parcela (<?= $brl($renov['proxima']['valor']) ?>)</a>
                <?php endif; ?>
                <form method="POST" action="<?= $basePath ?>/assinaturas/<?= (int) $renov['pendente']['id'] ?>/cancelar" class="d-inline">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="button button-ghost" onclick="return confirm('Cancelar o pedido de renovação?')">Cancelar renovação</button>
                </form>
            </div>
        </section>
    <?php elseif ($renov['pode']): ?>
        <div class="bloco-titulo mt-24" id="renovar"><h2>Renovar o plano</h2></div>
        <section class="panel renovar-plano">
            <?php if ($renov['cupom']): ?>
                <div class="renovar-cupom">
                    <i class="fas fa-ticket"></i>
                    <div>
                        <strong>Cupom de renovação: <?= h($renov['cupom']['codigo']) ?></strong>
                        <span><?= rtrim(rtrim(number_format((float) $renov['cupom']['percentual'], 2, ',', ''), '0'), ',') ?>% de desconto, já aplicado abaixo.
                              Válido até <?= date('d/m/Y', strtotime($renov['cupom']['valido_ate'])) ?>, só para esta turma.</span>
                    </div>
                </div>
            <?php endif; ?>
            <p class="field-help">Escolha o próximo plano. Ele começa em <strong><?= h($renov['inicio']) ?></strong>: os dias que faltam do plano atual não se perdem.</p>
            <?php
            $urlCupom = $base; $acaoForm = $base . '/renovar';
            $planoInicial = $atual['plano_codigo']; $textoBotao = 'Gerar Pix da renovação';
            require __DIR__ . '/_escolher.php';
            ?>
        </section>
    <?php endif; ?>

    <!-- ============ TROCAR DE PLANO ============ -->
    <div class="bloco-titulo mt-24" id="trocar"><h2>Trocar de plano</h2></div>
    <?php if (!$troca && ($renov['pode'] || $renov['pendente'])): ?>
        <p class="field-help">Perto do fim do plano, a troca dá lugar à renovação acima, onde você também pode escolher um plano maior.</p>
    <?php elseif ($troca): ?>
        <section class="panel plano-aguardando">
            <h2>Troca para o plano <?= h($troca['plano_nome']) ?> aguardando pagamento</h2>
            <p>
                Diferença: <?= (int) $troca['parcelas'] ?>x · total <?= $brl($troca['valor_total']) ?>.
                O plano <?= h($atual['plano_nome']) ?> continua valendo até a 1ª parcela ser paga.
                O novo plano começa no dia do pagamento, com a duração completa.
            </p>
            <div class="plano-aguardando-acoes">
                <?php if ($trocaProxima): ?>
                    <a class="button button-primary" href="<?= $basePath ?>/pagamentos/<?= (int) $trocaProxima['id'] ?>"><i class="fas fa-qrcode"></i> Pagar 1ª parcela (<?= $brl($trocaProxima['valor']) ?>)</a>
                <?php endif; ?>
                <form method="POST" action="<?= $basePath ?>/assinaturas/<?= (int) $troca['id'] ?>/cancelar" class="d-inline">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="button button-ghost" onclick="return confirm('Cancelar a troca? O plano atual continua.')">Cancelar troca</button>
                </form>
            </div>
        </section>
    <?php elseif ($trocaBloqueio): ?>
        <p class="field-help"><?= h($trocaBloqueio) ?></p>
    <?php else: ?>
        <details class="panel troca-plano">
            <summary><strong>Ver planos maiores</strong> · você paga só a diferença do preço</summary>
            <p class="field-help">
                Só é possível trocar para um plano mais caro. O novo plano começa no dia do pagamento, com a duração completa,
                e o tempo que falta do plano atual vira crédito: <strong><?= $brl($trocaCredito) ?></strong>
                (proporcional ao que foi pago e aos dias restantes). Você paga o preço do plano novo menos esse crédito. Cupom não vale na troca.
            </p>
            <?php
            $planos = $trocaPlanos; $precos = $trocaPrecos; $cupom = null; $codigoCupom = ''; $erroCupom = null;
            $semCupom = true; $urlCupom = $base; $acaoForm = $base . '/trocar';
            $planoInicial = $trocaPlanos[0]['codigo']; $textoBotao = 'Gerar Pix da diferença';
            require __DIR__ . '/_escolher.php';
            ?>
        </details>
    <?php endif; ?>

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
    <?php
    $urlCupom = $base; $acaoForm = $base . '/assinar';
    require __DIR__ . '/_escolher.php';
    ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
