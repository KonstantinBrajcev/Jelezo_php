<?php
// object_handler.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();

header('Content-Type: application/json');

// Определяем действие
$action = $_REQUEST['action'] ?? '';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    switch ($action) {
        case 'get_by_dogovor':
            // Получить объекты по договору
            $dogovor_id = $_GET['dogovor_id'] ?? 0;
            
            $sql = "SELECT id, name, address FROM LIFTEH_object WHERE dogovor_id = :dogovor_id ORDER BY name";
            $stmt = $db->prepare($sql);
            $stmt->execute([':dogovor_id' => $dogovor_id]);
            $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'objects' => $objects,
                'count' => count($objects)
            ]);
            break;
            
        case 'get_all':
            // Получить все объекты с разделением на привязанные/непривязанные
            $current_dogovor_id = $_GET['current_dogovor_id'] ?? 0;
            
            // Получаем привязанные объекты (к текущему договору)
            $sql_linked = "SELECT * FROM LIFTEH_object WHERE dogovor_id = :dogovor_id ORDER BY name";
            $stmt_linked = $db->prepare($sql_linked);
            $stmt_linked->execute([':dogovor_id' => $current_dogovor_id]);
            $linked = $stmt_linked->fetchAll(PDO::FETCH_ASSOC);
            
            // Получаем непривязанные объекты (где dogovor_id = NULL)
            $sql_unlinked = "SELECT * FROM LIFTEH_object WHERE dogovor_id IS NULL ORDER BY name";
            $stmt_unlinked = $db->query($sql_unlinked);
            $unlinked = $stmt_unlinked->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'linked' => $linked,
                'unlinked' => $unlinked
            ]);
            break;
            
        case 'add':
            // Добавить новый объект
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не поддерживается');
            }
            
            $dogovor_id = $_POST['dogovor_id'] ?? '';
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            
            if (empty($dogovor_id) || empty($name)) {
                throw new Exception('Заполните обязательные поля');
            }
            
            $sql = "INSERT INTO LIFTEH_object (dogovor_id, name, address) VALUES (:dogovor_id, :name, :address)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':dogovor_id' => $dogovor_id,
                ':name' => $name,
                ':address' => $address
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Объект успешно добавлен',
                'id' => $db->lastInsertId()
            ]);
            break;
            
        case 'link':
            // Привязать существующий объект к договору
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не поддерживается');
            }
            
            $object_id = $_POST['object_id'] ?? 0;
            $dogovor_id = $_POST['dogovor_id'] ?? 0;
            
            if (!$object_id || !$dogovor_id) {
                throw new Exception('Не указаны ID');
            }
            
            $sql = "UPDATE LIFTEH_object SET dogovor_id = :dogovor_id WHERE id = :object_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':dogovor_id' => $dogovor_id,
                ':object_id' => $object_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Объект привязан к договору'
            ]);
            break;
            
        case 'unlink':
            // Отвязать объект от договора
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Метод не поддерживается');
            }
            
            $object_id = $_POST['object_id'] ?? 0;
            
            if (!$object_id) {
                throw new Exception('Не указан ID объекта');
            }
            
            $sql = "UPDATE LIFTEH_object SET dogovor_id = NULL WHERE id = :object_id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':object_id' => $object_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Объект отвязан от договора'
            ]);
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>