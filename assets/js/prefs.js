/* Se carga antes de pintar la página para aplicar las preferencias sin parpadeos. */
(function applyStoredPrefs() {
  try {
    const stored = JSON.parse(globalThis.localStorage.getItem('a11y') ?? '{}');
    const root = document.documentElement;
    for (const key of ['theme', 'font', 'space']) {
      if (typeof stored[key] === 'string') {
        root.dataset[key] = stored[key].slice(0, 12);
      }
    }
    const size = Number(stored.size);
    if (size >= 80 && size <= 200) {
      root.style.setProperty('--fs', `${size}%`);
    }
    if (stored.simple === true) {
      root.dataset.simple = 'true';
    }
  } catch {
    /* sin preferencias guardadas: se usan las predeterminadas */
  }
})();
