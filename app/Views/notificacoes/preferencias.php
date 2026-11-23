<?php
$cssPagina = 'notificacoes.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

// Garante que as chaves existam
$prefs = array_merge([
    'receber_email'           => 0,
    'receber_email_prazos'    => 0,
    'receber_email_alertas'   => 0,
    'receber_email_avaliacoes'=> 0,
], $prefs ?? []);
?>

<section class="hero-section">
    <div>
        <div class="eyebrow">
            <span class="status-dot"></span>
            Preferências
        </div>
        <h1>Preferências de notificação</h1>
        <p class="hero-subtitle">
            Escolha quanto email você quer receber.
            Você sempre verá as notificações dentro do sistema.
        </p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/notificacoes">
            <i class="fas fa-arrow-left"></i> Voltar às notificações
        </a>
    </div>
</section>

<form method="POST" action="<?= $basePath ?>/notificacoes/preferencias/salvar" class="panel">
    <?= ViewHelper::csrfField() ?>

    <?php $nivelEmail = $prefs['nivel_email'] ?? 'importantes'; require __DIR__ . '/_nivel_email.php'; ?>

    <label class="pref-resumo">
        <input type="checkbox" name="resumo_semanal" value="1"<?= ($prefs['resumo_semanal'] ?? 1) ? ' checked' : '' ?>>
        <span><strong>Receber o resumo da semana</strong><small>Toda segunda-feira: o que vem pela frente na agenda, tarefas, enquetes abertas e avisos das suas turmas.
            <a href="<?= $basePath ?>/resumo-semanal/previa" target="_blank" rel="noopener">Ver como fica o meu</a></small></span>
    </label>
    <style>.pref-resumo { display: flex; gap: 12px; align-items: flex-start; padding: 14px; margin: 14px 0; border: 1px solid var(--border); border-radius: 12px; cursor: pointer; }
    .pref-resumo input { width: 18px; height: 18px; margin-top: 2px; accent-color: var(--primary); } .pref-resumo strong, .pref-resumo small { display: block; } .pref-resumo small { color: var(--muted); }</style>

    <div class="notice notice-blue mt-0">
        <span>i</span>
        <div>
            <strong>Avisos urgentes sempre chegam</strong>
            <p class="mb-0">Avisos marcados como <strong>urgentes</strong> pelo representante ou master são enviados por email para todos, qualquer que seja a escolha.</p>
        </div>
    </div>

    <p class="mt-20">
        <button type="submit" class="button button-primary">
            <i class="fas fa-check"></i> Salvar preferências
        </button>
        <a class="button button-ghost" href="<?= $basePath ?>/notificacoes">
            Cancelar
        </a>
    </p>
</form>

<!-- notificações neste aparelho e instalar o app (PWA) -->
<section class="panel mt-16 push-painel" id="aparelho">
    <div>
        <strong><i class="fas fa-mobile-screen-button"></i> Notificações neste aparelho</strong>
        <p class="mb-0" id="push-texto">Receba os avisos da turma direto no celular ou no computador, mesmo com o site fechado.</p>
    </div>
    <div class="push-botoes">
        <button type="button" class="button button-primary" id="push-ativar" hidden><i class="fas fa-bell"></i> Ativar</button>
        <button type="button" class="button button-secondary" id="push-testar" hidden>Enviar teste</button>
        <button type="button" class="button button-ghost" id="push-desativar" hidden>Desativar</button>
        <button type="button" class="button button-secondary" data-instalar-app><i class="fas fa-download"></i> Baixar o app</button>
    </div>
</section>
<style>.push-painel { display: flex; gap: 16px; align-items: center; justify-content: space-between; flex-wrap: wrap; }
.push-painel p { color: var(--muted); } .push-painel strong i { color: var(--primary); } .push-botoes { display: flex; gap: 8px; flex-wrap: wrap; }</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var P = window.PrumoPush, txt = document.getElementById('push-texto');
    var b = { ativar: document.getElementById('push-ativar'), testar: document.getElementById('push-testar'), desativar: document.getElementById('push-desativar') };
    function mostrar(estado) {
        b.ativar.hidden = estado !== 'inativo'; b.testar.hidden = b.desativar.hidden = estado !== 'ativo';
        if (estado === 'ativo') txt.textContent = 'Ligadas neste aparelho. Os avisos chegam mesmo com o site fechado.';
        if (estado === 'bloqueado') txt.textContent = 'O navegador bloqueou as notificações deste site. Libere nas configurações do navegador (cadeado na barra de endereço).';
        if (estado === 'sem_suporte') txt.textContent = window.isSecureContext ? 'Este navegador não recebe notificações do site.' : 'Por este endereço (http) o aparelho não deixa ativar notificações nem instalar o app. Toque em "Baixar o app" para ver como resolver.';
    }
    if (!P) { mostrar('sem_suporte'); return; }
    P.estado().then(mostrar);
    b.ativar.addEventListener('click', function () { b.ativar.disabled = true; P.ativar().then(function () { mostrar('ativo'); return P.testar(); }).catch(function () { P.estado().then(mostrar); }).finally(function () { b.ativar.disabled = false; }); });
    b.testar.addEventListener('click', function () { P.testar().then(function (r) { txt.textContent = r.ok ? 'Teste enviado! Confira a notificação.' : 'Não foi possível enviar o teste agora.'; }); });
    b.desativar.addEventListener('click', function () { P.desativar().then(function () { mostrar('inativo'); txt.textContent = 'Desligadas neste aparelho.'; }); });
});
</script>

<?php if (($_SESSION['usuario']['tipo'] ?? '') === 'master'): ?>
    <form method="POST" action="<?= $basePath ?>/resumo-semanal/enviar" class="panel mt-16" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <?= ViewHelper::csrfField() ?>
        <div style="flex:1;min-width:240px"><strong>Resumo semanal (master)</strong><p class="mb-0" style="color:var(--muted)">Sai sozinho às segundas. Aqui você manda agora para quem ainda não recebeu nesta semana.</p></div>
        <button type="submit" class="button button-secondary" data-carregando="Enviando…"><i class="fas fa-paper-plane"></i> Enviar resumo agora</button>
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>