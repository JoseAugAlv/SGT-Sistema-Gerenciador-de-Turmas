<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$todas      = $atas['todas'] ?? [];
$aPreencher = array_values(array_filter($todas, fn($a) => $a['status'] === 'pendente'));
$enviadas   = array_values(array_filter($todas, fn($a) => $a['status'] !== 'pendente'));

$acaoAta = function (array $a) use ($basePath, $projeto): array {
    $base = $basePath . '/atas/' . (int) $a['id'];
    if ($a['status'] === 'pendente' && !$projeto['encerrado']) return [['Preencher', $base . '/preencher', true]];
    return [['Abrir', $base, false]];
};
?>

<section class="hero-section">
    <div>
        <h1>Atas do meu grupo</h1>
        <p class="hero-subtitle"><?= h($projeto['nome']) ?></p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<section class="para-fazer">
    <div class="bloco-titulo"><h2>Para preencher (<?= count($aPreencher) ?>)</h2></div>
    <?php if (empty($aPreencher)): ?>
        <div class="lista-vazia"><i class="fas fa-circle-check"></i><span>Nenhuma ata esperando por você. Quando o representante criar uma, ela aparece aqui.</span></div>
    <?php else: ?>
        <?php $listaAtas = $aPreencher; require __DIR__ . '/_lista.php'; ?>
    <?php endif; ?>
</section>

<?php if ($enviadas): ?>
    <section class="para-fazer">
        <div class="bloco-titulo"><h2>Já enviadas</h2></div>
        <?php $listaAtas = $enviadas; require __DIR__ . '/_lista.php'; ?>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
