// public/js/tour.js
// Tutorial guiado nas telas de verdade. Recebe window.PRUMO_TOUR = {base, tours, vistos, token} (layouts/footer.php).
//  - 1ª visita a uma página com tour: card grande no meio da tela sugerindo o tour (Fazer / Agora não / Não sugerir mais).
//  - Tour: tela escurecida, "holofote" no elemento do passo e card ao lado (no celular, embaixo). Setas, Esc, Voltar/Próximo.
//  - ?tour=id na URL começa o tour direto (botão "Rodar tutorial" da página /tutorial).
(function () {
  'use strict';
  var C = window.PRUMO_TOUR; if (!C || !C.tours) return;
  var caminho = location.pathname.replace(new RegExp('^' + C.base.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')), '') || '/';
  var params = new URLSearchParams(location.search);

  function achar() {
    for (var id in C.tours) { try { if (new RegExp(C.tours[id].rota).test(caminho)) return id; } catch (_) {} }
    return null;
  }
  function marcar(tour, status) {
    if (C.vistos.indexOf(tour) < 0) C.vistos.push(tour);
    fetch(C.base + '/tutorial/visto', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': C.token }, body: JSON.stringify({ tour: tour, status: status }) }).catch(function () {});
  }
  function visivel(el) { if (!el) return false; var r = el.getBoundingClientRect(); var s = getComputedStyle(el); return r.width > 4 && r.height > 4 && s.visibility !== 'hidden' && s.display !== 'none'; }
  function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

  // ---------- motor do tour
  function rodar(id) {
    var t = C.tours[id]; if (!t) return;
    var passos = t.passos.filter(function (p) { return !p.alvo || visivel(document.querySelector(p.alvo)); });
    if (!passos.length) return;
    var i = 0, raiz = document.createElement('div');
    raiz.className = 'tour-raiz'; raiz.setAttribute('role', 'dialog'); raiz.setAttribute('aria-modal', 'true');
    raiz.innerHTML = '<div class="tour-escuro"></div><div class="tour-foco"></div><div class="tour-card" tabindex="-1"><div class="tour-topo"><span class="tour-ico"><i class="fas ' + esc(t.icone || 'fa-route') + '"></i></span><small class="tour-nome">' + esc(t.titulo) + '</small><button type="button" class="tour-x" aria-label="Fechar tour">&times;</button></div>'
      + '<h3 class="tour-titulo"></h3><p class="tour-texto"></p><div class="tour-pe"><span class="tour-pontos"></span><div class="tour-botoes"><button type="button" class="tour-voltar">Voltar</button><button type="button" class="tour-prox">Próximo</button></div></div></div>';
    document.body.appendChild(raiz); document.body.classList.add('tour-aberto');
    var foco = raiz.querySelector('.tour-foco'), card = raiz.querySelector('.tour-card'), escuro = raiz.querySelector('.tour-escuro');
    function furo(x, y, w, h) {
      var x2 = x + w, y2 = y + h;
      escuro.style.clipPath = w > 0 ? 'polygon(evenodd, 0 0, 100% 0, 100% 100%, 0 100%, 0 0, ' + x + 'px ' + y + 'px, ' + x2 + 'px ' + y + 'px, ' + x2 + 'px ' + y2 + 'px, ' + x + 'px ' + y2 + 'px, ' + x + 'px ' + y + 'px)' : 'none';
    }

    function posicionar() {
      var p = passos[i], el = p.alvo ? document.querySelector(p.alvo) : null, m = 10, vw = innerWidth, vh = innerHeight;
      card.classList.remove('baixo', 'centro');
      if (!el) { foco.style.cssText = 'display:none'; furo(0, 0, 0, 0); card.classList.add('centro'); card.style.cssText = ''; return; }
      var r = el.getBoundingClientRect();
      foco.style.cssText = 'top:' + (r.top - m) + 'px;left:' + (r.left - m) + 'px;width:' + (r.width + 2 * m) + 'px;height:' + (r.height + 2 * m) + 'px';
      furo(r.left - m, r.top - m, r.width + 2 * m, r.height + 2 * m);
      if (vw < 640) { card.classList.add('baixo'); card.style.cssText = ''; return; }
      var cw = Math.min(360, vw - 32), ch = card.offsetHeight || 220, top, left;
      if (r.right + m + 16 + cw < vw) { left = r.right + m + 16; top = r.top; }
      else if (r.left - m - 16 - cw > 0) { left = r.left - m - 16 - cw; top = r.top; }
      else { left = Math.min(Math.max(16, r.left), vw - cw - 16); top = r.bottom + m + 16 + ch < vh ? r.bottom + m + 16 : Math.max(16, r.top - m - 16 - ch); }
      top = Math.min(Math.max(16, top), vh - ch - 16);
      card.style.cssText = 'top:' + top + 'px;left:' + left + 'px;width:' + cw + 'px';
    }
    function mostrar() {
      var p = passos[i], el = p.alvo ? document.querySelector(p.alvo) : null;
      raiz.querySelector('.tour-titulo').textContent = p.titulo;
      raiz.querySelector('.tour-texto').textContent = p.texto;
      raiz.querySelector('.tour-pontos').innerHTML = passos.map(function (_, k) { return '<i class="' + (k === i ? 'on' : '') + '"></i>'; }).join('') + '<small>' + (i + 1) + ' de ' + passos.length + '</small>';
      raiz.querySelector('.tour-voltar').hidden = i === 0;
      raiz.querySelector('.tour-prox').textContent = i === passos.length - 1 ? 'Concluir' : 'Próximo';
      card.classList.remove('entra'); void card.offsetWidth; card.classList.add('entra');
      if (el) { el.scrollIntoView({ block: 'center', behavior: 'smooth' }); setTimeout(posicionar, 380); }
      posicionar(); card.focus({ preventScroll: true });
    }
    function fechar(concluiu) {
      removeEventListener('resize', posicionar); removeEventListener('scroll', posicionar, true); document.removeEventListener('keydown', teclas);
      raiz.classList.add('sai'); setTimeout(function () { raiz.remove(); document.body.classList.remove('tour-aberto'); }, 250);
      marcar(id, concluiu ? 'feito' : 'dispensado');
      if (params.has('tour')) history.replaceState(null, '', location.pathname);
    }
    function ir(d) { if (i + d >= passos.length) return fechar(true); if (i + d < 0) return; i += d; mostrar(); }
    function teclas(e) { if (e.key === 'Escape') fechar(false); else if (e.key === 'ArrowRight') ir(1); else if (e.key === 'ArrowLeft') ir(-1); }
    raiz.querySelector('.tour-prox').addEventListener('click', function () { ir(1); });
    raiz.querySelector('.tour-voltar').addEventListener('click', function () { ir(-1); });
    raiz.querySelector('.tour-x').addEventListener('click', function () { fechar(false); });
    foco.addEventListener('click', function () { ir(1); });
    escuro.addEventListener('click', function (e) { e.stopPropagation(); });
    addEventListener('resize', posicionar); addEventListener('scroll', posicionar, true); document.addEventListener('keydown', teclas);
    mostrar();
  }
  window.PrumoTour = { rodar: rodar };

  // ---------- card grande da 1ª visita
  function sugerir(id) {
    var t = C.tours[id], fundo = document.createElement('div');
    fundo.className = 'tour-sugestao'; fundo.setAttribute('role', 'dialog'); fundo.setAttribute('aria-modal', 'true'); fundo.setAttribute('aria-labelledby', 'tour-sug-t');
    fundo.innerHTML = '<div class="tour-sug-card"><div class="tour-sug-arte"><i class="fas ' + esc(t.icone || 'fa-route') + '"></i><span></span><span></span><span></span></div>'
      + '<p class="tour-sug-selo">Primeira vez aqui</p><h2 id="tour-sug-t">' + esc(t.titulo) + '</h2><p class="tour-sug-desc">' + esc(t.descricao) + '</p>'
      + '<p class="tour-sug-conv">Quer um tour rápido? Leva menos de 1 minuto e mostra onde fica cada coisa nesta tela.</p>'
      + '<div class="tour-sug-acoes"><button type="button" class="tour-sug-sim"><i class="fas fa-play"></i> Fazer o tour</button><button type="button" class="tour-sug-nao">Agora não</button></div>'
      + '<label class="tour-sug-nunca"><input type="checkbox"> Não sugerir tutoriais (dá para ver todos em Tutorial, no menu)</label></div>';
    document.body.appendChild(fundo); document.body.classList.add('tour-aberto');
    var sim = fundo.querySelector('.tour-sug-sim'); setTimeout(function () { sim.focus(); }, 50);
    function fechar() { fundo.classList.add('sai'); setTimeout(function () { fundo.remove(); document.body.classList.remove('tour-aberto'); }, 250); }
    sim.addEventListener('click', function () { fechar(); setTimeout(function () { rodar(id); }, 260); });
    fundo.querySelector('.tour-sug-nao').addEventListener('click', function () {
      marcar(fundo.querySelector('.tour-sug-nunca input').checked ? '*' : id, 'dispensado'); fechar();
    });
    fundo.addEventListener('keydown', function (e) { if (e.key === 'Escape') { marcar(id, 'dispensado'); fechar(); } });
  }

  function iniciar() {
    var pedido = params.get('tour');
    if (pedido && C.tours[pedido]) { setTimeout(function () { rodar(pedido); }, 500); return; }
    var id = achar();
    if (!id || C.vistos.indexOf(id) >= 0 || C.vistos.indexOf('*') >= 0 || document.querySelector('dialog[open]')) return;
    setTimeout(function () { if (!document.body.classList.contains('tour-aberto')) sugerir(id); }, 900);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
