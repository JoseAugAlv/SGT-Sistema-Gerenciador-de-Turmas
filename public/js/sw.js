// public/js/sw.js — service worker do Prumo (servido em __BASE__/sw.js pelo PwaController).
// Guarda o básico para abrir rápido, mostra a página offline sem internet e recebe as notificações (Web Push).
const BASE = '__BASE__';
const CACHE = 'prumo-v1';
const BASICO = [BASE + '/offline', BASE + '/public/img/logo-prumo.png'];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(BASICO)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;
  // páginas: sempre da rede (dados do dia), com a página offline se cair
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match(BASE + '/offline')));
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
