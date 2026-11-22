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
            Nova turma
        </div>
        <h1>Criar Turma</h1>
        <p class="hero-subtitle">
            O nome será montado como
            <code>{codigo_escola}-{ano_modulo}-{sigla_curso}-{sigla_periodo}</code>.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/turmas/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <div class="field-row">
        <div>
            <label class="field-label">Código da escola *</label>
            <input type="text" name="codigo_escola" class="field" pattern="\d+" maxlength="20" required autofocus>
        </div>
        <div>
            <label class="field-label">Ano/Módulo *</label>
            <select name="ano_modulo" class="field" required>
                <option value="">Selecione</option>
                <?php foreach ($anosModulo as $val => $label): ?>
                    <option value="<?= h($val) ?>"><?= h($label) ?></option>
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
                    <option value="<?= (int) $c['id'] ?>">
                        <?= h($c['nome']) ?> (<?= h($c['sigla']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($cursos)): ?>
                <small class="field-help-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Nenhum curso ativo.
                    <a href="<?= $basePath ?>/configuracoes/turmas">Cadastre um curso</a>.
                </small>
            <?php endif; ?>
        </div>
        <div>
            <label class="field-label">Período *</label>
            <select name="periodo_id" class="field" required>
                <option value="">Selecione</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>">
                        <?= h($p['nome']) ?> (<?= h($p['sigla']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($periodos)): ?>
                <small class="field-help-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Nenhum período ativo.
                    <a href="<?= $basePath ?>/configuracoes/turmas">Cadastre um período</a>.
                </small>
            <?php endif; ?>
        </div>
    </div>

    <label class="field-label">Código de acesso *</label>
    <input type="text" name="codigo_acesso" class="field" maxlength="30" required
           placeholder="Ex: Info2026">
    <small class="field-help">
        É o que os alunos digitam em <strong>Entrar em turma</strong>. Deve ser único.
    </small>

    <div class="field-row mt-16">
        <div>
            <label class="field-label">Cor primária</label>
            <input type="color" name="cor_primaria" class="field" value="#7165e8">
        </div>
        <div>
            <label class="field-label">Cor secundária</label>
            <input type="color" name="cor_secundaria" class="field" value="#39b889">
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary"
                <?= (empty($cursos) || empty($periodos)) ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Criar turma
        </button>
    </p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>