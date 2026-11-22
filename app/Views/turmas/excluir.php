<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section danger-hero">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Zona de perigo
        </div>
        <h1>Excluir Turma</h1>
        <p class="hero-subtitle">
            Esta ação é <strong class="text-red">permanente</strong> e não pode ser desfeita.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2><?= h($turma['nome']) ?></h2>
            <p>Código: <code class="code-inline"><?= h($turma['codigo_acesso']) ?></code></p>
        </div>
    </div>

    <h3>Verificações</h3>
    <div class="status-line">
        <strong>Alunos ativos:</strong>
        <span><?= (int) $totalAlunos ?></span>
    </div>
    <div class="status-line">
        <strong>Projetos:</strong>
        <span><?= (int) $totalProjetos ?></span>
    </div>
</section>

<?php if (!$podeExcluir): ?>
    <section class="panel mt-16">
        <div class="notice notice-danger mt-0">
            <span>!</span>
            <div>
                <strong>Não é possível excluir</strong>
                <p>
                    Remova todos os alunos e projetos antes. Se for só para suspender
                    o acesso, use <strong>Bloquear</strong> em vez de excluir.
                </p>
            </div>
        </div>
        <p>
            <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
                Voltar à turma
            </a>
        </p>
    </section>
<?php else: ?>
    <section class="panel mt-16">
        <form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/excluir"
              onsubmit="return confirm('Tem certeza absoluta? Esta ação é irreversível.')">
            <?= ViewHelper::csrfField() ?>

            <label class="check-line">
                <input type="checkbox" name="confirmo" value="1" required>
                <span>Confirmo que desejo excluir a turma <strong><?= h($turma['nome']) ?></strong> permanentemente.</span>
            </label>

            <p class="mt-20">
                <button type="submit" class="button btn-danger">
                    <i class="fas fa-trash"></i> Excluir definitivamente
                </button>
                <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
                    Cancelar
                </a>
            </p>
        </form>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>