<?php
$cssPagina = ['projetos.css', 'atas.css'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Nova ata
        </div>
        <h1><?= h($projeto['nome']) ?></h1>
        <p class="hero-subtitle">
            <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a>
            — Escolha os grupos e os dados da ata.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atas">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</section>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atas/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <label class="field-label">Título *</label>
    <input type="text" name="titulo" class="field" maxlength="200" required autofocus>

    <label class="field-label">Descrição</label>
    <textarea name="descricao" class="field" rows="3" maxlength="2000"></textarea>

    <fieldset class="mt-16">
        <legend>Grupos *</legend>

        <?php if (empty($grupos)): ?>
            <div class="notice notice-warning mt-0">
                <span>!</span>
                <div>
                    <strong>Nenhum grupo cadastrado</strong>
                    <p>Crie grupos neste projeto antes de criar atas.</p>
                </div>
            </div>
        <?php else: ?>
            <p class="text-muted mb-12" style="font-size:12px;">
                <i class="fas fa-info-circle"></i>
                Apenas grupos com diretor ativo podem receber atas. Marque os que devem receber esta ata.
            </p>

            <div class="ata-replicar-tabela-wrapper">
                <table class="ata-replicar-tabela">
                    <thead>
                        <tr>
                            <th style="width:36px;"></th>
                            <th>Grupo</th>
                            <th>Diretores ativos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($grupos as $g): ?>
                            <?php $temDiretor = (int) $g['total_diretores'] > 0; ?>
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           class="check-grupo"
                                           name="grupos[]"
                                           value="<?= (int) $g['id'] ?>"
                                           data-tem-diretor="<?= $temDiretor ? '1' : '0' ?>"
                                           <?= $temDiretor ? '' : 'disabled' ?>>
                                </td>
                                <td><strong><?= h($g['nome']) ?></strong></td>
                                <td>
                                    <?php if ($temDiretor): ?>
                                        <span class="badge badge-green">
                                            <i class="fas fa-check-circle"></i>
                                            <?= (int) $g['total_diretores'] ?> diretor(es)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-red">
                                            <i class="fas fa-ban"></i>
                                            Sem diretor ativo
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <p class="mt-12">
                <label class="check-line">
                    <input type="checkbox" id="check-todos" onclick="toggleTodos(this)">
                    <span><strong>Selecionar todos os grupos com diretor ativo</strong></span>
                </label>
            </p>
        <?php endif; ?>
    </fieldset>

    <?php $modoPreenchimento = 'diretor'; require __DIR__ . '/_preenchimento.php'; ?>

    <div class="field-row mt-16">
        <div>
            <label class="field-label">Data da ata *</label>
            <input type="date" name="data_ata" class="field" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div>
            <label class="field-label">Prazo de preenchimento</label>
            <input type="date" name="prazo_preenchimento" class="field">
        </div>
    </div>

    <div class="field-row">
        <div>
            <label class="field-label">Horário início</label>
            <input type="time" name="horario_inicio" class="field">
        </div>
        <div>
            <label class="field-label">Horário fim</label>
            <input type="time" name="horario_fim" class="field">
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary" <?= empty($grupos) ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Criar ata(s)
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atas">
            Cancelar
        </a>
    </p>
</form>

<script>
function toggleTodos(master) {
    document.querySelectorAll('.check-grupo').forEach(function(c) {
        if (c.disabled) return;
        c.checked = master.checked;
    });
}

document.addEventListener('change', function(e) {
    if (!e.target.matches('.check-grupo')) return;

    const todos = document.querySelectorAll('.check-grupo:not([disabled])');
    const marcados = document.querySelectorAll('.check-grupo:not([disabled]):checked');
    const master = document.getElementById('check-todos');
    if (master) master.checked = (todos.length > 0 && todos.length === marcados.length);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>