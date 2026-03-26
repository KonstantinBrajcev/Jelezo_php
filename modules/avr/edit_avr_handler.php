<?php
// edit_avr_handler.php
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
    $id = intval($_POST['id'] ?? 0);
    $problem = trim($_POST['problem'] ?? '');
    $object_id = intval($_POST['object_id'] ?? 0);
    $user_id = intval($_POST['user_id'] ?? 0);
    $result = trim($_POST['result'] ?? '');
    $insert_date = $_POST['insert_date'] ?? '';
    $works = $_POST['works'] ?? [];
    
    // Валидация
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Неверный ID записи']);
        exit;
    }
    
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
        // Получаем старые работы
        $old_works_sql = "SELECT id, name, quantity, unit FROM LIFTEH_work WHERE avr_id = :avr_id";
        $old_works_stmt = $db->prepare($old_works_sql);
        $old_works_stmt->execute([':avr_id' => $id]);
        $old_works = $old_works_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Удаляем старые работы (но сначала корректируем количество)
        foreach ($old_works as $old_work) {
            // Уменьшаем количество в глобальном счетчике
            $decrease_sql = "UPDATE LIFTEH_work SET quantity = quantity - :quantity WHERE id = :id";
            $decrease_stmt = $db->prepare($decrease_sql);
            $decrease_stmt->execute([
                ':quantity' => $old_work['quantity'],
                ':id' => $old_work['id']
            ]);
            
            // Проверяем, нужно ли удалить работу
            $check_sql = "SELECT quantity FROM LIFTEH_work WHERE id = :id";
            $check_stmt = $db->prepare($check_sql);
            $check_stmt->execute([':id' => $old_work['id']]);
            $work_check = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($work_check && $work_check['quantity'] <= 0) {
                $delete_sql = "DELETE FROM LIFTEH_work WHERE id = :id";
                $delete_stmt = $db->prepare($delete_sql);
                $delete_stmt->execute([':id' => $old_work['id']]);
            } else {
                // Если работа остается, но больше не связана с этой AVR, обнуляем avr_id
                $clear_avr_sql = "UPDATE LIFTEH_work SET avr_id = 0 WHERE id = :id";
                $clear_avr_stmt = $db->prepare($clear_avr_sql);
                $clear_avr_stmt->execute([':id' => $old_work['id']]);
            }
        }
        
        // Создаем новые работы
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
                $update_sql = "UPDATE LIFTEH_work SET quantity = quantity + :quantity, avr_id = :avr_id WHERE id = :id";
                $update_stmt = $db->prepare($update_sql);
                $update_stmt->execute([
                    ':quantity' => $quantity,
                    ':avr_id' => $id,
                    ':id' => $work_id
                ]);
            } else {
                // Создаем новую работу
                $insert_work_sql = "INSERT INTO LIFTEH_work (name, quantity, unit, avr_id) 
                                    VALUES (:name, :quantity, :unit, :avr_id)";
                $insert_work_stmt = $db->prepare($insert_work_sql);
                $insert_work_stmt->execute([
                    ':name' => $work_name,
                    ':quantity' => $quantity,
                    ':unit' => $unit,
                    ':avr_id' => $id
                ]);
                $work_id = $db->lastInsertId();
            }
            
            $work_ids[] = $work_id;
        }
        
        // Обновляем запись в LIFTEH_avr
        $main_work_id = !empty($work_ids) ? $work_ids[0] : 0;
        
        if (!empty($insert_date)) {
            $sql = "UPDATE LIFTEH_avr 
                    SET problem = :problem, 
                        work_id = :work_id, 
                        object_id = :object_id, 
                        user_id = :user_id, 
                        result = :result,
                        insert_date = :insert_date
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result_update = $stmt->execute([
                ':problem' => $problem,
                ':work_id' => $main_work_id,
                ':object_id' => $object_id,
                ':user_id' => $user_id,
                ':result' => $result,
                ':insert_date' => $insert_date,
                ':id' => $id
            ]);
        } else {
            $sql = "UPDATE LIFTEH_avr 
                    SET problem = :problem, 
                        work_id = :work_id, 
                        object_id = :object_id, 
                        user_id = :user_id, 
                        result = :result
                    WHERE id = :id";
            
            $stmt = $db->prepare($sql);
            $result_update = $stmt->execute([
                ':problem' => $problem,
                ':work_id' => $main_work_id,
                ':object_id' => $object_id,
                ':user_id' => $user_id,
                ':result' => $result,
                ':id' => $id
            ]);
        }
        
        if ($result_update) {
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Запись успешно обновлена. Обновлено работ: ' . count($works)]);
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении записи']);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>