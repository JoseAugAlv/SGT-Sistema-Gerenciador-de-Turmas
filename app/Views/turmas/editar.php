<?php
$cssPagina = 'turmas.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Editar turma
        </div>
        <h1><?= h($turma['nome']) ?></h1>
        <p class="hero-subtitle">
            Ajuste as informações e o código de acesso.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/atualizar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <div class="field-row">
        <div>
            <label class="field-label">Código da escola *</label>
            <input type="text" name="codigo_escola" class="field" pattern="\d+" maxlength="20"
                   value="<?= h($codigoEscolaAtual) ?>" required>
        </div>
        <div>
            <label class="field-label">Ano/Módulo *</label>
            <select name="ano_modulo" class="field" required>
                <?php foreach ($anosModulo as $val => $label): ?>
                    <option value="<?= h($val) ?>" <?= $val === $anoModuloAtual ? 'selected' : '' ?>>
                        <?= h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="field-row">
        <div>
            <label class="field-label">Curso *</label>
            <select name="curso_id" class="field" required>
                <option value="">Selecione</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                            <?= (int) $c['id'] === (int) $cursoAtualId ? 'selected' : '' ?>>
                        <?= h($c['nome']) ?> (<?= h($c['sigla']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="field-label">Período *</label>
            <select name="periodo_id" class="field" required>
                <option value="">Selecione</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                            <?= (int) $p['id'] === (int) $periodoAtualId ? 'selected' : '' ?>>
                        <?= h($p['nome']) ?> (<?= h($p['sigla']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <label class="field-label">Código de acesso *</label>
    <input type="text" name="codigo_acesso" class="field" maxlength="30"
           value="<?= h($turma['codigo_acesso']) ?>" required>

    <div class="field-row mt-16">
        <div>
            <label class="field-label">Cor primária</label>
            <input type="color" name="cor_primaria" class="field"
                   value="<?= h($turma['cor_primaria']) ?>">
        </div>
        <div>
            <label class="field-label">Cor secundária</label>
            <input type="color" name="cor_secundaria" class="field"
                   value="<?= h($turma['cor_secundaria']) ?>">
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar alterações
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>">
            Cancelar
        </a>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>