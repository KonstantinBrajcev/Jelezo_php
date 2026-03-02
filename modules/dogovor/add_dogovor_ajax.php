<?php
// add_dogovor_ajax.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();
checkSuperuser();

// Устанавливаем заголовок для JSON ответа
header('Content-Type: application/json');

// Проверяем, что запрос POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Неверный метод запроса']);
}

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем данные из POST запроса
    $customer = trim($_POST['customer'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $financing = trim($_POST['financing'] ?? '');
    $prolong = (int)($_POST['add_prolong'] ?? 1);
    $date = $_POST['date'] ?? '';
    $validate = $_POST['validate'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;

        // Получаем значения чекбоксов (исправлено!)
    $to1 = isset($_POST['add_to1']) ? (int)$_POST['add_to1'] : 0;
    $avr = isset($_POST['add_avr']) ? (int)$_POST['add_avr'] : 0;
    $efi = isset($_POST['add_efi']) ? (int)$_POST['add_efi'] : 0;

    error_log("Получены данные: customer=$customer, number=$number, financing=$financing, prolong=$prolong");
    error_log("Чекбоксы: to1=$to1, avr=$avr, efi=$efi");
    
    // Валидация
    $errors = [];
    if (empty($customer)) $errors[] = 'Клиент обязателен';
    if (empty($number)) $errors[] = 'Номер договора обязателен';
    if (empty($date)) $errors[] = 'Дата договора обязательна';
    if (empty($validate)) $errors[] = 'Срок действия договора обязателен';
    
    if (empty($errors)) {
        // Проверка уникальности номера
        $stmt = $db->prepare("SELECT COUNT(*) FROM LIFTEH_dogovor WHERE number = ?");
        $stmt->execute([$number]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Договор с таким номером уже существует';
        }
    }
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode('<br>', $errors)]);
    }
    
    // Вставка данных
    $sql = "INSERT INTO LIFTEH_dogovor 
            (customer, number, financing, prolong, date, validate, is_active, to1, avr, efi, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))";
    

    $stmt = $db->prepare($sql);
    $stmt->execute([
        $customer, 
        $number, 
        $financing, 
        $prolong, 
        $date, 
        $validate, 
        $is_active,
        $to1,
        $avr,
        $efi
    ]);
    
    // Получаем ID последней вставленной записи
    $lastId = $db->lastInsertId();

    sendJsonResponse([
        'success' => true, 
        'message' => 'Договор успешно добавлен!'
    ]);
    
} catch (PDOException $e) {
    sendJsonResponse([
        'success' => false, 
        'message' => 'Ошибка при добавлении договора: ' . $e->getMessage()
    ]);
}
?>