<?php
// modules/auth/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Проверяем, определена ли уже константа DB_PATH
if (!defined('DB_PATH')) {
    // Если не определена, определяем здесь (как запасной вариант)
    define('DB_PATH', __DIR__ . '/../../includes/db.sqlite3');
}

/**
 * Проверяет авторизацию пользователя и перенаправляет в зависимости от роли
 */
function checkAuth() {
    global $databaseFile;
    // Проверяем, авторизован ли пользователь
    if (!isset($_SESSION['user_id'])) {
        // Сохраняем URL, на который пытался зайти пользователь
        $_SESSION['return_url'] = $_SERVER['REQUEST_URI'];
        header('Location: /modules/auth/login.php');
        exit();
    }
    
    // Проверяем существование пользователя в БД
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT id, is_staff, is_superuser, dogovor FROM auth_user WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Пользователь не найден или неактивен
            session_destroy();
            header('Location: /modules/auth/login.php?error=session_expired');
            exit();
        }
        
        // Сохраняем информацию о пользователе в сессии
        $_SESSION['is_staff'] = $user['is_staff'] ?? 0;
        $_SESSION['is_superuser'] = $user['is_superuser'] ?? 0;
        $_SESSION['dogovor_id'] = $user['dogovor'] ?? null;
        
        // Если пользователь is_staff = 0, перенаправляем на гостевую страницу
        // (кроме случаев, когда он уже на гостевой странице или странице входа)
        $currentPage = basename($_SERVER['PHP_SELF']);
        if ($user['is_staff'] == 0 && $currentPage != 'guest.php' && $currentPage != 'login.php') {
            header('Location: /guest.php');
            exit();
        }
        
        return $user;
        
    } catch (PDOException $e) {
        // В случае ошибки БД перенаправляем на логин
        session_destroy();
        header('Location: /modules/auth/login.php?error=db_error');
        exit();
    }
}

/**
 * Проверяет, имеет ли пользователь доступ к странице
 * Если is_staff = 0, разрешает доступ только к guest.php
 */
function checkPageAccess() {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $currentPage = basename($_SERVER['PHP_SELF']);
    $isStaff = $_SESSION['is_staff'] ?? 0;
    
    // Если пользователь не является сотрудником (is_staff = 0)
    if ($isStaff == 0) {
        // Разрешаем доступ только к guest.php и странице выхода
        $allowedPages = ['guest.php', 'logout.php', 'login.php'];
        if (!in_array($currentPage, $allowedPages)) {
            header('Location: /guest.php');
            exit();
        }
    }
    
    return true;
}

/** ПРОВЕРКА НА SUPERUSER
 * Проверяет, является ли пользователь superuser
 * Если нет - перенаправляет на страницу с access_denied */
function checkSuperuser() {
    global $databaseFile;
    if (!isset($_SESSION['user_id'])) {
        header('Location: /modules/auth/login.php');
        exit();
    }
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT is_superuser FROM auth_user WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || $user['is_superuser'] != 1) {
            // Пользователь не суперпользователь
            header('Location: /modules/admin/access_denied.php');
            exit();
        }
        return true;
    } catch (PDOException $e) {
        error_log("Ошибка проверки superuser: " . $e->getMessage());
        header('Location: /modules/admin/access_denied.php');
        exit();
    }
}

/** Получает информацию о текущем пользователе */
function getCurrentUser() {
    global $databaseFile;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, is_superuser, is_staff, is_active, dogovor FROM auth_user WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Ошибка получения пользователя: " . $e->getMessage());
        return null;
    }
}

/** Проверяет login и pass пользователя
 * @param string $username Логин пользователя
 * @param string $password Пароль пользователя
 * @return array|false Массив с данными пользователя или false при ошибке */
function verifyUserCredentials($username, $password) {
    global $databaseFile;
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Ищем пользователя
        $stmt = $pdo->prepare("SELECT id, pass, is_active, is_staff, dogovor FROM auth_user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false; // Пользователь не найден
        }
        
        // Проверяем активность пользователя
        if (!$user['is_active']) {
            return false; // Пользователь неактивен
        }
        
        // Проверяем пароль с помощью password_verify
        if (password_verify($password, $user['pass'])) {
            // Пароль верный
            return [
                'id' => $user['id'],
                'username' => $username,
                'is_staff' => $user['is_staff'] ?? 0,
                'dogovor_id' => $user['dogovor'] ?? null
            ];
        }
        return false; // Неверный пароль
    } catch (PDOException $e) {
        error_log("Ошибка проверки учетных данных: " . $e->getMessage());
        return false;
    }
}

/* Обновляет время последнего входа пользователя */
function updateLastLogin($userId) {
    global $databaseFile;
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("UPDATE auth_user SET last_login = datetime('now') WHERE id = ?");
        $stmt->execute([$userId]);
        return true;
    } catch (PDOException $e) {
        error_log("Ошибка обновления last_login: " . $e->getMessage());
        return false;
    }
}

/** Создает нового user с хешированным паролем
 * @param string $username Логин
 * @param string $password Пароль
 * @param array $additionalData Дополнительные данные (email, first_name и т.д.)
 * @return int|false ID нового пользователя или false при ошибке */
function createUser($username, $password, $additionalData = []) {
    global $databaseFile;
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Хешируем пароль
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Подготавливаем данные для вставки
        $defaultData = [
            'username' => $username,
            'pass' => $hashedPassword,
            'is_active' => 1,
            'is_staff' => 0,
            'is_superuser' => 0,
            'date_joined' => date('Y-m-d H:i:s')
        ];
        $data = array_merge($defaultData, $additionalData);
        
        // Формируем SQL запрос
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $pdo->prepare("INSERT INTO auth_user ($columns) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Ошибка создания пользователя: " . $e->getMessage());
        return false;
    }
}

/** Проверяет, является ли пароль валидным (безопасным) */
function isPasswordValid($password) {
    // Минимальная длина пароля
    if (strlen($password) < 6) {
        return false;
    }
    return true;
}

/** Проверяет, существует ли пользователь с таким логином */
function userExists($username) {
    global $databaseFile;
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM auth_user WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}
?>