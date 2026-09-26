<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Novo Aluno</h1>
        <p class="hero-subtitle">
            Uma senha temporária será gerada e enviada por email.
            O aluno deverá trocá-la no primeiro acesso.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/alunos/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label class="field-label">Nome completo *</label>
        <input type="text" name="nome" class="field" maxlength="150" required autofocus>
    </p>

    <p>
        <label class="field-label">Email *</label>
        <input type="email" name="email" class="field" required>
    </p>

    <fieldset>
        <legend>Turmas</legend>
        <p style="color:var(--muted); font-size:12px;">
            Selecione em qual(is) turma(s) este aluno deve ser adicionado.
        </p>

        <?php if (empty($minhasTurmas)): ?>
            <p style="color:var(--muted);">Nenhuma turma disponível.</p>
        <?php else: ?>
            <?php foreach ($minhasTurmas as $t): ?>
                <label class="participante participante-turma">
                    <input type="checkbox" name="turmas[]" value="<?= (int) $t['id'] ?>">
                    <?= h($t['nome']) ?>
                    <small style="color:var(--muted);">(<?= h($t['codigo_acesso']) ?>)</small>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </fieldset>

    <p style="margin-top:20px;">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Criar aluno
        </button>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>