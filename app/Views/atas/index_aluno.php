<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$minhas    = $atas['minhas'] ?? [];
$abertas   = $atas['abertas'] ?? [];
$presencas = array_count_values(array_column($minhas, 'presente'));

$acaoAta = fn(array $a) => [['Abrir', $basePath . '/atas/' . (int) $a['id'], false]];
?>

<section class="hero-section">
    <div>
        <h1>Minhas atas</h1>
        <p class="hero-subtitle"><?= h($projeto['nome']) ?> · reuniões em que você foi registrado</p>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<?php if ($abertas): ?>
    <section class="para-fazer">
        <div class="bloco-titulo"><h2>Para você preencher (<?= count($abertas) ?>)</h2></div>
        <?php
        $acaoOriginal = $acaoAta;
        $acaoAta = fn(array $a) => [['Registrar o que fiz', $basePath . '/atas/' . (int) $a['id'] . '/preencher', true]];
        $listaAtas = $abertas;
        require __DIR__ . '/_lista.php';
        $acaoAta = $acaoOriginal;
        ?>
    </section>
<?php endif; ?>

<?php if (empty($minhas) && empty($abertas)): ?>
    <div class="lista-vazia">
        <i class="fas fa-book"></i>
        <span>Você ainda não aparece em nenhuma ata. Quando o diretor do seu grupo registrar uma reunião com você, ela aparece aqui.</span>
    </div>
<?php elseif ($minhas): ?>
    <section class="grade-numeros" aria-label="Resumo de presenças">
        <div class="grade-celula"><span class="num"><?= (int) ($presencas['sim'] ?? 0) ?></span><small>presenças</small></div>
        <div class="grade-celula"><span class="num"><?= (int) ($presencas['justificado'] ?? 0) ?></span><small>faltas justificadas</small></div>
        <div class="grade-celula"><span class="num <?= !empty($presencas['nao']) ? 'txt-alerta' : '' ?>"><?= (int) ($presencas['nao'] ?? 0) ?></span><small>faltas</small></div>
    </section>

    <section class="para-fazer">
        <div class="bloco-titulo"><h2>Reuniões</h2></div>
        <?php $listaAtas = $minhas; require __DIR__ . '/_lista.php'; ?>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
