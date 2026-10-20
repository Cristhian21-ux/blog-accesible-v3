import test from 'node:test';
import assert from 'node:assert/strict';
import * as lib from '../../assets/js/lib.js';

function withStorage(store, run) {
  const original = Object.getOwnPropertyDescriptor(globalThis, 'localStorage');
  Object.defineProperty(globalThis, 'localStorage', { value: store, configurable: true, writable: true });
  try {
    run();
  } finally {
    if (original) {
      Object.defineProperty(globalThis, 'localStorage', original);
    } else {
      delete globalThis.localStorage;
    }
  }
}

test('clampSize respeta los límites', () => {
  assert.equal(lib.clampSize(1), lib.SIZE_MIN);
  assert.equal(lib.clampSize(999), lib.SIZE_MAX);
  assert.equal(lib.clampSize(120.4), 120);
  assert.equal(lib.clampSize('x'), 100);
});

test('pick devuelve el valor o el valor por defecto', () => {
  assert.equal(lib.pick('dark', lib.THEMES, 'light'), 'dark');
  assert.equal(lib.pick('<script>', lib.THEMES, 'light'), 'light');
});

test('normalizePrefs ignora valores inválidos', () => {
  const bad = lib.normalizePrefs({ theme: '<script>', font: 5, size: 'abc', space: 'x', simple: 'yes' });
  assert.deepEqual(bad, { theme: 'light', size: 100, font: 'default', space: 'normal', simple: false });
  assert.equal(lib.normalizePrefs(null).theme, 'light');
  assert.equal(lib.normalizePrefs({ theme: 'contrast', simple: true }).simple, true);
});

test('parseJson y parseSaved toleran datos corruptos', () => {
  assert.equal(lib.parseJson('{no', 7), 7);
  assert.equal(lib.parseJson(null, 7), 7);
  assert.deepEqual(lib.parseJson('[1]', 0), [1]);
  assert.deepEqual(lib.parseSaved('basura'), []);
  assert.deepEqual(lib.parseSaved('{"a":1}'), []);
  assert.deepEqual(lib.parseSaved('[1,2,"x",-3,2,"<b>"]'), ['1', '2']);
  const many = JSON.stringify(Array.from({ length: 80 }, (_, i) => i + 1));
  assert.equal(lib.parseSaved(many).length, lib.MAX_SAVED);
});

test('toggleSaved agrega, quita y respeta el máximo', () => {
  assert.deepEqual(lib.toggleSaved(['1'], 2), { list: ['1', '2'], saved: true });
  assert.deepEqual(lib.toggleSaved(['1', '2'], 2), { list: ['1'], saved: false });
  const full = Array.from({ length: lib.MAX_SAVED }, (_, i) => String(i + 1));
  assert.equal(lib.toggleSaved(full, 9999).list.length, lib.MAX_SAVED);
});

test('isSafeUrl solo acepta https y assets/', () => {
  assert.equal(lib.isSafeUrl('https://a.com/x.png'), true);
  assert.equal(lib.isSafeUrl('assets/a.png'), true);
  for (const bad of ['javascript:alert(1)', 'http://a.com', '', null, undefined, 'assets/../x', 'data:x', 5]) {
    assert.equal(lib.isSafeUrl(bad), false, String(bad));
  }
});

test('searchUrl y savedUrl codifican parámetros', () => {
  const url = lib.searchUrl('a b&c', '2');
  assert.match(url, /^api\.php\?a=search&q=a\+b%26c&cat=2$/);
  assert.match(lib.searchUrl('x', 'abc'), /cat=0$/);
  assert.equal(lib.searchUrl('y'.repeat(300), 0).includes('y'.repeat(101)), false);
  assert.equal(lib.savedUrl(['1', '2']), 'api.php?a=saved&ids=1%2C2');
});

test('countMessage', () => {
  assert.match(lib.countMessage(0), /No hay resultados/);
  assert.equal(lib.countMessage(3), '3 noticia(s) encontrada(s).');
});

test('readStorage y writeStorage funcionan y no fallan si el almacenamiento lanza', () => {
  const data = new Map();
  withStorage({ getItem: (k) => data.get(k) ?? null, setItem: (k, v) => data.set(k, v) }, () => {
    assert.equal(lib.writeStorage('k', 'v'), true);
    assert.equal(lib.readStorage('k'), 'v');
  });
  const broken = { getItem() { throw new Error('x'); }, setItem() { throw new Error('x'); } };
  withStorage(broken, () => {
    assert.equal(lib.readStorage('k'), null);
    assert.equal(lib.writeStorage('k', 'v'), false);
  });
});
