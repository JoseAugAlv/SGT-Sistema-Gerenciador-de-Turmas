<?php
$cssPagina = ['projetos.css', 'materiais.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$movBadge = function (string $t): string {
    $icon = match ($t) {
        'cadastro' => 'fas fa-box',
        'compra'   => 'fas fa-cart-plus',
        'uso'      => 'fas fa-minus-circle',
        'ajuste'   => 'fas fa-sliders',
        default    => 'fas fa-circle',
    };
    $label = match ($t) {
        'cadastro' => 'Cadastro',
        'compra'   => 'Compra',
        'uso'      => 'Uso',
        'ajuste'   => 'Ajuste',
        default    => ucfirst($t),
    };
    return '<span class="mov-tipo ' . h($t) . '"><i class="' . $icon . '"></i> ' . h($label) . '</span>';
};
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
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<form method="GET" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/movimentacoes"
      class="mov-filtros">
    <div class="field-group">
        <label class="field-label">Material</label>
        <select name="material_id" class="field">
            <option value="">Todos</option>
            <?php foreach ($materiais as $m): ?>
                <option value="<?= (int) $m['id'] ?>"
                    <?= (string) $filtros['material_id'] === (string) $m['id'] ? 'selected' : '' ?>>
                    <?= h($m['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field-group">
        <label class="field-label">Tipo</label>
        <select name="tipo" class="field">
            <option value="">Todos</option>
            <?php foreach ($tipos as $chave => $label): ?>
                <option value="<?= h($chave) ?>" <?= $filtros['tipo'] === $chave ? 'selected' : '' ?>>
                    <?= h($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field-group">
        <label class="field-label">De</label>
        <input type="date" name="de" class="field" value="<?= h($filtros['de']) ?>">
    </div>

    <div class="field-group">
        <label class="field-label">Até</label>
        <input type="date" name="ate" class="field" value="<?= h($filtros['ate']) ?>">
    </div>

    <div class="filtros-acoes">
        <button type="submit" class="button button-primary">
            <i class="fas fa-filter"></i> Filtrar
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/materiais/movimentacoes">
            <i class="fas fa-eraser"></i> Limpar
        </a>
    </div>
</form>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Histórico (<?= count($movimentacoes) ?>)</h2>
            <p>Cada entrada e saída do estoque fica registrada aqui e não pode ser apagada: é a prestação de contas do projeto.</p>
        </div>
    </div>

    <?php if (empty($movimentacoes)): ?>
        <div class="material-empty">
            <i class="fas fa-inbox mat-vazio-icone"></i>
            Nenhuma movimentação encontrada com os filtros atuais.
        </div>
    <?php else: ?>
        <div class="mov-wrapper">
            <table class="mov-tabela">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Material</th>
                        <th>Tipo</th>
                        <th class="td-qtd">Qtd</th>
                        <th class="td-preco">Preço unit.</th>
                        <th>Usuário</th>
                        <th>Observação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movimentacoes as $mv): ?>
                        <tr>
                            <td class="td-data"><?= ViewHelper::data($mv['created_at'], true) ?></td>
                            <td class="td-material"><?= h($mv['material_nome']) ?></td>
                            <td><?= $movBadge($mv['tipo_movimentacao']) ?></td>
                            <td class="td-qtd">
                                <?= rtrim(rtrim(number_format((float) $mv['quantidade'], 2, ',', '.'), '0'), ',') ?>
                                <small class="mat-unidade">
                                    <?= h($mv['unidade'] ?? '') ?>
                                </small>
                            </td>
                            <td class="td-preco">
                                R$ <?= number_format((float) $mv['preco_unitario'], 2, ',', '.') ?>
                            </td>
                            <td class="td-usuario">
                                <?= h($mv['usuario_nome']) ?>
                                <?php if (!empty($mv['usuario_papel'])): ?>
                                    <small><?= h(ucfirst($mv['usuario_papel'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="td-obs">
                                <?= h($mv['observacao'] ?? '—') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>