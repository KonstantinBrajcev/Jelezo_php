<!-- add-modal.php -->
<!----- Модальное окно добавления новой записи ----->
<div id="addModal" class="modal" onclick="if(event.target===this) closeAddModal()">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Добавление объекта</div>
            <button class="close-modal" onclick="closeAddModal()">&times;</button>
        </div>
        
        <form id="addForm">
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="addCustomer" name="customer" required>
                <label for="addCustomer">Заказчик:</label>
            </div>
            
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="addAddress" name="address">
                <label for="addAddress">Адрес:</label>
            </div>
            
            <div class="d-flex flex-nowrap gap-2">
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="addModel" name="model">
                    <label for="addModel">Модель:</label>
                </div>

                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="addNumber" name="serial_number">
                    <label for="addNumber">Номер:</label>
                </div>
            </div>
            
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="addPhone" name="phone">
                <label for="addPhone">Телефон:</label>
            </div>
            
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="addName" name="name">
                <label for="addName">Имя:</label>
            </div>
            
            <!-- <div class="form-group form-floating">
                <input type="text" class="form-control" id="addWork" name="work">
                <label for="addWork">Работа:</label>
            </div> -->
            
            <!-- Поля для месяцев (M1-M12) -->
            <div class="months-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                <div class="form-group form-floating">
                    <input type="text" class="form-control month-input" id="addM<?php echo $i; ?>" name="M<?php echo $i; ?>">
                    <label for="addM<?php echo $i; ?>">М<?php echo $i; ?> (<?php echo $months[$i] ?? $i; ?>):</label>
                </div>
                <?php endfor; ?>
            </div>
            
            <!-- Поля для координат -->
            <div class="d-flex flex-nowrap gap-2">
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="addLatitude" name="latitude">
                    <label for="addLatitude">Широта (latitude):</label>
                </div>
                
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="addLongitude" name="longitude">
                    <label for="addLongitude">Долгота (longitude):</label>
                </div>
            </div>
            
            <div class="d-flex flex-nowrap gap-2">
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="addFolderId" name="folder_id">
                    <label for="addFolderId">ID папки (folder_id):</label>
                </div>
                
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="addDogovorId" name="dogovor_id">
                    <label for="addDogovorId">ID договора (dogovor_id):</label>
                </div>
            </div>
            
            <div class="form-actions" style="flex-flow: nowrap; margin-top: 20px;">
                <button type="button" class="cancel-btn" onclick="closeAddModal()">Отмена</button>
                <button type="submit" class="edit-btn">Добавить</button>
            </div>
        </form>
    </div>
</div>
<!-- КОНЕЦ Модального окна добавления --->

<script>

let addModal = document.getElementById('addModal');
let addForm = document.getElementById('addForm');

// Функция открытия модального окна добавления
function openAddModal() {
    if (addModal) {
        addModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

// Функция закрытия модального окна добавления
function closeAddModal() {
    if (addModal) {
        addModal.style.display = 'none';
        document.body.style.overflow = '';
        if (addForm) {
            addForm.reset();
        }
    }
}

// Обработчик отправки формы добавления
if (addForm) {
    addForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Собираем данные из формы
        const formData = new FormData(addForm);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Показываем индикатор загрузки
        showLoading(true);
        
        try {
            const response = await fetch('/../../modules/service/add_object.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            // Проверяем статус ответа
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Проверяем Content-Type ответа
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Получен не JSON ответ:', text.substring(0, 200));
                throw new Error('Сервер вернул не JSON ответ');
            }
            
            const result = await response.json();
            
            if (result.success) {
                showMessage('Запись успешно добавлена', 'success');
                closeAddModal();
                // Перезагружаем страницу, чтобы увидеть новую запись
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showMessage('Ошибка: ' + (result.error || 'Неизвестная ошибка'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showMessage('Ошибка при добавлении записи: ' + error.message, 'error');
        } finally {
            showLoading(false);
        }
    });
}

// Функция показа сообщений (добавьте, если нет)
function showMessage(message, type = 'info') {
    // Создаем временное уведомление
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        border-radius: 4px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Функция показа/скрытия индикатора загрузки
function showLoading(show) {
    let loading = document.getElementById('loading');
    if (loading) {
        loading.style.display = show ? 'block' : 'none';
    }
}

// Закрытие по клику вне модального окна
window.addEventListener('click', function(e) {
    if (e.target === addModal) {
        closeAddModal();
    }
});
</script>