// public/js/theme.js
// Seletor dos três temas do Prumo: light (Claro), dark (Escuro) e misto.
// A aplicação inicial (antes de pintar) fica no <head> de header.php e auth_header.php.

(function () {
    'use strict';

    var STORAGE_KEY = 'sgt-tema';
    var TEMAS = ['light', 'dark', 'misto'];
    var html = document.documentElement;

    function marcarBotoes(tema) {
        document.querySelectorAll('[data-tema]').forEach(function (b) {
            var ativo = b.getAttribute('data-tema') === tema;
            b.classList.toggle('ativo', ativo);
            b.setAttribute('aria-pressed', ativo ? 'true' : 'false');
        });
    }

    function aplicar(tema) {
        if (TEMAS.indexOf(tema) === -1) tema = 'light';
        html.setAttribute('data-theme', tema);
        try { localStorage.setItem(STORAGE_KEY, tema); } catch (e) {}
        marcarBotoes(tema);
    }

    document.addEventListener('DOMContentLoaded', function () {
        marcarBotoes(html.getAttribute('data-theme') || 'light');

        document.querySelectorAll('[data-tema]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                aplicar(this.getAttribute('data-tema'));
            });
        });
    });

    // Atalho antigo: avança para o próximo tema
    window.alternarTema = function () {
        var atual = TEMAS.indexOf(html.getAttribute('data-theme'));
        aplicar(TEMAS[(atual + 1) % TEMAS.length]);
    };
})();
