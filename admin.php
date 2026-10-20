<?php
require_once __DIR__ . '/includes/bootstrap.php';
no_store();
require_admin();
$pdo = db();
$state = admin_handle_post($pdo);
$errors = $state['errors'];
$edit = $state['edit'] ?? admin_edit_target($pdo);

$v = static fn (string $k): string => e((string) ($edit[$k] ?? ''));
$err = static fn (string $k): string => isset($errors[$k])
    ? '<span class="field-error" id="e-' . $k . '" role="alert">Error: ' . e($errors[$k]) . '</span>' : '';
$inv = static fn (string $k): string => isset($errors[$k]) ? 'aria-invalid="true" aria-describedby="e-' . $k . '"' : '';

$title = 'Panel de administración';
require __DIR__ . '/includes/header.php';
?>
<h1>Panel de administración</h1>

<?php if ($errors !== []): ?>
  <div class="msg error" role="alert"><strong>Error:</strong> Revisa los campos marcados (<?= count($errors) ?>).</div>
<?php endif; ?>

<h2><?= !empty($edit['id']) ? 'Editar noticia' : 'Nueva noticia' ?></h2>
<form method="post" class="form" novalidate>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

  <label for="title">Título</label><?= $err('title') ?>
  <input id="title" name="title" maxlength="150" required value="<?= $v('title') ?>" <?= $inv('title') ?>>

  <label for="summary">Resumen (frases cortas y claras)</label><?= $err('summary') ?>
  <textarea id="summary" name="summary" rows="2" maxlength="300" required <?= $inv('summary') ?>><?= $v('summary') ?></textarea>

  <label for="body">Contenido (separa los párrafos con una línea en blanco)</label><?= $err('body') ?>
  <textarea id="body" name="body" rows="8" maxlength="20000" required <?= $inv('body') ?>><?= $v('body') ?></textarea>

  <label for="category_id">Categoría</label><?= $err('category_id') ?>
  <select id="category_id" name="category_id" required <?= $inv('category_id') ?>>
    <option value="0">Elige una categoría</option>
    <?php foreach (categories($pdo) as $c): ?>
      <option value="<?= (int) $c['id'] ?>" <?= (int) ($edit['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="tags">Etiquetas (separadas por comas, máximo 10)</label><?= $err('tags') ?>
  <input id="tags" name="tags" maxlength="400" value="<?= $v('tags') ?>" <?= $inv('tags') ?>>

  <label for="image_url">Dirección de la imagen (opcional, https:// o assets/...)</label><?= $err('image_url') ?>
  <input id="image_url" name="image_url" maxlength="500" value="<?= $v('image_url') ?>" <?= $inv('image_url') ?>>

  <label for="image_alt">Texto alternativo de la imagen (obligatorio si hay imagen)</label><?= $err('image_alt') ?>
  <input id="image_alt" name="image_alt" maxlength="200" value="<?= $v('image_alt') ?>" <?= $inv('image_alt') ?>>

  <label for="audio_url">Dirección del audio (opcional, https:// o assets/...)</label><?= $err('audio_url') ?>
  <input id="audio_url" name="audio_url" maxlength="500" value="<?= $v('audio_url') ?>" <?= $inv('audio_url') ?>>

  <label for="transcript">Transcripción del audio (obligatoria si hay audio)</label><?= $err('transcript') ?>
  <textarea id="transcript" name="transcript" rows="4" maxlength="20000" <?= $inv('transcript') ?>><?= $v('transcript') ?></textarea>

  <div class="row">
    <button class="btn" type="submit">Guardar noticia</button>
    <?php if (!empty($edit['id'])): ?><a class="btn secondary" href="admin.php">Cancelar edición</a><?php endif; ?>
  </div>
</form>

<h2>Noticias publicadas</h2>
<div class="table-wrap">
<table>
  <caption>Lista de noticias con acciones</caption>
  <thead><tr><th scope="col">Título</th><th scope="col">Categoría</th><th scope="col">Fecha</th><th scope="col">Acciones</th></tr></thead>
  <tbody>
  <?php foreach (list_news_for_admin($pdo) as $r): ?>
    <tr>
      <th scope="row"><?= e($r['title']) ?></th>
      <td><?= e($r['category']) ?></td>
      <td><?= e(substr((string) $r['created_at'], 0, 10)) ?></td>
      <td class="actions">
        <a class="btn secondary" href="admin.php?edit=<?= (int) $r['id'] ?>" aria-label="Editar <?= e($r['title']) ?>">Editar</a>
        <form method="post" data-confirm="¿Eliminar esta noticia? No se puede deshacer.">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <button class="btn danger" type="submit" aria-label="Eliminar <?= e($r['title']) ?>">Eliminar</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
