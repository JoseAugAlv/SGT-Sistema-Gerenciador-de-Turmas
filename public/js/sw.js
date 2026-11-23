// public/js/sw.js — service worker do Prumo (servido em __BASE__/sw.js pelo PwaController).
// Guarda o básico para abrir rápido, guarda as últimas páginas abertas para consultar sem internet
// (apagadas ao sair da conta ou quando outra pessoa entra), mostra a página offline e recebe as notificações (Web Push).
const BASE = '__BASE__';
const CACHE = 'prumo-v2';
const PAGINAS = 'prumo-paginas';
const MAX_PAGINAS = 25;
// páginas que nunca são guardadas (login, saída, convites, pagamentos)
const NAO_GUARDAR = /\/(login|logout|auth|convite|primeiro-acesso|completar-cadastro|webhooks|planos\/[^/]+\/pix)(\/|$|\?)/;
const BASICO = [BASE + '/offline', BASE + '/public/img/logo-prumo.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(BASICO)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE && k !== PAGINAS).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;
  // páginas: sempre da rede (dados do dia); guarda a cópia; sem internet, a última cópia (ou a página offline)
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).then((r) => {
      if (r.ok && r.type === 'basic' && !r.redirected && !NAO_GUARDAR.test(url.pathname) && !url.search.includes('saiu=1')) {
        const copia = r.clone();
        caches.open(PAGINAS).then((c) => c.put(req, copia).then(() => c.keys()).then((ks) => {
          if (ks.length > MAX_PAGINAS) return Promise.all(ks.slice(0, ks.length - MAX_PAGINAS).map((k) => c.delete(k)));
        })).catch(() => {});
      }
      return r;
    }).catch(() => caches.open(PAGINAS).then((c) => c.match(req, { ignoreVary: true }))
      .then((salva) => salva ? marcarCopia(salva) : caches.match(BASE + '/offline'))));
    return;
  }
  // CSS, JS e imagens do app: cache primeiro, atualizando por trás
  if (url.pathname.startsWith(BASE + '/public/')) {
    e.respondWith(caches.open(CACHE).then((c) => c.match(req).then((cached) => {
      const rede = fetch(req).then((r) => { if (r.ok) c.put(req, r.clone()); return r; }).catch(() => cached);
      return cached || rede;
    })));
  }
});

// cópia salva: marca o HTML para a página mostrar o aviso "sem internet" (navigator.onLine nem sempre sabe)
function marcarCopia(resp) {
  return resp.text().then((html) => new Response(html.replace(/<head([^>]*)>/i, '<head$1><meta name="prumo-copia-offline" content="1">'), {
    status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' },
  }));
}

// a página pede para esquecer as cópias (saiu da conta / trocou de pessoa)
self.addEventListener('message', (e) => {
  if (e.data && e.data.tipo === 'limpar-paginas') e.waitUntil(caches.delete(PAGINAS));
});

self.addEventListener('push', (e) => {
  let d = {};
  try { d = e.data ? e.data.json() : {}; } catch (_) { d = { titulo: 'Prumo', corpo: e.data ? e.data.text() : '' }; }
  e.waitUntil(self.registration.showNotification(d.titulo || 'Prumo', {
    body: d.corpo || '', icon: d.icone || BASE + '/public/img/logo-prumo.png', badge: BASE + '/public/img/logo-prumo.png',
    data: { link: d.link || BASE + '/notificacoes' }, tag: d.link || undefined, renotify: !!d.link,
  }));
});

self.addEventListener('notificationclick', (e) => {
  e.notification.close();
  const link = (e.notification.data && e.notification.data.link) || BASE + '/';
  e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((abertas) => {
    for (const w of abertas) { if (w.url.includes(BASE) && 'focus' in w) { w.navigate(link); return w.focus(); } }
    return self.clients.openWindow(link);
  }));
});
