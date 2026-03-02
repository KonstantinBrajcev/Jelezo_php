<?php
// modules/admin/access_denied.php
require_once __DIR__ . '/../../modules/auth/auth.php';

// Если пользователь не авторизован - отправляем на логин
if (!isset($_SESSION['user_id'])) {
    header('Location: /modules/auth/login.php');
    exit();
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ запрещен</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 50px 40px;
            text-align: center;
        }
        
        .error-icon {
            font-size: 64px;
            color: #ff6b6b;
            margin-bottom: 20px;
        }
        
        .error-title {
            color: #333;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .user-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🚫</div>
        <h1 class="error-title">Доступ запрещен</h1>
        <div class="error-message">
            У вас нет прав для просмотра этой страницы.<br>
            Доступ разрешен только администраторам системы.
        </div>
        
        <?php if ($currentUser): ?>
        <div class="user-info">
            Вы вошли как: <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong><br>
            Ваш уровень доступа: обычный пользователь
        </div>
        <?php endif; ?>
        
        <a href="/index.php" class="btn">Вернуться на главную</a>
    </div>
</body>
</html>