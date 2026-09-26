<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Resultado da criação em massa</h1>
        <p class="hero-subtitle">
            <?= (int) $resultado['criados'] ?> aluno(s) criado(s),
            <?= count($resultado['erros']) ?> erro(s).
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-primary" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Ver alunos
        </a>
    </div>
</section>

<?php if ($resultado['criados'] > 0): ?>
    <section class="panel">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-check-circle"></i> Criados</h2>
            </div>
        </div>
        <p>
            <strong><?= (int) $resultado['criados'] ?></strong> aluno(s) criado(s) com sucesso.
            Os emails de primeiro acesso foram disparados — as credenciais vão chegar
            na caixa de entrada de cada aluno.
        </p>
    </section>
<?php endif; ?>

<?php if (!empty($resultado['erros'])): ?>
    <section class="panel" style="margin-top:16px;">
        <div class="panel-header">
            <div>
                <h2><i class="fas fa-exclamation-triangle"></i> Erros</h2>
            </div>
        </div>
        <ul>
            <?php foreach ($resultado['erros'] as $e): ?>
                <li><?= h($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>