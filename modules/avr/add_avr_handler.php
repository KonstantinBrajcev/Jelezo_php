<?php
// add_avr_handler.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();
checkSuperuser();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
        exit;
    }
    
    // Получаем данные из POST
    $problem = trim($_POST['problem'] ?? '');
    $object_id = intval($_POST['object_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $result = trim($_POST['result'] ?? '');
    $works = $_POST['works'] ?? [];
    
    // Валидация
    if (empty($problem)) {
        echo json_encode(['success' => false, 'message' => 'Поле "Проблема" обязательно для заполнения']);
        exit;
    }
    
    if ($object_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Поле "Объект" обязательно для заполнения']);
        exit;
    }
    
    if (empty($works)) {
        echo json_encode(['success' => false, 'message' => 'Добавьте хотя бы одну работу']);
        exit;
    }
    
    // Проверяем каждую работу
    foreach ($works as $index => $work) {
        if (empty(trim($work['work_name']))) {
            echo json_encode(['success' => false, 'message' => "В работе №" . ($index + 1) . " не указано название"]);
            exit;
        }
        if (empty($work['quantity']) || floatval($work['quantity']) <= 0) {
            echo json_encode(['success' => false, 'message' => "В работе №" . ($index + 1) . " не указано количество"]);
            exit;
        }
    }
    
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Начинаем транзакцию
    $db->beginTransaction();
    
    try {
        // Сначала создаем запись в LIFTEH_avr
        $sql = "INSERT INTO LIFTEH_avr (insert_date, problem, work_id, object_id, user_id, result) 
                VALUES (datetime('now'), :problem, 0, :object_id, :user_id, :result)";
        
        $stmt = $db->prepare($sql);
        $result_insert = $stmt->execute([
            ':problem' => $problem,
            ':object_id' => $object_id,
            ':user_id' => $user_id,
            ':result' => $result
        ]);
        
        if (!$result_insert) {
            throw new Exception('Ошибка при создании записи AVR');
        }
        
        $avr_id = $db->lastInsertId();
        
        // Теперь создаем работы и связываем их с AVR
        $work_ids = [];
        foreach ($works as $work) {
            $work_name = trim($work['work_name']);
            $quantity = floatval($work['quantity']);
            $unit = trim($work['unit']);
            
            // Проверяем, существует ли уже такая работа
            $check_sql = "SELECT id, quantity FROM LIFTEH_work WHERE name = :name AND unit = :unit";
            $check_stmt = $db->prepare($check_sql);
            $check_stmt->execute([':name' => $work_name, ':unit' => $unit]);
            $existing_work = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_work) {
                $work_id = $existing_work['id'];
                // Обновляем количество существующей работы
                $update_sql = "UPDATE LIFTEH_work SET quantity = quantity + :quantity WHERE id = :id";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->execute([':quantity' => $quantity, ':id' => $work_id]);
            } else {
                // Создаем новую работу
                $insert_work_sql = "INSERT INTO LIFTEH_work (name, quantity, unit, avr_id) 
                                    VALUES (:name, :quantity, :unit, :avr_id)";
                $insert_work_stmt = $db->prepare($insert_work_sql);
                $insert_work_stmt->execute([
                    ':name' => $work_name,
                    ':quantity' => $quantity,
                    ':unit' => $unit,
                    ':avr_id' => $avr_id
                ]);
                $work_id = $db->lastInsertId();
            }
            
            $work_ids[] = $work_id;
        }
        
        // Обновляем work_id в LIFTEH_avr (берем первую работу как основную)
        $main_work_id = !empty($work_ids) ? $work_ids[0] : 0;
        $update_avr_sql = "UPDATE LIFTEH_avr SET work_id = :work_id WHERE id = :id";
        $update_avr_stmt = $db->prepare($update_avr_sql);
        $update_avr_stmt->execute([':work_id' => $main_work_id, ':id' => $avr_id]);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Запись успешно добавлена. Добавлено работ: ' . count($works)]);
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>