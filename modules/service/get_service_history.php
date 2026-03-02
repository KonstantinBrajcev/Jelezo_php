<?php
// modules/service/get_service_history.php
require_once __DIR__ . '/../../includes/config.php';
// require_once __DIR__ . '/../../modules/auth/auth.php';
header('Content-Type: application/json');
// $databaseFile = 'db.sqlite3';

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $object_id = $_GET['object_id'] ?? 0;
    
    // Проверяем существование таблицы
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='LIFTEH_service'")->fetch();
    
    if (!$tableExists) {
        echo json_encode(['success' => true, 'services' => []]);
        exit;
    }
    
    // Получаем историю ТО для объекта
    $stmt = $pdo->prepare("
        SELECT id, object_id, service_date, comments, user_id, result, foto
        FROM LIFTEH_service 
        WHERE object_id = :object_id 
        ORDER BY service_date DESC
        LIMIT 3
    ");
    
    $stmt->execute([':object_id' => $object_id]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'services' => $services]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>