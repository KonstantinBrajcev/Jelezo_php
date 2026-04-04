<?php
// modules/problem/problem.php
require_once __DIR__ . '/../../modules/auth/auth.php'; // Подключаем файл с функциями аутентификации
checkAuth(); // Проверяем авторизацию
// Получаем информацию о текущем пользователе
$currentUser = getCurrentUser();
// Определяем, является ли пользователь суперпользователем
$isSuperAdmin = ($currentUser['is_superuser'] ?? 0) == 1;

// Подключение к базе данных
try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Создание таблицы, если её нет
$db->exec("CREATE TABLE IF NOT EXISTS LIFTEH_problem (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_completed INTEGER DEFAULT 0,
    user_id INTEGER,
    priority INTEGER DEFAULT 0
)");
// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Исправлено: добавлено поле created_date с текущей датой
                $stmt = $db->prepare("INSERT INTO LIFTEH_problem (name, user_id, created_date, is_completed, priority) 
                          VALUES (:name, :user_id, datetime('now', 'localtime'), 0, 
                          (SELECT IFNULL(MAX(priority), 0) + 1 FROM LIFTEH_problem))");
                $stmt->execute([
                    ':name' => $_POST['name'],
                    ':user_id' => $currentUser['id'] ?? null,
                ]);
                break;

            case 'edit':
                $stmt = $db->prepare("UPDATE LIFTEH_problem SET name = :name, user_id = :user_id WHERE id = :id");
                $stmt->execute([
                    ':id' => $_POST['id'],
                    ':name' => $_POST['name'],
                    ':user_id' => $currentUser['id'] // Используем ID текущего пользователя
                ]);
                break;

            case 'toggle_complete':
                $stmt = $db->prepare("UPDATE LIFTEH_problem SET is_completed = NOT is_completed WHERE id = :id");
                $stmt->execute([':id' => $_POST['id']]);
                break;

            case 'move_up':
            // Получаем текущую задачу
            $current = $db->prepare("SELECT id, priority FROM LIFTEH_problem WHERE id = :id AND is_completed = 0");
            $current->execute([':id' => $_POST['id']]);
            $current_task = $current->fetch(PDO::FETCH_ASSOC);

            if ($current_task) {
                // Ищем задачу с более высоким приоритетом (меньшее число priority) среди активных
                $above = $db->prepare("SELECT id, priority FROM LIFTEH_problem 
                                    WHERE priority < :priority AND is_completed = 0 
                                    ORDER BY priority DESC LIMIT 1");
                $above->execute([':priority' => $current_task['priority']]);
                $above_task = $above->fetch(PDO::FETCH_ASSOC);

                if ($above_task) {
                    $db->beginTransaction();
                    // Меняем приоритеты местами
                    $stmt1 = $db->prepare("UPDATE LIFTEH_problem SET priority = :priority WHERE id = :id");
                    $stmt1->execute([':priority' => $above_task['priority'], ':id' => $current_task['id']]);
                    $stmt1->execute([':priority' => $current_task['priority'], ':id' => $above_task['id']]);
                    $db->commit();
                }
            }
            break;

        case 'move_down':
            // Получаем текущую задачу
            $current = $db->prepare("SELECT id, priority FROM LIFTEH_problem WHERE id = :id AND is_completed = 0");
            $current->execute([':id' => $_POST['id']]);
            $current_task = $current->fetch(PDO::FETCH_ASSOC);

            if ($current_task) {
                // Ищем задачу с более низким приоритетом (большее число priority) среди активных
                $below = $db->prepare("SELECT id, priority FROM LIFTEH_problem 
                                    WHERE priority > :priority AND is_completed = 0 
                                    ORDER BY priority ASC LIMIT 1");
                $below->execute([':priority' => $current_task['priority']]);
                $below_task = $below->fetch(PDO::FETCH_ASSOC);

                if ($below_task) {
                    $db->beginTransaction();
                    // Меняем приоритеты местами
                    $stmt1 = $db->prepare("UPDATE LIFTEH_problem SET priority = :priority WHERE id = :id");
                    $stmt1->execute([':priority' => $below_task['priority'], ':id' => $current_task['id']]);
                    $stmt1->execute([':priority' => $current_task['priority'], ':id' => $below_task['id']]);
                    $db->commit();
                }
            }
            break;

            case 'delete':
              $stmt = $db->prepare("UPDATE LIFTEH_problem SET is_completed = 2 WHERE id = :id");
              $stmt->execute([':id' => $_POST['id']]);
            break;
        }

        // Возвращаем JSON ответ для AJAX запросов
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true]);
            exit;
        }

        // Перенаправление для обычных запросов
        header('Location: problem.php');
        exit;
    }
}

// Получение всех задач с сортировкой по статусу и приоритету
// 0 - активно, 1 - завершено, 2 - удалено
$stmt = $db->query("SELECT * FROM LIFTEH_problem WHERE is_completed != 2 ORDER BY is_completed, priority ASC");
$problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Функция для получения данных задачи по ID (для модального окна)
if (isset($_GET['get_problem']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $stmt = $db->prepare("SELECT * FROM LIFTEH_problem WHERE id = :id");
    $stmt->execute([':id' => $_GET['get_problem']]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($problem);
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Управление проблемами'; include __DIR__ . '/../../includes/header.php'; ?>
<body>


<!-- Панель пользователя -->
<?php include __DIR__ . '/../../modules/admin/user_panel.php'; ?>


    <div class="container">

        <div class="controls">
            <div class="button-group">

                <a href="/map.php" class="map-button">
                    <span class="button-text">Карта</span>
                    <i class="fas fa-map"></i>
                </a>

                <a href="/index.php" class="map-button">
                    <span class="button-text">Таблица</span>
                    <i class="fas fa-table"></i>
                </a>


                <!-- ТОЛЬКО ДЛЯ АДМИНОВ -->
                <?php if ($isSuperAdmin): ?>
                    <a href="/dogovor.php" class="map-button">
                        <span class="button-text">Договора</span>
                        <i class="fas fa-file-alt"></i>
                    </a>

                    <a href="/charts.php" class="map-button">
                        <span class="button-text">Графики</span>
                        <i class="fas fa-chart-line"></i>
                    </a>

                    <a href="avr.php" class="map-button">
                        <span class="button-text">АВР</span>
                        <i class="fa-solid fa-gear"></i>
                    </a>
                <?php endif; ?>


                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>
                </button>

            </div>
        </div>
        
        <!-- Таблица проблем -->
        <table id="problemTable">
            <thead>
                <tr>
                    <!-- <th>ID</th> -->
                    <th>Название</th>
                    <th>Дата</th>
                    <th></th>
                    <!-- <th>User</th> -->
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($problems as $problem): ?>
                <tr class="<?php if ($problem['is_completed'] == 2) echo 'deleted';
                                else if ($problem['is_completed'] == 1) echo 'completed'; 
                                ?>" id="row-<?= $problem['id'] ?>"
                                class="btn edit-btn" onclick="openEditModal(<?= $problem['id'] ?>)">

                    <!-- <td><?= $problem['id'] ?></td> -->

                    <td style="text-align: left;"><?= htmlspecialchars($problem['name']) ?></td>

                    <!-- <td><?= $problem['created_date'] ?></td> -->
                    <td><?= date('d.m.Y', strtotime($problem['created_date'])) ?></td>

                    <td  style="text-align: right;">
                        <form method="POST" style="display: inline;" onsubmit="return toggleComplete(event, this)">
                            <input type="hidden" name="action" value="toggle_complete">
                            <input type="hidden" name="id" value="<?= $problem['id'] ?>">
                            <button type="submit" class="status-badge  <?= $problem['is_completed'] ? 'status-completed' : 'status-active' ?>" style="width: 30px;">
                                <?= $problem['is_completed'] ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i>' ?>
                            </button>
                        </form>
                    </td>

                    <!-- <td><?= $problem['user_id'] ?: '—' ?></td> -->

                    <td style="width: 70px;">
                      <div class="priority-buttons">
                        <?php
                        // Получаем минимальный и максимальный priority среди активных задач
                        $minMax = $db->query("SELECT MIN(priority) as min_priority, MAX(priority) as max_priority 
                                                    FROM LIFTEH_problem 
                                                    WHERE is_completed = 0")->fetch(PDO::FETCH_ASSOC);
                        $minPriority = $minMax['min_priority'];
                        $maxPriority = $minMax['max_priority'];
                        ?>

                        <?php if ($problem['is_completed'] == 0): ?>
                            <!-- Кнопка ВВЕРХ (повышение приоритета) - показываем только если не самая верхняя -->
                            <?php if ($problem['priority'] > $minPriority): ?>
                                <form method="POST" style="display: inline;" onsubmit="return movePriority(event, this)">
                                    <input type="hidden" name="action" value="move_up">
                                    <input type="hidden" name="id" value="<?= $problem['id'] ?>">
                                    <button type="submit" class="priority-btn" title="Повысить приоритет">↑</button>
                                </form>
                            <?php endif; ?>

                            <!-- Кнопка ВНИЗ (понижение приоритета) - показываем только если не самая нижняя -->
                            <?php if ($problem['priority'] < $maxPriority): ?>
                                <form method="POST" style="display: inline;" onsubmit="return movePriority(event, this)">
                                    <input type="hidden" name="action" value="move_down">
                                    <input type="hidden" name="id" value="<?= $problem['id'] ?>">
                                    <button type="submit" class="priority-btn" title="Понизить приоритет">↓</button>
                                </form>
                            <?php endif; ?>

                            <!-- Если задача одна, показываем заглушку -->
                            <?php if ($minPriority == $maxPriority): ?> <span class="priority-single"></span> <?php endif; ?>

                        <!-- Для завершенных задач не показываем кнопки -->
                        <?php else: ?> <span class="priority-disabled"></span> <?php endif; ?>
                      </div>
                    </td>



                    <!--<td class="actions">
                        <button class="btn edit-btn" onclick="openEditModal(<?= $problem['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                         <button class="btn btn-danger" onclick="deleteProblem(<?= $problem['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button> 
                    </td>-->
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="count">Найдено записей: <?= count($problems); ?></div>

    </div>

    <!-- Модальное окно для добавления -->
    <div id="addModal" class="modal">
        <div class="modal-content">


            <div class="modal-header">
                <h5><strong>Добавление проблемы</strong></h5>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>


            <form id="addForm" onsubmit="return submitAddForm(event)">
                <input type="hidden" name="action" value="add">


                <div class="form-group form-floating">
                    <textarea id="modal_name" class="form-control" name="name" required 
                    placeholder="Введи суть проблемы" style="height: 120px; min-height: 80px;"></textarea>
                    <label for="modal_name" class="form-label">Проблема:</label>
                </div>


                <!-- Поле user_id скрыто -->
                <div class="form-group" hidden>
                    <label>Создатель:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?> (ID: <?= $currentUser['id'] ?>)" disabled>
                </div>


                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-danger flex-fill" onclick="closeAddModal()">
                        <!-- <i class="fas fa-times"></i>  -->
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-success flex-fill">
                        <!-- <i class="fas fa-save"></i>  -->
                        Добавить
                    </button>
                </div>


            </form>


        </div>
    </div>

    <!-- Модальное окно для редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content">

            <div class="modal-header">
                <h5><strong>Редактировать проблему</strong></h5>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>

            <form id="editForm" onsubmit="return submitEditForm(event)">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group form-floating">
                    <textarea id="edit_name" class="form-control" name="name" style="height: 120px; min-height: 80px;" required></textarea>
                    <label for="edit_name" class="form-label">Проблема:</label>
                </div>

                <!-- Поле user_id скрыто -->
                <div class="form-group" hidden>
                    <label>Редактирует:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?> (ID: <?= $currentUser['id'] ?>)" disabled>
                </div>


                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-danger flex-fill" onclick="closeEditModal()">
                        <!-- <i class="fas fa-times"></i>  -->
                        Отмена
                    </button>
                    <button type="submit" class="btn btn-primary flex-fill">
                        <!-- <i class="fas fa-save"></i>  -->
                        Сохранить
                    </button>
                </div>


            </form>
        </div>
    </div>

<script src="/assets/js/problem.js"></script>
</body>
</html>