<?php
// get_avr_ajax.php
// Включите отображение ошибок для отладки
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();
checkSuperuser();

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID записи не указан']);
        exit;
    }
    
    $id = intval($_GET['id']);
    
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем основную информацию о записи
    $sql = "SELECT a.*, o.customer, o.address 
            FROM LIFTEH_avr a 
            LEFT JOIN LIFTEH_object o ON a.object_id = o.id
            WHERE a.id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

        
    if (!$record) {
        throw new Exception('Запись не найдена');
    }
    
    // if ($record) {
        // Получаем все работы, связанные с этой записью AVR
        $sql_works = "SELECT id, name as work_name, quantity, unit, avr_id 
                      FROM LIFTEH_work 
                      WHERE avr_id = :avr_id 
                      ORDER BY id";
        $stmt_works = $db->prepare($sql_works);
        $stmt_works->execute([':avr_id' => $id]);
        $works = $stmt_works->fetchAll(PDO::FETCH_ASSOC);
        
        $record['works'] = $works;
        
        // Очищаем буфер вывода перед отправкой JSON
        if (ob_get_length()) ob_clean();
        
        echo json_encode([
            'success' => true,
            'data' => $record
        ]);
    // } else {
    //     echo json_encode(['success' => false, 'message' => 'Запись не найдена']);
    // }
    
} catch (PDOException $e) {
    // Очищаем буфер и отправляем ошибку
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

// Завершаем скрипт
exit;
?>