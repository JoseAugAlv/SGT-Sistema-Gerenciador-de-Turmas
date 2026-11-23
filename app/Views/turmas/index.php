<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1><?= $isMaster ? 'Turmas' : 'Minhas turmas' ?></h1>
        <p class="hero-subtitle">
            <?php if ($isMaster): ?>
                Todas as turmas do sistema.
            <?php else: ?>
                Turmas em que você participa.
            <?php endif; ?>
        </p>
    </div>

    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/turmas/entrar">
            <i class="fas fa-right-to-bracket"></i> Entrar com código
        </a>
        <?php if ($isMaster): ?>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/criar">
                <i class="fas fa-plus"></i> Nova turma
            </a>
        <?php else: ?>
            <a class="button button-secondary" href="<?= $basePath ?>/gremios/novo">
                <i class="fas fa-landmark"></i> Criar grêmio
            </a>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/nova">
                <i class="fas fa-plus"></i> Criar minha turma
            </a>
        <?php endif; ?>
    </div>
</section>

<?php if (empty($turmas)): ?>
    <section class="panel turma-empty">
        <span class="icon-large text-muted"><i class="fas fa-users"></i></span>
        <h2>
            <?php if ($isMaster): ?>
                Nenhuma turma cadastrada
            <?php else: ?>
                Você ainda não está em nenhuma turma
            <?php endif; ?>
        </h2>
        <p>
            <?php if ($isMaster): ?>
                Crie a primeira turma clicando no botão abaixo.
            <?php else: ?>
                Peça o código de acesso da turma para o professor ou representante e entre por ele.
            <?php endif; ?>
        </p>
        <?php if ($isMaster): ?>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/criar">
                <i class="fas fa-plus"></i> Criar turma
            </a>
        <?php else: ?>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/entrar">
                <i class="fas fa-right-to-bracket"></i> Entrar em uma turma com código
            </a>
            <a class="button button-secondary" href="<?= $basePath ?>/turmas/nova">
                <i class="fas fa-plus"></i> Sou representante: criar a turma
            </a>
        <?php endif; ?>
    </section>
<?php else: ?>
    <section class="turmas-grid">
        <?php foreach ($turmas as $t): ?>
            <a class="turma-card" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>">
                <div class="turma-card-topo" style="<?= h(ViewHelper::estiloTurma($t)) ?>">
                    <span class="turma-card-logo">
                        <?php if (!empty($t['logo'])): ?>
                            <img src="<?= h(ViewHelper::asset($t['logo'])) ?>" alt="">
                        <?php else: ?>
                            <i class="fas fa-shield-halved" aria-hidden="true"></i>
                        <?php endif; ?>
                    </span>
                    <span class="turma-card-sigla num"><?= h(ViewHelper::siglaTurma($t['nome'])) ?></span>
                    <?php if (!empty($t['bloqueada'])): ?>
                        <span class="turma-card-selo erro"><i class="fas fa-ban"></i> Bloqueada</span>
                    <?php elseif (!$isMaster): ?>
                        <span class="turma-card-selo"><?= h($t['meu_papel'] ?? 'aluno') ?></span>
                    <?php endif; ?>
                </div>
                <div class="turma-card-corpo">
                    <strong><?= h($t['nome']) ?></strong>
                    <span class="turma-card-info">
                        <span><b class="num"><?= (int) ($t['total_alunos'] ?? 0) ?></b> alunos</span>
                        <?php if ($isMaster): ?><span><b class="num"><?= (int) ($t['total_reps'] ?? 0) ?>/2</b> representantes</span><?php endif; ?>
                        <span>Código <code><?= h($t['codigo_acesso']) ?></code></span>
                    </span>
                </div>
                <span class="turma-card-seta" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
            </a>
        <?php endforeach; ?>
    </section>

<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>