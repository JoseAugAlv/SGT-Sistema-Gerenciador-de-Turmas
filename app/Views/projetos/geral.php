<?php
$cssPagina = 'projetos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>
<section class="hero-section">
    <div>
        <h1><?= $isMaster ? 'Projetos' : 'Meus projetos' ?></h1>
        <p class="hero-subtitle">
            <?= $isMaster ? 'Projetos de todas as turmas.' : 'Projetos das turmas em que você participa.' ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas">
            <i class="fas fa-users"></i> Minhas turmas
        </a>
    </div>
</section>

<?php if (empty($projetosPorTurma)): ?>
    <section class="panel empty-state">
        <span class="icon-large text-muted"><i class="fas fa-diagram-project"></i></span>
        <h2>Você ainda não está em nenhuma turma</h2>
        <p>
            Entre em uma turma pelo código de acesso para ver seus projetos aqui.
        </p>
        <a class="button button-primary" href="<?= $basePath ?>/turmas/entrar">
            <i class="fas fa-right-to-bracket"></i> Entrar em uma turma
        </a>
    </section>
<?php else: ?>

    <?php foreach ($projetosPorTurma as $bloco): ?>
        <?php $turma = $bloco['turma']; $projetos = $bloco['projetos']; ?>

        <section class="projetos-turma">
            <div class="bloco-titulo">
                <h2>
                    <span class="mini-sigla num" style="<?= h(ViewHelper::estiloTurma($turma)) ?>"><?= h(ViewHelper::siglaTurma($turma['nome'])) ?></span>
                    <?= h($turma['nome']) ?>
                </h2>
                <a class="bloco-link" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">Abrir turma <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($projetos)): ?>
                <div class="lista-vazia"><i class="fas fa-diagram-project"></i><span>Nenhum projeto nesta turma.</span></div>
            <?php else: ?>
                <?php require __DIR__ . '/_lista.php'; ?>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>