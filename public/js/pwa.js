// public/js/pwa.js
// Registra o service worker (app instalável), cuida do botão "Baixar o app" e das notificações no aparelho:
// window.PrumoPush.ativar() / desativar() / estado() / testar().
// O botão [data-instalar-app] fica sempre visível: instala direto quando o navegador deixa; senão explica o motivo
// e o passo a passo daquele aparelho (http pela rede não é "seguro" para o celular, iPhone usa Compartilhar, etc.).
(function () {
  'use strict';
  var base = (document.querySelector('meta[name="app-base"]') || {}).content || '';
  var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var temSW = 'serviceWorker' in navigator && window.isSecureContext;
  var registro = temSW ? navigator.serviceWorker.register(base + '/sw.js', { scope: base + '/' }).catch(function () { return null; }) : Promise.resolve(null);

  // ---------- instalar o app
  var adiado = null;
  var ua = navigator.userAgent || '';
  var ios = /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  var android = /Android/i.test(ua);
  var instalado = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

  window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); adiado = e; });
  window.addEventListener('appinstalled', function () { adiado = null; instalado = true; });

  function ajuda() {
    var origem = location.origin, titulo, corpo;
    if (instalado) {
      titulo = 'O app já está instalado';
      corpo = '<p>Você já está usando o Prumo como app neste aparelho. Procure o ícone na tela inicial ou na lista de apps.</p>';
    } else if (!window.isSecureContext) {
      titulo = 'Este endereço não permite instalar';
      corpo = '<p>Você abriu o site por <b>' + origem + '</b>. Celulares só instalam apps (e recebem notificações) de sites com <b>https</b> — '
        + 'quando o Prumo for publicado com https, é só tocar em Baixar o app.</p>'
        + (ios ? '<p><b>No iPhone</b> não há como liberar endereços http: para testar agora, é preciso abrir o site por um endereço https.</p>'
          : '<p><b>Para testar agora no Android (Chrome):</b></p><ol>'
            + '<li>Abra uma aba e digite <code>chrome://flags/#unsafely-treat-insecure-origin-as-secure</code></li>'
            + '<li>No campo, escreva <code>' + origem + '</code> e mude para <b>Enabled</b></li>'
            + '<li>Toque em <b>Relaunch</b> (o Chrome reinicia), volte a esta página e toque em <b>Baixar o app</b> de novo</li></ol>'
            + '<p class="pwa-ajuda-nota">Isso vale só para o seu celular e só para esse endereço. Depois de testar, pode voltar a opção para Default.</p>');
    } else if (ios) {
      titulo = 'Instalar no iPhone';
      corpo = '<ol><li>Abra esta página no <b>Safari</b></li><li>Toque em <b>Compartilhar</b> (quadrado com a seta para cima)</li>'
        + '<li>Escolha <b>Adicionar à Tela de Início</b> e confirme</li></ol>';
    } else if (android) {
      titulo = 'Instalar no Android';
      corpo = '<ol><li>No <b>Chrome</b>, toque no menu <b>⋮</b> (canto de cima)</li><li>Escolha <b>Instalar app</b> (ou <b>Adicionar à tela inicial</b>)</li></ol>'
        + '<p class="pwa-ajuda-nota">Se a opção não aparecer, recarregue a página uma vez e tente de novo.</p>';
    } else {
      titulo = 'Instalar no computador';
      corpo = '<ol><li>Use o <b>Chrome</b> ou o <b>Edge</b></li><li>Clique no ícone de instalar na barra de endereço (à direita) ou no menu <b>⋮ → Instalar Prumo</b></li></ol>'
        + '<p class="pwa-ajuda-nota">No Firefox e no Safari do computador, sites não viram app.</p>';
    }
    var d = document.createElement('div');
    d.className = 'pwa-ajuda'; d.setAttribute('role', 'dialog'); d.setAttribute('aria-modal', 'true');
    d.innerHTML = '<div class="pwa-ajuda-card"><button type="button" class="pwa-ajuda-x" aria-label="Fechar">&times;</button>'
      + '<img src="' + base + '/public/img/logo-prumo.png" alt="" width="56" height="56"><h2>' + titulo + '</h2>' + corpo
      + '<button type="button" class="pwa-ajuda-ok">Entendi</button></div>';
    if (!document.getElementById('pwa-ajuda-css')) {
      var st = document.createElement('style'); st.id = 'pwa-ajuda-css';
      st.textContent = '.pwa-ajuda{position:fixed;inset:0;z-index:100001;display:grid;place-items:center;padding:16px;background:rgba(8,10,20,.6);backdrop-filter:blur(4px);font-family:inherit}'
        + '.pwa-ajuda-card{position:relative;width:min(460px,100%);max-height:calc(100vh - 32px);overflow:auto;background:#fff;color:#0f1222;border-radius:20px;padding:24px;text-align:left;box-shadow:0 30px 70px -20px rgba(0,0,0,.5)}'
        + '.pwa-ajuda-card img{display:block;margin:0 auto 8px;border-radius:14px}.pwa-ajuda-card h2{margin:0 0 10px;font-size:20px;text-align:center}'
        + '.pwa-ajuda-card p,.pwa-ajuda-card li{font-size:15px;line-height:1.55;color:#334155}.pwa-ajuda-card ol{padding-left:20px;margin:8px 0}'
        + '.pwa-ajuda-card code{font-size:12.5px;background:#eef2f7;padding:2px 5px;border-radius:6px;word-break:break-all}.pwa-ajuda-nota{font-size:13px!important;color:#64748b!important}'
        + '.pwa-ajuda-x{position:absolute;top:10px;right:12px;border:0;background:none;font-size:26px;color:#64748b;cursor:pointer}'
        + '.pwa-ajuda-ok{display:block;width:100%;margin-top:14px;border:0;border-radius:12px;padding:12px;background:#2563eb;color:#fff;font-weight:700;font-size:15px;cursor:pointer}';
      document.head.appendChild(st);
    }
    document.body.appendChild(d);
    function fechar() { d.remove(); }
    d.addEventListener('click', function (e) { if (e.target === d || e.target.closest('.pwa-ajuda-x, .pwa-ajuda-ok')) fechar(); });
    document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { fechar(); document.removeEventListener('keydown', esc); } });
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-instalar-app]'); if (!b) return;
    e.preventDefault();
    if (adiado) { adiado.prompt(); adiado.userChoice.finally(function () { adiado = null; }); return; }
    ajuda();
  });
  document.querySelectorAll('[data-instalar-app]').forEach(function (b) { b.hidden = false; });

  // ---------- card "Baixe o app": 1ª vez que o usuário entra (neste aparelho), se ainda não instalou
  var uid = (document.querySelector('meta[name="usuario-id"]') || {}).content;
  var chaveCard = 'prumo-card-app-' + uid;
  function lerVisto() { try { return localStorage.getItem(chaveCard) === '1'; } catch (e) { return true; } }
  function marcarVisto() { try { localStorage.setItem(chaveCard, '1'); } catch (e) {} }
  function ocupado() { return document.querySelector('.tour-sugestao, .tour-raiz, .pwa-ajuda, dialog[open]'); }
  function mostrarCard() {
    if (ocupado()) { setTimeout(mostrarCard, 1200); return; }      // tutorial/janela aberta: espera fechar
    if (lerVisto() || instalado) return;
    marcarVisto();
    var c = document.createElement('div');
    c.className = 'pwa-card'; c.setAttribute('role', 'dialog'); c.setAttribute('aria-labelledby', 'pwa-card-t');
    c.innerHTML = '<img src="' + base + '/public/img/logo-prumo.png" alt="" width="44" height="44">'
      + '<div class="pwa-card-txt"><strong id="pwa-card-t">Leve o Prumo no celular</strong>'
      + '<p>Instale o app: abre direto da tela inicial, em tela cheia, e avisa prazos e novidades.</p>'
      + '<div class="pwa-card-acoes"><button type="button" class="pwa-card-sim" data-instalar-app>Baixar o app</button>'
      + '<button type="button" class="pwa-card-nao">Agora não</button></div></div>'
      + '<button type="button" class="pwa-card-x" aria-label="Fechar">&times;</button>';
    if (!document.getElementById('pwa-card-css')) {
      var st = document.createElement('style'); st.id = 'pwa-card-css';
      st.textContent = '.pwa-card{position:fixed;z-index:100000;right:20px;bottom:20px;width:min(380px,calc(100vw - 32px));display:flex;gap:14px;align-items:flex-start;padding:18px 18px 16px;border-radius:18px;background:#fff;color:#0f1222;border:1px solid rgba(15,18,34,.08);box-shadow:0 24px 60px -18px rgba(15,18,34,.45);font-family:inherit;animation:pwaCardIn .38s cubic-bezier(.2,.8,.2,1) both}'
        + '.pwa-card img{border-radius:12px;flex:none}.pwa-card-txt{flex:1;min-width:0}.pwa-card-txt strong{display:block;font-size:16px;margin:2px 24px 4px 0}'
        + '.pwa-card-txt p{margin:0 0 12px;font-size:14px;line-height:1.5;color:#475569}.pwa-card-acoes{display:flex;gap:8px;flex-wrap:wrap}'
        + '.pwa-card-sim,.pwa-card-nao{border:0;border-radius:10px;padding:9px 14px;font:inherit;font-size:14px;font-weight:700;cursor:pointer}'
        + '.pwa-card-sim{background:#2563eb;color:#fff}.pwa-card-nao{background:#eef2f7;color:#334155}'
        + '.pwa-card-x{position:absolute;top:8px;right:10px;border:0;background:none;font-size:24px;line-height:1;color:#94a3b8;cursor:pointer}'
        + '@media (max-width:639px){.pwa-card{left:16px;right:16px;bottom:calc(16px + env(safe-area-inset-bottom));width:auto}}'
        + '@keyframes pwaCardIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}'
        + '@media (prefers-reduced-motion:reduce){.pwa-card{animation:none}}'
        + 'html[data-theme="dark"] .pwa-card,body.dark .pwa-card{background:#151a2b;color:#e5e9f5;border-color:rgba(255,255,255,.08)}'
        + 'html[data-theme="dark"] .pwa-card-txt p{color:#a3acc2}html[data-theme="dark"] .pwa-card-nao{background:#232a40;color:#d5dbea}';
      document.head.appendChild(st);
    }
    document.body.appendChild(c);
    c.addEventListener('click', function (e) { if (e.target.closest('.pwa-card-sim, .pwa-card-nao, .pwa-card-x')) c.remove(); });
  }
  if (uid && !instalado && !lerVisto()) {
    window.addEventListener('load', function () { setTimeout(mostrarCard, 1800); });
  }

  // ---------- notificações no aparelho
  function chaveUint8(b64) {
    var pad = '='.repeat((4 - b64.length % 4) % 4), raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from(raw, function (c) { return c.charCodeAt(0); });
  }
  function post(url, corpo) {
    return fetch(base + url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token }, body: JSON.stringify(corpo || {}) })
      .then(function (r) { return r.json(); });
  }
  window.PrumoPush = {
    suportado: function () { return temSW && 'PushManager' in window && 'Notification' in window; },
    estado: function () {
      if (!this.suportado()) return Promise.resolve('sem_suporte');
      if (Notification.permission === 'denied') return Promise.resolve('bloqueado');
      return registro.then(function (reg) { return reg ? reg.pushManager.getSubscription() : null; }).then(function (s) { return s ? 'ativo' : 'inativo'; });
    },
    ativar: function () {
      return Notification.requestPermission().then(function (p) {
        if (p !== 'granted') throw new Error('permissao');
        return Promise.all([registro, fetch(base + '/push/chave', { credentials: 'same-origin' }).then(function (r) { return r.json(); })]);
      }).then(function (x) {
        return x[0].pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: chaveUint8(x[1].chave) });
      }).then(function (sub) { return post('/push/inscrever', sub.toJSON()); });
    },
    desativar: function () {
      return registro.then(function (reg) { return reg ? reg.pushManager.getSubscription() : null; }).then(function (s) {
        if (!s) return; var ep = s.endpoint; return s.unsubscribe().then(function () { return post('/push/cancelar', { endpoint: ep }); });
      });
    },
    testar: function () { return post('/push/teste'); },
  };
})();
