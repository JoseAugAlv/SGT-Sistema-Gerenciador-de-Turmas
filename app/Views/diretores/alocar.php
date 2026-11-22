<?php
$cssPagina = ['projetos.css', 'grupos.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Alocar membros
        </div>
        <h1>Alocar em <?= h($grupo['nome']) ?></h1>
        <p class="hero-subtitle">
            Selecione vários alunos da turma para adicionar de uma vez.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar ao grupo
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<section class="panel mb-16">
    <div class="panel-header">
        <div>
            <h2>Membros atuais (<?= count($membros) ?>)</h2>
        </div>
    </div>

    <?php if (empty($membros)): ?>
        <p class="text-muted">Nenhum membro.</p>
    <?php else: ?>
        <div class="membros-lista">
            <?php foreach ($membros as $m): ?>
                <div class="membro-item">
                    <span class="avatar avatar-blue"><?= strtoupper(substr($m['nome'], 0, 1)) ?></span>
                    <div class="membro-info">
                        <strong><?= h($m['nome']) ?></strong>
                        <small><?= h($m['email']) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Adicionar alunos</h2>
            <p>
                Marque quem deve entrar no grupo. Alunos que já estão não aparecem na lista.
            </p>
        </div>
    </div>

    <?php if (empty($candidatos)): ?>
        <p class="text-muted">
            Nenhum aluno disponível na turma (todos já estão neste grupo).
        </p>
    <?php else: ?>
        <form method="POST" action="<?= $basePath ?>/grupos/<?= (int) $grupo['id'] ?>/diretores/alocar">
            <?= ViewHelper::csrfField() ?>

            <div class="membros-lista">
                <?php foreach ($candidatos as $c): ?>
                    <label class="participante participante-turma">
                        <input type="checkbox" name="usuarios[]" value="<?= (int) $c['id'] ?>">
                        <?= h($c['nome']) ?>
                        <small class="text-muted">(<?= h($c['email']) ?>)</small>
                    </label>
                <?php endforeach; ?>
            </div>

            <p class="mt-20">
                <button type="submit" class="button button-primary">
                    <i class="fas fa-users"></i> Adicionar selecionados
                </button>
            </p>
        </form>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>