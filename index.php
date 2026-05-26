<?php
// index.php
// $currentRoute = 'index';
require_once __DIR__ . '/modules/auth/auth.php';
checkAuth();
// Получаем информацию о текущем пользователе
$currentUser = getCurrentUser();
// Определяем, является ли пользователь суперпользователем
$isSuperAdmin = ($currentUser['is_superuser'] ?? 0) == 1;
// Настройки подключения к базе данных
$databaseFile = __DIR__ . '/includes/db.sqlite3';
// Определяем текущий месяц (1-12)
$currentMonth = (int)date('n'); // n - номер месяца без ведущего нуля
// Определяем название колонки для текущего месяца
// $monthColumn = 'M' . $currentMonth;


// Получаем выбранный месяц из GET параметра или используем текущий
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
// Ограничиваем значение от 1 до 12
$selectedMonth = max(1, min(12, $selectedMonth));

$selectedMonthColumn = 'M' . $selectedMonth;

try {
    // Создаем подключение через PDO
    $pdo = new PDO("sqlite:" . DB_PATH);
    // Устанавливаем режим ошибок
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Проверяем существование таблицы
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='LIFTEH_object'")->fetch();

    if (!$tableExists) {
        die('<div class="error">Таблица "LIFTEH_object" не существует в базе данных</div>');
    }

    // Получаем все колонки таблицы для редактирования
    $columnsQuery = $pdo->query("PRAGMA table_info(LIFTEH_object)");
    $allColumns = $columnsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Получаем только те записи, где в колонке текущего месяца есть цифра
    // ВЫБИРАЕМ ВСЕ СТОЛБЦЫ ДЛЯ РЕДАКТИРОВАНИЯ
    $sql = "SELECT 
            LIFTEH_object. *,
            LIFTEH_dogovor.number as dogovor_number
        FROM LIFTEH_object 
        LEFT JOIN LIFTEH_dogovor ON LIFTEH_object.dogovor_id = LIFTEH_dogovor.id
            WHERE $selectedMonthColumn IS NOT NULL 
            AND $selectedMonthColumn != '' 
            AND CAST($selectedMonthColumn AS TEXT) != '0'";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('<div class="error">Ошибка подключения к базе данных: ' . $e->getMessage() . '</div>');
}

// Массив месяцев для отображения
$months = [
    1 => 'январь', 2 => 'февраль', 3 => 'март',
    4 => 'апрель', 5 => 'май', 6 => 'июнь',
    7 => 'июль', 8 => 'август', 9 => 'сентябрь',
    10 => 'октябрь', 11 => 'ноябрь', 12 => 'декабрь'
];

$selectedMonthName = $months[$selectedMonth] ?? 'неизвестный месяц';

$currentMonthName = $months[$currentMonth] ?? 'неизвестный месяц';
$idField = 'id'; // Получаем ID поля


// Получаем информацию о сервисах для каждого объекта
$serviceInfo = [];
try {
    // Получаем последний сервис ДО или В выбранном месяце
    $lastDayOfMonth = date('Y-m-t', mktime(0, 0, 0, $selectedMonth, 1, date('Y')));
    
    // ИСПРАВЛЕННЫЙ ЗАПРОС - без вложенного MAX()
    $serviceStmt = $pdo->prepare("
        SELECT 
            object_id,
            service_date as last_service,
            result as last_result,
            COUNT(*) OVER (PARTITION BY object_id) as service_count,
            strftime('%m', service_date) as last_service_month,
            strftime('%Y', service_date) as last_service_year
        FROM LIFTEH_service 
        WHERE service_date <= :lastDayOfMonth
        AND (object_id, service_date) IN (
            SELECT object_id, MAX(service_date)
            FROM LIFTEH_service
            WHERE service_date <= :lastDayOfMonth2
            GROUP BY object_id
        )
    ");
    
    $serviceStmt->execute([
        ':lastDayOfMonth' => $lastDayOfMonth,
        ':lastDayOfMonth2' => $lastDayOfMonth
    ]);
    $serviceData = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($serviceData as $service) {
        $serviceInfo[$service['object_id']] = $service;
    }
    
    // Отладка
    // echo '<div class="debug" style="background: #e3f2fd; padding: 10px; margin: 10px; font-size: 12px;">';
    // echo 'Найдено объектов с сервисами: ' . count($serviceInfo) . '<br>';
    // if (count($serviceInfo) > 0) {
        // $first = reset($serviceInfo);
        // echo 'Пример: object_id=' . key($serviceInfo) . ', дата=' . $first['last_service'] . ', результат=' . $first['last_result'];
    // }
    // echo '</div>';
    
} catch (Exception $e) {
    // echo '<div class="error" style="background: #ffd5d5; padding: 10px; margin: 10px;">';
    // echo 'Ошибка получения сервисов: ' . $e->getMessage();
    // echo '</div>';
}
?>







<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Список объектов'; include 'includes/header.php'; ?>
<body>


<?php include 'modules/admin/user_panel.php'; ?>


    <div class="container">




        

        <!-- Верхняя панель с кнопками -->
        <div class="controls">
            <div class="button-group">

                <a href="map.php" class="map-button">
                    <span class="button-text">Карта</span>
                    <i class="fas fa-map"></i>
                </a>
                
                <a href="problem.php" class="map-button">
                    <span class="button-text">Проблемы</span>
                    <i class="fas fa-list"></i>
                </a>



                <!-- ТОЛЬКО ДЛЯ АДМИНОВ -->
                <?php if ($isSuperAdmin): ?>

                    <a href="dogovor.php" class="map-button">
                        <span class="button-text">Договора</span>
                        <i class="fas fa-file"></i>
                    </a>

                    <a href="charts.php" class="map-button">
                        <span class="button-text">Графики</span>
                        <i class="fas fa-chart-line"></i>
                    </a>

                    <a href="avr.php" class="map-button">
                        <span class="button-text">АВР</span>
                        <i class="fa-solid fa-gear"></i>
                    </a>

                    <button type="button" class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i><!-- Добавление -->
                    </button>

                <?php endif; ?>



                <!-- Кнопка фильтра по цвету -->
                <div class="filter-dropdown">
                    <button type="button" class="btn btn-filter" onclick="toggleFilterDropdown()">
                        <i class="fas fa-filter"></i>
                        <span id="filterBadge" class="filter-badge" style="display: none;">●</span>
                    </button>
                    <div id="filterMenu" class="dropdown-menu">
                        <div class="dropdown-item" onclick="applyColorFilter('all')">
                            <span class="color-indicator all"></span>
                            Все записи
                        </div>
                        <div class="dropdown-item" onclick="applyColorFilter('green')">
                            <span class="color-indicator green"></span>Зеленый
                        </div>
                        <div class="dropdown-item" onclick="applyColorFilter('yellow')">
                            <span class="color-indicator yellow"></span>Желтый
                        </div>
                        <div class="dropdown-item" onclick="applyColorFilter('red')">
                            <span class="color-indicator red"></span>Красный
                        </div>
                        <div class="dropdown-item" onclick="applyColorFilter('none')">
                            <span class="color-indicator none"></span>Без цвета
                        </div>
                    </div>
                </div>





            </div>
        </div>


        <!-- Селектор месяца -->
        <div class="month-selector">
            <label for="monthSelect" class="month-label">
                <i class="fas fa-calendar-alt"></i> Месяц:
            </label>
            <select id="monthSelect" class="month-select">
                <?php foreach ($months as $num => $name): ?>
                    <option value="<?php echo $num; ?>" <?php echo $selectedMonth == $num ? 'selected' : ''; ?>>
                        <?php echo $name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <!-- <span class="month-hint">(состояние на последний день месяца)</span> -->
            Отображено состояние объектов на 
                <strong><?php echo $selectedMonthName, ' ' , date('Y'); ?></strong> года
                <?php if ($selectedMonth != (int)date('n')): ?>
                    <span class="warning-badge" style="font-weight: bold; color: red;">
                        <i class="fas fa-info-circle"></i> 
                        (не текущий месяц)
                    </span>
                    <span class="month-hint">(состояние на последний день месяца)</span>
                <?php endif; ?>
        </div>


        <div id="message" class="message">
            <!-- Тут будут сообщения -->
        </div>

        <!-- Индикатор загрузки -->
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> Сохранение...
        </div>

        <?php if (count($rows) > 0): ?>

<!-- Временная отладка - удалите после проверки -->
<!-- <div style="background: #f0f0f0; padding: 10px; margin: 10px; font-size: 12px;">
    <strong>Отладка:</strong><br>
    Выбранный месяц: <?php echo $selectedMonth; ?> (<?php echo $selectedMonthColumn; ?>)<br>
    Количество записей: <?php echo count($rows); ?><br>
    Количество сервисов: <?php echo count($serviceInfo); ?><br>
    <?php if (!empty($serviceInfo)): ?>
        Пример сервиса: <?php echo htmlspecialchars(print_r(array_slice($serviceInfo, 0, 1, true), true)); ?>
    <?php endif; ?>
</div> -->

        <table id="sortableTable">
            <thead> <!--- HEAD таблицы --->
                <tr>
                    <th data-column="6" data-sort="none">№</th>
                    <th data-column="0" data-sort="none">Заказчик</th>
                    <th data-column="1" data-sort="none">Адрес</th>
                    <th data-column="2" data-sort="none">Модель</th>
                    <th data-column="3" data-sort="none">Телефон</th>
                    <th data-column="4" data-sort="none">Имя</th>
                    <th data-column="5" data-sort="none"></th>
                </tr>
            </thead>
            <tbody> <!--- Тело таблицы --->

                <?php foreach ($rows as $index => $row): 
                // Определяем переменные внутри цикла
                $objectId = $row[$idField] ?? $index;
                $hasServices = isset($serviceInfo[$objectId]);
                $lastService = $hasServices ? $serviceInfo[$objectId]['last_service'] : null;
                $serviceCount = $hasServices ? $serviceInfo[$objectId]['service_count'] : 0;
                $lastResult = $hasServices ? $serviceInfo[$objectId]['last_result'] : null;
                $lastServiceMonth = $hasServices ? (int)$serviceInfo[$objectId]['last_service_month'] : null;

                $lastServiceYear = $hasServices ? $serviceInfo[$objectId]['last_service_year'] : null;
                $currentYear = date('Y');
                
                // Определяем класс для цвета строки
                $rowClass = '';

                // Получаем значение из колонки выбранного месяца (статус объекта)
                $monthStatus = $row[$selectedMonthColumn] ?? null;
                            
                // Приоритет 1: Если есть проблемы в выбранном месяце (красный)
                if ($monthStatus == 2 || $monthStatus === '2') {
                    $rowClass = 'background-color: #ffd5d5 !important;'; // Красный - проблемы
                } 
                // Приоритет 2: Если объект не работает в выбранном месяце (красный)
                elseif ($monthStatus == 0 || $monthStatus === '0') {
                    $rowClass = 'background-color: #ffd5d5 !important;'; // Красный - не работает
                }
                // Приоритет 3: Если было ТО в выбранном месяце и результат плохой
                elseif ($hasServices && 
                        $lastServiceMonth !== null && 
                        $lastResult !== null && 
                        $lastServiceMonth == $selectedMonth &&
                        $lastServiceYear == $currentYear) {
                    switch ($lastResult) {
                        case '0':
                            $rowClass = 'background-color: #d4edda !important;'; // Зеленый - успешное ТО
                            break;
                        case '1':
                            $rowClass = 'background-color: #fffed9 !important;'; // Желтый - есть замечания
                            break;
                        case '2':
                            $rowClass = 'background-color: #ffd5d5 !important;'; // Красный - серьезные проблемы
                            break;
                    }
                }
                // Приоритет 4: Проверяем статус из колонки месяца (если не учтен выше)
                elseif ($monthStatus == 1 || $monthStatus === '1') {
                    // Обычный статус без ТО - оставляем без цвета или желтый
                    // Можно добавить дополнительную логику
                }
                ?>

                <!-- Строка Таблицы Обьектов -->
                <tr data-id="<?php echo htmlspecialchars($objectId); ?>" style="<?php echo $rowClass; ?>">

                    <!-- Колонка ДОГОВОР -->
                    <td style="text-align: center;" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                        <?php 
                        $dogovorNumber = $row['dogovor_number'] ?? '';
                        if (strlen($dogovorNumber) > 6) {
                            echo htmlspecialchars(substr($dogovorNumber, 0, 6) . '...');
                        } else {
                            echo htmlspecialchars($dogovorNumber);
                        }
                        ?>
                    </td>

                    <!-- Колонка ЗАКАЗЧИК -->
                    <td onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                        <?php echo htmlspecialchars($row['customer'] ?? ''); ?>
                    </td>

                    <!-- Колонка АДРЕС -->
                    <td>
                            <!-- <?php $encodedAddress = urlencode($row['address']); ?> -->
                                <a style="z-index: 1000;" href="https://yandex.ru/maps/?text=<?php echo $encodedAddress; ?>" 
                                    target="_blank" class="address-link">
                                    <?php echo htmlspecialchars($row['address']); ?>
                                </a>
                    </td>

                    <!-- Колонка МОДЕЛЬ -->
                    <td onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                        style="text-wrap-mode: nowrap;"><?php echo htmlspecialchars($row['model'] ?? ''); ?>
                    </td>

                    <!-- Колонка ТЕЛЕФОН -->
                    <td>
                        <!-- <?php if (!empty($row['phone'])): ?> -->
                            <?php $phone = preg_replace('/[^0-9]/', '', $row['phone']);?>
                            <a href="tel:+<?php echo $phone; ?>" class="phone-link">
                                <?php echo htmlspecialchars($row['phone']); ?>
                            </a>
                        <!-- <?php else: ?>
                            <span class="no-phone">нет телефона</span>
                        <?php endif; ?> -->
                    </td>

                    <!-- Колонка ИМЯ -->
                    <td onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">
                        <?php echo htmlspecialchars($row['name'] ?? ''); ?>
                    </td>

                    <!-- Колонка с Действиями -->
                    <td class="actions-column">

                        <!-- Кнопка обслуживания -->
                        <button class="service-btn" onclick="openServiceModal(<?php echo htmlspecialchars($objectId); ?>, '<?php echo htmlspecialchars($row['customer'] ?? ''); ?>', '<?php echo htmlspecialchars($row['address'] ?? ''); ?>')"
                                title="<?php echo $hasServices ? 'Последнее ТО: ' . ($lastService ? htmlspecialchars($lastService) : '') . ' (всего: ' . $serviceCount . ')' : 'Нет записей о ТО'; ?>">
                            <i class="fas fa-wrench"></i>
                            <?php if ($hasServices && $serviceCount > 0): ?>
                                <span class="service-badge"><?php echo $serviceCount; ?></span>
                            <?php endif; ?>
                        </button>

                    </td>
                </tr>
                <!-- КОнец строки таблицы -->

            <?php endforeach; ?>

        </tbody>
        </table>

        <div class="count">Найдено записей: <?php echo count($rows); ?></div>

        <?php else: ?>
            <div class="empty">
                <p>В таблице нет записей с данными в колонке <?php echo $selectedMonthColumn; ?> (<?php echo $currentMonthName; ?>)</p>
                <p>Или проверьте правильность названия таблицы и колонок</p>
            </div>
        <?php endif; ?>

    </div>



<!-- Модальные окна -->
<!-- Модальное окно для добавления ТО -->
<?php include 'modules/service/edit-modal.php'; ?>
<!-- Модальное окно для добавления ТО -->
<?php include 'modules/service/service-modal.php'; ?>
<!-- Модальное окно для просмотра фото ТО -->
<?php include 'modules/service/foto-modal.php'; ?>
<!-- Модальное окно для добавления записи -->
<?php include 'modules/service/add-modal.php'; ?>

<!-- Подключаем JavaScript файл -->
<!-- Файл сортировки в таблице -->
<script src="assets/js/table-sort.js"></script>
<!-- Для окна редактирования -->
<script src="assets/js/edit-modal.js"></script>
<!-- Для окна обслуживания -->
<script src="assets/js/service-modal.js"></script>
<!-- Для окна просмотра фото -->
<script src="assets/js/foto-modal.js"></script>



<script>
// Функция обновления строки в таблице
function updateTableRow(data) {
    const row = document.querySelector(`tr[data-id="${data.id}"]`);
    if (row) {
        // Обновляем ячейки
        row.cells[1].textContent = data.customer || '';
        row.cells[2].innerHTML = data.address ? 
            `<a href="https://yandex.ru/maps/?text=${encodeURIComponent(data.address)}" target="_blank" class="address-link">${data.address}</a>` : 
            '<span class="no-address">нет адреса</span>';
        row.cells[3].textContent = data.model || '';
        if (data.phone) {
            const cleanPhone = data.phone.replace(/\D/g, '');
            row.cells[4].innerHTML = `<a href="tel:+${cleanPhone}" class="phone-link">${data.phone}</a>`;
        } else {
            row.cells[4].innerHTML = '<span class="no-phone">нет телефона</span>';
        }
        row.cells[5].textContent = data.name || '';

        // Обновляем кнопку редактирования с новыми данными
        const editButton = row.querySelector('.edit-btn');
        if (editButton) {
            // Обновляем onclick с новыми данными
            const updatedRowData = { ...currentEditData, ...data }; // Объединяем старые и новые данные
            editButton.setAttribute('onclick', `openEditModal(${JSON.stringify(updatedRowData)})`);
        }


        // После обновления строки, применяем текущий фильтр заново
        if (currentFilter && currentFilter !== 'all') {
            filterTableByColor(currentFilter);
        }


    }
}


// Функция показа сообщений INDEX.PHP
function showMessage(text, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = text;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';
    // Автоматически скрываем через 5 секунд
    setTimeout(hideMessage, 5000);
}

function hideMessage() {
    document.getElementById('message').style.display = 'none';
}

// Горячие клавиши
document.addEventListener('keydown', function(e) {
    // ESC - закрыть модальне окна
    if (e.key === 'Escape') {
        closeEditModal();
        closeServiceModal();
    }
});
</script>




<!-- ФИЛЬТРАЦИЯ ПО ЦВЕТУ -->
<script>
// Переменные для фильтрации
let currentFilter = localStorage.getItem('colorFilter') || 'all';

// Функция для открытия/закрытия выпадающего меню
function toggleFilterDropdown() {
    const menu = document.getElementById('filterMenu');
    menu.classList.toggle('show');
    
    // Закрываем меню при клике вне его
    if (menu.classList.contains('show')) {
        document.addEventListener('click', function closeMenu(e) {
            if (!menu.contains(e.target) && !e.target.closest('.btn-filter')) {
                menu.classList.remove('show');
                document.removeEventListener('click', closeMenu);
            }
        });
    }
}

// Функция применения фильтра по цвету
function applyColorFilter(color) {
    // Обновляем текущий фильтр
    currentFilter = color;
    
    // Сохраняем в localStorage
    localStorage.setItem('colorFilter', color);
    
    // Обновляем бейдж
    updateFilterBadge(color);
    
    // Закрываем меню
    document.getElementById('filterMenu').classList.remove('show');
    
    // Применяем фильтр к таблице
    filterTableByColor(color);
}

// Функция фильтрации таблицы по цвету
function filterTableByColor(color) {
    const rows = document.querySelectorAll('#sortableTable tbody tr');
    
    rows.forEach(row => {
        const rowStyle = row.getAttribute('style') || '';
        let showRow = false;
        
        switch(color) {
            case 'all':
                showRow = true;
                break;
            case 'green':
                showRow = rowStyle.includes('#d4edda');
                break;
            case 'yellow':
                showRow = rowStyle.includes('#fffed9');
                break;
            case 'red':
                showRow = rowStyle.includes('#ffd5d5');
                break;
            case 'none':
                // Показываем строки без фонового цвета
                showRow = !rowStyle.includes('#d4edda') && 
                         !rowStyle.includes('#fffed9') && 
                         !rowStyle.includes('#ffd5d5');
                break;
        }
        
        if (showRow) {
            row.classList.remove('filtered-out');
        } else {
            row.classList.add('filtered-out');
        }
    });
    
    // Показываем сообщение о количестве отфильтрованных записей
    const visibleRows = document.querySelectorAll('#sortableTable tbody tr:not(.filtered-out)').length;
    showFilterMessage(visibleRows, color);
}

// Функция обновления бейджа фильтра
function updateFilterBadge(color) {
    const badge = document.getElementById('filterBadge');
    if (color !== 'all') {
        badge.style.display = 'inline';
        // Меняем цвет бейджа в зависимости от фильтра
        badge.style.color = getColorCode(color);
    } else {
        badge.style.display = 'none';
    }
}

// Вспомогательная функция для получения цвета бейджа
function getColorCode(color) {
    switch(color) {
        case 'green': return '#28a745';
        case 'yellow': return '#ffc107';
        case 'red': return '#dc3545';
        default: return '#ffc107';
    }
}

// Функция показа сообщения о фильтрации
function showFilterMessage(visibleCount, color) {
    const totalRows = document.querySelectorAll('#sortableTable tbody tr').length;
    const messageDiv = document.getElementById('message');
    
    if (visibleCount < totalRows) {
        let colorName = '';
        switch(color) {
            case 'green': colorName = 'зеленым'; break;
            case 'yellow': colorName = 'желтым'; break;
            case 'red': colorName = 'красным'; break;
            case 'none': colorName = 'без цвета'; break;
        }
        
        messageDiv.textContent = `Показано ${visibleCount} из ${totalRows} записей (фильтр: ${colorName} цвет)`;
        messageDiv.className = 'message info';
        messageDiv.style.display = 'block';
        
        // Добавляем кнопку сброса фильтра
        if (!document.getElementById('resetFilterBtn')) {
            const resetBtn = document.createElement('button');
            resetBtn.id = 'resetFilterBtn';
            resetBtn.innerHTML = '×';
            resetBtn.className = 'reset-filter-btn';
            resetBtn.onclick = function() {
                applyColorFilter('all');
                hideMessage();
            };
            messageDiv.appendChild(resetBtn);
        }
    } else {
        hideMessage();
    }
}

// Загружаем сохраненный фильтр при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    const savedFilter = localStorage.getItem('colorFilter');
    if (savedFilter && savedFilter !== 'all') {
        // Небольшая задержка, чтобы таблица успела загрузиться
        setTimeout(() => {
            applyColorFilter(savedFilter);
        }, 100);
    }
});
</script>


<script>
// Обработчик изменения месяца
document.addEventListener('DOMContentLoaded', function() {
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', function() {
            const selectedMonth = this.value;
            // Получаем текущий URL и обновляем параметр month
            const url = new URL(window.location.href);
            url.searchParams.set('month', selectedMonth);
            // Перезагружаем страницу с новым параметром
            window.location.href = url.toString();
        });
    }
});
</script>


</body>
</html>