<?php
// edit_dogovor.php
$financingOptions = [
    'Собственные' => 'Собств.',
    'Бюджет' => 'Бюджет'
];
?>



<form id="editDogovorForm" method="POST" action="/../../modules/dogovor/edit_dogovor_ajax.php">
    <input type="hidden" id="edit_id" name="id">
    <div class="modal-header">
        <h5 class="modal-title" id="editDogovorModalLabel">Редактирование договора</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div id="editErrorAlert" class="alert alert-danger d-none" role="alert"></div>
        <div id="editSuccessAlert" class="alert alert-success d-none" role="alert"></div>
        
        <div class="row g-3">
            <div class="col-md-6 form-group form-floating">
                <input type="text" class="form-control" id="edit_customer" name="customer" required>
                <label for="edit_customer" class="form-label">Клиент</label>
            </div>
            
            <div class="col-md-6 form-group form-floating">
                <input type="text" class="form-control" id="edit_number" name="number" required>
                <label for="edit_number" class="form-label">Номер договора</label>
            </div>



            <!-- Финансирование как radio-кнопки -->
            <div class="col-md-3" style="--bs-gutter-y: 0px; padding-bottom: 5px;">
                <label class="form-label">Финансирование</label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="financing" 
                        id="edit_financing_sobstvennye" value="Собственные">
                    <label class="btn btn-outline-primary" for="edit_financing_sobstvennye">Собств.</label>
                    
                    <input type="radio" class="btn-check" name="financing" 
                        id="edit_financing_budjet" value="Бюджет">
                    <label class="btn btn-outline-primary" for="edit_financing_budjet">Бюджет</label>
                </div>
            </div>
            
            <div class="col-md-3" style="--bs-gutter-y: 0px; padding-bottom: 5px;">
                <label class="form-label">Лонгирование</label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="prolong" id="prolong_yes" value="1">
                    <label class="btn btn-outline-primary" for="prolong_yes">Да</label>
                    
                    <input type="radio" class="btn-check" name="prolong" id="prolong_no" value="0">
                    <label class="btn btn-outline-primary" for="prolong_no">Нет</label>
                </div>
            </div>


            <!-- Дата договора и срок действия в одной строке -->
            <div class="col-md-6" style="--bs-gutter-y: 0px;">
                <div class="row">
                    <div class="col-6">
                        <label for="edit_date" class="form-label">Дата договора</label>
                        <input type="date" class="form-control" id="edit_date" name="date" required>
                    </div>
                    <div class="col-6">
                        <label for="edit_validate" class="form-label">Срок действия</label>
                        <input type="date" class="form-control" id="edit_validate" name="validate" required>
                    </div>
                </div>
            </div>
            

<!-- ВИДЫ РАБОТ: -->
<div class="mt-2">
    <div class="col-md-12 form-group form-floating" style="border: 1px solid #ced4da; border-radius: 5px; padding: 5px 10px;">
        <label class="form-label" style="padding: 5px 10px; font-size: 13px; color: #797473;">Работы по договору</label>
        <div style="padding-top: 20px;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="to1" id="edit_to1" value="1">
                <label class="form-check-label" for="edit_to1">
                    ТО (Техническое обслуживание)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="avr" id="edit_avr" value="1">
                <label class="form-check-label" for="edit_avr">
                    АВР (Аварийно-восстановительные работы)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="efi" id="edit_efi" value="1">
                <label class="form-check-label" for="edit_efi">
                    ЭФИ (Электрофизические измерения)
                </label>
            </div>
        </div>
    </div>
</div>
</div>



        <!-- БЛОК С ПРИВЯЗАННЫМИ ОБЪЕКТАМИ -->
        <div class="mt-2">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span>Привязанные объекты</span>
                    <div>
                    <span class="badge bg-secondary" style="padding: 10px 12px;" id="objectsCount">0</span>
                    <button type="button" class="btn btn-sm btn-info" onclick="showManageObjectsModal()">
                        <i class="fas fa-link"></i>
                    </button>
                    </div>
                </div>
                <div class="card-body" style="max-height: 150px; overflow-y: auto;">
                    <div class="alert alert-info d-none" id="noObjects">
                        Нет привязанных объектов
                    </div>
                    <table class="table table-sm table-hover" id="objectsTable" style="margin-top: 0px; margin-bottom: 0px;">
                        <!-- <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название объекта</th>
                                <th>Адрес</th>
                                <th>Действия</th>
                            </tr>
                        </thead> -->
                        <tbody id="objectsList">
                            <!-- Сюда будут добавляться объекты через JavaScript -->
                        </tbody>
                    </table>
                </div>
                <!-- <div class="card-footer">
                    <button type="button" class="btn btn-sm btn-primary" onclick="showAddObjectModal()">
                        <i class="fas fa-plus"></i> Добавить объект
                    </button>
                </div> -->
            </div>
        </div>
    </div>




    <div class="modal-footer" style="justify-content: space-between;">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
            <label class="form-check-label" for="edit_is_active">
                Активный договор
            </label>
        </div>

        <div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
        <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            Обновить
        </button>
        </div>
    </div>
</form>





<!-- Модальное окно для управления привязкой существующих объектов -->
<div class="modal fade" id="manageObjectsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Привязка объектов</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6" style="padding-right: 0px;">
                        <h5 style="justify-self: center;">Непривязанные объекты</h5>
                        <div style="max-height: 360px; overflow-y: auto;">
                            <table class="table table-sm table-hover" style="margin-bottom: 0px; margin-top: 0px;">
                                <!-- <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Адрес</th>
                                        <th></th>
                                    </tr>
                                </thead> -->
                                <tbody id="unlinkedObjectsList">
                                    <!-- Сюда будут добавляться непривязанные объекты -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6" style="padding-left: 0px;">
                        <h5 style="justify-self: center;">Привязанные объекты</h5>
                        <div style="max-height: 360px; overflow-y: auto;">
                            <table class="table table-sm table-hover" style="margin-bottom: 0px; margin-top: 0px;">
                                <!-- <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>Адрес</th>
                                        <th></th>
                                    </tr>
                                </thead> -->
                                <tbody id="linkedObjectsList">
                                    <!-- Сюда будут добавляться привязанные объекты -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div> -->
        </div>
    </div>
</div>




<!-- Модальное окно для добавления нового объекта -->
<div class="modal fade" id="addObjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Добавление нового объекта</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addObjectForm">
                    <input type="hidden" id="object_dogovor_id" name="dogovor_id" value="">
                    <div class="mb-3">
                        <label for="object_name" class="form-label">Название объекта *</label>
                        <input type="text" class="form-control" id="object_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="object_address" class="form-label">Адрес</label>
                        <input type="text" class="form-control" id="object_address" name="address">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" onclick="saveNewObject()">Сохранить</button>
            </div>
        </div>
    </div>
</div>






<script>
// Функция загрузки объектов для договора
function loadObjects(dogovorId) {
    $.ajax({
        url: '/../../modules/dogovor/object_handler.php',
        type: 'GET',
        data: { 
            action: 'get_by_dogovor',
            dogovor_id: dogovorId 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const objects = response.objects || [];
                const count = response.count;
                
                // Обновляем счетчик
                $('#objectsCount').text(count);
                
                // Очищаем список
                const tbody = $('#objectsList');
                tbody.empty();
                
                if (count === 0) {
                    $('#noObjects').removeClass('d-none');
                    $('#objectsTable').addClass('d-none');
                } else {
                    $('#noObjects').addClass('d-none');
                    $('#objectsTable').removeClass('d-none');
                    
                    // Заполняем таблицу
                    objects.forEach(function(obj) {
                        // <td>${obj.id}</td> - была строка с ID объекта
                        // <td>${escapeHtml(obj.name || 'Без названия')}</td> - строка имени
                        tbody.append(`
                            <tr>
                                <td style="text-align: left;">${escapeHtml(obj.address || 'Адрес не указан')}</td>
                                <td style="text-align: right;">
                                    <button type="button" class="btn btn-sm btn-danger" onclick="unlinkObject(${obj.id})" title="Отвязать">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
            } else {
                showErrorMessage(response.message || 'Ошибка загрузки объектов');
            }
        },
        error: function() {
            showErrorMessage('Ошибка сервера при загрузке объектов');
        }
    });
}

// Сохранить новый объект
function saveNewObject() {
    const formData = new FormData($('#addObjectForm')[0]);
    formData.append('action', 'add');
    
    $.ajax({
        url: '/../../modules/dogovor/object_handler.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#addObjectModal').modal('hide');
                loadObjects($('#edit_id').val());
                showSuccessMessage('Объект успешно добавлен');
            } else {
                showErrorMessage(response.message || 'Ошибка при добавлении объекта');
            }
        },
        error: function() {
            showErrorMessage('Ошибка сервера');
        }
    });
}

function unlinkObject(objectId) {
    if (!confirm('Вы уверены, что хотите отвязать этот объект от договора?')) {
        return;
    }
    
    $.ajax({
        url: '/../../modules/dogovor/object_handler.php',
        type: 'POST',
        data: { 
            action: 'unlink',
            object_id: objectId,
            dogovor_id: $('#edit_id').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadObjects($('#edit_id').val());
                showSuccessMessage('Объект отвязан');
            } else {
                showErrorMessage(response.message || 'Ошибка при отвязке объекта');
            }
        },
        error: function() {
            showErrorMessage('Ошибка сервера');
        }
    });
}


// Показать модальное окно управления привязкой
function showManageObjectsModal() {
    const dogovorId = $('#edit_id').val();
    if (!dogovorId) {
        showErrorMessage('Сначала сохраните договор');
        return;
    }
    
    // Очищаем списки перед загрузкой
    $('#linkedObjectsList').empty();
    $('#unlinkedObjectsList').empty();
    
    // Показываем индикатор загрузки (опционально)
    $('#linkedObjectsList').html('<tr><td colspan="3" class="text-center">Загрузка...</td></tr>');
    $('#unlinkedObjectsList').html('<tr><td colspan="3" class="text-center">Загрузка...</td></tr>');
    
    // Загружаем все объекты
    $.ajax({
        url: '/../../modules/dogovor/object_handler.php',
        type: 'GET',
        data: { 
            action: 'get_all',
            current_dogovor_id: dogovorId 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const linked = response.linked || [];
                const unlinked = response.unlinked || [];
                
                // Очищаем списки
                const linkedTbody = $('#linkedObjectsList');
                const unlinkedTbody = $('#unlinkedObjectsList');
                
                linkedTbody.empty();
                unlinkedTbody.empty();
                
                // Заполняем список привязанных объектов
                if (linked.length === 0) {
                    linkedTbody.append('<tr><td colspan="3" class="text-center text-muted">Нет привязанных объектов</td></tr>');
                } else {
                    linked.forEach(function(obj) {
                        // <td>${escapeHtml(obj.name || 'Без названия')}</td> - это строка таблицы
                        linkedTbody.append(`
                            <tr>
                                <td>${escapeHtml(obj.address || 'Адрес не указан')}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="unlinkObjectFromManage(${obj.id})">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
                
                // Заполняем список непривязанных объектов
                if (unlinked.length === 0) {
                    unlinkedTbody.append('<tr><td colspan="3" class="text-center text-muted">Нет доступных объектов</td></tr>');
                } else {
                    unlinked.forEach(function(obj) {
                        // <td>${escapeHtml(obj.name || 'Без названия')}</td> - это строка таблицы
                        unlinkedTbody.append(`
                            <tr>
                                <td>${escapeHtml(obj.address || 'Адрес не указан')}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success" onclick="linkObjectFromManage(${obj.id})">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }
                
                $('#manageObjectsModal').modal('show');
            } else {
                showErrorMessage(response.message || 'Ошибка загрузки объектов');
            }
        },
        error: function() {
            showErrorMessage('Ошибка сервера');
        }
    });
}


// Новая функция для привязки объекта из модального окна управления
function linkObjectFromManage(objectId) {
    $.ajax({
        url: '/../../modules/dogovor/object_handler.php',
        type: 'POST',
        data: { 
            action: 'link',
            object_id: objectId,
            dogovor_id: $('#edit_id').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Обновляем оба списка в модальном окне
                showManageObjectsModal(); // Перезагружаем модальное окно
                loadObjects($('#edit_id').val()); // Обновляем список в основном окне
                showSuccessMessage('Объект привязан');
            } else {
                showErrorMessage(response.message || 'Ошибка при привязке объекта');
            }
        },
        error: function() {
            showErrorMessage('Ошибка сервера');
        }
    });
}

// Новая функция для отвязки объекта из модального окна управления
function unlinkObjectFromManage(objectId) {
    if (!confirm('Вы уверены, что хотите отвязать этот объект от договора?')) {
        return;
    }
    
    $.ajax({
        url: '/../../modules/dogovor/object_handler.php',
        type: 'POST',
        data: { 
            action: 'unlink',
            object_id: objectId,
            dogovor_id: $('#edit_id').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Обновляем оба списка в модальном окне
                showManageObjectsModal(); // Перезагружаем модальное окно
                loadObjects($('#edit_id').val()); // Обновляем список в основном окне
                showSuccessMessage('Объект отвязан');
            } else {
                showErrorMessage(response.message || 'Ошибка при отвязке объекта');
            }
        },
        error: function() {
            showErrorMessage('Ошибка сервера');
        }
    });
}


// Привязать объект к договору
function linkObject(objectId) {
    $.ajax({
        url: '/../../modules/dogovor/object_handler.php',
        type: 'POST',
        data: { 
            action: 'link',
            object_id: objectId,
            dogovor_id: $('#edit_id').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                loadObjects($('#edit_id').val());
                showSuccessMessage('Объект привязан');
            } else {
                showErrorMessage(response.message || 'Ошибка при привязке объекта');
            }
        },
        error: function() {
            showErrorMessage('Ошибка сервера');
        }
    });
}


// Показать сообщение об успехе
function showSuccessMessage(message) {
    $('#editSuccessAlert').removeClass('d-none').text(message);
    setTimeout(function() {
        $('#editSuccessAlert').addClass('d-none');
    }, 3000);
}

// Показать сообщение об ошибке
function showErrorMessage(message) {
    $('#editErrorAlert').removeClass('d-none').text(message);
    setTimeout(function() {
        $('#editErrorAlert').addClass('d-none');
    }, 3000);
}


// Добавьте эту функцию в блок <script>
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


// Добавьте эту функцию в блок <script>
function showAddObjectModal() {
    const dogovorId = $('#edit_id').val();
    if (!dogovorId) {
        showErrorMessage('Сначала сохраните договор');
        return;
    }
    $('#object_dogovor_id').val(dogovorId);
    $('#addObjectForm')[0].reset();
    $('#addObjectModal').modal('show');
}


// Также добавьте обработчик закрытия модального окна, чтобы очищать данные
$('#manageObjectsModal').on('hidden.bs.modal', function () {
    // Очищаем списки при закрытии модального окна
    $('#linkedObjectsList').empty();
    $('#unlinkedObjectsList').empty();
});


// Добавьте обработчик открытия модального окна редактирования для загрузки объектов
$(document).ready(function() {
    // Перехватываем открытие модального окна редактирования
    $('#editDogovorModal').on('shown.bs.modal', function() {
        const dogovorId = $('#edit_id').val();
        if (dogovorId) {
            loadObjects(dogovorId);
        }
    });
});
</script>