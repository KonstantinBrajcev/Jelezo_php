<?php
// modules/map/map.php
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();

$currentUser = getCurrentUser();

// Очистить буфер и отправить заголовки
if (ob_get_length()) ob_end_clean();
header('Content-Type: text/html; charset=utf-8');

// map.php - Карта с использованием координат из БД
// $databaseFile = 'db.sqlite3';
$currentMonth = (int)date('n');
$monthColumn = 'M' . $currentMonth;

try {
    $pdo = new PDO("sqlite:" . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    // 1. Общее количество объектов в текущем месяце
    $sqlTotal = "SELECT COUNT(*) as total 
                FROM LIFTEH_object o
                WHERE o.$monthColumn IS NOT NULL 
                    AND o.$monthColumn != '' 
                    AND CAST(o.$monthColumn AS TEXT) != '0' ";
    
    $stmtTotal = $pdo->query($sqlTotal);
    $totalResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    $totalObjects = (int)$totalResult['total'];


    // 2. Количество объектов с выполненным ТО в текущем месяце
    $sqlCompleted = "SELECT COUNT(DISTINCT s.object_id) as completed 
        FROM LIFTEH_service s
        INNER JOIN LIFTEH_object o ON s.object_id = o.id
        WHERE o.$monthColumn IS NOT NULL 
            AND o.$monthColumn != '' 
            AND CAST(o.$monthColumn AS TEXT) != '0'
            AND strftime('%Y-%m', s.service_date) = strftime('%Y-%m', 'now')";

    $stmtCompleted = $pdo->query($sqlCompleted);
    $completedResult = $stmtCompleted->fetch(PDO::FETCH_ASSOC);
    $completedObjects = (int)$completedResult['completed'];


    // 3. Статистика по результатам ТО за текущий месяц (только по последним записям)
    $sqlStats = "SELECT s.result,
                    COUNT(DISTINCT s.object_id) as count
                FROM LIFTEH_service s
                INNER JOIN (SELECT 
                        object_id,
                        MAX(service_date) as max_date
                    FROM LIFTEH_service
                    GROUP BY object_id
                ) last_service ON s.object_id = last_service.object_id 
                    AND s.service_date = last_service.max_date
                INNER JOIN LIFTEH_object o ON s.object_id = o.id
                WHERE o.$monthColumn IS NOT NULL 
                    AND o.$monthColumn != '' 
                    AND CAST(o.$monthColumn AS TEXT) != '0'
                    AND strftime('%Y-%m', s.service_date) = strftime('%Y-%m', 'now')
                GROUP BY s.result";

    $stmtStats = $pdo->query($sqlStats);
    $statsResult = $stmtStats->fetchAll(PDO::FETCH_ASSOC);

    // Инициализируем счетчики с учетом, что 0 = Работают
    $statusCounts = ['0' => 0, '1' => 0, '2' => 0];

    // Заполняем полученными данными
    foreach ($statsResult as $row) {
        $statusCounts[$row['result']] = $row['count'];
    }

    // Для удобства переименуем переменные
    $workingCount = $statusCounts['0'];    // Работают
    $remarksCount = $statusCounts['1'];    // Замечания
    $notWorkingCount = $statusCounts['2']; // Не работают


    // 4. Объекты для отображения на карте (без ТО в текущем месяце)
    $sql = "SELECT o.* 
            FROM LIFTEH_object o
            LEFT JOIN LIFTEH_service s ON o.id = s.object_id 
                AND strftime('%Y-%m', s.service_date) = strftime('%Y-%m', 'now')
            WHERE o.$monthColumn IS NOT NULL 
                AND o.$monthColumn != '' 
                AND CAST(o.$monthColumn AS TEXT) != '0'
                AND s.id IS NULL ";

    $stmt = $pdo->query($sql);
    $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('<div class="error">Ошибка базы данных: ' . $e->getMessage() . '</div>');
}

// Подготовка данных для JavaScript с отладкой
$mapData = [];
$withCoords = 0;
$withoutCoords = 0;

foreach ($objects as $obj) {
    // Берем координаты
    $lat = $obj['latitude'] ?? null;
    $lng = $obj['longitude'] ?? null;
    
    // Проверяем, что координаты не пустые
    if (!empty($lat) && !empty($lng) && trim($lat) !== '' && trim($lng) !== '') {
        // Нормализуем координаты (на всякий случай)
        $lat = str_replace(',', '.', trim($lat));
        $lng = str_replace(',', '.', trim($lng));
        
        // Проверяем, что это числа
        if (is_numeric($lat) && is_numeric($lng)) {
            $mapData[] = [
                'id' => $obj['id'] ?? null,
                'customer' => $obj['customer'] ?? '',
                'address' => $obj['address'] ?? '',
                'model' => $obj['model'] ?? '',
                'phone' => $obj['phone'] ?? '',
                'name' => $obj['name'] ?? '',
                'lat' => (float)$lat,
                'lng' => (float)$lng
            ];
            $withCoords++;
        } else {
            $withoutCoords++;
        }
    } else {
        $withoutCoords++;
    }
}

$mapDataJson = json_encode($mapData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
if ($mapDataJson === false) {
    $mapDataJson = '[]';
}
?>



<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Карта объектов'; include __DIR__ . '/../../includes/header.php'; ?>
<style>
    /* Стили для сообщений на карте */
.map-message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 24px;
    border-radius: 4px;
    color: white;
    font-size: 14px;
    z-index: 10000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease;
    max-width: 300px;
}

.map-message.success {
    background-color: #4CAF50;
}

.map-message.error {
    background-color: #f44336;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
<body>

    <?php include __DIR__ . '/../../modules/service/service-modal.php'; ?>
    <?php include __DIR__ . '/../../modules/service/foto-modal.php'; ?>

    <!-- Карта -->
    <div id="map"></div>

    <!-- Панель управления -->
    <div class="map-controls">
        <a href="/index.php" class="control-button back-button">
            <i class="fas fa-table"></i>
        </a>
    </div>

    <!-- Прогресс-бар внизу страницы -->
    <div class="progress-container" id="progressContainer">
        <div class="progress-header">
            <div class="progress-title">Прогресс за <?php echo date('F Y'); ?></div>
            <div class="progress-percentage" id="progressPercentage">
                <?php 
                if ($totalObjects > 0) {
                    $percentage = round(($completedObjects / $totalObjects) * 100, 1);
                    echo $percentage . '%';
                } else { echo '0%'; }
                ?>
            </div>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 
                <?php 
                if ($totalObjects > 0) {
                    echo round(($completedObjects / $totalObjects) * 100, 1);
                } else { echo 0; }
                ?>%">
                <div class="progress-text" id="progressText">
                    <?php 
                    if ($totalObjects > 0) {
                        echo $completedObjects . ' / ' . $totalObjects;
                    } else { echo '0 / 0'; }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="progress-stats">
            <div class="progress-stat">
                <span class="stat-label">Всего объектов</span>
                <span class="stat-value status-total"><?php echo $totalObjects; ?></span>
            </div>
            <div class="progress-stat">
                <span class="stat-label">Выполнено ТО</span>
                <span class="stat-value status-working"><?php echo $completedObjects; ?></span>
            </div>
            <div class="progress-stat">
            <span class="stat-label">Статусы ТО</span>
                <div class="stat-values">
                    <span class="stat-value status-working" id="statWorking"><?php echo $workingCount; ?></span>
                    <span class="stat-separator">/</span>
                    <span class="stat-value status-remarks" id="statRemarks"><?php echo $remarksCount; ?></span>
                    <span class="stat-separator">/</span>
                    <span class="stat-value status-not-working" id="statNotWorking"><?php echo $notWorkingCount; ?></span>
                </div>
            </div>
            <div class="progress-stat">
                <span class="stat-label">Осталось</span>
                <span class="stat-value status-remaining"><?php echo $totalObjects - $completedObjects; ?></span>
            </div>
        </div>
    </div>

    <!-- Индикатор загрузки -->
    <div class="loading" id="loading">
        Загрузка карты...
    </div>

<script>
    // Данные объектов из PHP
    const objectsData = <?php echo $mapDataJson; ?>;

    // Переменные для прогресс-бара
    const totalObjects = <?php echo $totalObjects; ?>;
    const completedObjects = <?php echo $completedObjects; ?>;
    const currentPercentage = <?php echo ($totalObjects > 0) ? round(($completedObjects / $totalObjects) * 100, 1) : 0; ?>;

    // Статистика по результатам ТО
    const statusStats = {
        '0': <?php echo $workingCount; ?>,   // Работают
        '1': <?php echo $remarksCount; ?>,   // Замечания
        '2': <?php echo $notWorkingCount; ?> // Не работают
    };
</script>

<!-- Подключаем JavaScript файлы -->
<script src="/assets/js/balloon-template.js"></script>
<script src="/assets/js/service-history-functions.js"></script>
<script src="/assets/js/foto-modal.js"></script>
<script src="/assets/js/map.js"></script>

</body>
</html>