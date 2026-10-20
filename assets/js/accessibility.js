/* Barra de accesibilidad: temas, tamaño de letra, tipografía, atajos y lectura en voz alta. */
import { SIZE_STEP, normalizePrefs, parseJson, readStorage, writeStorage } from './lib.js';

const STORAGE_KEY = 'a11y';
const root = document.documentElement;
const byId = (id) => document.getElementById(id);

const THEME_NAMES = {
  light: 'Claro',
  dark: 'Oscuro',
  contrast: 'Alto contraste blanco sobre negro',
  yellow: 'Alto contraste amarillo sobre negro',
  protan: 'Modo protanopia',
  deutan: 'Modo deuteranopia',
  tritan: 'Modo tritanopia',
};

let prefs = normalizePrefs(parseJson(readStorage(STORAGE_KEY), {}));

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

function render() {
  root.dataset.theme = prefs.theme;
  root.dataset.font = prefs.font;
  root.dataset.space = prefs.space;
  root.style.setProperty('--fs', `${prefs.size}%`);
  if (prefs.simple) {
    root.dataset.simple = 'true';
  } else {
    root.removeAttribute('data-simple');
  }
  const controls = { 'theme-select': prefs.theme, 'font-select': prefs.font, 'space-select': prefs.space };
  for (const [id, value] of Object.entries(controls)) {
    const control = byId(id);
    if (control) {
      control.value = value;
    }
  }
  const simple = byId('simple-mode');
  if (simple) {
    simple.checked = prefs.simple;
  }
  const size = byId('font-value');
  if (size) {
    size.textContent = `${prefs.size} %`;
  }
}

function update(change, message) {
  prefs = normalizePrefs({ ...prefs, ...change });
  writeStorage(STORAGE_KEY, JSON.stringify(prefs));
  render();
  if (message) {
    announce(message);
  }
}

function changeSize(newSize) {
  update({ size: newSize });
  announce(`Tamaño de letra ${prefs.size} por ciento`);
}

function togglePanel(force) {
  const panel = byId('a11y-panel');
  const button = byId('a11y-toggle');
  if (!panel || !button) {
    return;
  }
  const open = force ?? panel.hidden;
  panel.hidden = !open;
  button.setAttribute('aria-expanded', String(open));
  (open ? byId('theme-select') : button)?.focus();
}

function stopReading() {
  const synth = globalThis.speechSynthesis;
  if (synth?.speaking) {
    synth.cancel();
    announce('Lectura detenida');
  }
  const button = byId('read-aloud');
  if (button) {
    button.textContent = 'Leer en voz alta';
  }
}

function toggleReadAloud() {
  const synth = globalThis.speechSynthesis;
  if (!synth) {
    announce('Tu navegador no admite lectura en voz alta.');
    return;
  }
  if (synth.speaking) {
    stopReading();
    return;
  }
  const main = byId('contenido');
  const button = byId('read-aloud');
  if (!main || !button) {
    return;
  }
  const utterance = new SpeechSynthesisUtterance(main.innerText);
  utterance.lang = 'es-ES';
  utterance.addEventListener('end', () => {
    button.textContent = 'Leer en voz alta';
  });
  button.textContent = 'Detener lectura';
  synth.speak(utterance);
  announce('Leyendo la página');
}

function toggleContrast() {
  const theme = prefs.theme === 'contrast' ? 'light' : 'contrast';
  update({ theme }, `Tema cambiado: ${THEME_NAMES[theme]}`);
}

function on(id, type, handler) {
  byId(id)?.addEventListener(type, handler);
}

on('a11y-toggle', 'click', () => togglePanel());
on('theme-select', 'change', (event) => {
  update({ theme: event.target.value }, `Tema cambiado: ${THEME_NAMES[event.target.value] ?? event.target.value}`);
});
on('font-select', 'change', (event) => {
  update({ font: event.target.value }, event.target.value === 'dyslexia'
    ? 'Tipografía para dislexia activada'
    : 'Tipografía estándar activada');
});
on('space-select', 'change', (event) => {
  update({ space: event.target.value }, `Espaciado de líneas ${event.target.value === 'wide' ? 'amplio' : 'normal'}`);
});
on('simple-mode', 'change', (event) => {
  update({ simple: event.target.checked }, `Modo lectura simple ${event.target.checked ? 'activado' : 'desactivado'}`);
});
on('font-up', 'click', () => changeSize(prefs.size + SIZE_STEP));
on('font-down', 'click', () => changeSize(prefs.size - SIZE_STEP));
on('font-reset', 'click', () => changeSize(100));
on('read-aloud', 'click', toggleReadAloud);
on('a11y-reset', 'click', () => {
  update({ theme: 'light', size: 100, font: 'default', space: 'normal', simple: false }, 'Opciones de accesibilidad restablecidas');
});

const SHORTCUTS = {
  a: () => togglePanel(),
  b: () => byId('q')?.focus(),
  c: toggleContrast,
  '+': () => changeSize(prefs.size + SIZE_STEP),
  '=': () => changeSize(prefs.size + SIZE_STEP),
  '-': () => changeSize(prefs.size - SIZE_STEP),
  _: () => changeSize(prefs.size - SIZE_STEP),
};

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    stopReading();
    const panel = byId('a11y-panel');
    if (panel && !panel.hidden) {
      togglePanel(false);
    }
    return;
  }
  if (!event.altKey || event.ctrlKey || event.metaKey) {
    return;
  }
  const action = SHORTCUTS[event.key.toLowerCase()];
  if (action) {
    event.preventDefault();
    action();
  }
});

render();
