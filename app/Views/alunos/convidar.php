<?php
// app/Views/alunos/convidar.php
// Representante cola os emails; cada aluno entra na turma só quando aceitar o convite (cria a conta ou confirma).
$cssPagina = 'alunos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Alunos</div>
        <h1>Convidar alunos</h1>
        <p class="hero-subtitle">Só o email. O aluno entra na lista da turma quando aceitar o convite: quem não tem conta cria pelo link, quem já tem confirma com um toque.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/alunos"><i class="fas fa-arrow-left"></i> Alunos</a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/alunos/convidar" class="panel convite-form">
    <?= ViewHelper::csrfField() ?>

    <?php if (count($turmas) > 1): ?>
        <label class="field-label" for="cv-turma">Turma *</label>
        <select id="cv-turma" name="turma_id" class="field" required>
            <?php foreach ($turmas as $t): ?>
                <option value="<?= (int) $t['id'] ?>"<?= (int) $t['id'] === $turmaSel ? ' selected' : '' ?>><?= h($t['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <input type="hidden" name="turma_id" value="<?= (int) $turmas[0]['id'] ?>">
        <p class="field-help">Turma: <strong><?= h($turmas[0]['nome']) ?></strong></p>
    <?php endif; ?>

    <label class="field-label" for="cv-emails">Emails dos alunos *</label>
    <textarea id="cv-emails" name="emails" class="field" rows="8" required
              placeholder="ana@email.com&#10;bruno@email.com, carla@email.com"></textarea>
    <small class="field-help" id="cv-contagem">Um por linha, ou separados por vírgula. Pode colar direto de uma planilha.</small>

    <p class="mt-16">
        <button type="submit" class="button button-primary"><i class="fas fa-paper-plane"></i> Enviar convites</button>
    </p>
    <p class="field-help mb-0">Quem já tem conta no sistema entra na turma na hora, sem precisar de convite.</p>
</form>

<div class="bloco-titulo mt-24"><h2>Convites aguardando resposta</h2></div>
<?php if (!$pendentes): ?>
    <div class="lista-vazia"><i class="fas fa-envelope-open"></i><span>Nenhum convite pendente.</span></div>
<?php else: ?>
    <ul class="lista-tarefas">
        <?php foreach ($pendentes as $c): $expirou = strtotime($c['expira_em']) < time(); ?>
            <li>
                <span class="tarefa-icone"><i class="fas fa-envelope"></i></span>
                <div class="tarefa-info">
                    <strong><?= h($c['email']) ?></strong>
                    <small><?= h($c['turma_nome']) ?> · enviado em <?= ViewHelper::data($c['created_at']) ?> ·
                        <?= $expirou ? '<span class="txt-alerta">link expirado</span>' : 'vale até ' . ViewHelper::data($c['expira_em']) ?></small>
                </div>
                <form method="POST" action="<?= $basePath ?>/convites/<?= (int) $c['id'] ?>/reenviar" class="d-inline">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="tarefa-acao">Reenviar</button>
                </form>
                <form method="POST" action="<?= $basePath ?>/convites/<?= (int) $c['id'] ?>/cancelar" class="d-inline">
                    <?= ViewHelper::csrfField() ?>
                    <button type="submit" class="tarefa-acao" onclick="return confirm('Cancelar o convite de <?= h($c['email']) ?>? A pessoa sai da turma.')">Cancelar</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<script>
(function () {
    var ta = document.getElementById('cv-emails'), info = document.getElementById('cv-contagem'), padrao = info.textContent;
    var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    ta.addEventListener('input', function () {
        var itens = ta.value.split(/[\s,;]+/).filter(Boolean), ok = itens.filter(function (e) { return re.test(e); });
        var unicos = Array.from(new Set(ok.map(function (e) { return e.toLowerCase(); })));
        info.textContent = itens.length ? unicos.length + ' email(s) válido(s)' + (itens.length - ok.length ? ' · ' + (itens.length - ok.length) + ' inválido(s) serão ignorados' : '') : padrao;
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
