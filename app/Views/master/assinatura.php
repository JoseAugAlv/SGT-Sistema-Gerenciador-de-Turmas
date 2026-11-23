<?php
// app/Views/master/assinatura.php
// Master: detalhes de uma assinatura e ajustes (baixa manual, prazo, limite, plano, parcelas, cancelamento).
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$aid = (int) $ass['id'];
$url = $basePath . '/master/comercial/assinaturas/' . $aid;
$statusAss = ['aguardando' => 'Aguardando pagamento', 'ativa' => 'Ativa', 'cancelada' => 'Cancelada', 'trocada' => 'Trocada por outro plano'];
$statusParcela = ['pendente' => 'A pagar', 'pago' => 'Paga', 'cancelado' => 'Cancelada'];
$encerrada = in_array($ass['status'], ['cancelada', 'trocada'], true);
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Master · Assinatura #<?= $aid ?></div>
        <h1><?= h($ass['turma_nome']) ?> · <?= h($ass['plano_nome']) ?></h1>
        <p class="hero-subtitle">
            <?= $statusAss[$ass['status']] ?? h($ass['status']) ?>
            <?php if ($ass['inicio']): ?> · desde <?= ViewHelper::data($ass['inicio']) ?><?php endif; ?>
            <?php if ($ass['status'] === 'ativa'): ?> · <?= $ass['fim'] ? 'até ' . ViewHelper::data($ass['fim']) : 'sem vencimento' ?><?php endif; ?>
            <?= $ass['troca_de_nome'] ? ' · troca do plano ' . h($ass['troca_de_nome']) : '' ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/master/comercial"><i class="fas fa-arrow-left"></i> Comercial</a>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $ass['turma_id'] ?>/plano">Plano da turma</a>
    </div>
</section>

<div class="grade-numeros">
    <div class="grade-celula"><span class="num"><?= $projetosUsados ?>/<?= (int) $ass['projetos_limite'] ?></span><small>projetos usados</small></div>
    <div class="grade-celula"><span class="num plano-num-valor"><?= $brl($ass['valor_total']) ?></span><small>total · <?= (int) $ass['parcelas'] ?>x</small></div>
    <div class="grade-celula">
        <span class="num"><?= (float) $ass['desconto_pct'] > 0 ? rtrim(rtrim(number_format((float) $ass['desconto_pct'], 2, ',', ''), '0'), ',') . '%' : '—' ?></span>
        <small>desconto<?= $ass['cupom_codigo'] ? ' · cupom ' . h($ass['cupom_codigo']) : '' ?></small>
    </div>
</div>

<!-- ============ QUEM PAGA ============ -->
<section class="panel mb-16">
    <h2 class="comercial-h2">Dados de cobrança de quem comprou</h2>
    <?php if ($cobranca): ?>
        <p class="field-help">
            <strong><?= h($cobranca['nome_completo']) ?></strong> · CPF <?= h(CobrancaService::formatarCpf($cobranca['cpf'])) ?><br>
            <?= h($cobranca['logradouro']) ?>, <?= h($cobranca['numero']) ?><?= $cobranca['complemento'] ? ' · ' . h($cobranca['complemento']) : '' ?>
            · <?= h($cobranca['bairro']) ?> · <?= h($cobranca['cidade']) ?>/<?= h($cobranca['uf']) ?> · CEP <?= h(CobrancaService::formatarCep($cobranca['cep'])) ?>
        </p>
    <?php else: ?>
        <p class="field-help">Sem dados de cobrança (compra feita pelo master ou antes desta exigência).</p>
    <?php endif; ?>
</section>

<!-- ============ PARCELAS ============ -->
<div class="bloco-titulo"><h2>Parcelas</h2></div>
<ul class="lista-tarefas lista-parcelas">
    <?php foreach ($parcelas as $p): ?>
        <li>
            <span class="tarefa-icone <?= $p['status'] === 'pago' ? 'pago' : '' ?>"><i class="fas <?= $p['status'] === 'pago' ? 'fa-check' : 'fa-receipt' ?>"></i></span>
            <div class="tarefa-info">
                <strong><?= ($p['tipo'] ?? 'parcela') === 'adicional' ? 'Dia a dia (adicional)' : 'Parcela ' . (int) $p['numero'] ?> · <?= $brl($p['valor']) ?></strong>
                <small>
                    <?php if ($p['status'] === 'pago'): ?>Paga em <?= ViewHelper::data($p['pago_em'], true) ?> · <?= $p['metodo'] === 'manual' ? 'baixa manual' : h($p['metodo']) ?>
                    <?php else: ?><?= $statusParcela[$p['status']] ?? h($p['status']) ?> · vence em <?= ViewHelper::data($p['vencimento']) ?><?php endif; ?>
                </small>
            </div>
            <?php if ($p['status'] === 'pendente'): ?>
                <details class="cupom-editar">
                    <summary class="tarefa-acao">Alterar</summary>
                    <form method="POST" action="<?= $basePath ?>/master/comercial/parcelas/<?= (int) $p['id'] ?>" class="cupom-editar-form">
                        <?= ViewHelper::csrfField() ?>
                        <label>Valor (R$)<input type="number" name="valor" class="field" step="0.01" min="0.01" value="<?= h($p['valor']) ?>" required></label>
                        <label>Vencimento<input type="date" name="vencimento" class="field" value="<?= h($p['vencimento']) ?>" required></label>
                        <button type="submit" class="button button-primary">Salvar parcela</button>
                    </form>
                </details>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (!$encerrada): ?>
<div class="comercial-grade mt-24">
    <!-- ============ PAGAMENTO ============ -->
    <section class="panel">
        <h2 class="comercial-h2">Baixa manual</h2>
        <?php if ($proxima): ?>
            <p class="field-help">Use quando o pagamento foi recebido por fora do sistema. Marca a parcela <?= (int) $proxima['numero'] ?> (<?= $brl($proxima['valor']) ?>) como paga<?= $ass['status'] === 'aguardando' ? ' e ativa o plano' : '' ?>.</p>
            <form method="POST" action="<?= $url ?>/pagar">
                <?= ViewHelper::csrfField() ?>
                <button type="submit" class="button button-primary" onclick="return confirm('Confirmar o recebimento da parcela <?= (int) $proxima['numero'] ?>?')"><i class="fas fa-check"></i> Marcar parcela <?= (int) $proxima['numero'] ?> como paga</button>
            </form>
        <?php else: ?>
            <p class="field-help">Todas as parcelas estão pagas.</p>
        <?php endif; ?>
    </section>

    <!-- ============ PRAZO ============ -->
    <section class="panel">
        <h2 class="comercial-h2">Prazo</h2>
        <?php if ($ass['status'] === 'ativa' && $ass['fim']): ?>
            <p class="field-help">Some meses (cortesia, mês extra) ou tire (número negativo). Hoje vai até <?= ViewHelper::data($ass['fim']) ?>.</p>
            <form method="POST" action="<?= $url ?>/prazo" class="plano-cupom-linha">
                <?= ViewHelper::csrfField() ?>
                <input type="number" name="meses" class="field" min="-36" max="36" value="1" aria-label="Meses">
                <button type="submit" class="button button-secondary">Aplicar</button>
            </form>
        <?php else: ?>
            <p class="field-help"><?= $ass['status'] === 'ativa' ? 'Plano sem vencimento.' : 'O prazo começa a contar quando a 1ª parcela for paga.' ?></p>
        <?php endif; ?>
    </section>

    <!-- ============ LIMITE DE PROJETOS ============ -->
    <section class="panel">
        <h2 class="comercial-h2">Limite de projetos</h2>
        <p class="field-help">Libere projetos extras sem trocar de plano. A turma usa <?= $projetosUsados ?>.</p>
        <form method="POST" action="<?= $url ?>/limite" class="plano-cupom-linha">
            <?= ViewHelper::csrfField() ?>
            <input type="number" name="projetos_limite" class="field" min="1" max="999" value="<?= (int) $ass['projetos_limite'] ?>" aria-label="Limite de projetos">
            <button type="submit" class="button button-secondary">Salvar</button>
        </form>
    </section>

    <!-- ============ TROCAR PLANO SEM COBRANÇA ============ -->
    <section class="panel">
        <h2 class="comercial-h2">Trocar plano sem cobrança</h2>
        <p class="field-help">Só o master pode trocar para qualquer plano, inclusive mais barato. O limite de projetos e o fim são recalculados a partir do início. Para cobrar a diferença, use “Trocar de plano” na tela do plano da turma.</p>
        <form method="POST" action="<?= $url ?>/plano" class="plano-cupom-linha">
            <?= ViewHelper::csrfField() ?>
            <select name="plano_id" class="field" aria-label="Novo plano">
                <?php foreach ($planos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"<?= (int) $p['id'] === (int) $ass['plano_id'] ? ' selected' : '' ?>>
                        <?= h($p['nome']) ?> · <?= $p['meses'] ? (int) $p['meses'] . ' meses' : 'sem vencimento' ?> · <?= (int) $p['projetos'] ?> projetos<?= $p['ativo'] ? '' : ' (fora de venda)' ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-secondary" onclick="return confirm('Trocar o plano sem cobrança?')">Trocar</button>
        </form>
    </section>
</div>

<section class="panel mt-16">
    <h2 class="comercial-h2">Cancelar assinatura</h2>
    <p class="field-help">As parcelas pendentes são canceladas. Se a turma não for isenta, ela fica bloqueada até ter um novo plano.</p>
    <form method="POST" action="<?= $url ?>/cancelar">
        <?= ViewHelper::csrfField() ?>
        <button type="submit" class="button button-ghost txt-alerta" onclick="return confirm('Cancelar esta assinatura? Isso não pode ser desfeito.')"><i class="fas fa-ban"></i> Cancelar assinatura</button>
    </form>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
