<?php
$cssPagina = 'projetos.css';

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
        <h1>Encerrar Projeto</h1>
        <p class="hero-subtitle">
            <strong><?= h($projeto['nome']) ?></strong>
            — turma <?= h($projeto['turma_nome']) ?>
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<section class="panel">
    <div class="notice notice-danger mt-0">
        <span>!</span>
        <div>
            <strong>Esta ação é irreversível</strong>
            <p>
                Ao encerrar o projeto, o <strong>boletim de todos os alunos será congelado</strong>.
                Edições posteriores nas avaliações <strong>não alterarão</strong> o boletim,
                a menos que o master force o recálculo.
            </p>
        </div>
    </div>

    <h3 class="mt-20">Resumo do que será congelado</h3>

    <div class="status-line">
        <strong>Alunos na turma:</strong>
        <span>serão todos processados</span>
    </div>
    <div class="status-line">
        <strong>Etapas:</strong>
        <span><?= (int) ($projeto['total_etapas'] ?? 0) ?></span>
    </div>
    <div class="status-line">
        <strong>Critérios:</strong>
        <span>todos serão calculados</span>
    </div>

    <hr>

    <form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/encerrar"
          onsubmit="return confirm('Tem certeza absoluta? O boletim será congelado permanentemente.')">
        <?= ViewHelper::csrfField() ?>
        <input type="hidden" name="confirmo" value="1">

        <label class="check-line">
            <input type="checkbox" id="confirmo-check" required>
            <span>Confirmo que desejo encerrar o projeto permanentemente.</span>
        </label>

        <p class="mt-20">
            <button type="submit" class="button btn-danger" id="btn-encerrar" disabled>
                <i class="fas fa-lock"></i> Encerrar definitivamente
            </button>
            <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>">
                Cancelar
            </a>
        </p>
    </form>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var check = document.getElementById('confirmo-check');
    var btn   = document.getElementById('btn-encerrar');
    if (check && btn) {
        check.addEventListener('change', function() {
            btn.disabled = !this.checked;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>