<?php
// modules/service/add_service.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
header('Content-Type: application/json');
// $databaseFile = 'db.sqlite3';

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Проверяем обязательные поля
    if (empty($_POST['object_id']) || empty($_POST['service_date']) || !isset($_POST['result'])) {
        echo json_encode(['success' => false, 'error' => 'Не все обязательные поля заполнены']);
        exit;
    }
    
    // Обработка загруженных файлов
    $fotoPaths = [];
    
    if (isset($_FILES['foto']) && !empty($_FILES['foto']['name'][0])) {
        $uploadDir = __DIR__ . '/../../assets/uploads/service_photos/';
        
        // Создаем директорию если не существует
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Обрабатываем все загруженные файлы
        $fileCount = count($_FILES['foto']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['foto']['error'][$i] === UPLOAD_ERR_OK) {
                // Проверяем тип файла
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileType = finfo_file($fileInfo, $_FILES['foto']['tmp_name'][$i]);
                finfo_close($fileInfo);
                
                if (!in_array($fileType, $allowedTypes)) {
                    continue;
                }
                
                // Генерируем уникальное имя файла
                $originalName = basename($_FILES['foto']['name'][$i]);
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $fileName = time() . '_' . uniqid() . '.' . strtolower($extension);
                $targetPath = $uploadDir . $fileName;
                
                // Перемещаем файл
                if (move_uploaded_file($_FILES['foto']['tmp_name'][$i], $targetPath)) {
                    // Сохраняем путь для веб-доступа
                    // $fotoPaths[] = $targetPath;
                    $fotoPaths[] = $fileName;
                }
            }
        }
    }
    
    // Преобразуем массив путей в JSON строку
    $fotoString = !empty($fotoPaths) ? json_encode($fotoPaths) : null;
    
    // Вставляем новую запись
    $stmt = $pdo->prepare("
        INSERT INTO LIFTEH_service (object_id, service_date, comments, user_id, result, foto)
        VALUES (:object_id, :service_date, :comments, :user_id, :result, :foto)
    ");
    
    $result = $stmt->execute([
        ':object_id' => $_POST['object_id'],
        ':service_date' => $_POST['service_date'],
        ':comments' => $_POST['comments'] ?? null,
        ':user_id' => $_POST['user_id'] ?? 1,
        ':result' => $_POST['result'],
        ':foto' => $fotoString
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'id' => $pdo->lastInsertId(),
            'foto_count' => count($fotoPaths),
            'fotos' => $fotoPaths
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ошибка при сохранении в БД']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>