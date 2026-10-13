<?php
// config.php Настройки базы данных
// define('DB_PATH', 'db.sqlite3');
define('DB_PATH', __DIR__ . '/db.sqlite3');
define('ROOT_PATH', __DIR__ . '/..');
define('SITE_URL', 'http://jelezo.by');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
// Инициализация сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция для безопасного вывода
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function getDbConnection() {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true,
    ]);

    return $pdo;
}

function ensureDogovorContactColumns(PDO $pdo) {
    $columns = $pdo->query('PRAGMA table_info(LIFTEH_dogovor)')->fetchAll(PDO::FETCH_COLUMN, 1);

    foreach (['responsible_name', 'responsible_phone', 'responsible_email'] as $column) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec("ALTER TABLE LIFTEH_dogovor ADD COLUMN $column TEXT");
        }
    }
}

// Функция для отправки JSON ответа
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>