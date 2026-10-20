<?php
declare(strict_types=1);

/** Contraseña que traían versiones antiguas del proyecto (ya publicada): se retira al abrir la base. */
const RETIRED_DEFAULT = 'Admin2026!';
const NEWS_LIMIT = 50;

function db_path(): string
{
    $custom = getenv('BLOG_DB_PATH');
    return ($custom !== false && $custom !== '') ? $custom : __DIR__ . '/../data/blog.sqlite';
}

/** Conexión única por petición. Con $reset=true se cierra y se vuelve a abrir (pruebas). */
function db(bool $reset = false): PDO
{
    static $pdo = null;
    if ($reset) {
        $pdo = null;
    }
    $pdo ??= db_connect(db_path());
    return $pdo;
}

function db_connect(string $path): PDO
{
    $inMemory = $path === ':memory:';
    if (!$inMemory) {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException('No se pudo crear la carpeta de datos.');
        }
    }
    $isNew = $inMemory || !file_exists($path);

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    ensure_schema($pdo);
    if ($isNew) {
        seed_sample_data($pdo);
        if (!$inMemory && is_file($path)) {
            chmod($path, 0600);
        }
    }
    retire_legacy_default_admin($pdo);
    return $pdo;
}

/** Crea lo que falte. Sirve también para bases creadas con versiones anteriores. */
function ensure_schema(PDO $pdo): void
{
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        );
        CREATE TABLE IF NOT EXISTS news (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            summary TEXT NOT NULL,
            body TEXT NOT NULL,
            category_id INTEGER NOT NULL REFERENCES categories(id),
            tags TEXT DEFAULT \'\',
            image_url TEXT DEFAULT \'\',
            image_alt TEXT DEFAULT \'\',
            audio_url TEXT DEFAULT \'\',
            transcript TEXT DEFAULT \'\',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
        CREATE INDEX IF NOT EXISTS idx_news_cat ON news(category_id);
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT NOT NULL,
            username TEXT NOT NULL,
            attempted_at INTEGER NOT NULL
        );
        CREATE INDEX IF NOT EXISTS idx_attempts_ip ON login_attempts(ip, attempted_at);
        CREATE TABLE IF NOT EXISTS meta (
            name TEXT PRIMARY KEY,
            value TEXT NOT NULL
        );');
}

function seed_sample_data(PDO $pdo): void
{
    $insertCategory = $pdo->prepare('INSERT INTO categories(name) VALUES(?)');
    foreach (['Ciencia', 'Salud', 'Educación', 'Tecnología'] as $name) {
        $insertCategory->execute([$name]);
    }
    $insertNews = $pdo->prepare(
        'INSERT INTO news(title, summary, body, category_id, tags, image_alt, transcript) VALUES(?,?,?,?,?,?,?)'
    );
    $insertNews->execute([
        'Nuevo método para enseñar ciencias en el aula',
        'Docentes usan experimentos simples para que los estudiantes aprendan mejor.',
        "Un grupo de docentes aplicó experimentos con materiales de bajo costo.\n\n"
        . "Los estudiantes participaron más y entendieron mejor los conceptos.\n\n"
        . 'Los resultados se presentarán en la feria escolar.',
        1, 'ciencia,escuela,experimentos', '', '',
    ]);
    $insertNews->execute([
        'Consejos para dormir mejor',
        'Cinco hábitos sencillos ayudan a descansar mejor cada noche.',
        "Dormir bien mejora la memoria y el ánimo.\n\nEvita pantallas antes de dormir y mantén horarios fijos.",
        2, 'salud,descanso', '', '',
    ]);
    $insertNews->execute([
        'Accesibilidad digital: internet para todas las personas',
        'Qué es la accesibilidad web y por qué importa.',
        "Una web accesible puede ser usada por personas con discapacidad visual, auditiva, motora o cognitiva.\n\n"
        . 'Las pautas WCAG ayudan a lograrlo.',
        4, 'accesibilidad,web,wcag', '',
        'Locutor: Hoy hablamos de accesibilidad digital. Una web accesible puede ser usada por todas las personas, con o sin discapacidad.',
    ]);
}

function meta_get(PDO $pdo, string $name): ?string
{
    $st = $pdo->prepare('SELECT value FROM meta WHERE name = ?');
    $st->execute([$name]);
    $value = $st->fetchColumn();
    return $value === false ? null : (string) $value;
}

function meta_set(PDO $pdo, string $name, string $value): void
{
    $pdo->prepare('INSERT OR REPLACE INTO meta(name, value) VALUES(?, ?)')->execute([$name, $value]);
}

/**
 * Las primeras versiones del proyecto creaban el usuario "admin" con una contraseña fija y publicada.
 * Si todavía existe, se elimina una sola vez y se pide crear un administrador nuevo.
 */
function retire_legacy_default_admin(PDO $pdo): bool
{
    if (meta_get($pdo, 'legacy_checked') === '1') {
        return false;
    }
    $removed = false;
    $row = $pdo->query("SELECT id, password_hash FROM users WHERE username = 'admin'")->fetch();
    if ($row !== false && password_verify(RETIRED_DEFAULT, (string) $row['password_hash'])) {
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$row['id']]);
        $removed = true;
    }
    meta_set($pdo, 'legacy_checked', '1');
    return $removed;
}

function has_users(PDO $pdo): bool
{
    return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
}

function categories(PDO $pdo): array
{
    return $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
}

function category_ids(PDO $pdo): array
{
    return array_map('intval', array_column(categories($pdo), 'id'));
}

function query_news(PDO $pdo, string $search = '', int $category = 0, array $ids = []): array
{
    $where = [];
    $args = [];
    if ($ids !== []) {
        $where[] = 'n.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
        foreach ($ids as $id) {
            $args[] = (int) $id;
        }
    }
    if ($search !== '') {
        $where[] = "(n.title LIKE ? ESCAPE '\\' OR n.summary LIKE ? ESCAPE '\\' OR n.tags LIKE ? ESCAPE '\\')";
        $like = '%' . like_escape($search) . '%';
        array_push($args, $like, $like, $like);
    }
    if ($category > 0) {
        $where[] = 'n.category_id = ?';
        $args[] = $category;
    }
    $sql = 'SELECT n.*, c.name AS category FROM news n JOIN categories c ON c.id = n.category_id'
        . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
        . ' ORDER BY n.created_at DESC, n.id DESC LIMIT ' . NEWS_LIMIT;
    $st = $pdo->prepare($sql);
    $st->execute($args);
    return $st->fetchAll();
}

function get_news(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT n.*, c.name AS category FROM news n JOIN categories c ON c.id = n.category_id WHERE n.id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row === false ? null : $row;
}

function list_news_for_admin(PDO $pdo): array
{
    return $pdo->query(
        'SELECT n.id, n.title, c.name AS category, n.created_at
         FROM news n JOIN categories c ON c.id = n.category_id ORDER BY n.id DESC'
    )->fetchAll();
}

/** Inserta (id = 0) o actualiza una noticia. Devuelve false si se intentó editar una que no existe. */
function save_news(PDO $pdo, array $data, int $id = 0): bool
{
    $fields = ['title', 'summary', 'body', 'category_id', 'tags', 'image_url', 'image_alt', 'audio_url', 'transcript'];
    $values = [];
    foreach ($fields as $field) {
        $values[$field] = $data[$field];
    }
    if ($id > 0) {
        $values['id'] = $id;
        $st = $pdo->prepare(
            'UPDATE news SET title=:title, summary=:summary, body=:body, category_id=:category_id, tags=:tags,
             image_url=:image_url, image_alt=:image_alt, audio_url=:audio_url, transcript=:transcript WHERE id=:id'
        );
        $st->execute($values);
        return $st->rowCount() > 0;
    }
    $pdo->prepare(
        'INSERT INTO news(title, summary, body, category_id, tags, image_url, image_alt, audio_url, transcript)
         VALUES(:title, :summary, :body, :category_id, :tags, :image_url, :image_alt, :audio_url, :transcript)'
    )->execute($values);
    return true;
}

function delete_news(PDO $pdo, int $id): bool
{
    $st = $pdo->prepare('DELETE FROM news WHERE id = ?');
    $st->execute([$id]);
    return $st->rowCount() > 0;
}
