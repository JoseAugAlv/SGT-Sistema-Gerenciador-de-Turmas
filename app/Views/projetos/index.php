<?php
$cssPagina = 'projetos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Projetos</h1>
        <p class="hero-subtitle">
            <?= h($turma['nome']) ?> · <?= count($projetos) ?> projeto(s)
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar à turma
        </a>
        <?php if ($isMaster): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/criar?turma_id=<?= (int) $turma['id'] ?>">
                <i class="fas fa-plus"></i> Novo projeto
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if (empty($projetos)): ?>
    <section class="panel empty-state">
        <h2>Nenhum projeto cadastrado</h2>
        <p>
            <?php if ($isMaster): ?>
                Crie o primeiro projeto desta turma clicando no botão abaixo.
            <?php else: ?>
                Aguarde o professor ou representante criar um projeto.
            <?php endif; ?>
        </p>
        <?php if ($isMaster): ?>
            <a class="button button-primary" href="<?= $basePath ?>/projetos/criar?turma_id=<?= (int) $turma['id'] ?>">
                <i class="fas fa-plus"></i> Criar projeto
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <?php require __DIR__ . '/_lista.php'; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>