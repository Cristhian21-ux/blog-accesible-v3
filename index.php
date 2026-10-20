<?php
$title = 'Inicio';
require __DIR__ . '/includes/header.php';
$search = input_str($_GET, 'q', 100);
$category = input_int($_GET, 'cat');
$news = query_news(db(), $search, $category);
?>
<div class="hero">
  <h1>Últimas noticias</h1>
  <p>Lee con calma. Puedes cambiar colores, letra y más en el botón "Accesibilidad".</p>
</div>

<search aria-label="Buscar noticias">
  <form class="search" method="get" action="index.php">
    <label for="q">Buscar por título, resumen o etiqueta</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" maxlength="100" autocomplete="off">
    <label for="cat">Categoría</label>
    <select id="cat" name="cat">
      <option value="0">Todas</option>
      <?php foreach (categories(db()) as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $category === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Buscar</button>
  </form>
</search>

<output id="count"><?= e(count_message(count($news))) ?></output>

<ul id="list" class="grid" aria-label="Lista de noticias">
  <?php foreach ($news as $n) { echo render_card($n); } ?>
</ul>
<?php require __DIR__ . '/includes/footer.php'; ?>
