<?php
// add_object.php - обработчик добавления объекта
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
// Устанавливаем заголовок JSON
header('Content-Type: application/json');
// Проверяем авторизацию
checkAuth();

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'error' => 'Метод не поддерживается']);
}

// Получаем данные из тела запроса
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    sendJsonResponse(['success' => false, 'error' => 'Неверный формат данных']);
}

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Подготавливаем SQL запрос для вставки
    $sql = "INSERT INTO LIFTEH_object (
        customer, address, model, serial_number, phone, name,
        M1, M2, M3, M4, M5, M6, M7, M8, M9, M10, M11, M12,
        latitude, longitude, folder_id, dogovor_id
    ) VALUES (
        :customer, :address, :model, :serial_number, :phone, :name,
        :M1, :M2, :M3, :M4, :M5, :M6, :M7, :M8, :M9, :M10, :M11, :M12,
        :latitude, :longitude, :folder_id, :dogovor_id
    )";
    
    $stmt = $pdo->prepare($sql);
    
    // Подставляем значения
    $stmt->bindValue(':customer', $data['customer'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':address', $data['address'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':model', $data['model'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':serial_number', $data['serial_number'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':phone', $data['phone'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':name', $data['name'] ?? '', PDO::PARAM_STR);
    
    // Месяцы
    for ($i = 1; $i <= 12; $i++) {
        $stmt->bindValue(":M$i", $data["M$i"] ?? '', PDO::PARAM_STR);
    }
    
    // Координаты и ID
    $stmt->bindValue(':latitude', $data['latitude'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':longitude', $data['longitude'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':folder_id', $data['folder_id'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':dogovor_id', $data['dogovor_id'] ?? '', PDO::PARAM_STR);
    
    // Выполняем запрос
    if ($stmt->execute()) {
        sendJsonResponse([
            'success' => true,
            'id' => $pdo->lastInsertId(),
            'message' => 'Объект успешно добавлен'
        ]);
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Ошибка при добавлении записи']);
    }
    
} catch (PDOException $e) {
    error_log("Ошибка добавления объекта: " . $e->getMessage());
    sendJsonResponse(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>