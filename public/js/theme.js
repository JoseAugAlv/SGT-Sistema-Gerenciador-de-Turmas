// public/js/theme.js

(function () {
    'use strict';

    var STORAGE_KEY = 'sgt-tema';
    var html = document.documentElement;

    function aplicar(tema) {
        html.setAttribute('data-theme', tema);
        var input = document.getElementById('theme-toggle-input');
        if (input) input.checked = (tema === 'dark');
    }

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('theme-toggle-input');
        if (!input) return;

        input.checked = html.getAttribute('data-theme') === 'dark';

        input.addEventListener('change', function () {
            var novo = this.checked ? 'dark' : 'light';
            aplicar(novo);
            try { localStorage.setItem(STORAGE_KEY, novo); } catch (e) {}
        });
    });

    window.alternarTema = function () {
        var atual = html.getAttribute('data-theme') || 'light';
        var novo  = atual === 'dark' ? 'light' : 'dark';
        aplicar(novo);
        try { localStorage.setItem(STORAGE_KEY, novo); } catch (e) {}
    };
})();