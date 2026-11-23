// public/js/landing-tema.js
// Botão claro/escuro da landing. Usa a mesma chave do app (sgt-tema, public/js/theme.js),
// então a escolha feita aqui continua valendo depois do login. O tema inicial é aplicado
// no <head> da landing, antes da primeira pintura.
(function () {
    'use strict';

    var CHAVE = 'sgt-tema';
    var html = document.documentElement;
    var meta = document.querySelector('meta[name="theme-color"]');

    function aplicar(tema) {
        html.setAttribute('data-theme', tema);
        html.setAttribute('data-tone', tema);   // o kit usa data-tone para rodapés escuros
        if (meta) meta.setAttribute('content', tema === 'dark' ? '#0c0f1d' : '#f6f7fb');
        document.querySelectorAll('[data-alternar-tema]').forEach(function (b) {
            b.setAttribute('aria-label', tema === 'dark' ? 'Mudar para o tema claro' : 'Mudar para o tema escuro');
            b.setAttribute('aria-pressed', tema === 'dark' ? 'true' : 'false');
        });
    }

    function alternar() {
        var novo = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(CHAVE, novo); } catch (e) {}
        aplicar(novo);
    }

    function iniciar() {
        aplicar(html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
        document.querySelectorAll('[data-alternar-tema]').forEach(function (b) {
            b.addEventListener('click', alternar);
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();

    // o kit (system.js) recalcula data-tone ao carregar; reaplica o do tema escolhido depois dele
    window.addEventListener('load', function () {
        setTimeout(function () { html.setAttribute('data-tone', html.getAttribute('data-theme') === 'dark' ? 'dark' : 'light'); }, 50);
    });
})();
