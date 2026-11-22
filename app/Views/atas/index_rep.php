<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$todas      = $atas['todas'] ?? [];
$aguardando = array_values(array_filter($todas, fn($a) => $a['status'] === 'preenchida'));
$contagem   = array_count_values(array_column($todas, 'status'));

$acaoAta = function (array $a) use ($basePath, $projeto): array {
    $base = $basePath . '/atas/' . (int) $a['id'];
    if ($a['status'] === 'preenchida') return [['Revisar e validar', $base . '/validar-form', true]];
    if ($a['status'] === 'pendente' && !$projeto['encerrado']) return [['Editar', $base . '/editar', false], ['Abrir', $base, false]];
    return [['Abrir', $base, false]];
};
?>

<section class="hero-section">
    <div>
        <h1>Atas</h1>
        <p class="hero-subtitle">
            <?= h($projeto['nome']) ?> · <?= count($todas) ?> ata(s)
        </p>
    </div>
    <?php if (!$projeto['encerrado']): ?>
        <div class="hero-actions">
            <a class="button button-primary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atas/criar">
                <i class="fas fa-plus"></i> Nova ata
            </a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php if ($aguardando): ?>
    <section class="para-fazer">
        <div class="bloco-titulo"><h2>Aguardando sua validação (<?= count($aguardando) ?>)</h2></div>
        <?php $listaAtas = $aguardando; require __DIR__ . '/_lista.php'; ?>
    </section>
<?php endif; ?>

<section class="para-fazer">
    <div class="bloco-titulo">
        <h2>Todas as atas</h2>
        <?php if ($todas): ?>
            <div class="ata-filtros" role="group" aria-label="Filtrar por situação">
                <button type="button" class="ativo" data-filtro="">Todas <b><?= count($todas) ?></b></button>
                <button type="button" data-filtro="pendente">Aguardando diretor <b><?= (int) ($contagem['pendente'] ?? 0) ?></b></button>
                <button type="button" data-filtro="preenchida">Aguardando validação <b><?= (int) ($contagem['preenchida'] ?? 0) ?></b></button>
                <button type="button" data-filtro="revisada">Validadas <b><?= (int) ($contagem['revisada'] ?? 0) ?></b></button>
            </div>
        <?php endif; ?>
    </div>
    <?php if (empty($todas)): ?>
        <div class="lista-vazia"><i class="fas fa-book"></i><span>Nenhuma ata ainda. Crie a primeira em “Nova ata”: o diretor do grupo é avisado para preencher.</span></div>
    <?php else: ?>
        <div data-lista-filtravel>
            <?php $listaAtas = $todas; require __DIR__ . '/_lista.php'; ?>
        </div>
    <?php endif; ?>
</section>

<script>
document.querySelectorAll('.ata-filtros button').forEach(function (b) {
    b.addEventListener('click', function () {
        document.querySelectorAll('.ata-filtros button').forEach(function (o) { o.classList.toggle('ativo', o === b); });
        document.querySelectorAll('[data-lista-filtravel] li').forEach(function (li) {
            li.hidden = !!b.dataset.filtro && li.dataset.status !== b.dataset.filtro;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
