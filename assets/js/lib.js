/* Funciones puras (sin DOM) usadas por accessibility.js y app.js. Tienen pruebas en tests/js. */

export const THEMES = ['light', 'dark', 'contrast', 'yellow', 'protan', 'deutan', 'tritan'];
export const FONTS = ['default', 'dyslexia'];
export const SPACES = ['normal', 'wide'];
export const SIZE_MIN = 80;
export const SIZE_MAX = 200;
export const SIZE_STEP = 10;
export const MAX_SAVED = 50;

export function clampSize(value) {
  const size = Number(value);
  if (!Number.isFinite(size)) {
    return 100;
  }
  return Math.min(SIZE_MAX, Math.max(SIZE_MIN, Math.round(size)));
}

export function pick(value, allowed, fallback) {
  return allowed.includes(value) ? value : fallback;
}

/** Devuelve preferencias válidas aunque lo guardado esté dañado o alterado. */
export function normalizePrefs(raw) {
  const prefs = raw !== null && typeof raw === 'object' ? raw : {};
  return {
    theme: pick(prefs.theme, THEMES, 'light'),
    size: clampSize(prefs.size ?? 100),
    font: pick(prefs.font, FONTS, 'default'),
    space: pick(prefs.space, SPACES, 'normal'),
    simple: prefs.simple === true,
  };
}

export function parseJson(text, fallback) {
  try {
    return JSON.parse(text ?? '');
  } catch {
    return fallback;
  }
}

export function readStorage(key) {
  try {
    return globalThis.localStorage.getItem(key);
  } catch {
    return null;
  }
}

export function writeStorage(key, value) {
  try {
    globalThis.localStorage.setItem(key, value);
    return true;
  } catch {
    return false;
  }
}

/** Lista de ids guardados: solo enteros positivos, sin repetir y con tope. */
export function parseSaved(raw) {
  const list = parseJson(raw, []);
  if (!Array.isArray(list)) {
    return [];
  }
  const ids = [];
  for (const item of list) {
    const id = String(item);
    if (/^[1-9]\d{0,8}$/.test(id) && !ids.includes(id)) {
      ids.push(id);
    }
  }
  return ids.slice(0, MAX_SAVED);
}

export function toggleSaved(list, id) {
  const key = String(id);
  if (list.includes(key)) {
    return { list: list.filter((item) => item !== key), saved: false };
  }
  return { list: [...list, key].slice(-MAX_SAVED), saved: true };
}

/** Solo https:// o rutas locales dentro de assets/. */
export function isSafeUrl(url) {
  if (typeof url !== 'string' || url.includes('..')) {
    return false;
  }
  return url.startsWith('https://') || /^assets\/[\w./-]+$/.test(url);
}

export function searchUrl(query, category) {
  const params = new URLSearchParams({
    a: 'search',
    q: String(query).slice(0, 100),
    cat: String(Number.parseInt(category, 10) || 0),
  });
  return `api.php?${params}`;
}

export function savedUrl(ids) {
  return `api.php?a=saved&ids=${encodeURIComponent(ids.join(','))}`;
}

export function countMessage(count) {
  return count === 0
    ? 'No hay resultados. Prueba con otra palabra o categoría.'
    : `${count} noticia(s) encontrada(s).`;
}
