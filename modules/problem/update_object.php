<?php
// update_object.php - Обработчик обновления записей

// Настройки подключения к базе данных
$databaseFile = 'db.sqlite3';

header('Content-Type: application/json');

try {
    // Получаем данные из POST запроса
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Некорректные данные');
    }
    
    // Проверяем наличие ID
    if (empty($data['id'])) {
        throw new Exception('ID записи не указан');
    }
    
    // Подключаемся к базе данных
    $pdo = new PDO("sqlite:" . $databaseFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем информацию о таблице
    $stmt = $pdo->query("PRAGMA table_info(LIFTEH_object)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    
    // Подготавливаем данные для обновления
    $updateFields = [];
    $updateValues = [];
    
    foreach ($data as $key => $value) {
        // Пропускаем ID и несуществующие колонки
        if ($key === 'id' || !in_array($key, $columns)) {
            continue;
        }
        
        $updateFields[] = "$key = ?";
        $updateValues[] = $value === '' ? null : $value;
    }
    
    if (empty($updateFields)) {
        throw new Exception('Нет полей для обновления');
    }
    
    // Добавляем ID в конец значений
    $updateValues[] = $data['id'];
    
    // Создаем SQL запрос
    $sql = "UPDATE LIFTEH_object SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    // Выполняем обновление
    $stmt = $pdo->prepare($sql);
    $stmt->execute($updateValues);
    
    // Проверяем, была ли обновлена запись
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Запись обновлена успешно'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Запись не найдена или данные не изменились'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>