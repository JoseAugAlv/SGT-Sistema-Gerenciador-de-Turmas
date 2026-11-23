// public/js/parcial.js
// Atualizações sem recarregar a página, sem mudar o servidor:
//   <form data-parcial="#area1,#area2"> — envia em segundo plano (mesmo POST de sempre), segue o redirecionamento,
//      e troca só o conteúdo dessas áreas pelo da página de resposta, mais a mensagem de sucesso/erro.
//      Se a resposta for outra página (outro endereço), navega para ela. Qualquer falha: envia do jeito normal.
//   <div id="x" data-atualizar="20"> — a cada 20 s busca a própria página e atualiza só essa área, se mudou.
//      Pausa com a aba escondida, com o foco ou digitação dentro dela, ou com um diálogo aberto.
// Troca o conteúdo (innerHTML) e mantém o elemento: eventos ligados nele continuam funcionando.
// Depois de cada troca dispara "parcial:atualizado" (detail = elemento) e "parcial:feito" no documento.
(function () {
  'use strict';
  if (!window.fetch || !window.DOMParser) return;

  var sujas = new WeakSet();   // áreas em que a pessoa está digitando (a atualização automática espera)
  function parse(html) { return new DOMParser().parseFromString(html, 'text/html'); }
  function mesmoEndereco(url) {
    try { var u = new URL(url, location.href); return u.pathname === location.pathname && u.search === location.search; } catch (_) { return false; }
  }
  function trocar(doc, seletores) {
    seletores.forEach(function (sel) {
      sel = sel.trim(); if (!sel) return;
      var atual = document.querySelector(sel), novo = doc.querySelector(sel);
      if (!atual) return;
      atual.innerHTML = novo ? novo.innerHTML : '';
      sujas.delete(atual);
      if (novo) { Array.prototype.forEach.call(novo.attributes, function (a) { if (a.name !== 'id' && a.name.indexOf('data-') !== 0) atual.setAttribute(a.name, a.value); }); }
      atual.classList.remove('parcial-novo'); void atual.offsetWidth; atual.classList.add('parcial-novo');
      document.dispatchEvent(new CustomEvent('parcial:atualizado', { detail: atual }));
    });
  }
  function mensagem(doc) {
    var nova = doc.querySelector('[data-flash]'), velha = document.querySelector('[data-flash]');
    if (velha) velha.remove();
    if (!nova) return;
    var lugar = document.querySelector('.page-wrap') || document.querySelector('main') || document.body;
    lugar.insertBefore(document.importNode(nova, true), lugar.firstChild);
    var f = lugar.firstChild;
    if (f.classList && f.classList.contains('flash-sucesso')) setTimeout(function () { f.classList.add('saindo'); setTimeout(function () { f.remove(); }, 300); }, 5000);
  }
  function soltarBotao(form) {
    form.dataset.enviando = '0';
    form.querySelectorAll('.carregando').forEach(function (b) {
      b.classList.remove('carregando'); b.disabled = false; b.removeAttribute('aria-busy'); b.style.minWidth = '';
      if (b.dataset.htmlOriginal) b.innerHTML = b.dataset.htmlOriginal;
    });
  }

  // ---------- formulários
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-parcial') || e.defaultPrevented) return;   // clique duplo barrado pelo carregando.js
    if (form.dataset.parcialNativo === '1') return;            // queda para o envio normal
    var alvos = form.getAttribute('data-parcial').split(',');
    var dados;
    try { dados = e.submitter ? new FormData(form, e.submitter) : new FormData(form); } catch (_) { dados = new FormData(form); }
    e.preventDefault();
    var metodo = (form.getAttribute('method') || 'get').toUpperCase(), url = form.action;
    var opc = { method: metodo, credentials: 'same-origin', headers: { 'X-Parcial': '1' }, redirect: 'follow' };
    if (metodo === 'GET') url += (url.indexOf('?') < 0 ? '?' : '&') + new URLSearchParams(dados).toString(); else opc.body = dados;
    fetch(url, opc).then(function (r) {
      if (!r.ok) throw new Error('http ' + r.status);
      if (!mesmoEndereco(r.url)) { location.href = r.url; return null; }   // a ação levou para outra página
      return r.text();
    }).then(function (html) {
      if (html === null) return;
      var doc = parse(html);
      trocar(doc, alvos); mensagem(doc); soltarBotao(form);
      if (form.hasAttribute('data-parcial-limpar')) { form.reset(); form.dispatchEvent(new Event('change', { bubbles: true })); }
      document.dispatchEvent(new CustomEvent('parcial:feito', { detail: form }));
    }).catch(function () {
      soltarBotao(form); form.dataset.parcialNativo = '1';
      HTMLFormElement.prototype.submit.call(form);             // envio normal, sem o JavaScript
    });
  });

  // ---------- áreas que se atualizam sozinhas
  function marcarSuja(e) { var a = e.target.closest && e.target.closest('[data-atualizar]'); if (a) sujas.add(a); }
  document.addEventListener('input', marcarSuja, true);
  document.addEventListener('toggle', marcarSuja, true);        // abriu/fechou um painel (Editar, menu …) dentro da área
  function ocupada(el) { return sujas.has(el) || el.querySelector('.arrastando, [aria-busy="true"]') || el.contains(document.activeElement) || document.querySelector('dialog[open], .tour-raiz, .tour-sugestao') || el.querySelector('details[open] textarea:not(:placeholder-shown)'); }
  var timers = [];
  function iniciarAtualizacao() {
    timers.forEach(clearInterval); timers = [];
    document.querySelectorAll('[data-atualizar][id]').forEach(function (el) {
      var seg = Math.max(8, parseInt(el.getAttribute('data-atualizar'), 10) || 20), ocup = false;
      timers.push(setInterval(function () {
        if (document.hidden || ocup || ocupada(el)) return;
        ocup = true;
        fetch(location.href, { credentials: 'same-origin', headers: { 'X-Parcial': '1' } }).then(function (r) { return r.ok && mesmoEndereco(r.url) ? r.text() : null; })
          .then(function (html) {
            if (!html || ocupada(el)) return;
            var novo = parse(html).getElementById(el.id);
            if (novo && novo.innerHTML.trim() !== el.innerHTML.trim()) trocar(parse(html), ['#' + el.id]);
          }).catch(function () {}).finally(function () { ocup = false; });
      }, seg * 1000));
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciarAtualizacao); else iniciarAtualizacao();
})();
