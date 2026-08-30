<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$dbPath = __DIR__ . '/../includes/db.sqlite3';
if (!file_exists($dbPath)) {
    echo json_encode(['success' => false, 'error' => 'База данных не найдена']);
    exit;
}

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем объекты с координатами
    $sql = "SELECT id, customer, address, model, phone, 
                   latitude, longitude, name 
            FROM LIFTEH_object 
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND latitude != '' 
              AND longitude != ''";
    $stmt = $db->query($sql);
    $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($objects as $obj) {
        $lat = str_replace(',', '.', trim($obj['latitude']));
        $lng = str_replace(',', '.', trim($obj['longitude']));
        
        if (is_numeric($lat) && is_numeric($lng)) {
            $result[] = [
                'id' => (int)$obj['id'],
                'customer' => $obj['customer'] ?? '',
                'address' => $obj['address'] ?? '',
                'model' => $obj['model'] ?? '',
                'phone' => $obj['phone'] ?? '',
                'name' => $obj['name'] ?? '',
                'lat' => (float)$lat,
                'lng' => (float)$lng,
                'status' => 0 // По умолчанию работает
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'count' => count($result)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>