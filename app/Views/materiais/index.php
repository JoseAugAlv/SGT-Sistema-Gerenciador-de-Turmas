<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Materiais</h2>

<p>
    <?php if ($podeEditar && !$projeto['encerrado']): ?>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/cadastrar">
            <i class="fas fa-plus"></i> Novo material
        </a>
        —
    <?php endif; ?>
    <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/movimentacoes">
        <i class="fas fa-list"></i> Ver movimentações
    </a>
</p>

<?php if (empty($materiais)): ?>
    <p>Nenhum material cadastrado.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Estoque</th>
                <th>Unidade</th>
                <th>Preço</th>
                <th>Mov.</th>
                <?php if ($podeEditar && !$projeto['encerrado']): ?><th>Ações</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materiais as $m): ?>
                <tr>
                    <td><?= h($m['nome']) ?></td>
                    <td>
                        <strong><?= number_format((float) $m['quantidade'], 2, ',', '.') ?></strong>
                    </td>
                    <td><?= h($m['unidade'] ?? '—') ?></td>
                    <td>R$ <?= number_format((float) $m['preco'], 2, ',', '.') ?></td>
                    <td><?= (int) $m['total_movimentacoes'] ?></td>

                    <?php if ($podeEditar && !$projeto['encerrado']): ?>
                        <td>
                            <a href="<?= $basePath ?>/materiais/<?= (int) $m['id'] ?>/usar">
                                <i class="fas fa-minus-circle"></i> Usar
                            </a>
                            —
                            <a href="<?= $basePath ?>/materiais/<?= (int) $m['id'] ?>/comprar">
                                <i class="fas fa-plus-circle"></i> Comprar
                            </a>
                            —
                            <form method="POST" action="<?= $basePath ?>/materiais/<?= (int) $m['id'] ?>/excluir" style="display:inline">
                                <?= ViewHelper::csrfField() ?>
                                <button type="submit" style="color:#b00"
                                        onclick="return confirm('Excluir este material? O histórico de movimentações será removido.')">
                                    <i class="fas fa-trash"></i> Excluir
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>