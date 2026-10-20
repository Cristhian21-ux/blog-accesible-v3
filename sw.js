/* Service worker liviano: archivos estáticos en caché y páginas públicas visitadas disponibles sin conexión. */
const CACHE = 'nt-v2';
const ASSETS = [
  'assets/css/styles.css',
  'assets/js/prefs.js',
  'assets/js/lib.js',
  'assets/js/accessibility.js',
  'assets/js/app.js',
  'assets/icon.svg',
];
const PRIVATE_PAGES = /\/(admin|login|logout|setup)\.php/;
const OFFLINE_PAGE = '<!doctype html><html lang="es"><meta charset="utf-8">'
  + '<meta name="viewport" content="width=device-width,initial-scale=1"><title>Sin conexión</title>'
  + '<body style="font-family:sans-serif;max-width:40rem;margin:2rem auto;padding:1rem;line-height:1.6">'
  + '<h1>Sin conexión</h1><p>No hay internet. Puedes abrir las noticias que ya visitaste. '
  + 'Vuelve a intentarlo más tarde.</p></body></html>';

/** Solo se guardan respuestas propias y correctas que el servidor no marcó como privadas. */
function cacheable(response) {
  const control = response.headers.get('Cache-Control') ?? '';
  return response.ok && response.type === 'basic' && !control.includes('no-store');
}

async function remember(request, response) {
  if (cacheable(response)) {
    const cache = await caches.open(CACHE);
    await cache.put(request, response.clone());
  }
  return response;
}

async function fromCacheFirst(request) {
  const hit = await caches.match(request);
  return hit ?? remember(request, await fetch(request));
}

async function fromNetworkFirst(request) {
  try {
    return await remember(request, await fetch(request));
  } catch {
    const hit = await caches.match(request);
    return hit ?? new Response(OFFLINE_PAGE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
  }
}

globalThis.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(ASSETS)).then(() => globalThis.skipWaiting()));
});

globalThis.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    for (const key of await caches.keys()) {
      if (key !== CACHE) {
        await caches.delete(key);
      }
    }
    await globalThis.clients.claim();
  })());
});

globalThis.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  if (request.method !== 'GET' || url.origin !== globalThis.location.origin) {
    return;
  }
  if (url.pathname.includes('/assets/')) {
    event.respondWith(fromCacheFirst(request));
  } else if (request.mode === 'navigate' && !PRIVATE_PAGES.test(url.pathname)) {
    event.respondWith(fromNetworkFirst(request));
  }
});
