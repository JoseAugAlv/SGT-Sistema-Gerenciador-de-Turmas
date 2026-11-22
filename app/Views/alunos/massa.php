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
            Criação em massa
        </div>
        <h1>Criar Alunos em Massa</h1>
        <p class="hero-subtitle">
            Cole uma lista com um nome por linha. O sistema gera email e senha
            temporária automaticamente para cada um.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/alunos">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<div class="notice notice-blue mt-0 mb-16">
    <span>i</span>
    <div>
        <strong>Como funciona a criação em massa</strong>
        <p class="mb-0">
            Para cada nome, o sistema gera um email no formato
            <code>nome.sobrenome@aluno.edu</code> (com sufixo numérico se já existir),
            uma senha temporária e envia o email de primeiro acesso.
            Você verá um resumo no final.
        </p>
    </div>
</div>

<form method="POST" action="<?= $basePath ?>/alunos/massa/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Nomes (um por linha) *</label>
    <textarea name="nomes" id="massa-nomes" class="massa-textarea" required
              placeholder="João da Silva&#10;Maria Oliveira Santos&#10;Pedro Henrique Costa"
              oninput="atualizarContador()"></textarea>

    <div class="massa-contador">
        <span>
            <i class="fas fa-list-ol"></i>
            <strong id="contador-nomes">0</strong> nome(s) detectado(s)
        </span>
        <span>Um nome por linha</span>
    </div>

    <fieldset class="mt-20">
        <legend>Turmas *</legend>

        <p class="text-muted" style="font-size:12px;">
            Todos os alunos criados serão adicionados nestas turmas.
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
                <?= empty($minhasTurmas) ? 'disabled' : '' ?>
                onclick="return confirm('Criar todos os alunos da lista? Esta operação pode demorar.')">
            <i class="fas fa-users"></i> Criar todos
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/alunos">
            Cancelar
        </a>
    </p>
</form>

<script>
function atualizarContador() {
    var v = document.getElementById('massa-nomes').value;
    var linhas = v.split(/\r\n|\r|\n/).filter(function(l) { return l.trim() !== ''; });
    document.getElementById('contador-nomes').textContent = linhas.length;
}
document.addEventListener('DOMContentLoaded', atualizarContador);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>