<?php
// dogovor.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';
checkAuth(); // Проверяем авторизацию
checkSuperuser(); // Только для суперпользователей!
// Получаем информацию о текущем пользователе
$currentUser = getCurrentUser();

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем данные из таблицы
    $sql = "SELECT * FROM LIFTEH_dogovor ORDER BY is_active DESC, CAST(number AS INTEGER)";
    $stmt = $db->query($sql);
    $dogovors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем текущий месяц
    $currentMonth = date('m');
    $currentYear = date('Y');
    $currentMonthFormatted = date('Y-m');
    
    // Для каждого договора определяем статус выполнения ТО
    foreach ($dogovors as &$dogovor) {
        $dogovorId = $dogovor['id'];
        
        // Получаем все объекты, привязанные к этому договору
        $sqlObjects = "SELECT id FROM LIFTEH_object WHERE dogovor_id = :dogovor_id";
        $stmtObjects = $db->prepare($sqlObjects);
        $stmtObjects->execute([':dogovor_id' => $dogovorId]);
        $objects = $stmtObjects->fetchAll(PDO::FETCH_ASSOC);
        
        $totalObjects = count($objects);
        
        if ($totalObjects === 0) {
            // Нет привязанных объектов - без выделения
            $dogovor['service_status'] = 'no_objects';
            continue;
        }
        
        $objectsWithService = 0;
        
        // Для каждого объекта проверяем, было ли ТО в текущем месяце
        foreach ($objects as $object) {
            $objectId = $object['id'];
            
            // Проверяем наличие записей обслуживания за текущий месяц
            $sqlService = "SELECT COUNT(*) as service_count FROM LIFTEH_service 
                          WHERE object_id = :object_id 
                          AND strftime('%Y-%m', service_date) = :year_month";
            $stmtService = $db->prepare($sqlService);
            $stmtService->execute([
                ':object_id' => $objectId,
                ':year_month' => $currentMonthFormatted
            ]);
            $serviceResult = $stmtService->fetch(PDO::FETCH_ASSOC);
            
            if ($serviceResult['service_count'] > 0) {
                $objectsWithService++;
            }
        }
        
        // Определяем статус выполнения ТО
        if ($objectsWithService === $totalObjects) {
            // На ВСЕХ объектах проведено ТО
            $dogovor['service_status'] = 'all_done';
        } elseif ($objectsWithService === 0) {
            // НИ НА ОДНОМ объекте не проведено ТО - нет выделения
            $dogovor['service_status'] = 'none_done';
        } else {
            // ТО проведено ТОЛЬКО НА НЕКОТОРЫХ объектах
            $dogovor['service_status'] = 'partial_done';
        }
    }
    
    // Разделяем договоры на активные и неактивные
    $activeDogovors = [];
    $inactiveDogovors = [];
    
    foreach ($dogovors as $dogovor) {
        if ($dogovor['is_active'] == 1) {
            $activeDogovors[] = $dogovor;
        } else {
            $inactiveDogovors[] = $dogovor;
        }
    }
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
    <?php $pageTitle = 'Учет договоров'; include __DIR__ . '/../../includes/header.php'; ?>
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

                <a href="avr.php" class="map-button">
                    <span class="button-text">АВР</span>
                    <i class="fa-solid fa-gear"></i>
                </a>

                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDogovorModal">
                    <i class="fas fa-plus"></i>
                </button>

                <!-- КНОПКА ПЕЧАТИ -->
                <button type="button" class="btn btn-secondary" onclick="printTable()">
                    <i class="fas fa-print"></i>
                </button>
              </div>

              <!-- Легенда цветов
              <div class="status-legend">
                  <div class="legend-item">
                      <div class="legend-color green"></div>
                      <span>Все работы выполнены</span>
                  </div>
                  <div class="legend-item">
                      <div class="legend-color yellow"></div>
                      <span>Работы выполнены частично</span>
                  </div>
                  <div class="legend-item">
                      <div style="width: 20px; height: 20px; border-radius: 3px; background-color: transparent; border: 1px solid #dee2e6;"></div>
                      <span>Работы не выполнялись или нет объектов</span>
                  </div>
              </div> -->

                <table id="dogovorTable">
                    <thead>
                        <tr>
                            <th>№</th>
                            <th>Клиент</th>
                            <th>Дата</th>
                            <th>Срок</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Активные договоры -->
                        <?php foreach ($activeDogovors as $dogovor): ?>
                            <?php
                            // Определяем CSS класс для строки в зависимости от статуса обслуживания
                            $rowClass = '';
                            if ($dogovor['service_status'] == 'all_done') {
                                $rowClass = 'service-status-all';
                            } elseif ($dogovor['service_status'] == 'partial_done') {
                                $rowClass = 'service-status-partial';
                            }
                            // none_done и no_objects - без выделения
                            ?>
                            <tr class="edit-dogovor <?= $rowClass ?>" data-id="<?= $dogovor['id'] ?>">
                                <td><strong><?= htmlspecialchars($dogovor['number']) ?></strong></td>
                                <td><?= htmlspecialchars($dogovor['customer']) ?></td>
                                <td><?= date('d.m.Y', strtotime($dogovor['date'])) ?></td>
                                <td><?= date('d.m.Y', strtotime($dogovor['validate'])) ?></td>
                                <td>
                                    <span class="<?= $dogovor['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $dogovor['is_active'] ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $dogovor['is_active'] ? 'Актив.' : 'Нет' ?>
                                    </span>

                                    <span class="<?= $dogovor['prolong'] ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $dogovor['prolong'] ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $dogovor['prolong'] ? 'Лонг.' : 'Без' ?>
                                    </span>

                                    <span class="<?= $dogovor['financing'] !== 'Бюджет' ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $dogovor['financing'] !== 'Бюджет' ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $dogovor['financing'] !== 'Бюджет' ? 'Собств.' : 'Бюджет' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Строка-аккордеон для неактивных договоров (только если есть) -->
                        <?php if (count($inactiveDogovors) > 0): ?>
                            <tr class="accordion-row" id="accordionRow">
                                <td colspan="5" style="background-color: #f5f5f5; cursor: pointer; padding: 12px; text-align: center; font-weight: bold;" onclick="toggleInactiveDogovors()">
                                    <span id="accordionIcon">
                                        <i class="fas fa-chevron-down"></i>
                                    </span>
                                    Неактивные договоры 
                                    <span style="background-color: #6c757d; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; margin-left: 8px;">
                                        <?= count($inactiveDogovors) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    
                    <!-- Скрытые неактивные договоры -->
                    <tbody id="inactiveDogovorsBody" style="display: none;">
                        <?php foreach ($inactiveDogovors as $dogovor): ?>
                            <?php
                            // Определяем CSS класс для строки в зависимости от статуса обслуживания
                            $rowClass = '';
                            if ($dogovor['service_status'] == 'all_done') {
                                $rowClass = 'service-status-all';
                            } elseif ($dogovor['service_status'] == 'partial_done') {
                                $rowClass = 'service-status-partial';
                            }
                            ?>
                            <tr class="edit-dogovor <?= $rowClass ?> inactive-row" data-id="<?= $dogovor['id'] ?>">
                                <td><strong><?= htmlspecialchars($dogovor['number']) ?></strong></td>
                                <td><?= htmlspecialchars($dogovor['customer']) ?></td>
                                <td><?= date('d.m.Y', strtotime($dogovor['date'])) ?></td>
                                <td><?= date('d.m.Y', strtotime($dogovor['validate'])) ?></td>
                                <td>
                                    <span class="<?= $dogovor['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $dogovor['is_active'] ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $dogovor['is_active'] ? 'Актив.' : 'Нет' ?>
                                    </span>

                                    <span class="<?= $dogovor['prolong'] ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $dogovor['prolong'] ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $dogovor['prolong'] ? 'Лонг.' : 'Без' ?>
                                    </span>

                                    <span class="<?= $dogovor['financing'] !== 'Бюджет' ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $dogovor['financing'] !== 'Бюджет' ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $dogovor['financing'] !== 'Бюджет' ? 'Собств.' : 'Бюджет' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="count">Найдено записей: <?= count($dogovors); ?></div>

            </div>
        </div>
    </div>

    <!-- Модальное окно добавления договора -->
    <div class="modal fade" id="addDogovorModal" tabindex="-1" aria-labelledby="addDogovorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <?php include __DIR__ . '/add_dogovor.php'; ?>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования договора -->
    <div class="modal fade" id="editDogovorModal" tabindex="-1" aria-labelledby="editDogovorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <?php include __DIR__ . '/edit_dogovor.php'; ?>
            </div>
        </div>
    </div>

    <script>
        // Функция для переключения отображения неактивных договоров
        function toggleInactiveDogovors() {
            const body = document.getElementById('inactiveDogovorsBody');
            const icon = document.querySelector('#accordionIcon i');
            
            if (body.style.display === 'none') {
                body.style.display = '';
                if (icon) {
                    icon.className = 'fas fa-chevron-up';
                }
            } else {
                body.style.display = 'none';
                if (icon) {
                    icon.className = 'fas fa-chevron-down';
                }
            }
        }
        
        $(document).ready(function() {
            // Открытие модального окна редактирования
            $('.edit-dogovor').click(function() {
                const id = $(this).data('id');
                loadDogovorData(id);
            });

            // Загрузка данных договора для редактирования
            function loadDogovorData(id) {
                $.ajax({
                    url: '/../../modules/dogovor/get_dogovor_ajax.php',
                    type: 'GET',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            console.log('Данные получены:', response.data);
                            
                            // Сначала заполняем ВСЕ поля формы
                            $('#edit_id').val(response.data.id);
                            $('#edit_customer').val(response.data.customer);
                            $('#edit_number').val(response.data.number);
                            $('#edit_date').val(response.data.date);
                            $('#edit_validate').val(response.data.validate);
                            $('#edit_is_active').prop('checked', response.data.is_active == 1);
                            $('#edit_created_at').text(response.data.created_at_formatted);
                            $('#edit_updated_at').text(response.data.updated_at_formatted);
                            $('#editDogovorModalLabel').text('Редактировать договор #' + response.data.id);
                            
                            // Устанавливаем radio-кнопку prolong
                            const prolongValue = parseInt(response.data.prolong);
                            console.log('prolongValue:', prolongValue, 'type:', typeof prolongValue);
                            
                            // Сбрасываем обе кнопки
                            $('#prolong_yes').prop('checked', false);
                            $('#prolong_no').prop('checked', false);
                            
                            // Устанавливаем нужную кнопку
                            if (prolongValue === 1) {
                                $('#prolong_yes').prop('checked', true);
                                console.log('Установлена кнопка: Да');
                            } else {
                                $('#prolong_no').prop('checked', true);
                                console.log('Установлена кнопка: Нет');
                            }

                            // Устанавливаем radio-кнопку Финансирования
                            const financingValue = response.data.financing;
                            console.log('financingValue:', financingValue);
                            
                            // Сбрасываем все кнопки финансирования
                            $('input[name="financing"]').prop('checked', false);

                            // Устанавливаем нужную кнопку
                            if (financingValue === 'Собственные') {
                                $('#edit_financing_sobstvennye').prop('checked', true);
                                console.log('Установлено: Собственные');
                            } else if (financingValue === 'Бюджет') {
                                $('#edit_financing_budjet').prop('checked', true);
                                console.log('Установлено: Бюджет');
                            }
                            
                            // Устанавливаем чекбоксы to, avr, efi
                            $('#edit_to1').prop('checked', response.data.to1 == 1);
                            $('#edit_avr').prop('checked', response.data.avr == 1);
                            $('#edit_efi').prop('checked', response.data.efi == 1);

                            console.log('Установлены чекбоксы:', {
                                to1: response.data.to1,
                                avr: response.data.avr,
                                efi: response.data.efi
                            });
                            
                            // Принудительно обновляем отображение radio-кнопок
                            $('input[name="prolong"]').trigger('change');
                            $('input[name="financing"]').trigger('change');
                            
                            // Скрываем алерты
                            $('#editErrorAlert').addClass('d-none');
                            $('#editSuccessAlert').addClass('d-none');
                            
                            // ПОСЛЕ установки всех значений показываем модальное окно
                            const editModalElement = document.getElementById('editDogovorModal');
                            const editModal = bootstrap.Modal.getOrCreateInstance(editModalElement);
                            editModal.show();
                            
                            // Дополнительная проверка после показа модального окна
                            setTimeout(function() {
                                console.log('После открытия модального окна:');
                                console.log('prolong_yes checked:', $('#prolong_yes').is(':checked'));
                                console.log('prolong_no checked:', $('#prolong_no').is(':checked'));
                                console.log('to1 checked:', $('#edit_to1').is(':checked'));
                                console.log('avr checked:', $('#edit_avr').is(':checked'));
                                console.log('efi checked:', $('#edit_efi').is(':checked'));
                            }, 100);
                            
                        } else {
                            alert(response.message || 'Ошибка загрузки данных');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Ошибка AJAX:', status, error);
                        alert('Ошибка сервера при загрузке данных');
                    }
                });
            }
            
            // Обработка формы добавления договора
            $('#addDogovorForm').submit(function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const spinner = submitBtn.find('.spinner-border');
                const originalBtnText = submitBtn.html();
                
                // Показываем спиннер и блокируем кнопку
                spinner.removeClass('d-none');
                submitBtn.prop('disabled', true);
                
                // Скрываем предыдущие сообщения
                $('#addErrorAlert').addClass('d-none');
                $('#addSuccessAlert').addClass('d-none');
                
                // Собираем данные формы
                const formData = new FormData(form[0]);
                
                // Явно добавляем значения для чекбоксов (1 если отмечен, 0 если нет)
                const checkboxes = ['to1', 'avr', 'efi'];
                checkboxes.forEach(function(name) {
                    if ($('#add_' + name).is(':checked')) {
                        formData.set(name, '1');
                    } else {
                        formData.set(name, '0');
                    }
                });
                
                console.log('Отправляемые данные:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Ответ сервера:', response);
                        if (response.success) {
                            // Показываем сообщение об успехе
                            $('#addSuccessAlert').removeClass('d-none').text(response.message);
                            
                            const modal = bootstrap.Modal.getInstance(document.getElementById('addDogovorModal'));
                            if (modal) {
                                modal.hide();
                            }
                            // Очищаем форму
                            form[0].reset();
                            $('#date').val('<?= date("Y-m-d") ?>');
                            $('#validate').val('<?= date("Y-m-d") ?>');
                            $('#is_active').prop('checked', true);
                            // Сбрасываем чекбоксы
                            $('#add_to1, #add_avr, #add_efi').prop('checked', false);
                            
                            // Обновляем страницу через 1.5 секунды
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            console.log('Ответ ошибки сервера:', response);
                            // Показываем сообщение об ошибке
                            $('#addErrorAlert').removeClass('d-none').text(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Ошибка AJAX:', error);
                        console.log('Ответ сервера:', xhr.responseText);
                        $('#addErrorAlert').removeClass('d-none').text('Ошибка сервера: ' + xhr.status);
                    },
                    complete: function() {
                        // Скрываем спиннер и разблокируем кнопку
                        spinner.addClass('d-none');
                        submitBtn.prop('disabled', false);
                    }
                });
            });

            // Обработка формы редактирования договора
            $('#editDogovorForm').submit(function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const spinner = submitBtn.find('.spinner-border');
                
                // Собираем данные формы
                const formData = new FormData(form[0]);
                
                // Явно добавляем значения для чекбоксов (1 если отмечен, 0 если нет)
                const checkboxes = ['to1', 'avr', 'efi'];
                checkboxes.forEach(function(name) {
                    if ($('#edit_' + name).is(':checked')) {
                        formData.set(name, '1');
                    } else {
                        formData.set(name, '0');
                    }
                });
                
                console.log('Отправляемые данные:');
                for (let pair of formData.entries()) {
                    console.log(pair[0] + ': ' + pair[1]);
                }
                
                // Показываем спиннер и блокируем кнопку
                spinner.removeClass('d-none');
                submitBtn.prop('disabled', true);
                
                // Скрываем предыдущие сообщения
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
                        console.log('Ответ сервера:', response);
                        if (response.success) {
                            // Показываем сообщение об успехе
                            $('#editSuccessAlert').removeClass('d-none').text(response.message);
                            
                            // Обновляем страницу через 1.5 секунды
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            // Показываем сообщение об ошибке
                            $('#editErrorAlert').removeClass('d-none').text(response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Ошибка AJAX:', status, error);
                        console.log('Ответ сервера:', xhr.responseText);
                        $('#editErrorAlert').removeClass('d-none').text('Ошибка сервера: ' + xhr.status);
                    },
                    complete: function() {
                        // Скрываем спиннер и разблокируем кнопку
                        spinner.addClass('d-none');
                        submitBtn.prop('disabled', false);
                    }
                });
            });
            
            // Сброс формы при закрытии модального окна
            $('#addDogovorModal').on('hidden.bs.modal', function() {
                $('#addDogovorForm')[0].reset();
                $('#date').val('<?= date("Y-m-d") ?>');
                $('#validate').val('<?= date("Y-m-d") ?>');
                $('#is_active').prop('checked', true);
                $('#addErrorAlert').addClass('d-none');
                $('#addSuccessAlert').addClass('d-none');
            });
            
            $('#editDogovorModal').on('hidden.bs.modal', function() {
                $('#editErrorAlert').addClass('d-none');
                $('#editSuccessAlert').addClass('d-none');
            });

        });
        
        function confirmDelete(id) {
            if (confirm('Вы уверены, что хотите удалить этот договор?')) {
                window.location.href = '/../../modules/dogovor/delete_dogovor.php?id=' + id;
            }
        }
        
        // Автоматическое скрытие алертов через 5 секунд
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Простая функция печати (без перезагрузки)
        function printTable() {
            // Получаем HTML таблицы
            const tableHTML = document.getElementById('dogovorTable').outerHTML;
            // Создаем временный DOM элемент
            const temp = document.createElement('div');
            temp.innerHTML = tableHTML;
            // Удаляем ненужные ячейки (последнюю колонку с кнопками)
            const rows = temp.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                if (cells.length > 5) {
                    cells[5].remove();
                }
            });
            
            // Создаем скрытый iframe для печати
            const iframe = document.createElement('iframe');
            iframe.style.position = 'absolute';
            iframe.style.visibility = 'hidden';
            
            document.body.appendChild(iframe);
            
            // Формируем содержимое для печати
            const printDocument = iframe.contentWindow.document;
            printDocument.open();
            printDocument.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                   <style>
                        .date, th {text-align: center;}
                        table {width: 100%; border-collapse: collapse;}
                        th, td {border: 1px solid #000; padding: 5px 5px;}
                        #dogovorTable th:nth-child(5), #dogovorTable td:nth-child(5) {width: 50px; text-align: center;}
                        #dogovorTable th:nth-child(1), #dogovorTable td:nth-child(1) {text-align: center;}
                        .service-status-all {background-color: #d4edda !important;}
                        .service-status-partial {background-color: #fff3cd !important;}
                    </style>
                </head>
                <body>
                    <h1 style="text-align: center;">Реестр договоров</h1>
                    ${temp.innerHTML}
                    <div class="count" style="margin-top: 20px; text-align: left; font-size: 14px; color: #666;">
                        Всего записей: ${document.querySelectorAll('#dogovorTable tbody tr').length}
                    </div>
                </body>
                </html>
            `);
            printDocument.close();
            // Печатаем
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
            // Удаляем iframe после печати
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 1000);
        }
    </script>
</body>
</html>