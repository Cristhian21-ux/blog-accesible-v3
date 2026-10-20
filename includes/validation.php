<?php
declare(strict_types=1);

const MAX_TITLE      = 150;
const MAX_SUMMARY    = 300;
const MAX_BODY       = 20000;
const MAX_TRANSCRIPT = 20000;
const MAX_ALT        = 200;
const MAX_URL        = 500;
const MAX_TAGS       = 10;
const MAX_TAG_LENGTH = 30;
const MAX_SAVED_IDS  = 50;
const MIN_PASSWORD   = 10;
const MAX_PASSWORD_BYTES = 72; // límite real de bcrypt

const MSG_URL_INVALID = 'Usa una dirección que empiece con https:// o una ruta que empiece con assets/.';

function str_len(string $text): int
{
    return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

function str_cut(string $text, int $max): string
{
    return function_exists('mb_substr') ? mb_substr($text, 0, $max, 'UTF-8') : substr($text, 0, $max);
}

function str_lower(string $text): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
}

/** Solo se aceptan rutas locales dentro de assets/ o direcciones https:// sin credenciales. */
function is_safe_media_url(string $url): bool
{
    if ($url === '' || strlen($url) > MAX_URL) {
        return false;
    }
    if (str_starts_with($url, 'assets/')) {
        return preg_match('#^assets/[A-Za-z0-9_./-]+$#', $url) === 1
            && !str_contains($url, '..')
            && !str_contains($url, '//');
    }
    if (!str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $parts = parse_url($url);
    return $parts !== false
        && !empty($parts['host'])
        && !isset($parts['user'])
        && !isset($parts['pass']);
}

function safe_url(?string $url): string
{
    $url = (string) $url;
    return is_safe_media_url($url) ? $url : '';
}

/** Etiquetas separadas por coma, sin vacías ni repetidas y sin caracteres de control. */
function parse_tags(string $raw): array
{
    $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw) ?? '';
    $tags = [];
    foreach (explode(',', $clean) as $tag) {
        $tag = trim($tag);
        if ($tag !== '' && !in_array($tag, $tags, true)) {
            $tags[] = $tag;
        }
    }
    return $tags;
}

/** Escapa % _ \ para que el buscador trate el texto literalmente en LIKE ... ESCAPE '\'. */
function like_escape(string $text): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);
}

/** "3,5,x,3,0" -> [3, 5]. Solo enteros positivos, sin repetir, con tope. */
function clean_ids(string $csv, int $max = MAX_SAVED_IDS): array
{
    $ids = [];
    foreach (explode(',', $csv) as $part) {
        $part = trim($part);
        if ($part !== '' && ctype_digit($part) && strlen($part) <= 9 && (int) $part > 0) {
            $id = (int) $part;
            if (!in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
    }
    return array_slice($ids, 0, $max);
}

function check_text(array &$errors, string $key, string $value, int $max, ?string $requiredMessage): void
{
    if ($value === '') {
        if ($requiredMessage !== null) {
            $errors[$key] = $requiredMessage;
        }
        return;
    }
    if (str_len($value) > $max) {
        $errors[$key] = 'No debe superar ' . $max . ' caracteres.';
    }
}

/**
 * Valida el formulario de noticias.
 * Devuelve ['data' => campos limpios, 'errors' => mensajes por campo].
 */
function validate_news(array $input, array $categoryIds): array
{
    $data = [];
    foreach (['title', 'summary', 'body', 'image_url', 'image_alt', 'audio_url', 'transcript'] as $key) {
        $data[$key] = input_str($input, $key);
    }
    $tags = parse_tags(input_str($input, 'tags'));
    $data['tags'] = implode(',', $tags);
    $data['category_id'] = input_int($input, 'category_id');

    $errors = [];
    check_text($errors, 'title', $data['title'], MAX_TITLE, 'El título es obligatorio.');
    check_text($errors, 'summary', $data['summary'], MAX_SUMMARY, 'El resumen es obligatorio.');
    check_text($errors, 'body', $data['body'], MAX_BODY, 'El contenido es obligatorio.');
    check_text($errors, 'image_alt', $data['image_alt'], MAX_ALT, null);
    check_text($errors, 'transcript', $data['transcript'], MAX_TRANSCRIPT, null);

    if (count($tags) > MAX_TAGS) {
        $errors['tags'] = 'Usa como máximo ' . MAX_TAGS . ' etiquetas.';
    } else {
        foreach ($tags as $tag) {
            if (str_len($tag) > MAX_TAG_LENGTH) {
                $errors['tags'] = 'Cada etiqueta debe tener máximo ' . MAX_TAG_LENGTH . ' caracteres.';
            }
        }
    }
    if (!in_array($data['category_id'], $categoryIds, true)) {
        $errors['category_id'] = 'Elige una categoría de la lista.';
    }
    foreach (['image_url', 'audio_url'] as $key) {
        if ($data[$key] !== '' && !is_safe_media_url($data[$key])) {
            $errors[$key] = MSG_URL_INVALID;
        }
    }
    if ($data['image_url'] !== '' && $data['image_alt'] === '' && !isset($errors['image_url'])) {
        $errors['image_alt'] = 'Describe la imagen: el texto alternativo es obligatorio.';
    }
    if ($data['audio_url'] !== '' && $data['transcript'] === '' && !isset($errors['audio_url'])) {
        $errors['transcript'] = 'Si hay audio, la transcripción es obligatoria.';
    }
    return ['data' => $data, 'errors' => $errors];
}

/** Reglas para crear la cuenta de administrador. */
function validate_new_admin(string $username, string $password, string $again): array
{
    $errors = [];
    if (preg_match('/^[A-Za-z0-9_.@-]{3,50}$/', $username) !== 1) {
        $errors['username'] = 'Usa de 3 a 50 caracteres: letras, números y . _ - @';
    }
    $common = ['1234567890', 'password123', 'contrasena123', 'admin12345', 'qwerty12345', 'administrador'];
    if (strlen($password) < MIN_PASSWORD) {
        $errors['password'] = 'La contraseña debe tener al menos ' . MIN_PASSWORD . ' caracteres.';
    } elseif (strlen($password) > MAX_PASSWORD_BYTES) {
        $errors['password'] = 'La contraseña no puede superar ' . MAX_PASSWORD_BYTES . ' caracteres.';
    } elseif (str_lower($password) === str_lower($username) || in_array(str_lower($password), $common, true)) {
        $errors['password'] = 'Esa contraseña es demasiado fácil de adivinar.';
    }
    if ($password !== $again) {
        $errors['password2'] = 'Las contraseñas no coinciden.';
    }
    return $errors;
}
