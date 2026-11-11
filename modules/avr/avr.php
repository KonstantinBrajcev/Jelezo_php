<?php
// avr.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth(); // Проверяем авторизацию
// checkSuperuser(); // Только для суперпользователей!

// Получаем информацию о текущем пользователе
$currentUser = getCurrentUser();

try {
    $db = getDbConnection();
    
    // Получаем данные с JOIN для отображения названия работы
    $sql = "SELECT a.*,
                   w.name as work_name, 
                   w.quantity, 
                   w.unit,
                   o.customer as object_customer,
                   o.address as object_address,
                   (SELECT COUNT(*) FROM LIFTEH_work WHERE avr_id = a.id) as works_count
            FROM LIFTEH_avr a 
            LEFT JOIN LIFTEH_work w ON a.work_id = w.id
            LEFT JOIN LIFTEH_object o ON a.object_id = o.id 
            ORDER BY a.id DESC";

    $stmt = $db->query($sql);
    $all_avr_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Разделяем записи на активные и завершенные (Выполнено / Отложено / Отменено)
    $active_records = [];
    $archived_records = [];
    
    foreach ($all_avr_records as $record) {
        // Считаем "архивными" те, что уже не требуют внимания
        if (in_array($record['result'], ['Выполнено', 'Отложено', 'Отменено'])) {
            $archived_records[] = $record;
        } else {
            $active_records[] = $record;
        }
    }
    
    // Общее количество для счетчика внизу
    $total_records_count = count($all_avr_records);
    
    // Получаем список работ для выпадающего списка
    $sql_works = "SELECT id, name, quantity, unit FROM LIFTEH_work ORDER BY name";
    $stmt_works = $db->query($sql_works);
    $works_list = $stmt_works->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
    <?php $pageTitle = 'Учет ремонтов'; include __DIR__ . '/../../includes/header.php'; ?>
<body>

<?php include __DIR__ . '/../../modules/admin/user_panel.php'; ?>

<div class="container-fluid">
    <div>
        <div class="controls">
            <!-- БЛОК С КНОПКАМИ -->
            <div class="button-group">
                <a href="/map.php" class="map-button">
                    <span class="button-text">Карта</span>
                    <i class="fas fa-map"></i>
                </a>

                <a href="/index.php" class="map-button">
                    <span class="button-text">Таблица</span>
                    <i class="fas fa-table"></i>
                </a>

                <a href="/problem.php" class="map-button">
                    <span class="button-text">Проблемы</span>
                    <i class="fas fa-list"></i>
                </a>

                <a href="/document.php" class="map-button">
                    <span class="button-text">Документы</span>
                    <i class="fas fa-folder-open"></i>
                </a>

                <a href="/dogovor.php" class="map-button">
                    <span class="button-text">Договора</span>
                    <i class="fas fa-file-alt"></i>
                </a>

                <a href="/charts.php" class="map-button">
                    <span class="button-text">Графики</span>
                    <i class="fas fa-chart-line"></i>
                </a>

                <!-- КНОПКА ДОБАВЛЕНИЯ - без data-bs-toggle, с onclick -->
                <button type="button" class="btn btn-primary" onclick="openAddModal()" title="Добавить запись АВР" aria-label="Добавить запись АВР">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                </button>

                <button type="button" class="btn btn-secondary" onclick="printTable()">
                    <i class="fas fa-print"></i>
                </button>
            </div>

            <table id="avrTable">
                <thead>
                    <tr>
                        <!-- <th>ID</th> -->
                        <th>Дата/ID</th>
                        <th style="min-width: 300px;">Объект</th>
                        <th>Проблема</th>
                        <!-- <th>Работ</th> -->
                        <!-- <th>Работа</th> -->
                        <!-- <th>Кол-во</th> -->
                        <!-- <th>Ед. изм.</th> -->
                        <!-- <th>User</th> -->
                        <th>Статус</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_records)): ?>
                        <tr>
                            <td colspan="5" class="text-center">Нет активных записей</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($active_records as $record): ?>
                            <tr data-id="<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>">

                                <td><strong><?= htmlspecialchars($record['id']) ?></strong><br>
                                <?= date('d.m.Y', strtotime($record['insert_date'])) ?>
                                </td>

                                <td>
                                    <?php 
                                        $object_display = '<strong>' . htmlspecialchars($record['object_customer']) . '</strong>';
                                        $object_display .= '<br>' . htmlspecialchars($record['object_address'] ?? 'адрес не указан') . '';
                                        echo $object_display;
                                    ?>
                                </td>

                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($record['works_count'] ?? '-') ?></span>
                                    <br>
                                    <?= htmlspecialchars($record['problem']) ?>
                                </td>

                                <!-- <td><?= htmlspecialchars($record['work_name'] ?? 'Не указана') ?></td> -->
                                <!-- <td><?= htmlspecialchars($record['quantity'] ?? '-') ?></td> -->
                                <!-- <td><?= htmlspecialchars($record['unit'] ?? '-') ?></td> -->
                                <!-- <td><?= htmlspecialchars($record['user_id']) ?></td> -->

                                <td>
                                    <span class="<?= $record['result'] === 'Выполнено' ? 'status-active' : ($record['result'] === 'В работе' ? 'status-warning' : 'status-inactive') ?>">
                                        <?= htmlspecialchars($record['result'] ?? 'Не указан') ?>
                                    </span>
                                </td>

                                <td class="actions-column">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary edit-avr"
                                            data-id="<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="Редактировать запись №<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>"
                                            title="Редактировать">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </button>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                            <!-- Строка аккордеона -->
                    <?php if (count($archived_records) > 0): ?>
                        <tr class="accordion-row" id="accordionRow" style="background-color: #f5f5f5; cursor: pointer;">
                            <td colspan="5" style="padding: 12px; text-align: center; font-weight: bold;" onclick="toggleArchivedAvr()">
                                <span id="accordionIcon">
                                    <i class="fas fa-chevron-down"></i>
                                </span>
                                Выполненные и отложенные 
                                <span style="background-color: #6c757d; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">
                                    <?= count($archived_records) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>


                    <!-- Скрытые архивные записи -->
                <?php if (count($archived_records) > 0): ?>
                <tbody id="archivedAvrBody" style="display: none;">
                    <?php foreach ($archived_records as $record): ?>
                        <tr class="archived-row" data-id="<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>">
                            <td><strong><?= htmlspecialchars($record['id']) ?></strong><br>
                            <?= date('d.m.Y', strtotime($record['insert_date'])) ?>
                            </td>
                            <td>
                                <?php 
                                    $object_display = '<strong>' . htmlspecialchars($record['object_customer']) . '</strong>';
                                    $object_display .= '<br>' . htmlspecialchars($record['object_address'] ?? 'адрес не указан') . '';
                                    echo $object_display;
                                ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($record['works_count'] ?? '-') ?></span>
                                <br>
                                <?= htmlspecialchars($record['problem']) ?>
                            </td>
                            <td>
                                <span class="<?= $record['result'] === 'Выполнено' ? 'status-active' : ($record['result'] === 'В работе' ? 'status-warning' : 'status-inactive') ?>">
                                    <?= htmlspecialchars($record['result'] ?? 'Не указан') ?>
                                </span>
                            </td>
                            <td class="actions-column">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary edit-avr"
                                        data-id="<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Редактировать запись №<?= htmlspecialchars((string) $record['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        title="Редактировать">
                                    <i class="fas fa-edit" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php endif; ?>
            </table>

            <div class="count">Найдено записей: <?= $total_records_count; ?></div>
        </div>
    </div>
</div>

<!-- Модальное окно добавления записи -->
<div id="addAvrModal" class="modal" style="display: none;">
    <div class="modal-content">
        <?php include __DIR__ . '/add_avr.php'; ?>
    </div>
</div>

<!-- Модальное окно редактирования записи -->
<div id="editAvrModal" class="modal" style="display: none;">
    <div class="modal-content">
        <?php include __DIR__ . '/edit_avr.php'; ?>
    </div>
</div>


<script>
$(document).ready(function() {
    // Обработка формы добавления записи
    $('#addAvrForm').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#addAvrModal button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        
        spinner.removeClass('d-none');
        submitBtn.prop('disabled', true);
        
        $('#addErrorAlert').addClass('d-none');
        $('#addSuccessAlert').addClass('d-none');
        
        const formData = new FormData(form[0]);
               
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // console.log('Ответ сервера:', response);
                    resetAddAvrForm();
                    $('#addSuccessAlert').removeClass('d-none').text(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    // console.log('Ответ ошибки сервера:', response);
                    $('#addErrorAlert').removeClass('d-none').text(response.message);
                }
            },
            error: function(xhr, status, error) {
                // console.log('Ошибка AJAX:', error);
                $('#addErrorAlert').removeClass('d-none').text('Ошибка сервера: ' + xhr.status);
            },
            complete: function() {
                spinner.addClass('d-none');
                submitBtn.prop('disabled', false);
            }
        });
    });

});

function confirmDelete(id) {
    if (confirm('Вы уверены, что хотите удалить эту запись?')) {
        window.location.href = '/modules/avr/delete_avr.php?id=' + encodeURIComponent(id);
    }
}


// Функция печати
function printTable() {
    const tableHTML = document.getElementById('avrTable').outerHTML;
    const temp = document.createElement('div');
    temp.innerHTML = tableHTML;
    temp.querySelectorAll('th:last-child, td.actions-column').forEach(cell => cell.remove());

    // Удаляем скрытые архивные строки и строку аккордеона (если нужно печатать только активные)
    const archivedBody = temp.querySelector('#archivedAvrBody');
    if (archivedBody) archivedBody.remove();
    const accordionRow = temp.querySelector('#accordionRow');
    if (accordionRow) accordionRow.remove();
    
    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.visibility = 'hidden';
    document.body.appendChild(iframe);
    
    const printDocument = iframe.contentWindow.document;
    printDocument.open();
    printDocument.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                table {width: 100%; border-collapse: collapse;}
                th, td {border: 1px solid #000; padding: 8px; text-align: left;}
                th {background-color: #f2f2f2;}
                .count {margin-top: 20px; text-align: left; font-size: 14px; color: #666;}
                h1 {text-align: center;}
            </style>
        </head>
        <body>
            <h1>Реестр ремонтов (AVR)</h1>
            ${temp.innerHTML}
            <div class="count">
                Всего записей: ${document.querySelectorAll('#avrTable tbody tr').length}
            </div>
        </body>
        </html>
    `);
    printDocument.close();
    iframe.contentWindow.focus();
    iframe.contentWindow.print();
    setTimeout(() => {
        document.body.removeChild(iframe);
    }, 1000);
}

// Функция для переключения отображения архивных записей
function toggleArchivedAvr() {
    const body = document.getElementById('archivedAvrBody');
    const icon = document.querySelector('#accordionIcon i');
    
    if (body.style.display === 'none') {
        body.style.display = ''; // показываем
        if (icon) icon.className = 'fas fa-chevron-up';
    } else {
        body.style.display = 'none'; // скрываем
        if (icon) icon.className = 'fas fa-chevron-down';
    }
}


// Функции для открытия/закрытия модальных окон
function openAddModal() {
    document.getElementById('addAvrModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addAvrModal').style.display = 'none';
    if (typeof resetAddAvrForm === 'function') {
        resetAddAvrForm();
    }
}

function openEditModal() {
    document.getElementById('editAvrModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editAvrModal').style.display = 'none';
}

// Закрытие при клике на фон
window.onclick = function(event) {
    const addModal = document.getElementById('addAvrModal');
    const editModal = document.getElementById('editAvrModal');
    
    if (event.target === addModal) {
        closeAddModal();
    }
    if (event.target === editModal) {
        closeEditModal();
    }
}

// Автоматическое скрытие алертов через 5 секунд
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (alert && alert.close) {
            alert.close();
        }
    });
}, 5000);
</script>


</body>
</html>