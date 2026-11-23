// public/js/agenda.js
// Agenda interativa: visões Mês / Semana / Lista (e "mini" na página inicial), painel do dia,
// criar/editar em diálogo, concluir, excluir, .ics, filtros, atalhos de teclado e lembrete na véspera.
// Config vem no atributo data-config do elemento .ag (ver app/Views/agenda/_componente.php).
(function () {
    'use strict';

    var TIPOS = {
        prova: ['Prova', 'fa-pen-to-square'], entrega: ['Entrega', 'fa-file-arrow-up'], tarefa: ['Tarefa', 'fa-list-check'],
        estudo: ['Estudo', 'fa-book-open'], reuniao: ['Reunião', 'fa-people-group'], evento: ['Evento', 'fa-star'],
        feriado: ['Feriado', 'fa-umbrella-beach'], outro: ['Outro', 'fa-thumbtack']
    };
    var PRIO = { baixa: 'Baixa', media: 'Média', alta: 'Alta', urgente: 'Urgente' };
    var PESO = { baixa: 1, media: 2, alta: 3, urgente: 4 };
    var DIAS_C = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];
    var MESES = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    var DIAS_L = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado'];

    // ---------- Datas em texto (AAAA-MM-DD) para não sofrer com fuso ----------
    function pad(n) { return n < 10 ? '0' + n : '' + n; }
    function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parse(s) { var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
    function soma(d, n) { var x = new Date(d); x.setDate(x.getDate() + n); return x; }
    function inicioSemana(d) { var x = new Date(d); var dw = (x.getDay() + 6) % 7; x.setDate(x.getDate() - dw); return x; }
    function hojeStr() { return ymd(new Date()); }
    function diasAte(s) { return Math.round((parse(s) - parse(hojeStr())) / 86400000); }
    function relativo(s) {
        var n = diasAte(s);
        if (n === 0) return 'hoje'; if (n === 1) return 'amanhã'; if (n === -1) return 'ontem';
        return n > 0 ? 'em ' + n + ' dias' : 'há ' + (-n) + ' dias';
    }
    function longa(s) { var d = parse(s); return DIAS_L[d.getDay()] + ', ' + d.getDate() + ' de ' + MESES[d.getMonth()]; }
    function esc(t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function guardar(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
    function ler(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }

    function Agenda(raiz) {
        this.raiz = raiz;
        this.cfg = JSON.parse(raiz.dataset.config);
        this.mini = this.cfg.modo === 'mini';
        var url = new URLSearchParams(location.search);
        var dataUrl = url.get('data');
        this.cursor = dataUrl && /^\d{4}-\d{2}-\d{2}$/.test(dataUrl) ? parse(dataUrl) : new Date();
        this.selecionado = ymd(this.cursor);
        this.view = this.mini ? 'mes' : (ler('ag-view') || (window.innerWidth < 640 ? 'lista' : 'mes'));
        this.filtroTurma = this.cfg.soTurma ? String(this.cfg.soTurma) : '';
        this.itens = [];
        this.montar();
        this.carregar();
    }

    Agenda.prototype.montar = function () {
        var s = this, c = this.cfg;
        var turmasFiltro = Object.keys(c.turmasDia || {});
        this.raiz.innerHTML =
            '<div class="ag-barra">' +
              '<div class="ag-nav">' +
                '<button type="button" class="ag-btn ag-btn-icone" data-acao="ant" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>' +
                '<button type="button" class="ag-btn" data-acao="hoje">Hoje</button>' +
                '<button type="button" class="ag-btn ag-btn-icone" data-acao="prox" aria-label="Próximo"><i class="fas fa-chevron-right"></i></button>' +
              '</div>' +
              '<h2 class="ag-titulo" aria-live="polite"></h2>' +
              '<span class="ag-espaco"></span>' +
              (this.mini ? '' :
                '<div class="ag-seg" role="group" aria-label="Visão">' +
                  '<button type="button" data-view="mes">Mês</button><button type="button" data-view="semana">Semana</button><button type="button" data-view="lista">Lista</button>' +
                '</div>' +
                (turmasFiltro.length > 1 && !c.soTurma ? '<select class="ag-filtro" aria-label="Filtrar por turma"><option value="">Todas as turmas</option>' +
                  turmasFiltro.map(function (id) { return '<option value="' + id + '">' + esc(c.turmasDia[id].nome) + '</option>'; }).join('') + '</select>' : '')) +
              (c.podeCriar ? '<button type="button" class="ag-btn ag-btn-primario" data-acao="novo"><i class="fas fa-plus"></i> ' + (this.mini ? 'Adicionar' : 'Novo') + '</button>' : '') +
            '</div>' +
            (this.mini ? '' : '<div class="ag-legenda" aria-hidden="true">' +
              Object.keys(PRIO).map(function (k) { return '<span><i class="p p-' + k + '" style="background:var(--c)"></i>' + PRIO[k] + '</span>'; }).join('') +
              '<span><i class="pessoal"></i>Só para mim</span></div>') +
            '<div class="ag-area"></div>' +
            '<div class="ag-toast" role="status" aria-live="polite"></div>';
        this.area = this.raiz.querySelector('.ag-area');
        this.titulo = this.raiz.querySelector('.ag-titulo');
        this.toastEl = this.raiz.querySelector('.ag-toast');
        this.formDl = document.getElementById(this.raiz.id + '-form');
        this.detDl = document.getElementById(this.raiz.id + '-det');

        this.raiz.addEventListener('click', function (e) { s.clique(e); });
        var f = this.raiz.querySelector('.ag-filtro');
        if (f) f.addEventListener('change', function () { s.filtroTurma = f.value; s.render(); });
        this.ligarForm();
        if (!this.mini) document.addEventListener('keydown', function (e) { s.teclado(e); });
    };

    // ---------- Período a carregar conforme a visão ----------
    Agenda.prototype.periodo = function () {
        if (this.view === 'semana') { var i = inicioSemana(this.cursor); return [i, soma(i, 6)]; }
        if (this.view === 'lista') { var a = new Date(this.cursor.getFullYear(), this.cursor.getMonth(), 1); return [a, soma(a, 62)]; }
        var p = new Date(this.cursor.getFullYear(), this.cursor.getMonth(), 1);
        var ini = inicioSemana(p), fim = soma(ini, 41);
        if (this.mini) { var limite = soma(new Date(), 14); if (limite > fim) fim = limite; if (new Date() < ini) ini = new Date(); }
        return [ini, fim];
    };

    Agenda.prototype.carregar = function () {
        var s = this, p = this.periodo();
        this.area.classList.add('ag-carregando');
        var q = '?de=' + ymd(p[0]) + '&ate=' + ymd(p[1]) + (this.cfg.soTurma ? '&turma=' + this.cfg.soTurma : '');
        fetch(this.cfg.base + '/agenda/eventos' + q, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) { s.itens = j.itens || []; s.render(); })
            .catch(function () { s.toast('Não foi possível carregar a agenda.'); })
            .then(function () { s.area.classList.remove('ag-carregando'); });
    };

    Agenda.prototype.visiveis = function () {
        var f = this.filtroTurma;
        return this.itens.filter(function (i) { return !f || String(i.turma_id) === f; });
    };
    Agenda.prototype.doDia = function (d) {
        return this.visiveis().filter(function (i) { return i.data === d; })
            .sort(function (a, b) { return (a.inicio || '00:00').localeCompare(b.inicio || '00:00') || PESO[b.prioridade] - PESO[a.prioridade]; });
    };

    // ---------- Renderização ----------
    Agenda.prototype.render = function () {
        var s = this;
        this.raiz.querySelectorAll('[data-view]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.view === s.view ? 'true' : 'false'); });
        if (this.view === 'semana') {
            var i = inicioSemana(this.cursor), f = soma(i, 6);
            this.titulo.textContent = i.getDate() + (i.getMonth() !== f.getMonth() ? ' ' + MESES[i.getMonth()].slice(0, 3) : '') + ' – ' + f.getDate() + ' ' + MESES[f.getMonth()].slice(0, 3) + ' ' + f.getFullYear();
        } else {
            this.titulo.textContent = MESES[this.cursor.getMonth()] + ' ' + this.cursor.getFullYear();
        }
        if (this.mini) return this.renderMini();
        if (this.view === 'semana') return this.renderSemana();
        if (this.view === 'lista') return this.renderLista();
        this.renderMes();
    };

    Agenda.prototype.pill = function (i, comHora) {
        var t = TIPOS[i.tipo] || TIPOS.outro;
        return '<button type="button" class="ag-pill p-' + i.prioridade + (i.visibilidade === 'pessoal' ? ' pessoal' : '') + (i.concluido ? ' feito' : '') + '" data-item="' + i.origem + ':' + i.id + '" title="' + esc(t[0] + ': ' + i.titulo) + '">' +
            '<i class="fas ' + t[1] + '"></i>' + (comHora && i.inicio ? '<b>' + i.inicio + '</b>' : '') + '<span>' + esc(i.titulo) + '</span></button>';
    };
    Agenda.prototype.pontos = function (lista) {
        return '<span class="ag-pontos" aria-hidden="true">' + lista.slice(0, 4).map(function (i) { return '<i class="p-' + i.prioridade + '"></i>'; }).join('') + '</span>';
    };

    Agenda.prototype.gradeMes = function (compacta) {
        var s = this, hoje = hojeStr(), mesAtual = this.cursor.getMonth();
        var ini = inicioSemana(new Date(this.cursor.getFullYear(), mesAtual, 1));
        var html = '<div class="ag-semana-cab">' + DIAS_C.map(function (d) { return '<span>' + (compacta ? d[0] : d) + '</span>'; }).join('') + '</div><div class="ag-mes" role="grid">';
        for (var k = 0; k < 42; k++) {
            var d = soma(ini, k), ds = ymd(d), lista = this.doDia(ds);
            var cls = 'ag-cel' + (d.getMonth() !== mesAtual ? ' fora' : '') + (ds === hoje ? ' hoje' : '') + (ds === this.selecionado ? ' selecionado' : '') + (k % 7 >= 5 ? ' fim-semana' : '');
            var rot = longa(ds) + (lista.length ? ', ' + lista.length + ' evento(s)' : '');
            html += '<div class="' + cls + '" role="gridcell" tabindex="0" data-dia="' + ds + '" aria-label="' + esc(rot) + '">' +
                '<span class="ag-num">' + d.getDate() + '</span>';
            if (compacta) html += this.pontos(lista);
            else {
                html += lista.slice(0, 3).map(function (i) { return s.pill(i, true); }).join('');
                if (lista.length > 3) html += '<span class="ag-mais">+' + (lista.length - 3) + ' mais</span>';
                html += this.pontos(lista);
                if (this.cfg.podeCriar) html += '<button type="button" class="ag-mais-btn" data-novo="' + ds + '" aria-label="Adicionar em ' + esc(longa(ds)) + '"><i class="fas fa-plus"></i></button>';
            }
            html += '</div>';
        }
        return html + '</div>';
    };

    Agenda.prototype.renderMes = function () {
        this.area.innerHTML = '<div class="ag-corpo"><div class="ag-cartao">' + this.gradeMes(false) + '</div>' +
            '<aside class="ag-cartao ag-painel" aria-live="polite">' + this.painelDia(this.selecionado) + '</aside></div>';
    };

    Agenda.prototype.painelDia = function (d) {
        var lista = this.doDia(d), s = this;
        return '<div><h3>' + esc(longa(d)) + '</h3><p class="ag-sub">' + relativo(d) + ' · ' + (lista.length ? lista.length + ' evento(s)' : 'nada marcado') + '</p></div>' +
            (lista.length ? lista.map(function (i) { return s.itemLista(i); }).join('') : '<div class="ag-vazio"><i class="fas fa-mug-hot"></i>Dia livre.</div>') +
            (this.cfg.podeCriar ? '<button type="button" class="ag-btn" data-novo="' + d + '"><i class="fas fa-plus"></i> Adicionar neste dia</button>' : '');
    };

    Agenda.prototype.itemLista = function (i) {
        var t = TIPOS[i.tipo] || TIPOS.outro;
        var podeConcluir = i.origem === 'evento';
        var hora = i.inicio ? i.inicio + (i.fim ? '–' + i.fim : '') : 'Dia todo';
        var sub = [t[0], i.visibilidade === 'pessoal' ? 'só você' : (i.turma_nome || ''), i.local || ''].filter(Boolean).join(' · ');
        return '<div class="ag-item p-' + i.prioridade + (i.visibilidade === 'pessoal' ? ' pessoal' : '') + (i.concluido ? ' feito' : '') + '" data-item="' + i.origem + ':' + i.id + '" role="button" tabindex="0">' +
            '<button type="button" class="ag-check" data-concluir="' + i.id + '" aria-pressed="' + (i.concluido ? 'true' : 'false') + '" aria-label="Marcar como feito"' + (podeConcluir ? '' : ' disabled') + '><i class="fas fa-check"></i></button>' +
            '<span class="ag-item-ico"><i class="fas ' + t[1] + '"></i></span>' +
            '<span style="min-width:0"><span class="ag-item-tit">' + esc(i.titulo) + '</span><small>' + esc(sub) + '</small></span>' +
            '<span class="ag-item-hora">' + hora + (i.prioridade === 'urgente' && !i.concluido ? '<br><span class="ag-chip urg">urgente</span>' : '') + '</span></div>';
    };

    Agenda.prototype.renderLista = function () {
        var s = this, porDia = {}, hoje = hojeStr();
        this.visiveis().forEach(function (i) { (porDia[i.data] = porDia[i.data] || []).push(i); });
        var dias = Object.keys(porDia).sort();
        var html = '<div class="ag-cartao ag-lista">';
        if (!dias.length) html += '<div class="ag-vazio"><i class="fas fa-calendar-check"></i>Nada marcado neste período.' + (this.cfg.podeCriar ? '<button type="button" class="ag-btn ag-btn-primario" data-acao="novo"><i class="fas fa-plus"></i> Adicionar</button>' : '') + '</div>';
        dias.forEach(function (d) {
            html += '<section class="ag-dia-grupo"><h3 class="ag-dia-rot' + (d === hoje ? ' hoje' : '') + '">' + esc(longa(d)) + ' <span class="ag-chip">' + relativo(d) + '</span></h3>' +
                s.doDia(d).map(function (i) { return s.itemLista(i); }).join('') + '</section>';
        });
        this.area.innerHTML = html + '</div>';
        var alvo = this.area.querySelector('.ag-dia-rot.hoje');
        if (alvo && !this._rolou) { this._rolou = true; alvo.scrollIntoView({ block: 'nearest' }); }
    };

    Agenda.prototype.renderSemana = function () {
        var s = this, ini = inicioSemana(this.cursor), hoje = hojeStr(), H0 = 6, PX = 48;
        var cab = '<div class="ag-sem-cab"><div></div>', todo = '<div class="ag-sem-diatodo"><div>dia todo</div>', cols = '';
        for (var k = 0; k < 7; k++) {
            var d = soma(ini, k), ds = ymd(d), lista = this.doDia(ds);
            cab += '<div class="' + (ds === hoje ? 'hoje' : '') + '">' + DIAS_C[k] + '<b>' + d.getDate() + '</b></div>';
            todo += '<div>' + lista.filter(function (i) { return !i.inicio; }).map(function (i) { return s.pill(i, false); }).join('') + '</div>';
            var evs = lista.filter(function (i) { return i.inicio; }).map(function (i) {
                var h = i.inicio.split(':'), top = ((+h[0] - H0) * 60 + +h[1]) / 60 * PX;
                var f = (i.fim || pad(Math.min(23, +h[0] + 1)) + ':' + h[1]).split(':');
                var alt = Math.max(24, (((+f[0] - H0) * 60 + +f[1]) / 60 * PX) - top);
                return s.pill(i, true).replace('class="ag-pill', 'style="top:' + Math.max(0, top) + 'px;height:' + alt + 'px" class="ag-sem-ev ag-pill');
            }).join('');
            var agora = '';
            if (ds === hoje) { var n = new Date(), m = (n.getHours() - H0) * 60 + n.getMinutes(); if (m >= 0) agora = '<div class="ag-agora" style="top:' + (m / 60 * PX) + 'px"></div>'; }
            cols += '<div class="ag-sem-col' + (ds === hoje ? ' hoje' : '') + '" data-dia="' + ds + '">' + agora + evs + '</div>';
        }
        var horas = '<div class="ag-horas">';
        for (var h = H0; h < H0 + 18; h++) horas += '<span>' + pad(h) + ':00</span>';
        this.area.innerHTML = '<div class="ag-cartao">' + cab + '</div>' + todo + '</div><div class="ag-sem-rolagem"><div class="ag-sem">' + horas + '</div>' + cols + '</div></div></div>';
        var rol = this.area.querySelector('.ag-sem-rolagem');
        if (rol) rol.scrollTop = Math.max(0, (Math.max(new Date().getHours(), 7) - H0 - 1) * PX);
    };

    Agenda.prototype.renderMini = function () {
        var s = this, hoje = hojeStr(), ate = ymd(soma(new Date(), 14));
        var prox = this.visiveis().filter(function (i) { return i.data >= hoje && i.data <= ate && !i.concluido; }).slice(0, 6);
        var sel = this.selecionado !== hoje ? this.doDia(this.selecionado) : null;
        var lista = sel ? sel : prox;
        this.area.innerHTML = '<div class="ag-mini"><div class="ag-cartao">' + this.gradeMes(true) + '</div>' +
            '<div class="ag-cartao ag-lista"><section class="ag-dia-grupo"><h3 class="ag-dia-rot">' + (sel ? esc(longa(this.selecionado)) : 'Próximos 14 dias') + '</h3>' +
            (lista.length ? lista.map(function (i) { return s.itemLista(i).replace('<span class="ag-item-hora">', '<span class="ag-item-hora">' + (sel ? '' : '<span class="ag-chip">' + relativo(i.data) + '</span><br>')); }).join('')
                : '<div class="ag-vazio"><i class="fas fa-calendar-check"></i>' + (sel ? 'Dia livre.' : 'Nada nos próximos dias.') + '</div>') +
            '</section></div></div>';
    };

    // ---------- Interações ----------
    Agenda.prototype.clique = function (e) {
        var s = this, b;
        if ((b = e.target.closest('[data-acao]'))) {
            var a = b.dataset.acao;
            if (a === 'novo') return this.abrirForm(null, this.selecionado);
            if (a === 'hoje') { this.cursor = new Date(); this.selecionado = hojeStr(); }
            if (a === 'ant' || a === 'prox') {
                var dir = a === 'ant' ? -1 : 1;
                if (this.view === 'semana') this.cursor = soma(this.cursor, 7 * dir);
                else this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + dir, 1);
            }
            return this.carregar();
        }
        if ((b = e.target.closest('[data-view]'))) { this.view = b.dataset.view; guardar('ag-view', this.view); this._rolou = false; return this.carregar(); }
        if ((b = e.target.closest('[data-concluir]'))) { e.stopPropagation(); return this.concluir(+b.dataset.concluir, b); }
        if ((b = e.target.closest('[data-novo]'))) { e.stopPropagation(); return this.abrirForm(null, b.dataset.novo); }
        if ((b = e.target.closest('[data-item]'))) { e.stopPropagation(); return this.abrirDetalhe(b.dataset.item); }
        if ((b = e.target.closest('.ag-sem-col'))) {
            if (!this.cfg.podeCriar) return;
            var r = b.getBoundingClientRect(), h = Math.floor((e.clientY - r.top) / 48) + 6;
            return this.abrirForm(null, b.dataset.dia, pad(Math.min(22, h)) + ':00');
        }
        if ((b = e.target.closest('.ag-cel'))) {
            var dia = b.dataset.dia;
            if (this.selecionado === dia && !this.mini && this.cfg.podeCriar && window.innerWidth >= 900) return this.abrirForm(null, dia); // 2º clique no mesmo dia: criar
            this.selecionado = dia;
            if (parse(dia).getMonth() !== this.cursor.getMonth()) { this.cursor = parse(dia); return this.carregar(); }
            return this.render();
        }
    };

    Agenda.prototype.teclado = function (e) {
        if (e.target.closest('input, textarea, select, dialog') || e.ctrlKey || e.metaKey || e.altKey) return;
        var k = e.key.toLowerCase();
        if (k === 'arrowleft' || k === 'arrowright') { e.preventDefault(); this.raiz.querySelector('[data-acao="' + (k === 'arrowleft' ? 'ant' : 'prox') + '"]').click(); }
        else if (k === 't') this.raiz.querySelector('[data-acao="hoje"]').click();
        else if (k === 'n' && this.cfg.podeCriar) { e.preventDefault(); this.abrirForm(null, this.selecionado); }
        else if (k === 'm' || k === 's' || k === 'l') { var v = { m: 'mes', s: 'semana', l: 'lista' }[k]; var b = this.raiz.querySelector('[data-view="' + v + '"]'); if (b) b.click(); }
        else if (k === 'enter' && e.target.classList.contains('ag-cel')) e.target.click();
    };

    Agenda.prototype.achar = function (chave) {
        var p = chave.split(':');
        return this.itens.filter(function (i) { return i.origem === p[0] && String(i.id) === p[1]; })[0];
    };

    // ---------- Detalhe ----------
    Agenda.prototype.abrirDetalhe = function (chave) {
        var i = this.achar(chave); if (!i) return;
        var t = TIPOS[i.tipo] || TIPOS.outro, c = this.cfg, dl = this.detDl;
        var hora = i.inicio ? i.inicio + (i.fim ? ' – ' + i.fim : '') : 'Dia todo';
        dl.className = 'ag-dialogo ag-det p-' + i.prioridade;
        dl.innerHTML = '<div class="ag-det-faixa"></div><div class="ag-dl-corpo">' +
            '<div class="ag-dl-cab" style="padding:0"><span class="ag-det-tipo"><i class="fas ' + t[1] + '"></i> ' + t[0] + ' · prioridade ' + PRIO[i.prioridade].toLowerCase() + '</span>' +
            '<button type="button" class="ag-btn ag-btn-icone" data-fechar aria-label="Fechar"><i class="fas fa-xmark"></i></button></div>' +
            '<h2>' + esc(i.titulo) + '</h2>' +
            '<dl><dt><i class="fas fa-calendar"></i></dt><dd>' + esc(longa(i.data)) + ' · ' + hora + ' <span class="ag-chip">' + relativo(i.data) + '</span></dd>' +
            '<dt><i class="fas ' + (i.visibilidade === 'pessoal' ? 'fa-lock' : 'fa-users') + '"></i></dt><dd>' + (i.visibilidade === 'pessoal' ? 'Só você vê' + (i.turma_nome ? ' · ' + esc(i.turma_nome) : '') : 'Para a turma ' + esc(i.turma_nome || '')) + '</dd>' +
            (i.local ? '<dt><i class="fas fa-location-dot"></i></dt><dd>' + esc(i.local) + '</dd>' : '') +
            (i.criador ? '<dt><i class="fas fa-user-pen"></i></dt><dd>Criado por ' + esc(i.criador) + '</dd>' : '') +
            (i.lembrete && i.origem === 'evento' ? '<dt><i class="fas fa-bell"></i></dt><dd>Lembrete na véspera</dd>' : '') +
            '</dl>' + (i.descricao ? '<div class="ag-desc">' + esc(i.descricao) + '</div>' : '') +
            '<div class="ag-dl-acoes">' +
              (i.origem === 'reuniao' ? '<a class="ag-btn ag-btn-primario" href="' + c.base + '/reunioes/' + i.id + '"><i class="fas fa-arrow-right"></i> Abrir reunião</a>' :
                (i.visibilidade === 'turma' && i.turma_id && /^(prova|entrega|tarefa)$/.test(i.tipo) ? '<a class="ag-btn" href="' + c.base + '/turmas/' + i.turma_id + '/estudos?evento=' + i.id + '"><i class="fas fa-book-open"></i> Materiais de estudo' + (i.materiais ? ' (' + i.materiais + ')' : '') + '</a>' : '') +
                '<a class="ag-btn" href="' + c.base + '/agenda/eventos/' + i.id + '/ics"><i class="fas fa-calendar-plus"></i> Adicionar ao celular</a>' +
                (i.pode_editar ? '<button type="button" class="ag-btn" data-excluir="' + i.id + '"><i class="fas fa-trash"></i> Excluir</button><button type="button" class="ag-btn" data-editar="' + chave + '"><i class="fas fa-pen"></i> Editar</button>' : '') +
                '<button type="button" class="ag-btn ag-btn-primario" data-concluir-det="' + i.id + '"><i class="fas fa-check"></i> ' + (i.concluido ? 'Desmarcar feito' : 'Marcar como feito') + '</button>') +
            '</div></div>';
        var s = this;
        dl.onclick = function (e) {
            if (e.target === dl || e.target.closest('[data-fechar]')) return dl.close();
            var b;
            if ((b = e.target.closest('[data-editar]'))) { dl.close(); return s.abrirForm(s.achar(b.dataset.editar)); }
            if ((b = e.target.closest('[data-excluir]'))) return s.excluir(+b.dataset.excluir);
            if ((b = e.target.closest('[data-concluir-det]'))) { dl.close(); return s.concluir(+b.dataset.concluirDet); }
        };
        dl.showModal();
    };

    // ---------- Formulário ----------
    Agenda.prototype.ligarForm = function () {
        var s = this, f = this.formDl && this.formDl.querySelector('form');
        if (!f) return;
        this.form = f;
        f.addEventListener('change', function (e) {
            if (e.target.name === 'dia_inteiro') s.atualizarHoras();
            if (e.target.name === 'visibilidade') s.atualizarPara();
        });
        f.addEventListener('submit', function (e) { e.preventDefault(); s.enviar(); });
        this.formDl.addEventListener('click', function (e) { if (e.target === s.formDl || e.target.closest('[data-fechar]')) s.formDl.close(); });
    };
    Agenda.prototype.atualizarHoras = function () {
        var todo = this.form.dia_inteiro.checked;
        this.form.querySelectorAll('[data-hora]').forEach(function (el) { el.hidden = todo; });
    };
    Agenda.prototype.atualizarPara = function () {
        var v = this.form.querySelector('[name=visibilidade]:checked');
        var turma = v && v.value === 'turma';
        var sel = this.form.turma_id, c = this.cfg, atual = sel.value;
        var lista = turma ? c.turmasEditaveis : Object.keys(c.turmasDia || {}).map(function (id) { return c.turmasDia[id]; });
        sel.innerHTML = (turma ? '' : '<option value="">Nenhuma (pessoal)</option>') + lista.map(function (t) { return '<option value="' + t.id + '">' + esc(t.nome) + '</option>'; }).join('');
        if ([].some.call(sel.options, function (o) { return o.value === atual; })) sel.value = atual;
        else if (this.cfg.soTurma && [].some.call(sel.options, function (o) { return o.value === String(c.soTurma); })) sel.value = String(c.soTurma);
        this.form.querySelector('[data-turma-rot]').textContent = turma ? 'Turma' : 'Turma relacionada (opcional)';
        this.form.querySelector('[data-aviso-turma]').hidden = !turma;
    };
    Agenda.prototype.abrirForm = function (item, dia, hora) {
        if (!this.form) return;
        var f = this.form, ehNovo = !item;
        f.reset();
        f.id.value = item ? item.id : '';
        this.formDl.querySelector('h2').textContent = ehNovo ? 'Novo evento' : 'Editar evento';
        f.querySelector('.ag-erro').hidden = true;
        var tipo = item ? item.tipo : (this.cfg.turmasEditaveis.length ? 'prova' : 'tarefa');
        var rTipo = f.querySelector('[name=tipo][value="' + tipo + '"]'); if (rTipo) rTipo.checked = true;
        f.querySelector('[name=prioridade][value="' + (item ? item.prioridade : 'media') + '"]').checked = true;
        f.titulo.value = item ? item.titulo : '';
        f.data.value = item ? item.data : (dia || hojeStr());
        f.dia_inteiro.checked = item ? !item.inicio : !hora;
        f.inicio.value = item && item.inicio ? item.inicio : (hora || '');
        f.fim.value = item && item.fim ? item.fim : '';
        f.local.value = item ? (item.local || '') : '';
        f.descricao.value = item ? (item.descricao || '') : '';
        f.lembrete.checked = item ? item.lembrete : true;
        var vis = item ? item.visibilidade : (this.cfg.soTurma && this.cfg.turmasEditaveis.some(function (t) { return t.id === +this.cfg.soTurma; }, this) ? 'turma' : 'pessoal');
        var rVis = f.querySelector('[name=visibilidade][value="' + vis + '"]');
        if (rVis) rVis.checked = true; else { var p = f.querySelector('[name=visibilidade][value=pessoal]'); if (p) p.checked = true; }
        this.atualizarPara();
        if (item && item.turma_id) f.turma_id.value = String(item.turma_id);
        this.atualizarHoras();
        this.formDl.showModal();
        setTimeout(function () { f.titulo.focus(); }, 30);
    };
    Agenda.prototype.enviar = function () {
        var s = this, f = this.form, btn = f.querySelector('[type=submit]'), erro = f.querySelector('.ag-erro');
        var dados = new FormData(f);
        if (!f.lembrete.checked) dados.delete('lembrete');
        dados.append('_csrf_token', this.cfg.csrf);
        btn.disabled = true; btn.classList.add('carregando');
        fetch(this.cfg.base + '/agenda/eventos/salvar', { method: 'POST', body: dados, credentials: 'same-origin', headers: { 'X-CSRF-Token': this.cfg.csrf, Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                if (!j.ok) { erro.textContent = j.erro || 'Não foi possível salvar.'; erro.hidden = false; return; }
                s.formDl.close();
                s.toast(f.id.value ? 'Evento atualizado' : (dados.get('visibilidade') === 'turma' ? 'Evento criado e a turma foi avisada' : 'Evento salvo na sua agenda'));
                s.selecionado = dados.get('data'); s.cursor = parse(dados.get('data'));
                s.carregar();
            })
            .catch(function () { erro.textContent = 'Sem conexão. Tente de novo.'; erro.hidden = false; })
            .then(function () { btn.disabled = false; btn.classList.remove('carregando'); });
    };

    Agenda.prototype.post = function (url) {
        var d = new FormData(); d.append('_csrf_token', this.cfg.csrf);
        return fetch(this.cfg.base + url, { method: 'POST', body: d, credentials: 'same-origin', headers: { 'X-CSRF-Token': this.cfg.csrf, Accept: 'application/json' } }).then(function (r) { return r.json(); });
    };
    Agenda.prototype.concluir = function (id, botao) {
        var s = this, item = this.itens.filter(function (i) { return i.origem === 'evento' && i.id === id; })[0];
        if (item) { item.concluido = !item.concluido; this.render(); } // otimista
        this.post('/agenda/eventos/' + id + '/concluir').then(function (j) {
            if (!j.ok) throw 0;
            if (item) { item.concluido = j.concluido; s.render(); }
            s.toast(j.concluido ? 'Feito! ✓' : 'Marcado como pendente');
        }).catch(function () { if (item) { item.concluido = !item.concluido; s.render(); } s.toast('Não foi possível atualizar.'); });
    };
    Agenda.prototype.excluir = function (id) {
        var s = this;
        if (!confirm('Excluir este evento?')) return;
        this.post('/agenda/eventos/' + id + '/excluir').then(function (j) {
            if (!j.ok) return s.toast(j.erro || 'Não foi possível excluir.');
            s.detDl.close(); s.toast('Evento excluído'); s.carregar();
        });
    };
    Agenda.prototype.toast = function (msg) {
        var t = this.toastEl; t.textContent = msg; t.classList.add('visivel');
        clearTimeout(this._toast); this._toast = setTimeout(function () { t.classList.remove('visivel'); }, 2600);
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.ag[data-config]').forEach(function (el) { el.agenda = new Agenda(el); });
    });
})();
