<?php
// delete_avr.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();
checkSuperuser();

try {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header('Location: /avr.php?error=invalid_id');
        exit;
    }
    
    $id = intval($_GET['id']);
    
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем существование записи
    $check_sql = "SELECT id FROM LIFTEH_avr WHERE id = :id";
    $check_stmt = $db->prepare($check_sql);
    $check_stmt->execute([':id' => $id]);
    
    if (!$check_stmt->fetch()) {
        header('Location: /avr.php?error=not_found');
        exit;
    }
    
    // Удаляем запись
    $sql = "DELETE FROM LIFTEH_avr WHERE id = :id";
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([':id' => $id]);
    
    if ($result) {
        header('Location: /avr.php?success=deleted');
    } else {
        header('Location: /avr.php?error=delete_failed');
    }
    
} catch (PDOException $e) {
    header('Location: /avr.php?error=database_error');
}
?>