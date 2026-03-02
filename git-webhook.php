<?php
// /git-webhook.php (разместите в корне вашего проекта)

// Секретный ключ для безопасности (придумайте свой)
define('WEBHOOK_SECRET', 'kas5127766');

// Путь к вашему проекту на сервере
define('PROJECT_PATH', '/var/www/jelezo.by/PHP/'); // Linux
// define('PROJECT_PATH', 'D:/Program/JELEZO_2026'); // Windows

// Путь к Git
define('GIT_PATH', 'https://github.com/KonstantinBrajcev/Jelezo_php'); // или полный путь, например '/usr/bin/git'

// Получаем данные от GitHub
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Проверяем подпись
if (!verifySignature($payload, $signature)) {
    http_response_code(401);
    die('Invalid signature');
}

// Логируем событие
logMessage("Webhook received: " . $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'unknown');

// Обрабатываем разные типы событий
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

switch ($event) {
    case 'push':
        handlePushEvent();
        break;
    case 'ping':
        echo json_encode(['status' => 'success', 'message' => 'pong']);
        break;
    default:
        echo json_encode(['status' => 'ignored', 'message' => 'Event not handled']);
}

function handlePushEvent() {
    global $payload;
    $data = json_decode($payload, true);
    
    // Проверяем ветку (например, только main)
    $branch = basename($data['ref'] ?? '');
    if ($branch !== 'main' && $branch !== 'master') {
        echo json_encode(['status' => 'ignored', 'message' => "Branch {$branch} ignored"]);
        return;
    }
    
    // Выполняем git pull
    $result = gitPull();
    
    if ($result['success']) {
        // Выполняем дополнительные действия после обновления
        postUpdateTasks();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Repository updated',
            'output' => $result['output']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Update failed',
            'error' => $result['error']
        ]);
    }
    
    logMessage("Push event processed: " . ($result['success'] ? 'success' : 'failed'));
}

function gitPull() {
    $output = [];
    $returnVar = 0;
    
    // Переходим в директорию проекта
    chdir(PROJECT_PATH);
    
    // Выполняем git pull
    exec(GIT_PATH . ' pull origin main 2>&1', $output, $returnVar);
    
    return [
        'success' => $returnVar === 0,
        'output' => implode("\n", $output),
        'error' => $returnVar !== 0 ? implode("\n", $output) : ''
    ];
}

function postUpdateTasks() {
    // Здесь можно выполнить дополнительные задачи:
    // - Очистка кэша
    // - Обновление зависимостей (composer)
    // - Запуск миграций БД
    // - Установка прав на файлы
    
    // Пример: установка прав на Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows-specific tasks
    } else {
        // Linux-specific tasks
        exec('chmod -R 755 ' . PROJECT_PATH . '/assets/uploads');
        exec('chmod -R 755 ' . PROJECT_PATH . '/cache');
    }
}

function verifySignature($payload, $signature) {
    if (empty(WEBHOOK_SECRET)) {
        return true; // Если секрет не установлен, пропускаем проверку
    }
    
    $expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($expected, $signature);
}

function logMessage($message) {
    $logFile = __DIR__ . '/logs/webhook.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
}