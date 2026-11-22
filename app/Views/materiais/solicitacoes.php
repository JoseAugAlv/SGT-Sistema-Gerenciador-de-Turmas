<?php
$cssPagina = ['projetos.css', 'materiais.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$pid     = (int) $projeto['id'];
$aberto  = !$projeto['encerrado'];
$isMasterSol = ($_SESSION['usuario']['tipo'] ?? '') === 'master';
$qtdFmt  = fn($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
$brl     = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
$urlBase = $basePath . '/projetos/' . $pid . '/materiais/solicitacoes';
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
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/solicitar">
                <i class="fas fa-hand-holding-heart"></i> Pedir material
            </a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<div class="mat-filtros" role="group" aria-label="Filtrar por situação">
    <a class="<?= $statusFiltro === null ? 'ativo' : '' ?>" href="<?= $urlBase ?>">Todas</a>
    <?php foreach ($statusLabels as $chave => $label): ?>
        <a class="<?= $statusFiltro === $chave ? 'ativo' : '' ?>" href="<?= $urlBase ?>?status=<?= h($chave) ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($solicitacoes)): ?>
    <div class="lista-vazia">
        <i class="fas fa-inbox"></i>
        <span>
            <?= $statusFiltro
                ? 'Nenhum pedido com a situação “' . h($statusLabels[$statusFiltro]) . '”.'
                : 'Ninguém pediu material ainda.' ?>
            <?php if ($aberto): ?><a href="<?= $basePath ?>/projetos/<?= $pid ?>/materiais/solicitar">Fazer um pedido</a><?php endif; ?>
        </span>
    </div>
<?php else: ?>
    <ul class="lista-tarefas lista-pedidos">
        <?php foreach ($solicitacoes as $s):
            $sid      = (int) $s['id'];
            $souDono  = (int) $s['solicitante_id'] === (int) $meuId;
            $emAberto = in_array($s['status'], ['pendente', 'aprovada'], true);
            $gerir    = $podeGerenciar && $aberto && $emAberto;
        ?>
            <li>
                <span class="tarefa-data num"><?= date('d', strtotime($s['created_at'])) ?><small><?= ['JAN','FEV','MAR','ABR','MAI','JUN','JUL','AGO','SET','OUT','NOV','DEZ'][(int) date('n', strtotime($s['created_at'])) - 1] ?></small></span>
                <div class="tarefa-info">
                    <strong><?= $qtdFmt($s['quantidade']) ?> <?= h($s['unidade'] ?? '') ?> · <?= h($s['nome_material']) ?></strong>
                    <small>
                        pedido por <?= $souDono ? 'você' : h($s['solicitante_nome']) ?>
                        <?php if (!empty($s['preco_estimado'])): ?> · estimado <?= $brl($s['preco_estimado']) ?><?php endif; ?>
                        <?php if ($s['status'] === 'aprovada' && !empty($s['aprovador_nome'])): ?> · aprovado por <?= h($s['aprovador_nome']) ?><?php endif; ?>
                        <?php if ($s['status'] === 'comprada'): ?> · comprado por <?= h($s['comprador_nome'] ?? '—') ?> em <?= ViewHelper::data($s['comprado_em']) ?><?php if (!empty($s['material_nome_final'])): ?>, entrou no estoque como “<?= h($s['material_nome_final']) ?>”<?php endif; ?><?php endif; ?>
                    </small>
                    <?php if (!empty($s['justificativa'])): ?><small class="sol-just">“<?= h($s['justificativa']) ?>”</small><?php endif; ?>
                    <?php if ($s['status'] === 'recusada'): ?><small class="txt-alerta">Recusado: <?= h($s['motivo_recusa'] ?? '—') ?></small><?php endif; ?>
                </div>
                <span class="sol-chip <?= h($s['status']) ?>"><?= h($statusLabels[$s['status']] ?? $s['status']) ?></span>
                <?php if ($gerir || ($souDono && $emAberto) || $isMasterSol): ?>
                    <span class="mat-acoes-linha">
                        <?php if ($gerir && $s['status'] === 'pendente'): ?>
                            <form method="POST" action="<?= $basePath ?>/solicitacoes/<?= $sid ?>/aprovar" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="tarefa-acao primaria">Aprovar</button>
                            </form>
                        <?php elseif ($gerir): ?>
                            <a class="tarefa-acao primaria" href="<?= $basePath ?>/solicitacoes/<?= $sid ?>/comprar">Registrar compra</a>
                        <?php endif; ?>
                        <?php if ($gerir): ?>
                            <details class="mat-recusa">
                                <summary class="tarefa-acao perigo">Recusar</summary>
                                <form method="POST" action="<?= $basePath ?>/solicitacoes/<?= $sid ?>/recusar">
                                    <?= ViewHelper::csrfField() ?>
                                    <input type="text" name="motivo" class="field" maxlength="500" required placeholder="Motivo (o aluno vai ver)">
                                    <button type="submit" class="button small btn-outline-danger">Confirmar recusa</button>
                                </form>
                            </details>
                        <?php endif; ?>
                        <?php if ($souDono && $emAberto): ?>
                            <form method="POST" action="<?= $basePath ?>/solicitacoes/<?= $sid ?>/cancelar" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="tarefa-acao" onclick="return confirm('Cancelar seu pedido?')">Cancelar pedido</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($isMasterSol): ?>
                            <form method="POST" action="<?= $basePath ?>/solicitacoes/<?= $sid ?>/excluir" class="d-inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" class="tarefa-acao perigo" aria-label="Excluir pedido" onclick="return confirm('Excluir este pedido para sempre?')"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
