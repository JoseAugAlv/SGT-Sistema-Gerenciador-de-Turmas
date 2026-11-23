// public/js/contagem.js
// Contagem regressiva: <div data-contagem-alvo="timestamp em ms"> com filhos [data-u=a|me|d|h|m|s] e opcional [data-fim].
// Anos e meses aparecem só quando a data está longe (meses de calendário de verdade, não blocos de 30 dias).
window.Contagem = (function () {
    function partes(alvo, agora) {
        var a = new Date(agora), b = new Date(alvo);
        if (b <= a) return null;
        var anos = b.getFullYear() - a.getFullYear(), meses = b.getMonth() - a.getMonth();
        var total = anos * 12 + meses;
        var base = new Date(a); base.setMonth(a.getMonth() + total);
        if (base > b) { total--; base = new Date(a); base.setMonth(a.getMonth() + total); }
        var resto = Math.floor((b - base) / 1000);
        return { a: Math.floor(total / 12), me: total % 12, d: Math.floor(resto / 86400),
                 h: Math.floor(resto % 86400 / 3600), m: Math.floor(resto % 3600 / 60), s: resto % 60 };
    }
    var dois = function (n) { return n < 10 ? '0' + n : '' + n; };
    function pintar(el) {
        var p = partes(+el.dataset.contagemAlvo, Date.now());
        var relogio = el.querySelector('[data-relogio]') || el, fim = el.querySelector('[data-fim]');
        if (!p) { relogio.hidden = true; if (fim) fim.hidden = false; return; }
        ['a', 'me', 'd', 'h', 'm', 's'].forEach(function (k) {
            var c = el.querySelector('[data-u=' + k + ']');
            if (!c) return;
            c.textContent = (k === 'h' || k === 'm' || k === 's') ? dois(p[k]) : p[k];
            var caixa = c.closest('[data-unidade]');
            if (caixa) caixa.hidden = (k === 'a' && !p.a) || (k === 'me' && !p.a && !p.me);
        });
        // Longe (mais de 1 mês): esconde os segundos para não poluir
        var seg = el.querySelector('[data-u=s]');
        if (seg && seg.closest('[data-unidade]')) seg.closest('[data-unidade]').hidden = p.a > 0 || p.me > 0;
    }
    function iniciar(raiz) {
        var els = (raiz || document).querySelectorAll('[data-contagem-alvo]');
        els.forEach(pintar);
        return els;
    }
    var timer = null;
    function rodar() { if (timer) clearInterval(timer); iniciar(); timer = setInterval(function () { iniciar(); }, 1000); }
    document.addEventListener('DOMContentLoaded', rodar);
    return { pintar: pintar, rodar: rodar };
})();
