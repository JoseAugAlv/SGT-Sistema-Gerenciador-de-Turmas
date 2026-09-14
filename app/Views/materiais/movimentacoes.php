<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$badges = [
    'cadastro' => '<i class="fas fa-box" style="color:#666"></i> Cadastro',
    'compra'   => '<i class="fas fa-cart-plus" style="color:#0a0"></i> Compra',
    'uso'      => '<i class="fas fa-minus-circle" style="color:#c80"></i> Uso',
    'ajuste'   => '<i class="fas fa-sliders" style="color:#06f"></i> Ajuste',
];
?>

<h1><?= h($projeto['nome']) ?></h1>
<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Movimentações de Material</h2>

<p>
    <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais">
        <i class="fas fa-arrow-left"></i> Voltar aos materiais
    </a>
</p>

<form method="GET" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/movimentacoes">
    <fieldset>
        <legend>Filtros</legend>

        <p><label>Material<br>
            <select name="material_id">
                <option value="">Todos</option>
                <?php foreach ($materiais as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"
                        <?= (string) $filtros['material_id'] === (string) $m['id'] ? 'selected' : '' ?>>
                        <?= h($m['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select></label></p>

        <p><label>Tipo<br>
            <select name="tipo">
                <option value="">Todos</option>
                <?php foreach ($tipos as $chave => $label): ?>
                    <option value="<?= h($chave) ?>"
                        <?= $filtros['tipo'] === $chave ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select></label></p>

        <p><label>De <input type="date" name="de" value="<?= h($filtros['de']) ?>"></label></p>
        <p><label>Até <input type="date" name="ate" value="<?= h($filtros['ate']) ?>"></label></p>

        <p>
            <button type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/movimentacoes">Limpar</a>
        </p>
    </fieldset>
</form>

<?php if (empty($movimentacoes)): ?>
    <p>Nenhuma movimentação encontrada.</p>
<?php else: ?>
    <table border="1" cellpadding="6">
        <thead>
            <tr>
                <th>Data</th>
                <th>Material</th>
                <th>Tipo</th>
                <th>Qtd</th>
                <th>Preço unit.</th>
                <th>Usuário</th>
                <th>Cargo</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimentacoes as $mv): ?>
                <tr>
                    <td><?= h($mv['created_at']) ?></td>
                    <td><?= h($mv['material_nome']) ?></td>
                    <td><?= $badges[$mv['tipo_movimentacao']] ?? h($mv['tipo_movimentacao']) ?></td>
                    <td>
                        <?= number_format((float) $mv['quantidade'], 2, ',', '.') ?>
                        <?= h($mv['unidade'] ?? '') ?>
                    </td>
                    <td>R$ <?= number_format((float) $mv['preco_unitario'], 2, ',', '.') ?></td>
                    <td><?= h($mv['usuario_nome']) ?></td>
                    <td><?= h($mv['usuario_papel'] ?? '—') ?></td>
                    <td><?= h($mv['observacao'] ?? '—') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>