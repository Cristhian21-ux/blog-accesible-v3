</main>
<footer class="site-footer">
  <p>Proyecto académico · Cumple pautas WCAG 2.1 · Hecho para ser leído por todas las personas.</p>
</footer>
<div id="live" class="sr-only" aria-live="polite" aria-atomic="true"></div>

<template id="card-template">
  <li class="card">
    <article>
      <img class="card-img" alt="" loading="lazy" width="400" height="225" hidden>
      <p class="cat"></p>
      <h2><a class="card-link" href="index.php">Título de la noticia</a></h2>
      <p class="card-summary"></p>
      <ul class="tags" aria-label="Etiquetas"></ul>
      <button type="button" class="btn secondary save-btn" aria-pressed="false">Guardar para leer después</button>
    </article>
  </li>
</template>
</body>
</html>
