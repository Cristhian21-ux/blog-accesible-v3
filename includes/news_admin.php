<?php
declare(strict_types=1);

const MSG_NEWS_MISSING = 'La noticia ya no existe.';

/**
 * Procesa los formularios del panel (guardar o eliminar).
 * @return array{errors: array, edit: ?array} estado para volver a mostrar el formulario
 */
function admin_handle_post(PDO $pdo): array
{
    $state = ['errors' => [], 'edit' => null];
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return $state;
    }
    csrf_check();
    $action = input_str($_POST, 'action');
    if ($action === 'delete') {
        admin_delete($pdo);
    }
    if ($action === 'save') {
        return admin_save($pdo);
    }
    return $state;
}

function admin_delete(PDO $pdo): never
{
    if (delete_news($pdo, input_int($_POST, 'id'))) {
        flash('ok', 'Noticia eliminada.');
    } else {
        flash('error', MSG_NEWS_MISSING);
    }
    redirect(URL_ADMIN);
}

function admin_save(PDO $pdo): array
{
    $id = input_int($_POST, 'id');
    $result = validate_news($_POST, category_ids($pdo));
    if ($result['errors'] !== []) {
        return ['errors' => $result['errors'], 'edit' => $result['data'] + ['id' => $id]];
    }
    if (!save_news($pdo, $result['data'], $id)) {
        flash('error', MSG_NEWS_MISSING);
        redirect(URL_ADMIN);
    }
    flash('ok', $id > 0 ? 'Noticia actualizada.' : 'Noticia creada.');
    redirect(URL_ADMIN);
}

function admin_edit_target(PDO $pdo): ?array
{
    $id = input_int($_GET, 'edit');
    return $id > 0 ? get_news($pdo, $id) : null;
}
