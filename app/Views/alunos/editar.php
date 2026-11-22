<?php
$cssPagina = 'alunos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Editar aluno
        </div>
        <h1><?= h($aluno['nome']) ?></h1>
        <div class="aluno-hero-meta">
            <span><?= h($aluno['email']) ?></span>
            <span>
                <?php if ($aluno['ativo']): ?>
                    <span class="badge badge-green"><i class="fas fa-check"></i> Ativo</span>
                <?php else: ?>
                    <span class="badge badge-red"><i class="fas fa-ban"></i> Inativo</span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos/<?= (int) $aluno['id'] ?>/grupos">
            <i class="fas fa-users"></i> Ver grupos
        </a>
        <a class="button button-ghost" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/alunos/<?= (int) $aluno['id'] ?>/atualizar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome completo *</label>
    <input type="text" name="nome" class="field" maxlength="150"
           value="<?= h($aluno['nome']) ?>" required autofocus>

    <label class="field-label">Email (não editável)</label>
    <input type="email" class="field" value="<?= h($aluno['email']) ?>" disabled>

    <div class="field-row">
        <div>
            <label class="field-label">Telefone</label>
            <input type="text" name="telefone" class="field" maxlength="20"
                   value="<?= h($aluno['telefone'] ?? '') ?>"
                   placeholder="(11) 99999-8888">
        </div>
        <div>
            <label class="field-label">Data de nascimento</label>
            <input type="date" name="data_nascimento" class="field"
                   value="<?= h($aluno['data_nascimento'] ?? '') ?>">
        </div>
    </div>

    <p class="mt-16">
        <label class="check-line">
            <input type="checkbox" name="ativo" value="1" <?= $aluno['ativo'] ? 'checked' : '' ?>>
            <span>Aluno ativo (desmarcar desativa o acesso)</span>
        </label>
    </p>

    <fieldset class="mt-20">
        <legend>Turmas</legend>

        <p class="text-muted" style="font-size:12px;">
            Marque as turmas em que o aluno deve estar.
            Desmarcar remove o aluno da turma (o histórico é mantido).
        </p>

        <?php if (empty($minhasTurmas)): ?>
            <div class="notice notice-warning">
                <span>!</span>
                <div>
                    <strong>Nenhuma turma disponível</strong>
                    <p class="mb-0">
                        Você não é representante de nenhuma turma ainda.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="checkbox-list">
                <?php foreach ($minhasTurmas as $t): ?>
                    <label class="checkbox-turma">
                        <input type="checkbox" name="turmas[]" value="<?= (int) $t['id'] ?>"
                               <?= in_array((int) $t['id'], $turmasDoAluno ?? [], true) ? 'checked' : '' ?>>
                        <div class="checkbox-turma-info">
                            <strong><?= h($t['nome']) ?></strong>
                            <small>Código: <?= h($t['codigo_acesso']) ?></small>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </fieldset>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar alterações
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/alunos">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>