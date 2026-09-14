<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1><?= h($projeto['nome']) ?></h1>
<p>Turma: <a href="<?= $basePath ?>/turmas/<?= (int) $projeto['turma_id'] ?>"><?= h($projeto['turma_nome']) ?></a></p>

<?php require __DIR__ . '/../projetos/_nav.php'; ?>

<h2>Nova Ata</h2>

<form method="POST" action="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atas/salvar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Título *<br>
        <input type="text" name="titulo" maxlength="200" required autofocus></label></p>

    <p><label>Descrição<br>
        <textarea name="descricao" rows="3" maxlength="2000"></textarea></label></p>

    <fieldset>
        <legend>Grupos *</legend>

        <?php if (empty($grupos)): ?>
            <p><strong>Nenhum grupo cadastrado neste projeto.</strong></p>
        <?php else: ?>
            <p>
                <label>
                    <input type="checkbox" id="check-todos" onclick="toggleTodos(this)">
                    <strong>Selecionar todos os grupos com diretor ativo</strong>
                </label>
            </p>

            <table border="1" cellpadding="6">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th>Grupo</th>
                        <th>Diretor(es) ativo(s)</th>
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
                            <td><?= h($g['nome']) ?></td>
                            <td>
                                <?php if ($temDiretor): ?>
                                    <i class="fas fa-check-circle" style="color:#0a0"></i>
                                    <?= (int) $g['total_diretores'] ?>
                                <?php else: ?>
                                    <i class="fas fa-ban" style="color:#b00"></i>
                                    <small>Sem diretor — não pode receber ata</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </fieldset>

    <p><label>Data da ata *<br>
        <input type="date" name="data_ata" value="<?= date('Y-m-d') ?>" required></label></p>

    <p><label>Prazo de preenchimento<br>
        <input type="date" name="prazo_preenchimento"></label></p>

    <p><label>Horário início<br>
        <input type="time" name="horario_inicio"></label></p>

    <p><label>Horário fim<br>
        <input type="time" name="horario_fim"></label></p>

    <p>
        <button type="submit" <?= empty($grupos) ? 'disabled' : '' ?>>
            <i class="fas fa-check"></i> Criar ata(s)
        </button>
        <a href="<?= $basePath ?>/projetos/<?= (int) $projeto['id'] ?>/atas">Cancelar</a>
    </p>
</form>

<script>
function toggleTodos(master) {
    document.querySelectorAll('.check-grupo').forEach(function(c) {
        if (c.disabled) return;
        c.checked = master.checked;
    });
}

// Se todos os grupos individuais estiverem marcados, reflete no "todos"
document.addEventListener('change', function(e) {
    if (!e.target.matches('.check-grupo')) return;

    const todos = document.querySelectorAll('.check-grupo:not([disabled])');
    const marcados = document.querySelectorAll('.check-grupo:not([disabled]):checked');
    document.getElementById('check-todos').checked = (todos.length === marcados.length);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>