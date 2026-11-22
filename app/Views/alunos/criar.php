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
            Novo aluno
        </div>
        <h1>Cadastrar Aluno</h1>
        <p class="hero-subtitle">
            Uma senha temporária será gerada e enviada por email.
            O aluno deverá trocá-la no primeiro acesso.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/alunos/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nome completo *</label>
    <input type="text" name="nome" class="field" maxlength="150" required autofocus
           placeholder="Ex: João da Silva">

    <label class="field-label">Email *</label>
    <input type="email" name="email" class="field" required
           placeholder="joao@escola.edu">

    <div class="notice notice-blue mt-16">
        <span>i</span>
        <div>
            <strong>Primeiro acesso automático</strong>
            <p class="mb-0">
                Uma senha temporária é gerada e enviada por email junto com o link de
                confirmação. No primeiro login, o aluno troca a senha e aceita os
                termos LGPD.
            </p>
        </div>
    </div>

    <fieldset class="mt-20">
        <legend>Turmas *</legend>

        <p class="text-muted" style="font-size:12px;">
            Selecione em qual(is) turma(s) este aluno deve ser adicionado.
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
                        <input type="checkbox" name="turmas[]" value="<?= (int) $t['id'] ?>">
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
        <button type="submit" class="button button-primary"
                <?= empty($minhasTurmas) ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Criar aluno
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/alunos">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>