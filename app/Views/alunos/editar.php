<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Editar Aluno</h1>
        <p class="hero-subtitle">
            <strong><?= h($aluno['nome']) ?></strong> —
            <?= h($aluno['email']) ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos/<?= (int) $aluno['id'] ?>/grupos">
            <i class="fas fa-users"></i> Ver grupos
        </a>
        <a class="button button-secondary" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/alunos/<?= (int) $aluno['id'] ?>/atualizar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label class="field-label">Nome completo *</label>
        <input type="text" name="nome" class="field" maxlength="150"
               value="<?= h($aluno['nome']) ?>" required autofocus>
    </p>

    <p>
        <label class="field-label">Email (não editável)</label>
        <input type="email" class="field" value="<?= h($aluno['email']) ?>" disabled>
    </p>

    <p>
        <label class="field-label">Telefone</label>
        <input type="text" name="telefone" class="field" maxlength="20"
               value="<?= h($aluno['telefone'] ?? '') ?>">
    </p>

    <p>
        <label class="field-label">Data de nascimento</label>
        <input type="date" name="data_nascimento" class="field"
               value="<?= h($aluno['data_nascimento'] ?? '') ?>">
    </p>

    <p>
        <label class="check-line">
            <input type="checkbox" name="ativo" value="1" <?= $aluno['ativo'] ? 'checked' : '' ?>>
            <span>Aluno ativo</span>
        </label>
    </p>

    <fieldset>
        <legend>Turmas</legend>
        <p style="color:var(--muted); font-size:12px;">
            Marque as turmas em que o aluno deve estar.
            Desmarcar remove o aluno da turma (mantém histórico).
        </p>

        <?php if (empty($minhasTurmas)): ?>
            <p style="color:var(--muted);">Nenhuma turma disponível.</p>
        <?php else: ?>
            <?php foreach ($minhasTurmas as $t): ?>
                <label class="participante participante-turma">
                    <input type="checkbox" name="turmas[]" value="<?= (int) $t['id'] ?>"
                           <?= in_array((int) $t['id'], $turmasDoAluno ?? [], true) ? 'checked' : '' ?>>
                    <?= h($t['nome']) ?>
                    <small style="color:var(--muted);">(<?= h($t['codigo_acesso']) ?>)</small>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </fieldset>

    <p style="margin-top:20px;">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar alterações
        </button>
        <a href="<?= $basePath ?>/alunos" class="button button-ghost">
            <i class="fas fa-times"></i> Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>