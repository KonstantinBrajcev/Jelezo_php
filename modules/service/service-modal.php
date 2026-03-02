<!-- modules/service/service-modal.php -->
<!-- Модальное окно для добавления ТО -->
 
<div id="serviceModal" class="modal">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <div class="modal-title">Обслуживание</div>
            <button class="close-modal" onclick="closeServiceModal()">&times;</button>
        </div>
        
        <form id="serviceForm">
            <input type="hidden" id="serviceObjectId" name="object_id">
            
            <div class="form-group form-floating">
                <input type="text" id="serviceCustomer" class="form-control" readonly>
                <label for="serviceCustomer">Заказчик:</label>
            </div>
            
            <div class="form-group form-floating">
                <input type="text" id="serviceAddress" class="form-control" readonly>
                <label for="serviceAddress">Адрес:</label>
            </div>
            
            <div class="form-group form-floating" hidden>
                <input type="datetime-local" class="form-control" id="serviceDate" name="service_date" required>
                <label for="serviceDate">Дата обслуживания:</label>
            </div>
            
            <div class="form-group form-floating">
                <select class="form-control" id="serviceResult" name="result" required onchange="handleResultChange()">
                    <option value="0">В исправном состоянии</option>
                    <option value="1">Требуется устранить замечания</option>
                    <option value="2">Не работает</option>
                </select>
                <label for="serviceResult">Результат:</label>
            </div>
            
            <div class="form-group form-floating">
                <textarea class="form-control" id="serviceComments" name="comments" placeholder="Введите комментарии по проведенному ТО..."></textarea>
                <label for="serviceComments">Комментарии:</label>
            </div>
            
            <!-- Блок загрузки фото -->
            <div class="form-group form-floating mb-2" id="fileUploadContainer" style="display: none;">
                <label for="serviceFoto" style="transform: scale(.85) translateY(-.9rem) translateX(.15rem); padding-bottom: 10px;">Фото:</label>
                <input type="file" class="form-control" id="serviceFoto" name="foto[]" multiple accept="image/*">
            </div>

            <!-- Блок истории ТО -->
            <div class="service-history form-floating" id="serviceHistory">
                <div class="no-services">Загрузка истории...</div>
            </div>

            <!-- Блок с кнопками -->
            <div class="form-actions" style="flex-flow: nowrap;">
                <button type="button" class="cancel-btn" onclick="closeServiceModal()">Отмена</button>
                <button type="submit" class="save-btn">Добавить</button>
            </div>
        </form>
    </div>
</div>

