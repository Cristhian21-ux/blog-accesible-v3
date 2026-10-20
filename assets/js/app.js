/* Búsqueda en vivo, guardados para leer después, confirmación de borrado y uso sin conexión. */
import {
  countMessage, isSafeUrl, parseSaved, readStorage, savedUrl, searchUrl, toggleSaved, writeStorage,
} from './lib.js';

const SAVED_KEY = 'saved_news';
const byId = (id) => document.getElementById(id);

function announce(message) {
  const live = byId('live');
  if (!live) {
    return;
  }
  live.textContent = '';
  globalThis.setTimeout(() => {
    live.textContent = message;
  }, 60);
}

const getSaved = () => parseSaved(readStorage(SAVED_KEY));
const setSaved = (list) => writeStorage(SAVED_KEY, JSON.stringify(list));

function refreshButtons() {
  const saved = getSaved();
  for (const button of document.querySelectorAll('.save-btn')) {
    const isSaved = saved.includes(button.dataset.id);
    button.setAttribute('aria-pressed', String(isSaved));
    button.textContent = isSaved ? 'Guardada (quitar de guardados)' : 'Guardar para leer después';
  }
}

/** Crea la tarjeta con textContent/atributos: nunca se inserta HTML recibido del servidor. */
function buildCard(item) {
  const card = byId('card-template').content.firstElementChild.cloneNode(true);
  const titleId = `t${Number(item.id)}`;
  card.dataset.id = String(Number(item.id));
  card.querySelector('article').setAttribute('aria-labelledby', titleId);

  const image = card.querySelector('.card-img');
  if (isSafeUrl(item.image_url)) {
    image.src = item.image_url;
    image.alt = String(item.image_alt);
    image.hidden = false;
  } else {
    image.remove();
  }
  card.querySelector('.cat').textContent = `Categoría: ${item.category}`;
  card.querySelector('h2').id = titleId;
  const link = card.querySelector('.card-link');
  link.href = `article.php?id=${Number(item.id)}`;
  link.textContent = item.title;
  card.querySelector('.card-summary').textContent = item.summary;

  const tags = card.querySelector('.tags');
  if (item.tags.length === 0) {
    tags.remove();
  }
  for (const tag of item.tags) {
    const entry = document.createElement('li');
    entry.textContent = tag;
    tags.append(entry);
  }
  card.querySelector('.save-btn').dataset.id = String(Number(item.id));
  return card;
}

async function fetchItems(url, signal) {
  const response = await fetch(url, { signal });
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }
  const data = await response.json();
  if (!Array.isArray(data.items)) {
    throw new TypeError('Respuesta inesperada');
  }
  return data.items;
}

let controller = null;
let timer = 0;

async function runSearch() {
  const input = byId('q');
  const category = byId('cat');
  const list = byId('list');
  const count = byId('count');
  if (!input || !category || !list || !count) {
    return;
  }
  controller?.abort();
  const mine = new AbortController();
  controller = mine;
  count.textContent = 'Buscando…';
  count.classList.remove('is-error');
  list.setAttribute('aria-busy', 'true');
  try {
    const items = await fetchItems(searchUrl(input.value, category.value), mine.signal);
    list.replaceChildren(...items.map(buildCard));
    const message = countMessage(items.length);
    count.textContent = message;
    announce(message);
    refreshButtons();
  } catch (error) {
    if (error.name === 'AbortError') {
      return;
    }
    count.textContent = 'Error: no se pudo buscar. Intenta de nuevo.';
    count.classList.add('is-error');
  } finally {
    if (controller === mine) {
      list.removeAttribute('aria-busy');
    }
  }
}

async function loadSaved() {
  const list = byId('list');
  const count = byId('count');
  if (!list || !count || !document.querySelector('[data-saved-page]')) {
    return;
  }
  const ids = getSaved();
  if (ids.length === 0) {
    list.replaceChildren();
    count.textContent = 'Aún no has guardado noticias. Usa el botón "Guardar para leer después".';
    return;
  }
  try {
    const items = await fetchItems(savedUrl(ids));
    list.replaceChildren(...items.map(buildCard));
    count.textContent = `${items.length} noticia(s) guardada(s).`;
    count.classList.remove('is-error');
    refreshButtons();
  } catch {
    count.textContent = 'Error: no se pudieron cargar los guardados.';
    count.classList.add('is-error');
  }
}

function bindSearch() {
  const input = byId('q');
  const category = byId('cat');
  const form = document.querySelector('form.search');
  if (!input || !category || !form) {
    return;
  }
  input.addEventListener('input', () => {
    globalThis.clearTimeout(timer);
    timer = globalThis.setTimeout(runSearch, 250);
  });
  category.addEventListener('change', runSearch);
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    void runSearch();
  });
}

document.addEventListener('click', (event) => {
  const button = event.target instanceof Element ? event.target.closest('.save-btn') : null;
  if (!button) {
    return;
  }
  const { list, saved } = toggleSaved(getSaved(), button.dataset.id);
  setSaved(list);
  announce(saved ? 'Noticia guardada para leer después' : 'Noticia quitada de guardados');
  refreshButtons();
  if (!saved) {
    void loadSaved();
  }
});

document.addEventListener('submit', (event) => {
  const form = event.target;
  if (form instanceof HTMLFormElement && form.dataset.confirm && !globalThis.confirm(form.dataset.confirm)) {
    event.preventDefault();
  }
});

bindSearch();
refreshButtons();
void loadSaved();

if ('serviceWorker' in navigator) {
  globalThis.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js').catch(() => {
      /* sin conexión o sin HTTPS: el sitio funciona igual, solo sin modo offline */
    });
  });
}
globalThis.addEventListener('offline', () => announce('Sin conexión. Puedes leer las noticias ya visitadas.'));
globalThis.addEventListener('online', () => announce('Conexión restablecida.'));
