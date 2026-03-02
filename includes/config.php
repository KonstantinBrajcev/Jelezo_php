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

// Функция для отправки JSON ответа
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}
?>