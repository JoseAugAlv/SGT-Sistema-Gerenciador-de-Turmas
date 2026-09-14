<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<h1>Novo Alerta — <?= h($turma['nome']) ?></h1>

<form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/alertas/salvar">
    <?= ViewHelper::csrfField() ?>

    <p><label>Título *<br>
        <input type="text" name="titulo" maxlength="200" required autofocus></label></p>

    <p><label>Mensagem *<br>
        <textarea name="mensagem" rows="4" maxlength="2000" required></textarea></label></p>

    <p><label>Escopo *<br>
        <select name="escopo" id="escopo" required onchange="mudarEscopo()">
            <option value="turma">Turma inteira</option>
            <option value="projeto">Projeto específico</option>
            <option value="grupo">Grupo específico</option>
        </select></label></p>

    <div id="box-projeto" style="display:none;">
        <p><label>Projeto *<br>
            <select name="projeto_id" id="select-projeto" onchange="carregarGrupos()">
                <option value="">Selecione um projeto</option>
                <?php foreach ($projetos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= h($p['nome']) ?></option>
                <?php endforeach; ?>
            </select></label></p>
    </div>

    <div id="box-grupo" style="display:none;">
        <p><label>Grupo *<br>
            <select name="grupo_id" id="select-grupo">
                <option value="">Selecione um projeto primeiro</option>
            </select></label></p>
    </div>

    <p><label>
        <input type="checkbox" name="urgente" value="1">
        <strong>Urgente</strong> — envia email mesmo para quem desativou notificações
    </label></p>

    <p><label>Expira em (opcional)<br>
        <input type="datetime-local" name="expira_em"></label></p>

    <p>
        <button type="submit"><i class="fas fa-paper-plane"></i> Enviar alerta</button>
        <a href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/alertas">Cancelar</a>
    </p>
</form>

<script>
const gruposPorProjeto = <?= json_encode($gruposPorProjeto) ?>;

function mudarEscopo() {
    const escopo = document.getElementById('escopo').value;
    document.getElementById('box-projeto').style.display = (escopo === 'projeto' || escopo === 'grupo') ? 'block' : 'none';
    document.getElementById('box-grupo').style.display   = (escopo === 'grupo') ? 'block' : 'none';
}

function carregarGrupos() {
    const pid = document.getElementById('select-projeto').value;
    const sel = document.getElementById('select-grupo');
    sel.innerHTML = '<option value="">Selecione um grupo</option>';

    if (!pid || !gruposPorProjeto[pid]) return;

    gruposPorProjeto[pid].forEach(function(g) {
        const opt = document.createElement('option');
        opt.value = g.id;
        opt.textContent = g.nome;
        sel.appendChild(opt);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>