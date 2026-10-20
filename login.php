<?php
require_once __DIR__ . '/includes/bootstrap.php';
no_store();
$pdo = db();
if (!has_users($pdo)) {
    redirect(URL_SETUP);
}
if (is_admin()) {
    redirect(URL_ADMIN);
}

$error = '';
$username = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_check();
    $username = input_str($_POST, 'username', 50);
    $result = attempt_login($pdo, client_ip(), $username, input_raw($_POST, 'password'));
    if ($result['status'] === 'ok') {
        login_user((int) $result['user']['id']);
        flash('ok', 'Sesión iniciada correctamente.');
        redirect(URL_ADMIN);
    }
    if ($result['status'] === 'blocked') {
        http_response_code(429);
        $error = 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.';
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
$title = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<h1>Iniciar sesión</h1>
<?php if ($error !== ''): ?><div class="msg error" role="alert"><strong>Error:</strong> <?= e($error) ?></div><?php endif; ?>
<form method="post" class="form" novalidate>
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <label for="username">Usuario</label>
  <input id="username" name="username" required maxlength="50" autocomplete="username" value="<?= e($username) ?>" <?= $error !== '' ? 'aria-invalid="true"' : '' ?>>
  <label for="password">Contraseña</label>
  <input id="password" name="password" type="password" required maxlength="200" autocomplete="current-password" <?= $error !== '' ? 'aria-invalid="true"' : '' ?>>
  <button class="btn" type="submit">Entrar</button>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
