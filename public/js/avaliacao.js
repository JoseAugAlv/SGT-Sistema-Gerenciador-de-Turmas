// public/js/avaliacao.js
// Tela de avaliação "um critério por vez" (representante, pares e outras):
// troca de critério, conceito ↔ valor, progresso, busca, preencher quem está sem nota,
// justificativa obrigatória em R e I, salvamento automático (data-autosave),
// atalhos de teclado e aviso de alterações não salvas.
// Regra do valor padrão: topo da faixa (igual ao servidor).

(function () {
    'use strict';

    var form = document.getElementById('form-avaliacao');
    if (!form) return;

    var CFG = {
        iMax: parseFloat(form.dataset.iMax),
        rMax: parseFloat(form.dataset.rMax),
        bMax: parseFloat(form.dataset.bMax)
    };
    var chave = form.dataset.chave;
    var autosave = form.dataset.autosave === '1';
    var statusEl = form.querySelector('[data-status]');
    var timers = {};

    function valorPorConceito(c) { return { I: CFG.iMax, R: CFG.rMax, B: CFG.bMax, MB: 100 }[c]; }
    function conceitoPorValor(v) {
        if (v <= CFG.iMax) return 'I';
        if (v <= CFG.rMax) return 'R';
        if (v <= CFG.bMax) return 'B';
        return 'MB';
    }
    function lerNumero(txt) {
        var t = String(txt).trim().replace(',', '.');
        if (t === '' || isNaN(t)) return null;
        return Math.max(0, Math.min(100, parseFloat(t)));
    }
    function fmt(n) { return String(Math.round(n * 100) / 100).replace('.', ','); }
    function semAcento(t) { return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }

    // ---------- Estado de uma linha ----------
    function linhaDe(el) { return el.closest('.aval-linha'); }
    function conceitoDa(linha) { var r = linha.querySelector('.conceito-op input:checked'); return r ? r.value : ''; }

    function marcarConceito(celula, c) {
        celula.querySelectorAll('input[type=radio]').forEach(function (r) { r.checked = r.value === c; });
        celula.classList.toggle('preenchida', !!c);
        var linha = linhaDe(celula);
        if (linha.querySelector('[data-just]')) {
            linha.classList.toggle('just-obrigatoria', c === 'R' || c === 'I');
            linha.classList.remove('just-faltando');
        }
    }
    function definirConceito(celula, c) {
        marcarConceito(celula, c);
        celula.querySelector('.aval-valor').value = c ? fmt(valorPorConceito(c)) : '';
        mudou(linhaDe(celula));
    }
    function mudou(linha) {
        linha.dataset.sujo = '1';
        atualizarStatus();
        var painel = linha.closest('[data-criterio]');
        atualizarProgresso(painel);
        if (autosave) agendarAutosave(painel);
    }
    function faltaJustificativa(linha) {
        var t = linha.querySelector('[data-just] textarea');
        var c = conceitoDa(linha);
        return !!t && (c === 'R' || c === 'I') && t.value.trim() === '';
    }

    // ---------- Progresso e status ----------
    function atualizarProgresso(painel) {
        if (!painel) return;
        var cid = painel.dataset.criterio;
        var celulas = painel.querySelectorAll('[data-celula]');
        var feitas = 0;
        celulas.forEach(function (c) { if (c.querySelector('.aval-valor').value.trim() !== '') feitas++; });
        var p = form.querySelector('[data-progresso="' + cid + '"]');
        var b = form.querySelector('[data-barra="' + cid + '"]');
        if (p) { p.textContent = feitas + '/' + celulas.length; p.classList.toggle('completo', feitas === celulas.length && feitas > 0); }
        if (b) b.style.width = (celulas.length ? feitas / celulas.length * 100 : 0) + '%';
    }
    function pendentes() { return form.querySelectorAll('.aval-linha[data-sujo]').length; }
    function atualizarStatus(texto) {
        var n = pendentes();
        statusEl.textContent = texto || (n === 0 ? 'Nenhuma alteração' : n === 1 ? '1 alteração não salva' : n + ' alterações não salvas');
        statusEl.classList.toggle('pendente', !texto && n > 0);
    }

    // ---------- Salvamento automático (por critério) ----------
    function agendarAutosave(painel) {
        var cid = painel.dataset.criterio;
        clearTimeout(timers[cid]);
        timers[cid] = setTimeout(function () { salvarPainel(painel); }, 1200);
    }
    function salvarPainel(painel) {
        var fd = new FormData();
        form.querySelectorAll(':scope > input[type=hidden]').forEach(function (i) { fd.append(i.name, i.value); });
        fd.append('criterio_id', painel.dataset.criterio);
        painel.querySelectorAll('input, textarea').forEach(function (el) {
            if (!el.name) return;
            if (el.type === 'radio' && !el.checked) return;
            fd.append(el.name, el.value);
        });
        // Linhas sem nenhum conceito marcado precisam mandar o campo vazio (para apagar)
        painel.querySelectorAll('[data-celula]').forEach(function (c) {
            if (!c.querySelector('input[type=radio]:checked')) {
                var r = c.querySelector('input[type=radio]');
                if (r) fd.append(r.name, '');
            }
        });
        atualizarStatus('Salvando…');
        fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (dados) {
                painel.querySelectorAll('.aval-linha[data-sujo]').forEach(function (l) {
                    if (!faltaJustificativa(l)) delete l.dataset.sujo;
                });
                var hora = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                atualizarStatus(dados.pulados > 0
                    ? 'Salvo às ' + hora + ' · ' + dados.pulados + ' sem justificativa ficou de fora'
                    : 'Salvo automaticamente às ' + hora);
            })
            .catch(function () { atualizarStatus('Não foi possível salvar automaticamente. Use o botão Salvar.'); });
    }

    // ---------- Troca de critério ----------
    function abrirCriterio(cid, rolar) {
        form.querySelectorAll('.aval-crit-btn').forEach(function (b) {
            b.setAttribute('aria-selected', b.dataset.alvo === String(cid) ? 'true' : 'false');
        });
        form.querySelectorAll('.aval-painel').forEach(function (p) { p.hidden = p.dataset.criterio !== String(cid); });
        try { localStorage.setItem(chave, cid); } catch (e) {}
        if (rolar && window.innerWidth < 1000) {
            var painel = document.getElementById('painel-' + cid);
            if (painel) painel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    form.querySelectorAll('.aval-crit-btn').forEach(function (b) {
        b.addEventListener('click', function () { abrirCriterio(b.dataset.alvo, true); });
    });

    // ---------- Eventos ----------
    form.addEventListener('change', function (e) {
        if (e.target.matches('.conceito-op input')) definirConceito(e.target.closest('[data-celula]'), e.target.value);
    });
    form.addEventListener('input', function (e) {
        if (e.target.matches('.aval-valor')) {
            var celula = e.target.closest('[data-celula]');
            var n = lerNumero(e.target.value);
            marcarConceito(celula, n === null ? '' : conceitoPorValor(n));
            mudou(linhaDe(celula));
        }
        if (e.target.matches('.aval-just textarea')) {
            linhaDe(e.target).classList.remove('just-faltando');
            mudou(linhaDe(e.target));
        }
        if (e.target.matches('[data-busca-aluno]')) {
            var painel = e.target.closest('.aval-painel');
            var termo = semAcento(e.target.value.trim());
            var visiveis = 0;
            painel.querySelectorAll('.aval-linha').forEach(function (li) {
                var ok = !termo || semAcento(li.dataset.nome).indexOf(termo) !== -1;
                li.hidden = !ok;
                if (ok) visiveis++;
            });
            var vazio = painel.querySelector('.aval-sem-resultado');
            if (vazio) vazio.hidden = visiveis > 0;
        }
    });
    form.addEventListener('blur', function (e) {
        if (e.target.matches && e.target.matches('.aval-valor')) {
            var n = lerNumero(e.target.value);
            e.target.value = n === null ? '' : fmt(n);
        }
    }, true);

    form.addEventListener('click', function (e) {
        var comentar = e.target.closest('[data-comentar]');
        if (comentar) {
            var l = linhaDe(comentar);
            l.classList.add('comentando');
            l.querySelector('.aval-just textarea').focus();
        }

        var limpar = e.target.closest('[data-limpar]');
        if (limpar) definirConceito(limpar.closest('[data-celula]'), '');

        var preencher = e.target.closest('[data-preencher]');
        if (preencher) {
            var painel = preencher.closest('.aval-painel');
            var n = 0;
            painel.querySelectorAll('.aval-linha:not([hidden]) [data-celula]').forEach(function (c) {
                if (c.querySelector('.aval-valor').value.trim() === '') { definirConceito(c, preencher.dataset.preencher); n++; }
            });
            preencher.blur();
            if (n === 0) atualizarStatus('Todos já têm nota neste critério');
        }
    });

    // Atalhos: com o foco numa linha, 1/2/3/4 ou I/R/B/M definem o conceito; Enter desce
    form.addEventListener('keydown', function (e) {
        var celula = e.target.closest && e.target.closest('[data-celula]');
        if (!celula) return;
        var mapa = { '1': 'I', '2': 'R', '3': 'B', '4': 'MB', i: 'I', r: 'R', b: 'B', m: 'MB' };
        var k = e.key.toLowerCase();
        if (!e.target.matches('.aval-valor') && mapa[k]) { e.preventDefault(); definirConceito(celula, mapa[k]); }
        if (e.key === 'Enter') {
            e.preventDefault();
            var linhas = Array.prototype.slice.call(celula.closest('.aval-lista').querySelectorAll('.aval-linha:not([hidden]) [data-celula]'));
            var prox = linhas[linhas.indexOf(celula) + 1];
            if (prox) (prox.querySelector('input:checked') || prox.querySelector('.aval-valor')).focus();
        }
    });

    // ---------- Salvar ----------
    form.addEventListener('submit', function (e) {
        var faltando = Array.prototype.filter.call(form.querySelectorAll('.aval-linha'), faltaJustificativa);
        if (faltando.length) {
            e.preventDefault();
            faltando.forEach(function (l) { l.classList.add('just-faltando'); });
            var painel = faltando[0].closest('[data-criterio]');
            abrirCriterio(painel.dataset.criterio, false);
            faltando[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            faltando[0].querySelector('textarea').focus({ preventScroll: true });
            atualizarStatus(faltando.length === 1 ? 'Falta 1 justificativa (R ou I)' : 'Faltam ' + faltando.length + ' justificativas (R ou I)');
            return;
        }
        var limparAntes = form.querySelector('[data-limpar-antes]');
        if (limparAntes && limparAntes.checked &&
            !confirm('Limpar e salvar apaga TODAS as suas notas dos critérios abertos e grava só o que está na tela. Continuar?')) {
            e.preventDefault();
            return;
        }
        form.querySelectorAll('.aval-linha[data-sujo]').forEach(function (l) { delete l.dataset.sujo; });
    });
    window.addEventListener('beforeunload', function (e) {
        if (pendentes() > 0) { e.preventDefault(); e.returnValue = ''; }
    });

    // ---------- Início ----------
    form.querySelectorAll('[data-celula]').forEach(function (c) {
        var r = c.querySelector('input:checked');
        marcarConceito(c, r ? r.value : '');
        c.classList.toggle('preenchida', c.querySelector('.aval-valor').value.trim() !== '');
    });
    form.querySelectorAll('.aval-painel').forEach(atualizarProgresso);
    // ?criterio=ID na URL tem prioridade; senão, o último critério aberto
    var pedido = new URLSearchParams(location.search).get('criterio');
    var salvo = null;
    try { salvo = localStorage.getItem(chave); } catch (e) {}
    var inicial = (pedido && document.getElementById('painel-' + pedido)) ? pedido : salvo;
    if (inicial && document.getElementById('painel-' + inicial)) abrirCriterio(inicial, false);
})();
