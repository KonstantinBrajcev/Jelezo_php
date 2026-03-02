<!-- edit-modal.php -->
<!----- Модальное окно редактирования ----->
<div id="editModal" class="modal" onclick="if(event.target===this) closeEditModal()">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Редактирование</div>
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form id="editForm">
            <input type="hidden" id="editId" name="id">
            
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="editCustomer" name="customer" required>
                <label for="editCustomer">Заказчик:</label>
            </div>
            
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="editAddress" name="address">
                <label for="editAddress">Адрес:</label>
            </div>
            
            <div class="d-flex flex-nowrap gap-2">
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="editModel" name="model">
                    <label for="editModel">Модель:</label>
                </div>

                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="editNumber" name="serial_number">
                    <label for="editNumber">Номер:</label>
                </div>
            </div>
            
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="editPhone" name="phone">
                <label for="editPhone">Телефон:</label>
            </div>
            
            <div class="form-group form-floating">
                <input type="text" class="form-control" id="editName" name="name">
                <label for="editName">Имя:</label>
            </div>
            
            <!-- Добавляем поля для координат -->
            <div class="d-flex flex-nowrap gap-2">
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="editLatitude" name="latitude">
                    <label for="editLatitude">Широта (latitude):</label>
                </div>
                
                <div class="form-group form-floating flex-fill">
                    <input type="text" class="form-control" id="editLongitude" name="longitude">
                    <label for="editLongitude">Долгота (longitude):</label>
                </div>
            </div>
            
            <div class="form-actions" style="flex-flow: nowrap;">
                <button type="button" class="cancel-btn" onclick="closeEditModal()">Отмена</button>
                <button type="submit" class="edit-btn">Сохранить</button>
            </div>
        </form>
    </div>
</div>
<!-- КОНЕЦ Модального окна редактирования --->