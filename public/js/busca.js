// public/js/busca.js
// Busca rápida de funções (Ctrl+K ou "/"): lista os links do menu lateral,
// as ações da página atual e atalhos fixos, e filtra enquanto a pessoa digita.

(function () {
    'use strict';

    var dialogo = document.getElementById('busca-rapida');
    if (!dialogo) return;

    var campo = dialogo.querySelector('input');
    var lista = dialogo.querySelector('ul');
    var itens = [];
    var selecionado = 0;

    function semAcento(t) {
        return (t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function coletar() {
        var vistos = {};
        itens = [];
        document.querySelectorAll('[data-busca], .main-nav a, .acao, .acoes-turma a, .user-menu-dropdown a').forEach(function (a) {
            var href = a.getAttribute('href');
            if (!href || href === '#' || vistos[href]) return;
            var rotulo = (a.getAttribute('data-busca') || a.querySelector('strong')?.textContent || a.textContent).trim().replace(/\s+/g, ' ');
            var dica = a.querySelector('small')?.textContent.trim() || '';
            var icone = a.querySelector('i')?.className || 'fas fa-arrow-right';
            if (!rotulo) return;
            vistos[href] = true;
            itens.push({ rotulo: rotulo, dica: dica, href: href, icone: icone, chave: semAcento(rotulo + ' ' + dica) });
        });
    }

    function desenhar() {
        var termo = semAcento(campo.value.trim());
        var achados = itens.filter(function (i) { return !termo || i.chave.indexOf(termo) !== -1; }).slice(0, 12);
        selecionado = Math.min(selecionado, Math.max(0, achados.length - 1));
        lista.innerHTML = '';
        if (!achados.length) {
            lista.innerHTML = '<li class="busca-vazia">Nenhuma função encontrada para “' + campo.value.replace(/</g, '&lt;') + '”.</li>';
            return;
        }
        achados.forEach(function (i, n) {
            var li = document.createElement('li');
            var a = document.createElement('a');
            a.href = i.href;
            a.className = n === selecionado ? 'ativo' : '';
            a.innerHTML = '<i></i><span><strong></strong><small></small></span>';
            a.querySelector('i').className = i.icone;
            a.querySelector('strong').textContent = i.rotulo;
            a.querySelector('small').textContent = i.dica;
            li.appendChild(a);
            lista.appendChild(li);
        });
    }

    function abrir() {
        coletar();
        campo.value = '';
        selecionado = 0;
        desenhar();
        dialogo.showModal();
        campo.focus();
    }

    document.querySelectorAll('[data-abrir-busca]').forEach(function (b) { b.addEventListener('click', abrir); });

    document.addEventListener('keydown', function (e) {
        var digitando = /input|textarea|select/i.test(e.target.tagName) || e.target.isContentEditable;
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); abrir(); }
        else if (e.key === '/' && !digitando && !dialogo.open) { e.preventDefault(); abrir(); }
    });

    campo.addEventListener('input', function () { selecionado = 0; desenhar(); });
    campo.addEventListener('keydown', function (e) {
        var links = lista.querySelectorAll('a');
        if (e.key === 'ArrowDown') { e.preventDefault(); selecionado = Math.min(selecionado + 1, links.length - 1); desenhar(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); selecionado = Math.max(selecionado - 1, 0); desenhar(); }
        else if (e.key === 'Enter' && links[selecionado]) { e.preventDefault(); window.location.href = links[selecionado].href; }
    });

    // Fecha ao clicar fora da caixa
    dialogo.addEventListener('click', function (e) { if (e.target === dialogo) dialogo.close(); });
})();
