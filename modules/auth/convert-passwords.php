<?php
// convert-passwords.php
// Этот скрипт конвертирует все существующие пароли в хеши
$databaseFile = 'db.sqlite3';

try {
    $pdo = new PDO("sqlite:" . $databaseFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем всех пользователей с паролями в открытом виде
    $stmt = $pdo->query("SELECT id, pass FROM auth_user WHERE pass IS NOT NULL AND pass != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Найдено пользователей: " . count($users) . "<br>";
    
    foreach ($users as $user) {
        // Хешируем пароль
        $hashedPassword = password_hash($user['pass'], PASSWORD_DEFAULT);
        
        // Обновляем в базе
        $updateStmt = $pdo->prepare("UPDATE auth_user SET pass = ? WHERE id = ?");
        $updateStmt->execute([$hashedPassword, $user['id']]);
        
        echo "Пользователь ID {$user['id']}: пароль захеширован<br>";
    }
    
    echo "<br>Все пароли успешно захешированы!";
    
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>