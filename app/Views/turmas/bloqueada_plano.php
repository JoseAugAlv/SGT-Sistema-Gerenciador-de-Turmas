<?php
// app/Views/turmas/bloqueada_plano.php
// Turma barrada pelo plano (sem plano, vencido ou em atraso). Espera: $turma, $situacaoPlano.
// Funciona como página inteira ou no meio de uma página já começada (header/nav são require_once).
$cssPagina = $cssPagina ?? 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';

$souRepBloq = !empty($_SESSION['usuario']) && (new TurmaUsuario())->ehRepresentante((int) $turma['id'], (int) $_SESSION['usuario']['id']);
?>

<section class="panel turma-bloqueada-hero">
    <span class="icon-ban"><i class="fas fa-lock"></i></span>
    <h1><?= h($situacaoPlano['titulo']) ?></h1>
    <p class="hero-subtitle"><?= h($turma['nome']) ?></p>
</section>

<section class="panel panel-center mt-16">
    <p><?= h($situacaoPlano['texto']) ?></p>
    <?php if ($souRepBloq): ?>
        <p class="mt-20">
            <a class="button button-primary" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/plano">
                <i class="fas fa-receipt"></i> Ver plano e pagar
            </a>
        </p>
    <?php else: ?>
        <p class="text-muted">Avise o representante da turma. Assim que o plano for regularizado, o acesso volta na hora.</p>
        <p class="mt-20">
            <a class="button button-ghost" href="<?= $basePath ?>/turmas"><i class="fas fa-arrow-left"></i> Minhas turmas</a>
        </p>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
