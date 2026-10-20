<?php
require_once __DIR__ . '/includes/bootstrap.php';
no_store();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(URL_HOME);
}
csrf_check();
destroy_session();
flash('ok', 'Sesión cerrada.');
redirect(URL_HOME);
