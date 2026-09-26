<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <h1>Criar Alunos em Massa</h1>
        <p class="hero-subtitle">
            Cole uma lista com um nome por linha. O sistema gera email e senha
            temporária automaticamente para cada um.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-secondary" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/alunos/massa/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <p>
        <label class="field-label">Nomes (um por linha) *</label>
        <textarea name="nomes" rows="10" class="field" required
                  placeholder="João da Silva&#10;Maria Oliveira&#10;Pedro Santos"></textarea>
    </p>

    <fieldset>
        <legend>Turmas</legend>
        <p style="color:var(--muted); font-size:12px;">
            Todos os alunos criados serão adicionados nestas turmas.
        </p>

        <?php foreach ($minhasTurmas as $t): ?>
            <label class="participante participante-turma">
                <input type="checkbox" name="turmas[]" value="<?= (int) $t['id'] ?>">
                <?= h($t['nome']) ?>
                <small style="color:var(--muted);">(<?= h($t['codigo_acesso']) ?>)</small>
            </label>
        <?php endforeach; ?>
    </fieldset>

    <p style="margin-top:20px;">
        <button type="submit" class="button button-primary"
                onclick="return confirm('Criar todos os alunos da lista?')">
            <i class="fas fa-users"></i> Criar todos
        </button>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>