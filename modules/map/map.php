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

    <!-- Подключаем JavaScript файлы -->
    <script src="/assets/js/balloon-template.js"></script>
    <script src="/assets/js/service-history-functions.js"></script>
    <script src="/assets/js/foto-modal.js"></script>

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

    // Функция для обновления статистики
    function updateStatusStats(resultValue) {
        // resultValue может быть строкой или числом
        const resultKey = String(resultValue);

        switch(resultKey) {
            case '0': // Работают
                statusStats['0'] += 1;
                document.getElementById('statWorking').textContent = statusStats['0'];
                document.getElementById('statWorking').style.color = '#4CAF50';
                break;
            case '1': // Замечания
                statusStats['1'] += 1;
                document.getElementById('statRemarks').textContent = statusStats['1'];
                document.getElementById('statRemarks').style.color = '#FF9800';
                break;
            case '2': // Не работают
                statusStats['2'] += 1;
                document.getElementById('statNotWorking').textContent = statusStats['2'];
                document.getElementById('statNotWorking').style.color = '#f44336';
                break;
            // default:
            //     console.warn('Unknown result value:', resultValue);
        }
    }

    // Глобальная функция для открытия модального окна
    function openServiceModal(objectId, customer, address) {
        console.log('Открытие модального окна ТО с карты:', { objectId, customer, address });

        // Заполняем форму
        document.getElementById('serviceObjectId').value = objectId;
        document.getElementById('serviceCustomer').value = customer || '';
        document.getElementById('serviceAddress').value = address || '';

        // Устанавливаем текущую дату
        const now = new Date();
        const tzOffset = now.getTimezoneOffset() * 60000;
        const localDate = new Date(now.getTime() - tzOffset);
        document.getElementById('serviceDate').value = localDate.toISOString().slice(0, 16);

        // Сбрасываем форму
        document.getElementById('serviceResult').value = '';
        document.getElementById('serviceComments').value = '';
        document.getElementById('fileUploadContainer').style.display = 'none';
        document.getElementById('serviceFoto').value = '';

        // Загружаем историю ТО
        loadServiceHistory(objectId);

        // Показываем модальное окно
        document.getElementById('serviceModal').style.display = 'block';
    }


    // Функция для открытия обслуживания из балуна
    function openServiceFromMap(objectId) {
        const obj = objectsData.find(item => item.id == objectId);
        if (obj) {
            console.log('Открытие ТО для:', obj.customer);
            openServiceModal(obj.id, obj.customer, obj.address);
        }
    }


    // Функция для обновления прогресс-бара после добавления ТО
    function updateProgressBar(resultValue) {
        // Увеличиваем счетчик выполненных ТО
        const newCompleted = completedObjects + 1;
        const newRemaining = Math.max(0, totalObjects - newCompleted);
        const newPercentage = totalObjects > 0 ? Math.min(100, (newCompleted / totalObjects) * 100) : 0;

        // Обновляем элементы прогресс-бара
        document.getElementById('progressPercentage').textContent = newPercentage.toFixed(1) + '%';
        document.getElementById('progressFill').style.width = newPercentage.toFixed(1) + '%';
        document.getElementById('progressText').textContent = newCompleted + ' / ' + totalObjects;

        // Обновляем статистику выполнено/осталось
        const statsElements = document.querySelectorAll('.stat-value');
        if (statsElements.length >= 7) {
            statsElements[1].textContent = newCompleted; // Выполнено
            statsElements[1].style.color = '#4CAF50';
            statsElements[5].textContent = newRemaining; // Осталось
            statsElements[5].style.color = '#f44336';
        }

        // Обновляем статистику по конкретному результату
        updateStatusStats(resultValue);
    }


    // Обработчик изменения результата
    function handleResultChange() {
        const resultSelect = document.getElementById('serviceResult');
        const commentsTextarea = document.getElementById('serviceComments');
        const fileUploadContainer = document.getElementById('fileUploadContainer');
        const selectedValue = resultSelect.value;
        const selectedText = resultSelect.options[resultSelect.selectedIndex].text;

        // Автоматически обновляем комментарии
        const previousResultTexts = ['В исправном состоянии', 'Требуется устранить замечания', 'Не работает'];
        const currentComment = commentsTextarea.value.trim();
        const containsPreviousResult = previousResultTexts.some(text => currentComment === text);

        if (selectedValue && (currentComment === '' || containsPreviousResult)) {
            commentsTextarea.value = selectedText;
        }

        // Показываем/скрываем поле для загрузки файлов
        if (selectedValue === '1' || selectedValue === '2') {
            fileUploadContainer.style.display = 'block';
        } else {
            fileUploadContainer.style.display = 'none';
        }
    }


    // Функция закрытия модального окна
    function closeServiceModal() {
        document.getElementById('serviceModal').style.display = 'none';
        document.getElementById('serviceForm').reset();
        document.getElementById('serviceHistory').innerHTML = '<div class="no-services">Загрузка истории...</div>';
    }


    // Добавление записи о ТО
    function addServiceRecord() {
        const form = document.getElementById('serviceForm');
        const formData = new FormData(form);

        // Получаем значение результата до отправки
        const resultValue = document.getElementById('serviceResult').value;

        // Добавляем дополнительные данные
        formData.append('user_id', 1);

        // Получаем дату и преобразуем
        const dateInput = document.getElementById('serviceDate');
        if (dateInput.value) {
            const formattedDate = dateInput.value.replace('T', ' ') + ':00';
            formData.set('service_date', formattedDate);
        }

        // Показываем индикатор загрузки
        const loadingDiv = document.getElementById('loading');
        if (loadingDiv) loadingDiv.style.display = 'block';

        // Отправка данных
        fetch('/../../modules/service/add_service.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            // if (result.success) {
            //     // Обновляем прогресс-бар с учетом результата
            //     updateProgressBar(resultValue);
            //     alert('Запись о ТО успешно добавлена!');
            //     closeServiceModal();
            // } else {
            //     alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
            // }
                    if (loadingDiv) loadingDiv.style.display = 'none';

            if (result.success) {
                // Обновляем прогресс-бар с учетом результата
                updateProgressBar(resultValue);
                
                // Показываем сообщение об успехе
                showMapMessage('Запись о ТО успешно добавлена!', 'success');
                
                // Закрываем модальное окно
                closeServiceModal();
            } else {
                showMapMessage('Ошибка: ' + (result.error || 'Неизвестная ошибка'), 'error');
            }
        })
        .catch(error => {
            // alert('Ошибка сети: ' + error.message);
            if (loadingDiv) loadingDiv.style.display = 'none';
            showMapMessage('Ошибка сети: ' + error.message, 'error');
        });
    }


    // Функция для показа сообщений на карте
    function showMapMessage(text, type) {
        // Проверяем, есть ли уже контейнер для сообщений
        let messageContainer = document.getElementById('mapMessage');
        
        if (!messageContainer) {
            // Создаем контейнер для сообщений
            messageContainer = document.createElement('div');
            messageContainer.id = 'mapMessage';
            messageContainer.className = 'map-message';
            document.body.appendChild(messageContainer);
        }
        
        // Устанавливаем текст и тип сообщения
        messageContainer.textContent = text;
        messageContainer.className = `map-message ${type}`;
        messageContainer.style.display = 'block';
        
        // Автоматически скрываем через 3 секунды
        setTimeout(() => {
            messageContainer.style.display = 'none';
        }, 3000);
    }


    // Карта и связанные функции
    let myMap;
    let clusterer;
    let objectPlacemarks = [];
    
    // Открыть маршрут в Яндекс.Картах
    function openRoute(lat, lng) {
        const url = `https://yandex.ru/maps/?rtext=~${lat},${lng}&rtt=auto`;
        window.open(url, '_blank');
    }
    
    // Функция инициализации карты
    ymaps.ready(function() {
        initMap();
    });
    
    async function initMap() {
        document.getElementById('loading').style.display = 'none';
        myMap = new ymaps.Map('map', {
            center: [53.902365, 27.561709],
            zoom: 7,
            controls: ['zoomControl', 'fullscreenControl']
        });
        
        clusterer = new ymaps.Clusterer({
            preset: 'islands#invertedBlueClusterIcons',
            clusterDisableClickZoom: true,
            clusterOpenBalloonOnClick: true,
            gridSize: 64,
            maxZoom: 17
        });
        
        myMap.geoObjects.add(clusterer);
        await addObjectsToMap();

        // Закрытие балунов по клику на карту
        myMap.events.add('click', function (e) {
            myMap.balloon.close();
            objectPlacemarks.forEach(function(placemark) {
                if (placemark.balloon && placemark.balloon.isOpen()) {
                    placemark.balloon.close();
                }
            });
            if (clusterer.balloon && clusterer.balloon.isOpen()) {
                clusterer.balloon.close();
            }
        });
    }


    // Добавление объектов на карту
    function addObjectsToMap() {
        return new Promise((resolve) => {
            objectPlacemarks = [];
            objectsData.forEach((obj, index) => {
                if (obj.lat && obj.lng) {
                    const coordinates = [obj.lat, obj.lng];
                    const placemark = createPlacemark(obj, coordinates, index);
                    objectPlacemarks.push(placemark);
                    clusterer.add(placemark);
                }
            });
            setTimeout(resolve, 300);
        });
    }


    // Создание метки
    function createPlacemark(obj, coordinates, index) {
        let phoneLink = '';
        if (obj.phone) {
            const cleanPhone = obj.phone.replace(/\D/g, '');
            phoneLink = cleanPhone.startsWith('375') ? '+' + cleanPhone : '+375' + cleanPhone;
        }

        const template = generateBalloonTemplate(obj, coordinates, phoneLink);
        const placemark = new ymaps.Placemark(coordinates, {
            balloonContentHeader: template.header,
            balloonContentBody: template.body,
            hintContent: obj.address || obj.customer || 'Объект'
        }, {
            preset: 'islands#blueCircleDotIcon',
            balloonCloseButton: true,
            balloonAutoPan: true
        });
        return placemark;
    }


    // Инициализация формы после загрузки DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Обработчик отправки формы
        const serviceForm = document.getElementById('serviceForm');
        if (serviceForm) {
            serviceForm.addEventListener('submit', function(e) {
                e.preventDefault();
                addServiceRecord();
            });
        }

        // Обработчик изменения результата
        const resultSelect = document.getElementById('serviceResult');
        if (resultSelect) {
            resultSelect.addEventListener('change', handleResultChange);
        }

        // Закрытие по клику вне окна
        const serviceModal = document.getElementById('serviceModal');
        if (serviceModal) {
            serviceModal.addEventListener('click', function(e) {
                if (e.target === serviceModal) {
                    closeServiceModal();
                }
            });
        }

        // Горячие клавиши
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const serviceModal = document.getElementById('serviceModal');
                if (serviceModal && serviceModal.style.display === 'block') {
                    closeServiceModal();
                }

                const fotoModal = document.getElementById('fotoModal');
                if (fotoModal && fotoModal.style.display === 'block') {
                    if (typeof closeFotoModal === 'function') {
                        closeFotoModal();
                    }
                }
            }
        });
    });
</script>

</body>
</html>