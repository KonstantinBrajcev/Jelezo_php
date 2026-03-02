<?php
// modules/dogovor/get_dogovor_ajax.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';

// Проверяем, что запрос GET и есть ID
if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Неверный запрос']);
}

$id = (int)$_GET['id'];

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем данные договора
    $stmt = $db->prepare("SELECT * FROM LIFTEH_dogovor WHERE id = ?");
    $stmt->execute([$id]);
    $dogovor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dogovor) {
        sendJsonResponse(['success' => false, 'message' => 'Договор не найден']);
    }


// ВСТАВКА
    // ВАЖНО: Приводим значения к правильным типам
    $dogovor['prolong'] = (int)$dogovor['prolong']; // Гарантируем, что это число (1 или 0)
    $dogovor['is_active'] = (int)$dogovor['is_active']; // Гарантируем, что это число (1 или 0)
    
    // Форматируем даты для полей ввода
    if (!empty($dogovor['date'])) {
        $dogovor['date'] = date('Y-m-d', strtotime($dogovor['date']));
    }
    
    if (!empty($dogovor['validate'])) {
        $dogovor['validate'] = date('Y-m-d', strtotime($dogovor['validate']));
    }
// ВСТАВКА

    // Форматируем даты
    // $dogovor['date'] = date('Y-m-d', strtotime($dogovor['date']));
    $dogovor['created_at_formatted'] = date('d.m.Y H:i:s', strtotime($dogovor['created_at']));
    $dogovor['updated_at_formatted'] = date('d.m.Y H:i:s', strtotime($dogovor['updated_at']));

    // Добавляем поля to, avr, efi (убеждаемся, что они есть)
    $dogovor['to1'] = isset($dogovor['to1']) ? $dogovor['to1'] : 0;
    $dogovor['avr'] = isset($dogovor['avr']) ? $dogovor['avr'] : 0;
    $dogovor['efi'] = isset($dogovor['efi']) ? $dogovor['efi'] : 0;
    
    sendJsonResponse(['success' => true, 'data' => $dogovor]);
    
} catch (PDOException $e) {
    sendJsonResponse(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>