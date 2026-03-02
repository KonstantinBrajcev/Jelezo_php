<?php
// edit_dogovor_ajax.php
require_once __DIR__ . '/../../includes/config.php';


// Проверяем, что запрос POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Неверный метод запроса']);
}

// Проверяем наличие ID
if (!isset($_POST['id'])) {
    sendJsonResponse(['success' => false, 'message' => 'ID договора не указан']);
}

$id = (int)$_POST['id'];

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем существование договора
    $stmt = $db->prepare("SELECT id FROM LIFTEH_dogovor WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        sendJsonResponse(['success' => false, 'message' => 'Договор не найден']);
    }
    
    // Получаем данные из POST запроса
    $customer = trim($_POST['customer'] ?? '');
    $number = trim($_POST['number'] ?? '');
    $financing = trim($_POST['financing'] ?? '');
    // $prolong = (int)($_POST['prolong'] ?? '');
    $prolong = isset($_POST['prolong']) ? (int)$_POST['prolong'] : 1;
    $date = $_POST['date'] ?? '';
    $validate = $_POST['validate'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Получаем значения чекбоксов (исправлено!)
    // В форме редактирования используются id edit_to1, edit_avr, edit_efi
    // Но в POST они приходят как to1, avr, efi
    $to1 = isset($_POST['to1']) ? (int)$_POST['to1'] : 0;
    $avr = isset($_POST['avr']) ? (int)$_POST['avr'] : 0;
    $efi = isset($_POST['efi']) ? (int)$_POST['efi'] : 0;
    
    // Логируем полученные данные для отладки
    error_log("Редактирование договора ID: $id");
    error_log("POST данные: " . print_r($_POST, true));
    error_log("to1=$to1, avr=$avr, efi=$efi, prolong=$prolong");

    // Валидация
    $errors = [];
    if (empty($customer)) $errors[] = 'Клиент обязателен';
    if (empty($number)) $errors[] = 'Номер договора обязателен';
    if (empty($date)) $errors[] = 'Дата договора обязательна';
    if (empty($validate)) $errors[] = 'Срок действия договора обязателен';
    
    if (empty($errors)) {
        // Проверка уникальности номера (кроме текущего)
        $stmt = $db->prepare("SELECT COUNT(*) FROM LIFTEH_dogovor WHERE number = ? AND id != ?");
        $stmt->execute([$number, $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Другой договор с таким номером уже существует';
        }
    }
    
    if (!empty($errors)) {
        sendJsonResponse(['success' => false, 'message' => implode('<br>', $errors)]);
    }
    
    // Обновление данных
    $sql = "UPDATE LIFTEH_dogovor SET 
            customer = ?, 
            number = ?, 
            financing = ?, 
            prolong = ?, 
            date = ?, 
            validate = ?, 
            is_active = ?, 
            to1 = ?,
            avr = ?,
            efi = ?,
            updated_at = datetime('now')
            WHERE id = ?";


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
        $efi,
        $id
    ]);
    
    sendJsonResponse([
        'success' => true, 
        'message' => 'Договор успешно обновлен!'
    ]);
    
} catch (PDOException $e) {
    sendJsonResponse([
        'success' => false, 
        'message' => 'Ошибка при обновлении договора: ' . $e->getMessage()
    ]);
}
?>