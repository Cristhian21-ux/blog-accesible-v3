<?php
/*
 * Crea el administrador o cambia su contraseña desde la terminal.
 *   php bin/create-admin.php MI_USUARIO
 * La contraseña se lee de la variable BLOG_ADMIN_PASSWORD o, si no existe, se pide por teclado.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('BLOG_NO_SESSION', true);
require_once __DIR__ . '/../includes/bootstrap.php';

$username = trim((string) ($argv[1] ?? ''));
$password = (string) getenv('BLOG_ADMIN_PASSWORD');
if ($password === '') {
    fwrite(STDOUT, "Contraseña nueva (mínimo 10 caracteres; se verá al escribirla): ");
    $password = trim((string) fgets(STDIN));
}

$errors = validate_new_admin($username, $password, $password);
if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
set_user_password(db(), $username, $password);
fwrite(STDOUT, "Listo: el usuario '" . $username . "' ya puede iniciar sesión." . PHP_EOL);
