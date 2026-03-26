<?php
// edit_avr.php - ТОЛЬКО HTML форма
// Получаем список объектов для выпадающего списка
try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем список объектов
    $sql_objects = "SELECT id, customer, address FROM LIFTEH_object ORDER BY customer, address";
    $stmt_objects = $db->query($sql_objects);
    $objects_list = $stmt_objects->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $objects_list = [];
}
?>

<div class="modal-header">
    <h5 class="modal-title" id="editAvrModalLabel">Редактирование АВР</h5>
    <button type="button" class="close-modal" onclick="closeEditModal()">&times;</button>
</div>
<div class="modal-body">
    <div id="editErrorAlert" class="alert alert-danger d-none" role="alert"></div>
    <div id="editSuccessAlert" class="alert alert-success d-none" role="alert"></div>
    
    <form id="editAvrForm" action="/../../modules/avr/edit_avr_handler.php" method="POST">
        <input type="hidden" id="edit_id" name="id">
        
        <div class="mb-3 form-group form-floating">
            <select class="form-control" id="edit_object_id" name="object_id" required>
                <option value="">Выберите объект</option>
                <?php foreach ($objects_list as $object): ?>
                    <option value="<?= $object['id'] ?>">
                        <?= htmlspecialchars($object['customer']) ?> - <?= htmlspecialchars($object['address']) ?> (ID: <?= $object['id'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <label for="edit_object_id" class="form-label">Объект <span class="text-danger">*</span></label>
        </div>

        <div class="mb-3 form-group form-floating">
            <textarea style="height: 100px;" class="form-control" id="edit_problem" name="problem" rows="4" placeholder="Введите краткое описание неисправности" required></textarea>
            <label for="edit_problem" class="form-label">Проблема <span class="text-danger">*</span></label>
        </div>
        
        <!-- Блоки работ -->
        <div id="edit-works-container">
            <!-- Сюда будут динамически добавляться работы -->
        </div>
        
        <div class="mb-3" style="height: 10px;">
            <button type="button" class="btn btn-success" style="position: relative; top: -20px;
                left: 8px; display: flex; justify-self: end;" id="edit-add-work-btn">
                <i class="fas fa-plus" style="height: 22px; align-content: center; z-index: 10;"></i>
            </button>
        </div>
        
        <div class="mb-3 form-group form-floating">
            <select class="form-control" id="edit_result" name="result">
                <option value="">Выберите результат</option>
                <option value="В работе">В работе</option>
                <option value="Выполнено">Выполнено</option>
                <option value="Отложено">Отложено</option>
                <option value="Отменено">Отменено</option>
            </select>
            <label for="edit_result" class="form-label">Результат</label>
        </div>
        
        <div class="mb-3" hidden>
            <label for="edit_insert_date" class="form-label">Дата создания</label>
            <input type="datetime-local" class="form-control" id="edit_insert_date" name="insert_date">
        </div>
        
        <div class="mb-3" hidden>
            <label for="edit_user_id" class="form-label">Пользователь</label>
            <input type="text" class="form-control" id="edit_user_id" name="user_id" 
                   value="<?= htmlspecialchars($currentUser['id']) ?>" readonly>
        </div>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary cancel-btn" onclick="closeEditModal()">Отмена</button>
    <button type="submit" form="editAvrForm" class="btn btn-primary save-btn">
        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        Обновить
    </button>
</div>

<script>
let editWorkCounter = 0;

// Функция для добавления блока работы в форму редактирования
function addEditWorkBlock(index, workData = null) {
    const workName = workData ? workData.work_name : '';
    const quantity = workData ? workData.quantity : '';
    const unit = workData ? workData.unit : 'шт';
    
    // Экранируем специальные символы для безопасной вставки в HTML
    const escapedWorkName = workName.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    }).replace(/["']/g, function(m) {
        if (m === '"') return '&quot;';
        if (m === "'") return '&#39;';
        return m;
    });
    
    const workHtml = `
        <div class="work-item mb-2 p-1 border rounded" data-work-index="${index}">
            <div class="mb-2" style="height: 0px;">
                <button type="button" class="btn btn-danger remove-work" style="display: block; 
                position: relative; justify-self: end; right: -10px; top: -10px; z-index: 1;">
                &times;</button>
            </div>
            
            <div class="mb-1 form-group form-floating">
                <input type="text" class="form-control work-name" name="works[${index}][work_name]" value="${escapedWorkName}" placeholder="" required>
                <label class="form-label">Название работы <span class="text-danger">*</span></label>
            </div>
            
            <div class="input-group">
                <span class="input-group-text">Кол-во</span>
                <input type="number" class="form-control work-quantity" name="works[${index}][quantity]" value="${quantity ? quantity : 1}" step="1" required placeholder="">
                <span class="input-group-text">ед.изм.</span>
                <select class="form-select work-unit" name="works[${index}][unit]">
                    <option value="шт" ${unit === 'шт' ? 'selected' : ''}>шт</option>
                    <option value="м" ${unit === 'м' ? 'selected' : ''}>м</option>
                    <option value="м²" ${unit === 'м²' ? 'selected' : ''}>м²</option>
                    <option value="м³" ${unit === 'м³' ? 'selected' : ''}>м³</option>
                    <option value="кг" ${unit === 'кг' ? 'selected' : ''}>кг</option>
                    <option value="л" ${unit === 'л' ? 'selected' : ''}>л</option>
                    <option value="час" ${unit === 'час' ? 'selected' : ''}>час</option>
                    <option value="компл" ${unit === 'компл' ? 'selected' : ''}>компл</option>
                </select>
            </div>
        </div>
    `;
    
    $('#edit-works-container').append(workHtml);
}

// Функция для загрузки данных в форму редактирования
function loadAvrData(id) {
    $.ajax({
        url: '/../../modules/avr/get_avr_ajax.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // console.log('Данные получены:', response.data);
                
                // Очищаем контейнер с работами
                $('#edit-works-container').empty();
                editWorkCounter = 0;
                
                // Заполняем основные поля
                $('#edit_id').val(response.data.id);
                $('#edit_problem').val(response.data.problem);
                $('#edit_object_id').val(response.data.object_id);
                $('#edit_result').val(response.data.result);
                
                // Устанавливаем дату
                if (response.data.insert_date) {
                    const date = new Date(response.data.insert_date);
                    const formattedDate = date.toISOString().slice(0, 16);
                    $('#edit_insert_date').val(formattedDate);
                }
                
                // Загружаем работы
                if (response.data.works && response.data.works.length > 0) {
                    // console.log('Загружено работ:', response.data.works.length);
                    // console.log('Данные работ:', response.data.works);
                    response.data.works.forEach(function(work, index) {
                        addEditWorkBlock(index, work);
                        editWorkCounter++;
                    });
                } else {
                    // console.log('Работы не найдены');
                    // Если нет работ, добавляем пустой блок
                    addEditWorkBlock(0, null);
                    editWorkCounter = 1;
                }
                
                // Настройка видимости кнопок удаления
                setTimeout(function() {
                    updateRemoveButtonsVisibility();
                }, 100);
                
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

// Функция обновления видимости кнопок удаления
function updateRemoveButtonsVisibility() {
    const workItems = $('.work-item');
    if (workItems.length === 1) {
        workItems.find('.remove-work').hide();
    } else {
        workItems.find('.remove-work').show();
    }
}

// Обновление номеров работ
function updateWorkNumbers() {
    $('.work-item').each(function(index) {
        $(this).find('strong').text(`Работа №${index + 1}`);
        $(this).attr('data-work-index', index);
        
        // Обновляем name атрибуты
        $(this).find('.work-name').attr('name', `works[${index}][work_name]`);
        $(this).find('.work-quantity').attr('name', `works[${index}][quantity]`);
        $(this).find('.work-unit').attr('name', `works[${index}][unit]`);
    });
    editWorkCounter = $('.work-item').length;
}

$(document).ready(function() {
    $('.edit-avr').click(function() {
    const id = $(this).data('id');
    // console.log('Клик по кнопке редактирования, ID:', id);
    loadAvrData(id);
    });

    // Добавление новой работы в форму редактирования
    $('#edit-add-work-btn').click(function() {
        addEditWorkBlock(editWorkCounter, null);
        editWorkCounter++;
        updateRemoveButtonsVisibility();
        updateWorkNumbers();
    });
    
    // Удаление работы
    $(document).on('click', '.remove-work', function() {
        $(this).closest('.work-item').remove();
        updateRemoveButtonsVisibility();
        updateWorkNumbers();
    });
    
    // Обработка формы редактирования
    $('#editAvrForm').submit(function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const spinner = submitBtn.find('.spinner-border');
        
        // Проверяем, есть ли хотя бы одна работа
        if ($('.work-item').length === 0) {
            $('#editErrorAlert').removeClass('d-none').text('Добавьте хотя бы одну работу');
            return;
        }
        
        // Проверяем заполнение всех работ
        let isValid = true;
        $('.work-item').each(function(index) {
            const workName = $(this).find('.work-name').val().trim();
            const quantity = $(this).find('.work-quantity').val();
            
            if (!workName) {
                $('#editErrorAlert').removeClass('d-none').text(`В работе №${index + 1} не указано название`);
                isValid = false;
                return false;
            }
            if (!quantity || parseFloat(quantity) <= 0) {
                $('#editErrorAlert').removeClass('d-none').text(`В работе №${index + 1} не указано количество`);
                isValid = false;
                return false;
            }
        });
        
        if (!isValid) {
            return;
        }
        
        const formData = new FormData(form[0]);
        
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
});
</script>