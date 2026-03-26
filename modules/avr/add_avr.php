<?php
// add_avr.php - ТОЛЬКО HTML форма
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
}?>


<div class="modal-header">
    <h5 class="modal-title" id="addAvrModalLabel">Добавление АВР</h5>
    <button type="button" class="close-modal" onclick="closeAddModal()">&times;</button>
</div>
<div class="modal-body">
    <div id="addErrorAlert" class="alert alert-danger d-none" role="alert"></div>
    <div id="addSuccessAlert" class="alert alert-success d-none" role="alert"></div>
    


    <form id="addAvrForm" action="/../../modules/avr/add_avr_handler.php" method="POST">

        <div class="mb-3 form-group form-floating">
            <select class="form-control" id="object_id" name="object_id" required>
                <option value="">Выберите объект</option>
                <?php foreach ($objects_list as $object): ?>
                    <option value="<?= $object['id'] ?>">
                        <?= htmlspecialchars($object['customer']) ?> - <?= htmlspecialchars($object['address']) ?> (ID: <?= $object['id'] ?>)
                    </option>
                    <?php endforeach; ?>
            </select>
            <label for="object_id" class="form-label">Объект <span class="text-danger">*</span></label>
        </div>


        <div class="mb-3 form-group form-floating">
            <textarea style="height: 100px;" class="form-control" id="problem" name="problem" placeholder="Введите краткое описание неисправности" required></textarea>
            <label for="problem" class="form-label">Проблема <span class="text-danger">*</span></label>
        </div>
        
        <!-- Блоки работ -->
        <div id="works-container">
            <div class="work-item mb-2 p-1 border rounded">
                <div class="mb-2" style="height: 0px;">
                    <button type="button" class="btn btn-danger remove-work" style="display: block; 
                    position: relative; justify-self: end; right: -10px; top: -10px; z-index: 1;">
                    &times;</button>
                </div>
                
                <div class="mb-1 form-group form-floating">
                    <input type="text" class="form-control work-name" name="works[0][work_name]" placeholder="" required>
                    <label class="form-label">Название работы <span class="text-danger">*</span></label>
                </div>
                
                <div class="input-group">
                    <span class="input-group-text">Кол-во</span>
                    <input type="number" class="form-control work-quantity" name="works[0][quantity]" step="1" value="1" required placeholder="">
                    <span class="input-group-text">ед.изм.</span>
                    <select class="form-select work-unit" name="works[0][unit]">
                        <option value="шт">шт</option>
                        <option value="м">м</option>
                        <option value="м²">м²</option>
                        <option value="м³">м³</option>
                        <option value="кг">кг</option>
                        <option value="л">л</option>
                        <option value="час">час</option>
                        <option value="компл">компл</option>
                    </select>
                </div>
            </div>
        </div>
        

        <div class="mb-3" style="height: 0px;">
            <button type="button" class="btn btn-success" style="position: relative; top: -25px;
                left: 10px; display: flex; justify-self: end;" id="add-work-btn">
                <i class="fas fa-plus" style="height: 25px; align-content: center;"></i>
            </button>
        </div>
        
        
        <div class="mb-3 form-group form-floating">
            <select class="form-control" id="result" name="result">
                <option value="">Выберите результат</option>
                <option value="В работе">В работе</option>
                <option value="Выполнено">Выполнено</option>
                <option value="Отложено">Отложено</option>
                <option value="Отменено">Отменено</option>
            </select>
            <label for="result" class="form-label">Результат</label>
        </div>
        

        <div class="mb-3" hidden>
            <label for="user_id" class="form-label">Пользователь <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="user_id" name="user_id" 
                   value="<?= htmlspecialchars($currentUser['id']) ?>" readonly>
        </div>

    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary cancel-btn" onclick="closeAddModal()">Отмена</button>
    <button type="submit" form="addAvrForm" class="btn btn-primary save-btn">
        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        Сохранить
    </button>
</div>




<script>
let workCounter = 1;

$(document).ready(function() {
    // Добавление новой работы
    $('#add-work-btn').click(function() {
        workCounter++;
        const newWorkHtml = `
            <div class="work-item mb-3 p-1 border rounded">
                <div class="mb-2" style="height: 0px;">
                    <button type="button" class="btn btn-danger remove-work" style="display: block; 
                    position: relative; justify-self: end; right: -10px; top: -10px; z-index: 1;">
                    &times;</button>
                </div>
                
                <div class="mb-1 form-group form-floating">
                    <input type="text" class="form-control work-name" name="works[${workCounter}][work_name]" placeholder="" required>
                    <label class="form-label">Название работы <span class="text-danger">*</span></label>
                </div>
                
                <div class="input-group">
                    <span class="input-group-text">Кол-во</span>
                    <input type="number" class="form-control work-quantity" name="works[${workCounter}][quantity]" step="1" value="1" required placeholder="">
                    <span class="input-group-text">ед.изм.</span>
                    <select class="form-select work-unit" name="works[${workCounter}][unit]">
                        <option value="шт">шт</option>
                        <option value="м">м</option>
                        <option value="м²">м²</option>
                        <option value="м³">м³</option>
                        <option value="кг">кг</option>
                        <option value="л">л</option>
                        <option value="час">час</option>
                        <option value="компл">компл</option>
                    </select>
                </div>
            </div>
        `;
        $('#works-container').append(newWorkHtml);
        
        // Показываем кнопку удаления у первого блока, если она скрыта
        if ($('.remove-work:visible').length === 0) {
            $('.remove-work').first().show();
        }
    });
    
    // Удаление работы
    $(document).on('click', '.remove-work', function() {
        $(this).closest('.work-item').remove();
        workCounter--;
        
        // Перенумеровываем оставшиеся блоки
        $('.work-item').each(function(index) {
            const newNumber = index + 1;
            $(this).find('strong').text(`Работа №${newNumber}`);
            
            // Обновляем name атрибуты
            $(this).find('.work-name').attr('name', `works[${index}][work_name]`);
            $(this).find('.work-quantity').attr('name', `works[${index}][quantity]`);
            $(this).find('.work-unit').attr('name', `works[${index}][unit]`);
        });
        
        // Скрываем кнопку удаления, если остался только один блок
        if ($('.work-item').length === 1) {
            $('.remove-work').hide();
        }
    });
    
    // Скрываем кнопку удаления у первого блока, если он один
    if ($('.work-item').length === 1) {
        $('.remove-work').hide();
    }
});
</script>