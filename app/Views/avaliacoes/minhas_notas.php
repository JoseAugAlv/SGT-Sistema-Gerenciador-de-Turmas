<?php
$cssPagina = ['projetos.css', 'avaliacoes.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$linhas = $boletim['linhas'] ?? [];
?>

<section class="hero-section">
    <div>
        <h1>Minhas notas</h1>
        <p class="hero-subtitle">
            <?= h($projeto['nome']) ?> ·
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
        </p>
    </div>
    <?php if ($linhas): ?>
        <div class="hero-actions">
            <a class="button button-secondary" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/boletim/pdf">
                <i class="fas fa-file-pdf"></i> Baixar boletim
            </a>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if (!empty($boletim['congelado'])): ?>
    <div class="flash flash-info">
        <i class="fas fa-lock" aria-hidden="true"></i>
        <div class="flash-texto">
            <strong>Boletim final</strong>
            <span>O projeto foi encerrado<?= !empty($boletim['congelado_em']) ? ' em ' . h(date('d/m/Y', strtotime($boletim['congelado_em']))) : '' ?>. Estas notas não mudam mais.</span>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($linhas)): ?>
    <div class="lista-vazia">
        <i class="fas fa-graduation-cap"></i>
        <span>Este projeto ainda não tem critérios. Quando você for avaliado, as notas aparecem aqui.</span>
    </div>
<?php else: ?>
    <?php require __DIR__ . '/_boletim.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
