<?php
require_once __DIR__ . '/bootstrap.php';
$title ??= 'Noticias para Todos';
$messages = flashes();
if ($messages !== [] || is_admin()) {
    no_store();
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light" data-font="default">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | Noticias para Todos</title>
<meta name="description" content="Blog de noticias accesible, con lectura clara y opciones de personalización.">
<meta name="theme-color" content="#003a8c">
<link rel="manifest" href="manifest.json">
<link rel="icon" href="assets/icon.svg" type="image/svg+xml">
<script src="assets/js/prefs.js"></script>
<link rel="stylesheet" href="assets/css/styles.css">
<script type="module" src="assets/js/accessibility.js"></script>
<script type="module" src="assets/js/app.js"></script>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido principal</a>

<header class="site-header">
  <p class="brand"><a href="index.php">Noticias para Todos</a></p>
  <nav aria-label="Navegación principal">
    <ul class="nav">
      <li><a href="index.php">Inicio</a></li>
      <li><a href="saved.php">Guardados</a></li>
      <?php if (is_admin()): ?>
        <li><a href="admin.php">Panel</a></li>
        <li>
          <form method="post" action="logout.php" class="inline-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="linklike">Cerrar sesión</button>
          </form>
        </li>
      <?php else: ?>
        <li><a href="login.php">Administrar</a></li>
      <?php endif; ?>
    </ul>
  </nav>
  <button type="button" id="a11y-toggle" class="btn" aria-expanded="false" aria-controls="a11y-panel">Accesibilidad (Alt+A)</button>
</header>

<section id="a11y-panel" class="a11y-panel" aria-labelledby="a11y-title" hidden>
  <h2 id="a11y-title">Opciones de accesibilidad</h2>

  <fieldset>
    <legend>Tema y color</legend>
    <label for="theme-select">Tema visual</label>
    <select id="theme-select">
      <option value="light">Claro (predeterminado)</option>
      <option value="dark">Oscuro</option>
      <option value="contrast">Alto contraste (blanco sobre negro)</option>
      <option value="yellow">Alto contraste (amarillo sobre negro)</option>
      <option value="protan">Protanopia (sin rojo)</option>
      <option value="deutan">Deuteranopia (sin verde)</option>
      <option value="tritan">Tritanopia (sin azul)</option>
    </select>
  </fieldset>

  <fieldset>
    <legend>Texto</legend>
    <div class="row">
      <button type="button" class="btn secondary" id="font-down" aria-label="Reducir tamaño de letra">A−</button>
      <button type="button" class="btn secondary" id="font-reset" aria-label="Tamaño de letra normal">A</button>
      <button type="button" class="btn secondary" id="font-up" aria-label="Aumentar tamaño de letra">A+</button>
      <span id="font-value" aria-live="polite">100 %</span>
    </div>
    <label for="font-select">Tipografía</label>
    <select id="font-select">
      <option value="default">Sans-serif limpia</option>
      <option value="dyslexia">Para dislexia (OpenDyslexic)</option>
    </select>
    <label for="space-select">Espaciado de líneas</label>
    <select id="space-select">
      <option value="normal">Normal</option>
      <option value="wide">Amplio</option>
    </select>
  </fieldset>

  <fieldset>
    <legend>Lectura y comodidad</legend>
    <div class="check">
      <input type="checkbox" id="simple-mode">
      <label for="simple-mode">Modo lectura simple (oculta imágenes y decoración)</label>
    </div>
    <p class="hint">Las animaciones se desactivan solas si tu dispositivo lo pide.</p>
  </fieldset>

  <div class="row">
    <button type="button" class="btn secondary" id="read-aloud">Leer en voz alta</button>
    <button type="button" class="btn secondary" id="a11y-reset">Restablecer todo</button>
  </div>

  <details>
    <summary>Atajos de teclado</summary>
    <ul>
      <li><kbd>Alt</kbd>+<kbd>A</kbd>: abrir o cerrar este panel</li>
      <li><kbd>Alt</kbd>+<kbd>B</kbd>: ir al buscador</li>
      <li><kbd>Alt</kbd>+<kbd>C</kbd>: alternar alto contraste</li>
      <li><kbd>Alt</kbd>+<kbd>+</kbd> / <kbd>Alt</kbd>+<kbd>−</kbd>: aumentar o reducir letra</li>
      <li><kbd>Esc</kbd>: cerrar el panel o detener la lectura en voz alta</li>
    </ul>
  </details>
</section>

<main id="contenido" tabindex="-1">
<?php foreach ($messages as [$type, $text]): ?>
  <?php if ($type === 'error'): ?>
    <div class="msg error" role="alert"><strong>Error:</strong> <?= e($text) ?></div>
  <?php else: ?>
    <output class="msg ok"><strong>Éxito:</strong> <?= e($text) ?></output>
  <?php endif; ?>
<?php endforeach; ?>
