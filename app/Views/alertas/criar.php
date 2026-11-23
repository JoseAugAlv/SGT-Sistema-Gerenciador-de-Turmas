<?php
// app/Views/alertas/criar.php
// Novo aviso: comum, aviso do dia ou com contagem regressiva (anos/meses/dias/horas) no painel da turma.
$cssPagina = 'projetos.css';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$ehGremio = ($turma['tipo'] ?? 'turma') === 'gremio';
$tipoIni  = !empty($_GET['contagem']) && $temDia ? 'contagem' : 'aviso';
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Avisos</div>
        <h1>Novo aviso</h1>
        <p class="hero-subtitle"><?= $ehGremio ? 'Grêmio' : 'Turma' ?> <strong><?= h($turma['nome']) ?></strong> · todos recebem uma notificação.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/alertas"><i class="fas fa-arrow-left"></i> Avisos</a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/alertas/salvar" class="projeto-wizard aviso-form">
    <?= ViewHelper::csrfField() ?>

    <section class="panel pw-passo">
        <h2 class="pw-titulo"><span class="pw-num">1</span> Que tipo de aviso?</h2>
        <div class="cg-ops cg-ops-3">
            <label class="cg-op"><input type="radio" name="tipo_aviso" value="aviso"<?= $tipoIni === 'aviso' ? ' checked' : '' ?>>
                <span class="cg-card"><i class="fas fa-bullhorn"></i><strong>Aviso</strong><small>Fica no mural até expirar.</small></span></label>
            <label class="cg-op"><input type="radio" name="tipo_aviso" value="dia"<?= $temDia ? '' : ' disabled' ?>>
                <span class="cg-card"><i class="fas <?= $temDia ? 'fa-sun' : 'fa-lock' ?>"></i><strong>Aviso do dia</strong><small><?= $temDia ? 'Em destaque só no dia escolhido.' : 'Dia a dia: incluso a partir do Anual.' ?></small></span></label>
            <label class="cg-op"><input type="radio" name="tipo_aviso" value="contagem"<?= $tipoIni === 'contagem' && $temDia ? ' checked' : '' ?><?= $temDia ? '' : ' disabled' ?>>
                <span class="cg-card"><i class="fas <?= $temDia ? 'fa-hourglass-half' : 'fa-lock' ?>"></i><strong>Contagem regressiva</strong><small><?= $temDia ? 'Relógio até uma entrega, prova ou evento.' : 'Dia a dia: incluso a partir do Anual.' ?></small></span></label>
        </div>
        <div class="aviso-extra" data-so="dia" hidden>
            <label class="field-label" for="av-dia">Dia do aviso</label>
            <input id="av-dia" type="date" name="do_dia" class="field" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="aviso-extra" data-so="contagem" hidden>
            <input type="hidden" name="tem_contagem" value="0" id="av-tem-contagem">
            <label class="field-label" for="av-contagem">Contar até *</label>
            <input id="av-contagem" type="datetime-local" name="contagem_ate" class="field" min="<?= date('Y-m-d\TH:i') ?>">
            <div class="aviso-previa" id="av-previa" hidden>
                <small>Prévia de como aparece no painel:</small>
                <?php $alvoMs = 0; require __DIR__ . '/_relogio.php'; ?>
            </div>
        </div>
    </section>

    <section class="panel pw-passo">
        <h2 class="pw-titulo"><span class="pw-num">2</span> Mensagem</h2>
        <label class="field-label" for="av-titulo">Título *</label>
        <input id="av-titulo" type="text" name="titulo" class="field" maxlength="200" required autofocus placeholder="Ex.: Prova de matemática amanhã">
        <label class="field-label" for="av-msg">Mensagem *</label>
        <textarea id="av-msg" name="mensagem" class="field" rows="4" maxlength="2000" required></textarea>
        <label class="check-line mt-12"><input type="checkbox" name="urgente" value="1"> <strong>Urgente</strong> — vai por email até para quem desligou os emails</label>
    </section>

    <section class="panel pw-passo">
        <h2 class="pw-titulo"><span class="pw-num">3</span> Para quem</h2>
        <div class="pw-linha">
            <div>
                <label class="field-label" for="escopo">Quem recebe *</label>
                <select name="escopo" id="escopo" class="field" required>
                    <option value="turma"><?= $ehGremio ? 'Grêmio inteiro' : 'Turma inteira' ?></option>
                    <?php if ($projetos): ?>
                        <option value="projeto">Um projeto</option>
                        <option value="grupo">Um grupo de projeto</option>
                    <?php endif; ?>
                </select>
            </div>
            <div id="box-projeto" hidden>
                <label class="field-label" for="select-projeto">Projeto *</label>
                <select name="projeto_id" id="select-projeto" class="field">
                    <option value="">Selecione</option>
                    <?php foreach ($projetos as $p): ?><option value="<?= (int) $p['id'] ?>"><?= h($p['nome']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div id="box-grupo" hidden>
                <label class="field-label" for="select-grupo">Grupo *</label>
                <select name="grupo_id" id="select-grupo" class="field"><option value="">Selecione um projeto</option></select>
            </div>
        </div>
        <details class="mt-12" data-expira>
            <summary class="text-link">Definir quando o aviso some (opcional)</summary>
            <input type="datetime-local" name="expira_em" class="field mt-12">
            <small class="field-help">Aviso do dia some à meia-noite. Contagem sem data some 1 dia depois de zerar.</small>
        </details>
    </section>

    <div class="pw-acoes">
        <button type="submit" class="button button-primary" data-carregando="Enviando aviso…"><i class="fas fa-paper-plane"></i> Enviar aviso</button>
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= (int) $turma['id'] ?>/alertas">Cancelar</a>
    </div>
</form>

<style>
.projeto-wizard { display: grid; gap: 16px; max-width: 860px; }
.pw-passo { margin: 0; }
.pw-titulo { display: flex; align-items: center; gap: 10px; margin: 0 0 14px; font-size: 18px; }
.pw-num { display: inline-grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 14px; font-weight: 700; flex: none; }
.pw-linha { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.pw-acoes { display: flex; gap: 8px; flex-wrap: wrap; }
.cg-ops-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.aviso-extra { margin-top: 14px; max-width: 520px; }
.aviso-previa { margin-top: 12px; display: grid; gap: 6px; } .aviso-previa > small { color: var(--muted); }
@media (max-width: 680px) { .pw-linha, .cg-ops-3 { grid-template-columns: 1fr; } }
</style>
<script src="<?= ViewHelper::asset('js/contagem.js') ?>"></script>
<script>
(function () {
    var form = document.querySelector('.aviso-form');
    var gruposPorProjeto = <?= json_encode($gruposPorProjeto) ?>;
    var contagem = document.getElementById('av-contagem'), previa = document.getElementById('av-previa');
    function tipo() { return form.querySelector('[name=tipo_aviso]:checked').value; }
    function aplicarTipo() {
        form.querySelectorAll('[data-so]').forEach(function (b) { b.hidden = b.dataset.so !== tipo(); });
        contagem.required = tipo() === 'contagem';
        document.getElementById('av-tem-contagem').value = tipo() === 'contagem' ? '1' : '0';
    }
    function aplicarPrevia() {
        var t = contagem.value ? new Date(contagem.value).getTime() : 0;
        previa.hidden = !t;
        if (t) { var r = previa.querySelector('[data-contagem-alvo]'); r.dataset.contagemAlvo = t; Contagem.pintar(r); }
    }
    form.addEventListener('change', function (e) {
        if (e.target.name === 'tipo_aviso') aplicarTipo();
        if (e.target.id === 'escopo') {
            var v = e.target.value;
            document.getElementById('box-projeto').hidden = !(v === 'projeto' || v === 'grupo');
            document.getElementById('box-grupo').hidden = v !== 'grupo';
        }
        if (e.target.id === 'select-projeto') {
            var sel = document.getElementById('select-grupo');
            sel.innerHTML = '<option value="">Selecione um grupo</option>';
            (gruposPorProjeto[e.target.value] || []).forEach(function (g) {
                var o = document.createElement('option'); o.value = g.id; o.textContent = g.nome; sel.appendChild(o);
            });
        }
    });
    contagem.addEventListener('input', aplicarPrevia);
    setInterval(aplicarPrevia, 1000);
    aplicarTipo();
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
