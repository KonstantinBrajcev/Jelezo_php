<?php
// get_objects_by_dogovor.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();

header('Content-Type: application/json');

if (!isset($_GET['dogovor_id']) || !is_numeric($_GET['dogovor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID договора']);
    exit;
}

$dogovor_id = (int)$_GET['dogovor_id'];

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем объекты, привязанные к договору
    $sql = "SELECT id, name, address FROM LIFTEH_object WHERE dogovor_id = :dogovor_id ORDER BY customer";
    $stmt = $db->prepare($sql);
    $stmt->execute([':dogovor_id' => $dogovor_id]);
    $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'objects' => $objects,
        'count' => count($objects)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка базы данных: ' . $e->getMessage()
    ]);
}
?>