<?php
// add_dogovor.php
// Варианты для выпадающего списка финансирования
$financingOptions = [
    'Собственные' => 'Собств.',
    'Бюджет' => 'Бюджет'
];
?>
<form id="addDogovorForm" method="POST" action="/modules/dogovor/add_dogovor_ajax.php">
    <div class="modal-header">
        <h5 class="modal-title" id="addDogovorModalLabel">Добавление договора</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div id="addErrorAlert" class="alert alert-danger d-none" role="alert"></div>
        <div id="addSuccessAlert" class="alert alert-success d-none" role="alert"></div>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label for="customer" class="form-label">Клиент *</label>
                <input type="text" class="form-control" id="customer" name="customer" required>
            </div>
            
            <div class="col-md-6">
                <label for="number" class="form-label">Номер договора *</label>
                <input type="text" class="form-control" id="number" name="number" required>
            </div>
            
            <!-- Финансирование как radio-кнопки -->
            <div class="col-md-3">
                <label class="form-label">Финансирование</label>
                <div class="btn-group" role="group">
                    <?php foreach ($financingOptions as $value => $label): ?>
                        <input type="radio" class="btn-check" name="financing" 
                               id="financing_<?= htmlspecialchars(strtolower($value)) ?>" 
                               value="<?= htmlspecialchars($value) ?>" <?= $value == 'Собственные' ? 'checked' : '' ?>>
                        <label class="btn btn-outline-primary" for="financing_<?= htmlspecialchars(strtolower($value)) ?>">
                            <?= htmlspecialchars($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Лонгирование</label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="add_prolong" id="add_prolong_yes" value="1" checked>
                    <label class="btn btn-outline-primary" for="add_prolong_yes">Да</label>

                    <input type="radio" class="btn-check" name="add_prolong" id="add_prolong_no" value="0">
                    <label class="btn btn-outline-primary" for="add_prolong_no">Нет</label>
                </div>
            </div>


            <div class="col-md-3">
                <label for="date" class="form-label">Дата договора</label>
                <input type="date" class="form-control" id="date" name="date" 
                       value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="col-md-3">
                <label for="validate" class="form-label">Срок действия</label>
                <input type="date" class="form-control" id="validate" name="validate" 
                       value="<?= date('Y-m-d') ?>" required>
            </div>

<!-- ВИДЫ РАБОТ: -->
<div class="mt-4">
    <div class="col-md-12">
        <label class="form-label">Работы по договору</label>
        <div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="add_to1" id="add_to1" value="1">
                <label class="form-check-label" for="add_to1">
                    ТО (Техническое обслуживание)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="add_avr" id="add_avr" value="1">
                <label class="form-check-label" for="add_avr">
                    АВР (Аварийно-восстановительные работы)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="add_efi" id="add_efi" value="1">
                <label class="form-check-label" for="add_efi">
                    ЭФИ (Электрофизические измерения)
                </label>
            </div>
        </div>
    </div>
</div>



        <!-- БЛОК С ПРИВЯЗАННЫМИ ОБЪЕКТАМИ -->
        <div class="mt-4">
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span>Привязанные объекты</span>
                    <span class="badge bg-secondary" id="objectsCountAdd">0</span>
                </div>
                <div class="card-body" style="max-height: 200px; overflow-y: auto;">
                    <div class="alert alert-info" id="noObjectsAdd">
                        Объекты будут доступны для привязки после сохранения договора
                    </div>
                    <table class="table table-sm table-hover d-none" id="objectsTableAdd">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название объекта</th>
                                <th>Адрес</th>
                            </tr>
                        </thead>
                        <tbody id="objectsListAdd">
                            <!-- Сюда будут добавляться объекты через JavaScript после сохранения -->
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-muted small">
                    <i class="fas fa-info-circle"></i> Для привязки объектов перейдите в карточку договора после сохранения
                </div>
            </div>
        </div>
    <!-- </div> -->




        </div>
    </div>
    <div class="modal-footer" style="justify-content: space-between;">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
            <label class="form-check-label" for="is_active">
                Активный договор
            </label>
        </div>

        <div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
        <button type="submit" class="btn btn-primary">
            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
            Сохранить
        </button>
        </div>
    </div>
</form>



