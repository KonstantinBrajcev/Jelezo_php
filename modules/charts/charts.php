<?php
// modules/charts/charts.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth(); // Проверяем авторизацию
checkSuperuser(); // Только для суперпользователей!

$currentUser = getCurrentUser();

try {
    $db = new PDO("sqlite:" . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Статистика по договорам
    $dogovorStats = [
        'total' => 0,
        'budget' => 0,
        'sobstvennye' => 0,
        'prolong_yes' => 0,
        'prolong_no' => 0
    ];

    $stmt = $db->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN financing = 'Бюджет' THEN 1 ELSE 0 END) as budget,
        SUM(CASE WHEN financing = 'Собственные' THEN 1 ELSE 0 END) as sobstvennye,
        SUM(CASE WHEN prolong = 1 THEN 1 ELSE 0 END) as prolong_yes,
        SUM(CASE WHEN prolong = 0 THEN 1 ELSE 0 END) as prolong_no
        FROM LIFTEH_dogovor WHERE is_active = 1");
    
    $dogovorStats = array_merge($dogovorStats, $stmt->fetch(PDO::FETCH_ASSOC));

    // 2. Статистика по объектам и типам оборудования
    $objectStats = [
        'total' => 0,
        'by_model' => []
    ];

    // Запрос с учетом активных договоров
    $stmt = $db->query("
        SELECT o.model, COUNT(*) as count 
        FROM LIFTEH_object o
        INNER JOIN LIFTEH_dogovor d ON o.dogovor_id = d.id
        WHERE d.is_active = 1
        GROUP BY o.model 
        ORDER BY count DESC
    ");
    
    $objectStats['by_model'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем общее количество объектов по активным договорам
    $totalStmt = $db->query("
        SELECT COUNT(*) as total 
        FROM LIFTEH_object o
        INNER JOIN LIFTEH_dogovor d ON o.dogovor_id = d.id
        WHERE d.is_active = 1
    ");
    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $objectStats['total'] = $totalResult['total'] ?? 0;

    // 3. Помесячные суммы за ТО
    $monthlyTotals = [];
    $months = ['M1', 'M2', 'M3', 'M4', 'M5', 'M6', 'M7', 'M8', 'M9', 'M10', 'M11', 'M12'];
    $monthNames = [
        'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
        'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
    ];

    foreach ($months as $index => $month) {
        $stmt = $db->query("SELECT SUM(CAST($month AS DECIMAL)) as total 
            FROM LIFTEH_object 
            WHERE $month IS NOT NULL AND $month != '' AND CAST($month AS TEXT) != '0'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $monthlyTotals[$month] = (float)($result['total'] ?? 0);
    }

    // 4. Суммы по каждому заказчику
    $customerTotals = [];
    $stmt = $db->query("
        SELECT o.customer, 
               SUM(COALESCE(CAST(o.M1 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M2 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M3 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M4 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M5 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M6 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M7 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M8 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M9 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M10 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M11 AS DECIMAL), 0) + 
                   COALESCE(CAST(o.M12 AS DECIMAL), 0)) as total
        FROM LIFTEH_object o
        INNER JOIN LIFTEH_dogovor d ON o.dogovor_id = d.id
        WHERE d.is_active = 1
            AND o.customer IS NOT NULL 
            AND o.customer != ''
        GROUP BY o.customer
        HAVING total > 0
        ORDER BY total DESC
    ");
    $customerTotals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Общая сумма за год
    $yearTotal = array_sum($monthlyTotals);

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Аналитика и графики'; include __DIR__ . '/../../includes/header.php'; ?>

<body>
    <?php include __DIR__ . '/../../modules/admin/user_panel.php'; ?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="h3 mb-0">Аналитика и отчеты</h1>
            </div>
        </div>

        <!-- Блок с общей статистикой -->
        <div class="row g-4 mb-4">
            <!-- Общее количество договоров -->
            <div class="col-md-6 col-lg-3">
                <div class="card stats-card" style="height: 100%;">
                    <div class="card-body align-content-center">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-primary">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle mb-1">Всего договоров</h6>
                                <h2 class="card-title mb-0"><?= $dogovorStats['total'] ?></h2>
                                <small class="text-muted">
                                    Бюджет: <?= $dogovorStats['budget'] ?> | 
                                    Собств: <?= $dogovorStats['sobstvennye'] ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    Пролонг: <?= $dogovorStats['prolong_yes'] ?> | 
                                    Не лонг: <?= $dogovorStats['prolong_no'] ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Общее количество объектов -->
            <div class="col-md-6 col-lg-3">
                <div class="card stats-card" style="height: 100%;">
                    <div class="card-body align-content-center">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-success">
                                <i class="fas fa-cubes"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle mb-1">Всего объектов</h6>
                                <h2 class="card-title mb-0"><?= $objectStats['total'] ?></h2>
                                <small class="text-muted">Типов оборудования: <?= count($objectStats['by_model']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Общая сумма за год -->
            <div class="col-md-6 col-lg-3">
                <div class="card stats-card" style="height: 100%;">
                    <div class="card-body align-content-center">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-warning">
                                <i class="fas fa-ruble-sign"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle mb-1">Общая сумма за год</h6>
                                <h2 class="card-title mb-0"><?= number_format($yearTotal, 2, '.', ' ') ?> BYN</h2>
                                <small class="text-muted">По всем объектам</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Средняя сумма в месяц -->
            <div class="col-md-6 col-lg-3">
                <div class="card stats-card" style="height: 100%;">
                    <div class="card-body align-content-center">
                        <div class="d-flex align-items-center">
                            <div class="stats-icon bg-info">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="card-subtitle mb-1">Средняя сумма в месяц</h6>
                                <h2 class="card-title mb-0"><?= number_format($yearTotal / 12, 2, '.', ' ') ?> BYN</h2>
                                <small class="text-muted">За текущий год</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Графики -->
        <div class="row g-4">

            <!-- Помесячные суммы -->
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Суммы по месяцам</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" style="height: 500px;"></canvas>
                    </div>
                </div>
            </div>



            <!-- НОВЫЙ БЛОК: Распределение зарплаты -->
            <!-- <div class="row g-4"> -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Распределение фонда оплаты труда</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Данные о зарплате
                            $salaryData = [
                                ['position' => 'Директор (Брайцев)', 'percent' => 12.9],
                                ['position' => 'Маркетолог (Берсенева)', 'percent' => 11.5],
                                ['position' => 'Гл. бухгалтер (Евтухова)', 'percent' => 9],
                                ['position' => 'Электромеханик по подъемникам (Кравченко)', 'percent' => 8.7],
                                ['position' => 'Электромеханик по подъемникам (Короткий)', 'percent' => 8],
                                ['position' => 'Электромеханик по подъемникам (Гамалеев)', 'percent' => 3],
                                ['position' => 'Автомеханик (Беликов)', 'percent' => 5]
                            ];
                            
                            // Рассчитываем среднемесячную сумму и фонд оплаты
                            $averageMonthlyTotal = $yearTotal / 12;
                            $monthlySalaryFund = $averageMonthlyTotal * 0.20; // 20% от среднемесячной суммы
                            
                            // Суммируем проценты для проверки
                            $totalPercent = array_sum(array_column($salaryData, 'percent'));
                            
                            // Рассчитываем итоговые суммы для подвала таблицы
                            $totalCleanSum = 0;
                            $totalDeductions = 0;
                            $totalGross = 0;
                            
                            foreach ($salaryData as $employee) {
                                $cleanSum = $averageMonthlyTotal * ($employee['percent'] / 100);
                                $deductions = $cleanSum * 0.34;
                                $gross = $cleanSum + $deductions;
                                
                                $totalCleanSum += $cleanSum;
                                $totalDeductions += $deductions;
                                $totalGross += $gross;
                            }
                            ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Должность</th>
                                                <th class="text-center">%</th>
                                                <th class="text-center">Чистые (BYN)</th>
                                                <th class="text-center">Отчисления (BYN)</th>
                                                <th class="text-center">Грязные (BYN)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            foreach ($salaryData as $employee): 
                                                // Рассчитываем суммы для каждого сотрудника
                                                $cleanSum = $averageMonthlyTotal * ($employee['percent'] / 100);
                                                $deductions = $cleanSum * 0.34;
                                                $gross = $cleanSum + $deductions;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($employee['position']) ?></td>
                                                <td class="text-end"><?= $employee['percent'] ?>%</td>
                                                <td class="text-end"><?= number_format($cleanSum, 2, '.', ' ') ?></td>
                                                <td class="text-end"><?= number_format($deductions, 2, '.', ' ') ?></td>
                                                <td class="text-end"><?= number_format($gross, 2, '.', ' ') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-end">Итого:</th>
                                                <th class="text-end"><?= number_format($totalCleanSum, 2, '.', ' ') ?></th>
                                                <th class="text-end"><?= number_format($totalDeductions, 2, '.', ' ') ?></th>
                                                <th class="text-end"><?= number_format($totalGross, 2, '.', ' ') ?></th>
                                            </tr>
                                            <tr>
                                                <th colspan="4" class="text-end">Прибыль:</th>
                                                <th class="text-end"><?= number_format($averageMonthlyTotal - $totalGross, 2, '.', ' ') ?></th>
                                            </tr>
                                            <tr>
                                                <td colspan="5" class="text-muted small">
                                                    * Расчет произведен исходя из среднемесячной суммы <?= number_format($averageMonthlyTotal, 2, '.', ' ') ?> BYN
                                                </td>
                                            </tr>
                                            <?php if ($totalPercent != 100): ?>
                                            <!-- <tr>
                                                <td colspan="3" class="text-danger small">
                                                    Внимание: Сумма процентов составляет <?= $totalPercent ?>% (должно быть 100%)
                                                </td>
                                            </tr> -->
                                            <?php endif; ?>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-2 text-muted">Информация</h6>
                                            <p class="mb-1"><strong>Среднемесячная сумма (100%):</strong> <?= number_format($averageMonthlyTotal, 2, '.', ' ') ?> BYN</p>
                                            <p class="mb-1"><strong>Количество сотрудников:</strong> <?= count($salaryData) ?></p>
                                            <p class="mb-1"><strong>Фонд оплаты труда (60%):</strong> <?= number_format($totalGross, 2, '.', ' ') ?> BYN</p>
                                            <p class="mb-1"><strong>Сумма чистыми (на руки):</strong> <?= number_format($totalCleanSum, 2, '.', ' ') ?> BYN</p>
                                            <p class="mb-1"><strong>Отчисления (34%):</strong> <?= number_format($totalDeductions, 2, '.', ' ') ?> BYN</p>
                                            <p class="mb-0"><strong>Прибыль компании (40%):</strong> <?= number_format($averageMonthlyTotal - $totalGross, 2, '.', ' ') ?> BYN</p>
                                        </div>
                                    </div>
                                </div>
                            <!-- </div> -->
                        </div>
                    </div>
                </div>
            </div>





        </div>

        <div class="row g-4 mt-2">
            <!-- Суммы по заказчикам -->
            <div class="col-lg-8 d-flex">
                <div class="card w-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Суммы по заказчикам</h5>
                    </div>
                    <div class="card-body p-0" style="height: 100%;">
                        <canvas id="customerChart" style="width: 100%; height: 100%; min-height: 300px;"></canvas>
                    </div>
                </div>
            </div>



            <div class="col-lg-4 d-flex">
            <div class="card w-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Заказчики</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 800px; overflow-y: auto;">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Заказчик</th>
                                        <th class="text-end" style="text-wrap-mode: nowrap;">Сумма, BYN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($customerTotals, 0) as $customer): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($customer['customer']) ?></td>
                                        <td class="text-end"><?= number_format($customer['total'], 2, '.', ' ') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
              </div>
            </div>

<div class="row g-4 mt-2">
    <!-- Типы оборудования (график) -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Типы оборудования</h5>
            </div>
            <div class="card-body" style="height: 800px;">
                <canvas id="modelChart" style="width: 100%; height: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Детальная статистика (таблица) -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Типы оборудования</h5>
            </div>
            <div class="card-body p-0" style="height: 800px;">
                <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead class="sticky-top bg-white" style="top: 0; z-index: 1;">
                            <tr>
                                <th>Модель</th>
                                <th class="text-end">Количество</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($objectStats['by_model'] as $model): ?>
                            <tr>
                                <td><?= htmlspecialchars($model['model'] ?: 'Не указано') ?></td>
                                <td class="text-end"><?= $model['count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
    
    <!-- Данные для графиков -->
    <script>
        const monthlyData = {
            labels: <?= json_encode($monthNames) ?>,
            values: <?= json_encode(array_values($monthlyTotals)) ?>
        };

        const modelData = {
            labels: <?= json_encode(array_column($objectStats['by_model'], 'model')) ?>,
            values: <?= json_encode(array_column($objectStats['by_model'], 'count')) ?>
        };

        const customerData = {
            labels: <?= json_encode(array_column(array_slice($customerTotals, 0), 'customer')) ?>,
            values: <?= json_encode(array_column(array_slice($customerTotals, 0), 'total')) ?>
        };
    </script>

    <!-- Подключаем наши скрипты -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation"></script>
    <script src="/assets/js/charts.js"></script>
</body>
</html>