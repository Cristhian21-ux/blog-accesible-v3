<?php
/* Primer uso: crea la cuenta de administrador (no hay contraseñas fijas en el código). */
require_once __DIR__ . '/includes/bootstrap.php';
no_store();
$pdo = db();
if (has_users($pdo)) {
    redirect(URL_LOGIN);
}

$errors = [];
$username = '';
$allowed = setup_allowed();
if (!$allowed) {
    http_response_code(403);
}
if ($allowed && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_check();
    $username = input_str($_POST, 'username', 50);
    $password = input_raw($_POST, 'password');
    $errors = validate_new_admin($username, $password, input_raw($_POST, 'password2'));
    if ($errors === [] && create_first_admin($pdo, $username, $password)) {
        flash('ok', 'Administrador creado. Ya puedes iniciar sesión.');
        redirect(URL_LOGIN);
    }
}
$err = static fn (string $k): string => isset($errors[$k])
    ? '<span class="field-error" id="e-' . $k . '" role="alert">Error: ' . e($errors[$k]) . '</span>' : '';
$inv = static fn (string $k): string => isset($errors[$k]) ? 'aria-invalid="true" aria-describedby="e-' . $k . '"' : '';

$title = 'Crear administrador';
require __DIR__ . '/includes/header.php';
?>
<h1>Crear cuenta de administrador</h1>
<?php if (!$allowed): ?>
  <div class="msg error" role="alert"><strong>Error:</strong> Por seguridad, la cuenta inicial solo se crea desde el mismo equipo donde corre el sitio.</div>
  <p>Si estás en un servidor, abre una terminal en la carpeta del proyecto y ejecuta <code>php bin/create-admin.php TU_USUARIO</code>.</p>
<?php else: ?>
  <p>Es la primera vez que se abre el sitio. Crea ahora el usuario que gestionará las noticias.</p>
  <?php if ($errors !== []): ?>
    <div class="msg error" role="alert"><strong>Error:</strong> Revisa los campos marcados (<?= count($errors) ?>).</div>
  <?php endif; ?>
  <form method="post" class="form" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <label for="username">Usuario (3 a 50 caracteres)</label><?= $err('username') ?>
    <input id="username" name="username" required maxlength="50" autocomplete="username" value="<?= e($username) ?>" <?= $inv('username') ?>>

    <label for="password">Contraseña (mínimo 10 caracteres)</label><?= $err('password') ?>
    <input id="password" name="password" type="password" required maxlength="72" autocomplete="new-password" <?= $inv('password') ?>>

    <label for="password2">Repite la contraseña</label><?= $err('password2') ?>
    <input id="password2" name="password2" type="password" required maxlength="72" autocomplete="new-password" <?= $inv('password2') ?>>

    <button class="btn" type="submit">Crear administrador</button>
  </form>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
