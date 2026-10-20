<?php
require_once __DIR__ . '/includes/bootstrap.php';
$news = get_news(db(), input_int($_GET, 'id'));
if ($news === null) {
    http_response_code(404);
}
$title = $news['title'] ?? 'Noticia no encontrada';
require __DIR__ . '/includes/header.php';

if ($news === null): ?>
  <h1>Noticia no encontrada</h1>
  <p><a href="index.php">Volver al inicio</a></p>
<?php else:
    $image = safe_url($news['image_url']);
    $audio = safe_url($news['audio_url']);
    $paragraphs = preg_split('/\R{2,}/', (string) $news['body']) ?: [(string) $news['body']];
?>
<article class="article">
  <p class="cat">Categoría: <?= e($news['category']) ?> · <time datetime="<?= e($news['created_at']) ?>"><?= e(substr((string) $news['created_at'], 0, 10)) ?></time></p>
  <h1><?= e($news['title']) ?></h1>
  <p class="lead"><?= e($news['summary']) ?></p>

  <?php if ($image !== ''): ?>
    <figure>
      <img src="<?= e($image) ?>" alt="<?= e($news['image_alt']) ?>" loading="lazy" width="800" height="450">
      <figcaption><?= e($news['image_alt']) ?></figcaption>
    </figure>
  <?php endif; ?>

  <?php if ($audio !== ''): ?>
    <figure>
      <audio controls preload="none" src="<?= e($audio) ?>">
        <?php if (trim((string) $news['transcript']) !== ''): ?>
          <track kind="captions" src="captions.php?id=<?= (int) $news['id'] ?>" srclang="es" label="Español" default>
        <?php endif; ?>
        Tu navegador no reproduce audio. Lee la transcripción más abajo.
      </audio>
    </figure>
  <?php endif; ?>

  <div id="texto-noticia">
    <?php foreach ($paragraphs as $paragraph): ?>
      <p><?= nl2br(e(trim($paragraph))) ?></p>
    <?php endforeach; ?>
  </div>

  <?php if (trim((string) $news['transcript']) !== ''): ?>
    <section aria-labelledby="tr-title" class="transcript">
      <h2 id="tr-title">Transcripción del audio</h2>
      <p><?= nl2br(e($news['transcript'])) ?></p>
    </section>
  <?php endif; ?>

  <button type="button" class="btn secondary save-btn" data-id="<?= (int) $news['id'] ?>" aria-pressed="false">Guardar para leer después</button>
  <p><a href="index.php">← Volver a las noticias</a></p>
</article>
<?php endif;
require __DIR__ . '/includes/footer.php';
