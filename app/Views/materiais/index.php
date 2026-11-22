<?php
$cssPagina = ['projetos.css', 'materiais.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
require_once __DIR__ . '/../../Models/SolicitacaoMaterial.php';

$pid     = (int) $projeto['id'];
$aberto  = !$projeto['encerrado'];
$meuId   = (int) ($_SESSION['usuario']['id'] ?? 0);
$qtdFmt  = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
$brl     = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');

$valorEstoque = array_sum(array_map(fn($m) => (float) $m['quantidade'] * (float) $m['preco'], $materiais));
$esgotados    = count(array_filter($materiais, fn($m) => (float) $m['quantidade'] <= 0));

// Quem gerencia vê todos os pedidos abertos; o aluno vê só os dele
$pedidos = $podeEditar
    ? $solicitacoes
    : array_values(array_filter($solicitacoes, fn($s) => (int) $s['solicitante_id'] === $meuId));
$statusSol = SolicitacaoMaterial::STATUS;
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Materiais
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
        </p>
    </div>
    <?php if ($aberto): ?>
        <div class="hero-actions">
            <?php if ($podeEditar): ?>
                <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/cadastrar">
                    <i class="fas fa-plus"></i> Cadastrar material
                </a>
            <?php else: ?>
                <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/solicitar">
                    <i class="fas fa-hand-holding-heart"></i> Pedir material
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<!-- ============ PEDIDOS EM ABERTO ============ -->
<?php if ($pedidos): ?>
    <section class="para-fazer">
        <div class="bloco-titulo">
            <h2><?= $podeEditar ? 'Pedidos esperando você' : 'Seus pedidos em andamento' ?></h2>
            <a class="bloco-link" href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/solicitacoes">Todos <i class="fas fa-arrow-right"></i></a>
        </div>
        <ul class="lista-tarefas lista-pedidos">
            <?php foreach ($pedidos as $s): $sid = (int) $s['id']; ?>
                <li>
                    <span class="tarefa-icone <?= $s['status'] === 'pendente' ? 'urgente' : '' ?>"><i class="fas fa-hand-holding-heart"></i></span>
                    <div class="tarefa-info">
                        <strong><?= $qtdFmt($s['quantidade']) ?> <?= h($s['unidade'] ?? '') ?> · <?= h($s['nome_material']) ?></strong>
                        <small>
                            <?= h($s['solicitante_nome']) ?> · <?= ViewHelper::data($s['created_at']) ?>
                            <?php if (!empty($s['preco_estimado'])): ?> · estimado <?= $brl($s['preco_estimado']) ?><?php endif; ?>
                            <?php if (!empty($s['justificativa'])): ?> · “<?= h($s['justificativa']) ?>”<?php endif; ?>
                        </small>
                    </div>
                    <span class="sol-chip <?= h($s['status']) ?>"><?= h($statusSol[$s['status']] ?? $s['status']) ?></span>
                    <?php if ($podeEditar && $aberto): ?>
                        <span class="mat-acoes-linha">
                            <?php if ($s['status'] === 'pendente'): ?>
                                <form method="POST" action="<?= $basePath ?>/solicitacoes/<?= $sid ?>/aprovar" class="d-inline">
                                    <?= ViewHelper::csrfField() ?>
                                    <button type="submit" class="tarefa-acao primaria">Aprovar</button>
                                </form>
                            <?php else: ?>
                                <a class="tarefa-acao primaria" href="<?= $basePath ?>/solicitacoes/<?= $sid ?>/comprar">Registrar compra</a>
                            <?php endif; ?>
                            <details class="mat-recusa">
                                <summary class="tarefa-acao perigo">Recusar</summary>
                                <form method="POST" action="<?= $basePath ?>/solicitacoes/<?= $sid ?>/recusar">
                                    <?= ViewHelper::csrfField() ?>
                                    <input type="text" name="motivo" class="field" maxlength="500" required placeholder="Motivo (o aluno vai ver)">
                                    <button type="submit" class="button small btn-outline-danger">Confirmar recusa</button>
                                </form>
                            </details>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<!-- ============ ESTOQUE ============ -->
<?php if (empty($materiais)): ?>
    <section class="panel empty-state">
        <span class="icon-large text-muted"><i class="fas fa-boxes-stacked"></i></span>
        <h2>Nenhum material no estoque</h2>
        <p>
            <?= $podeEditar
                ? 'Cadastre o que a turma já tem. Cada compra e retirada fica registrada no histórico, que serve de prestação de contas.'
                : 'Precisa de algo para o projeto? Faça um pedido: o representante e os diretores são avisados.' ?>
        </p>
        <?php if ($aberto): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/<?= $podeEditar ? 'cadastrar' : 'solicitar' ?>">
                <i class="fas <?= $podeEditar ? 'fa-plus' : 'fa-hand-holding-heart' ?>"></i> <?= $podeEditar ? 'Cadastrar material' : 'Pedir material' ?>
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <div class="grade-numeros">
        <div class="grade-celula"><span class="num"><?= count($materiais) ?></span><small>itens no estoque</small></div>
        <div class="grade-celula"><span class="num mat-valor"><?= $brl($valorEstoque) ?></span><small>valor em estoque (preço médio)</small></div>
        <div class="grade-celula"><span class="num <?= $esgotados ? 'txt-alerta' : '' ?>"><?= $esgotados ?></span><small>esgotado(s)</small></div>
    </div>

    <div class="bloco-titulo">
        <h2>Estoque</h2>
        <?php if (!$podeEditar && $aberto): ?>
            <a class="bloco-link" href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/solicitar">Pedir outro material <i class="fas fa-arrow-right"></i></a>
        <?php endif; ?>
    </div>
    <ul class="lista-tarefas lista-materiais">
        <?php foreach ($materiais as $m):
            $mid = (int) $m['id'];
            $qtd = (float) $m['quantidade'];
        ?>
            <li class="<?= $qtd <= 0 ? 'esgotado' : '' ?>">
                <span class="mat-qtd num"><?= $qtdFmt($qtd) ?><small><?= h($m['unidade'] ?? '') ?></small></span>
                <div class="tarefa-info">
                    <strong><?= h($m['nome']) ?></strong>
                    <small>
                        <?= $brl($m['preco']) ?> cada (preço médio) · <?= $brl($qtd * (float) $m['preco']) ?> no total
                        · <a class="mat-link" href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/movimentacoes?material_id=<?= $mid ?>"><?= (int) $m['total_movimentacoes'] ?> movimentação(ões)</a>
                    </small>
                </div>
                <?php if ($qtd <= 0): ?><span class="sol-chip recusada">Esgotado</span><?php else: ?><span></span><?php endif; ?>
                <?php if ($podeEditar && $aberto): ?>
                    <span class="mat-acoes-linha">
                        <?php if ($qtd > 0): ?><a class="tarefa-acao" href="<?= $basePath ?>/materiais/<?= $mid ?>/usar">Retirar</a><?php endif; ?>
                        <a class="tarefa-acao" href="<?= $basePath ?>/materiais/<?= $mid ?>/comprar">Comprar</a>
                        <?php if ((int) $m['total_compras_usos'] === 0): ?>
                            <form method="POST" action="<?= $basePath ?>/materiais/<?= $mid ?>/excluir" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="tarefa-acao perigo" aria-label="Excluir <?= h($m['nome']) ?>"
                                        onclick="return confirm('Excluir <?= h(addslashes($m['nome'])) ?>?')"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
