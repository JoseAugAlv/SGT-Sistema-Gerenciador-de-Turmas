<?php
$cssPagina = ['projetos.css', 'avaliacoes.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

?>

<section class="hero-section">
    <div>
        <h1><?= h($aluno['nome']) ?><?= $souEu ? ' (você)' : '' ?></h1>
        <p class="hero-subtitle">
            Boletim parcial · <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>"><?= h($projeto['nome']) ?></a>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary"
           href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/relatorios/boletim?aluno_id=<?= (int) $aluno['id'] ?>">
            <i class="fas fa-file-lines"></i> Boletim oficial
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>
<?php require __DIR__ . '/_nav.php'; ?>

<?php if (empty($boletim['linhas'])): ?>
    <div class="lista-vazia"><i class="fas fa-graduation-cap"></i><span>Este projeto ainda não tem critérios.</span></div>
<?php else: ?>
    <?php
    $legendaParcial = 'Até agora: ' . (int) $abertos . ' critério(s) ainda em avaliação, ' . (int) $bloqueados . ' já fechado(s)';
    require __DIR__ . '/_boletim.php';
    ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
