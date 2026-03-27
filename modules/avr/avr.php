<?php
// avr.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth(); // Проверяем авторизацию
checkSuperuser(); // Только для суперпользователей!

// Получаем информацию о текущем пользователе
$currentUser = getCurrentUser();

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    $avr_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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

<div class="container">
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

                <a href="/charts.php" class="map-button">
                    <span class="button-text">Графики</span>
                    <i class="fas fa-chart-line"></i>
                </a>

                <!-- КНОПКА ДОБАВЛЕНИЯ - без data-bs-toggle, с onclick -->
                <button type="button" class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>
                </button>

                <button type="button" class="btn btn-secondary" onclick="printTable()">
                    <i class="fas fa-print"></i>
                </button>
            </div>

            <table id="avrTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th style="width: 300px;">Объект</th>
                        <th>Проблема</th>
                        <th>Работ</th>
                        <!-- <th>Работа</th> -->
                        <!-- <th>Кол-во</th> -->
                        <!-- <th>Ед. изм.</th> -->
                        <!-- <th>User</th> -->
                        <th>Статус</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avr_records)): ?>
                        <tr>
                            <td colspan="10" class="text-center">Нет данных</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avr_records as $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($record['id']) ?></td>
                                <td><?= date('d.m.Y', strtotime($record['insert_date'])) ?></td>
                                <td>
                                    <?php 
                                    if (!empty($record['object_customer'])) {
                                        $object_display = '<strong>' . htmlspecialchars($record['object_customer']) . '</strong>';
                                        $object_display .= '<br>' . htmlspecialchars($record['object_address'] ?? 'адрес не указан') . '';
                                        // $object_display .= ' [ID:' . htmlspecialchars($record['id']) . ']';
                                        echo $object_display;
                                    } else {
                                        echo htmlspecialchars($record['object_id'] ?? 'Не указан');
                                    }
                                    ?>
                                </td>

                                <td><?= htmlspecialchars($record['problem']) ?></td>
                                <td class="text-center">
                                    <?php $works_count = $record['works_count'] ?? 0;
                                        if ($works_count > 0) {
                                            echo '<span class="badge bg-info">' . $works_count . '</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">0</span>';
                                        }
                                    ?>
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
                                            class="btn btn-sm btn-warning edit-avr" 
                                            data-id="<?= $record['id'] ?>"
                                            title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- <button onclick="confirmDelete(<?= $record['id'] ?>)" 
                                            class="btn btn-sm btn-danger" 
                                            title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="count">Найдено записей: <?= count($avr_records); ?></div>
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
    // Открытие модального окна редактирования
    $('.edit-avr').click(function() {
        const id = $(this).data('id');
        loadAvrData(id);
    });

    // Загрузка данных для редактирования
    function loadAvrData(id) {
        $.ajax({
            url: '/../../modules/avr/get_avr_ajax.php',
            type: 'GET',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // console.log('Данные получены:', response.data);
                    
                    // Заполняем все поля формы
                    $('#edit_id').val(response.data.id);
                    $('#edit_problem').val(response.data.problem);
                    $('#edit_work_name').val(response.data.work_name);
                    $('#edit_quantity').val(response.data.quantity);
                    $('#edit_unit').val(response.data.unit);
                    
                    // Устанавливаем выбранный объект в select
                    if (response.data.object_id) {
                        $('#edit_object_id').val(response.data.object_id);
                    }
                    
                    // user_id не устанавливаем, так как он readonly и уже имеет значение
                    $('#edit_result').val(response.data.result);
                    
                    // Устанавливаем дату
                    if (response.data.insert_date) {
                        const date = new Date(response.data.insert_date);
                        const formattedDate = date.toISOString().slice(0, 16);
                        $('#edit_insert_date').val(formattedDate);
                    }
                    
                    $('#editAvrModalLabel').text('Редактировать запись #' + response.data.id);
                    
                    // Скрываем алерты
                    $('#editErrorAlert').addClass('d-none');
                    $('#editSuccessAlert').addClass('d-none');
                    
                    // Открываем модальное окно
                    openEditModal();
                    
                } else {
                    alert(response.message || 'Ошибка загрузки данных');
                }
            },
            error: function(xhr, status, error) {
                // console.log('Ошибка AJAX:', status, error);
                alert('Ошибка сервера при загрузке данных');
            }
        });
    }
    
    // Обработка формы добавления записи
    $('#addAvrForm').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        
        spinner.removeClass('d-none');
        submitBtn.prop('disabled', true);
        
        $('#addErrorAlert').addClass('d-none');
        $('#addSuccessAlert').addClass('d-none');
        
        const formData = new FormData(form[0]);
        
        // console.log('Отправляемые данные:');
        // for (let pair of formData.entries()) {
        //     console.log(pair[0] + ': ' + pair[1]);
        // }
        
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
                    $('#addSuccessAlert').removeClass('d-none').text(response.message);
                    form[0].reset();
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

    // Обработка формы редактирования записи
    $('#editAvrForm').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        
        const formData = new FormData(form[0]);
        
        // console.log('Отправляемые данные:');
        // for (let pair of formData.entries()) {
        //     console.log(pair[0] + ': ' + pair[1]);
        // }
        
        spinner.removeClass('d-none');
        submitBtn.prop('disabled', true);
        
        $('#editErrorAlert').addClass('d-none');
        $('#editSuccessAlert').addClass('d-none');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                // console.log('Ответ сервера:', response);
                if (response.success) {
                    $('#editSuccessAlert').removeClass('d-none').text(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#editErrorAlert').removeClass('d-none').text(response.message);
                }
            },
            error: function(xhr, status, error) {
                // console.log('Ошибка AJAX:', status, error);
                $('#editErrorAlert').removeClass('d-none').text('Ошибка сервера: ' + xhr.status);
            },
            complete: function() {
                spinner.addClass('d-none');
                submitBtn.prop('disabled', false);
            }
        });
    });
    
    // Сброс формы при закрытии модального окна
    $('#addAvrModal .close-modal, #addAvrModal .cancel-btn').on('click', function() {
        closeAddModal();
        $('#addAvrForm')[0].reset();
        $('#addErrorAlert').addClass('d-none');
        $('#addSuccessAlert').addClass('d-none');
    });
    
    $('#editAvrModal .close-modal, #editAvrModal .cancel-btn').on('click', function() {
        closeEditModal();
        $('#editErrorAlert').addClass('d-none');
        $('#editSuccessAlert').addClass('d-none');
    });
});

function confirmDelete(id) {
    if (confirm('Вы уверены, что хотите удалить эту запись?')) {
        window.location.href = '/../../modules/avr/delete_avr.php?id=' + id;
    }
}


// Функция печати
function printTable() {
    const tableHTML = document.getElementById('avrTable').outerHTML;
    const temp = document.createElement('div');
    temp.innerHTML = tableHTML;
    const rows = temp.querySelectorAll('tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        if (cells.length > 9) {
            cells[9].remove();
        }
    });
    
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

// Функции для открытия/закрытия модальных окон
function openAddModal() {
    document.getElementById('addAvrModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addAvrModal').style.display = 'none';
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
        addModal.style.display = 'none';
    }
    if (event.target === editModal) {
        editModal.style.display = 'none';
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