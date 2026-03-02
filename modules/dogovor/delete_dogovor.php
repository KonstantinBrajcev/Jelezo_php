<?php
// delete_dogovor.php
session_start();
require_once __DIR__ . '/../../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: /dogovor.php');
    exit();
}

$id = (int)$_GET['id'];

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем существование договора
    $stmt = $db->prepare("SELECT id FROM LIFTEH_dogovor WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetch()) {
        // Удаляем договор
        $stmt = $db->prepare("DELETE FROM LIFTEH_dogovor WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = 'Договор успешно удален!';
    } else {
        $_SESSION['error'] = 'Договор не найден';
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Ошибка при удалении: ' . $e->getMessage();
}

header('Location: /dogovor.php');
exit();
?>