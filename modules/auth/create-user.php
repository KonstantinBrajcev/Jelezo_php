<?php
// create-user.php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    $errors = [];
    
    // Проверка данных
    if (empty($username)) {
        $errors[] = 'Введите логин';
    }
    
    if (empty($password)) {
        $errors[] = 'Введите пароль';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Пароли не совпадают';
    } elseif (!isPasswordValid($password)) {
        $errors[] = 'Пароль должен содержать минимум 6 символов';
    }
    
    if (userExists($username)) {
        $errors[] = 'Пользователь с таким логином уже существует';
    }
    
    if (empty($errors)) {
        // Создаем пользователя
        $additionalData = [];
        if (!empty($email)) {
            $additionalData['email'] = $email;
        }
        
        $userId = createUser($username, $password, $additionalData);
        
        if ($userId) {
            echo "Пользователь '$username' успешно создан!";
        } else {
            echo "Ошибка при создании пользователя";
        }
    } else {
        foreach ($errors as $error) {
            echo "<div style='color: red;'>$error</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Создать пользователя</title>
</head>
<body>
    <h1>Создание нового пользователя</h1>
    <form method="POST">
        <div>
            <label>Логин:</label>
            <input type="text" name="username" required>
        </div>
        <div>
            <label>Email (опционально):</label>
            <input type="email" name="email">
        </div>
        <div>
            <label>Пароль:</label>
            <input type="password" name="password" required>
        </div>
        <div>
            <label>Подтвердите пароль:</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit">Создать пользователя</button>
    </form>
</body>
</html>