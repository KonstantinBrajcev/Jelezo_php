<?php
// modules/service/get_service_count.php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['object_id'])) {
        echo json_encode(['success' => false, 'error' => 'Не указан ID объекта']);
        exit;
    }

    $objectId = $_GET['object_id'];
    
    // Используем DB_PATH из конфига
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем существование таблицы
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='LIFTEH_service'")->fetch();
    
    if (!$tableExists) {
        echo json_encode(['success' => true, 'count' => 0]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM LIFTEH_service WHERE object_id = :object_id");
    $stmt->execute([':object_id' => $objectId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'count' => $result['count'] ?? 0
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка: ' . $e->getMessage()]);
}
?>