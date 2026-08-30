<?php
// guest.php
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth();

// Получаем информацию о текущем пользователе
$currentUser = getCurrentUser();

// Проверяем, что пользователь имеет доступ к этой странице
if (($currentUser['is_staff'] ?? 1) == 1) {
    // Если это сотрудник, перенаправляем на главную
    header('Location: /index.php');
    exit();
}

$dogovorId = $currentUser['dogovor'] ?? null;

if (!$dogovorId) {
    // Если у пользователя нет привязанного договора
    $errorMessage = "У вашей учетной записи не привязан договор. Обратитесь к администратору.";
} else {
    try {
        $pdo = new PDO("sqlite:" . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Получаем объекты, привязанные к договору пользователя
        // И добавляем подзапрос для получения последнего статуса из LIFTEH_service
        $stmt = $pdo->prepare("
            SELECT 
                LIFTEH_object.*,
                LIFTEH_dogovor.number as dogovor_number,
                (
                    SELECT result 
                    FROM LIFTEH_service 
                    WHERE LIFTEH_service.object_id = LIFTEH_object.id 
                    ORDER BY LIFTEH_service.service_date DESC, LIFTEH_service.id DESC 
                    LIMIT 1
                ) as last_service_result,
                (
                    SELECT service_date 
                    FROM LIFTEH_service 
                    WHERE LIFTEH_service.object_id = LIFTEH_object.id 
                    ORDER BY LIFTEH_service.service_date DESC, LIFTEH_service.id DESC 
                    LIMIT 1
                ) as last_service_date
            FROM LIFTEH_object 
            LEFT JOIN LIFTEH_dogovor ON LIFTEH_object.dogovor_id = LIFTEH_dogovor.id
            WHERE LIFTEH_object.dogovor_id = ?
            ORDER BY LIFTEH_object.id
        ");
        $stmt->execute([$dogovorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalObjects = count($rows);
        
    } catch (PDOException $e) {
        $errorMessage = "Ошибка загрузки данных: " . $e->getMessage();
    }
}

// Получаем имя заказчика из первой записи (если есть)
$customerName = !empty($rows) ? htmlspecialchars($rows[0]['customer'] ?? '') : '';

// Формируем заголовок страницы
$pageTitle = 'Список оборудования' . ($customerName ? ' - ' . $customerName : '');
?>
<!DOCTYPE html>
<html lang="ru">
<?php include __DIR__ . '/../../includes/header.php'; ?>

<body>
    <div class="header">
        <div class="header-content">
            <h1><i class="fas fa-building"></i><?php echo $customerName ?> </h1>
            <div class="user-info">
                <span class="username">
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($currentUser['first_name'] ?? $currentUser['username'] ?? 'Пользователь'); ?>
                </span>
                <a href="/modules/auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Выйти
                </a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($errorMessage)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($rows) && !isset($errorMessage)): ?>
            <div class="stats">
                <div class="count">
                    <i class="fas fa-list"></i> 
                    Всего объектов: <strong><?php echo $totalObjects; ?></strong>
                </div>
                <?php if ($dogovorId && !empty($rows)): ?>
                    <div class="dogovor-info">
                        <i class="fas fa-file-contract"></i>
                        Договор №<?php echo htmlspecialchars($rows[0]['dogovor_number'] ?? $dogovorId); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($totalObjects > 0): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>№</th>
                                <!-- <th>Заказчик</th> -->
                                <th>Адрес</th>
                                <th>Модель</th>
                                <th>Телефон</th>
                                <th>Контактное лицо</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $index => $row): 
                                // Определяем статус объекта из последней записи в LIFTEH_service
                                $lastResult = $row['last_service_result'] ?? null;
                                $lastDate = $row['last_service_date'] ?? null;
                                
                                // Если есть результат последнего обслуживания
                                if ($lastResult !== null) {
                                    if ($lastResult == 0 || $lastResult === '0') {
                                        $statusClass = 'success';
                                        $statusText = '✅ Работает';
                                    } elseif ($lastResult == 1 || $lastResult === '1') {
                                        $statusClass = 'warning';
                                        $statusText = '⚠️ Есть замечания';
                                    } elseif ($lastResult == 2 || $lastResult === '2') {
                                        $statusClass = 'danger';
                                        $statusText = '❌ Не работает';
                                    } else {
                                        $statusClass = 'secondary';
                                        $statusText = '❓ Неизвестно';
                                    }
                                } else {
                                    // Если нет записей о обслуживании
                                    $statusClass = 'secondary';
                                    $statusText = '❓ Нет данных';
                                }
                            ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <!-- <td><?php echo htmlspecialchars($row['customer'] ?? ''); ?></td> -->
                                    <td>
                                        <?php if (!empty($row['address'])): ?>
                                            <a href="https://yandex.ru/maps/?text=<?php echo urlencode($row['address']); ?>" 
                                               target="_blank" class="address-link">
                                                <?php echo htmlspecialchars($row['address']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['model'] ?? ''); ?></td>
                                    <td>
                                        <?php if (!empty($row['phone'])): ?>
                                            <a href="tel:+<?php echo preg_replace('/[^0-9]/', '', $row['phone']); ?>" 
                                               class="phone-link">
                                                <?php echo htmlspecialchars($row['phone']); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['name'] ?? ''); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                            <?php if ($lastDate): ?>
                                                <span class="service-date">
                                                    <?php echo date('d.m.Y', strtotime($lastDate)); ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-building"></i>
                    <p>По вашему договору нет привязанных объектов.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>