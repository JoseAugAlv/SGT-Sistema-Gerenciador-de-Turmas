// public/js/carregando.js
// Animação de "carregando" em todo botão que envia formulário (POST): mostra o giro, trava novos cliques
// e evita envio duplicado (ex.: enviar convites ou avisos, que mandam email e demoram).
// Para não usar num formulário: <form data-sem-carregando>. Texto personalizado: <button data-carregando="Gerando Pix…">.
(function () {
    function textoPadrao(form) {
        var acao = (form.getAttribute('action') || '').toLowerCase();
        if (/convid|alerta|aviso|reenviar|enviar|email|cadastr|salvar-cadastro/.test(acao)) return 'Enviando…';
        if (/excluir|remover|cancelar/.test(acao)) return 'Aguarde…';
        return 'Salvando…';
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || e.defaultPrevented) return;
        if ((form.method || 'get').toLowerCase() !== 'post' || form.hasAttribute('data-sem-carregando')) return;

        if (form.dataset.enviando === '1') { e.preventDefault(); return; } // segundo clique: ignora
        form.dataset.enviando = '1';

        var botao = e.submitter || form.querySelector('button[type=submit], button:not([type]), input[type=submit]');
        if (!botao) return;
        // Botões com name/value (ex.: Aprovar/Recusar) precisam chegar ao servidor mesmo desabilitados
        if (botao.name) {
            var oculto = document.createElement('input');
            oculto.type = 'hidden'; oculto.name = botao.name; oculto.value = botao.value;
            form.appendChild(oculto);
        }
        var texto = botao.getAttribute('data-carregando') || textoPadrao(form);
        botao.dataset.htmlOriginal = botao.innerHTML;
        botao.style.minWidth = botao.offsetWidth + 'px';
        botao.classList.add('carregando');
        botao.setAttribute('aria-busy', 'true');
        if (botao.tagName === 'INPUT') botao.value = texto;
        else botao.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>' + texto + '</span>';
        // Desabilita depois do envio começar (desabilitar antes cancelaria o envio em alguns navegadores)
        setTimeout(function () { botao.disabled = true; }, 0);
    }, false);

    // Voltar pelo navegador (cache da página): devolve os botões ao normal
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-enviando="1"]').forEach(function (f) { delete f.dataset.enviando; });
        document.querySelectorAll('.carregando').forEach(function (b) {
            b.disabled = false; b.classList.remove('carregando'); b.removeAttribute('aria-busy'); b.style.minWidth = '';
            if (b.dataset.htmlOriginal !== undefined) b.innerHTML = b.dataset.htmlOriginal;
        });
    });
})();
