    <!-- Модальное окно для добавления ребенка -->
    <div id="addChildModal" class="modal">
        <div class="modal-content">
            <h3>Добавить ребенка</h3>
            <form id="addChildForm">
                <input type="hidden" id="parent_id" name="parent_id">
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Фамилия:</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" name="middle_name">
                </div>
                <div class="form-group">
                    <label>Пол:</label>
                    <select name="gender" required>
                        <option value="male">Мужской</option>
                        <option value="female">Женский</option>
                    </select>
                </div>

<!-- Внутри формы добавления ребенка -->
<div class="form-group">
    <label for="child_city">Город рождения:</label>
    <input type="text" id="child_city" name="city" placeholder="Город рождения">
</div>
<div class="form-group">
    <label for="child_home">Город проживания:</label>
    <input type="text" id="child_home" name="home" placeholder="Город проживания">
</div>


                <div class="year_pair">
                  <div class="form-group year">
                      <label>Год рождения:</label>
                      <input type="number" name="birth_year" min="1000" max="2500">
                  </div>
                  <div class="form-group year">
                      <label>Год смерти:</label>
                      <input type="number" name="death_year" min="1000" max="2500">
                  </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="hideModal('addChildModal')">Отмена</button>
                    <button type="submit" class="btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для добавления родителей -->
    <div id="addParentsModal" class="modal">
        <div class="modal-content">
            <h3>Добавить родителей</h3>
            <form id="addParentsForm">
                <input type="hidden" id="child_id" name="child_id">
                <h4>Отец</h4>
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" name="father[first_name]" required>
                </div>
                <div class="form-group">
                    <label>Фамилия:</label>
                    <input type="text" name="father[last_name]" required>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" name="father[middle_name]">
                </div>


<div class="form-group">
    <label for="father_city">Город рождения отца:</label>
    <input type="text" id="father_city" name="father[city]" placeholder="Город рождения">
</div>
<div class="form-group">
    <label for="father_home">Город проживания отца:</label>
    <input type="text" id="father_home" name="father[home]" placeholder="Город проживания">
</div>


                <div class="year_pair">
                  <div class="form-group year">
                      <label>Год рождения:</label>
                      <input type="number" name="father[birth_year]" min="1000" max="2100">
                  </div>
                  <div class="form-group year">
                      <label>Год смерти:</label>
                      <input type="number" name="father[death_year]" min="1000" max="2100">
                  </div>
                </div>
                
                <h4>Мать</h4>
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" name="mother[first_name]" required>
                </div>
                <div class="form-group">
                    <label>Фамилия:</label>
                    <input type="text" name="mother[last_name]" required>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" name="mother[middle_name]">
                </div>


<div class="form-group">
    <label for="mother_city">Город рождения матери:</label>
    <input type="text" id="mother_city" name="mother[city]" placeholder="Город рождения">
</div>
<div class="form-group">
    <label for="mother_home">Город проживания матери:</label>
    <input type="text" id="mother_home" name="mother[home]" placeholder="Город проживания">
</div>


                <div class="year_pair">
                  <div class="form-group year">
                      <label>Год рождения:</label>
                      <input type="number" name="mother[birth_year]" min="1000" max="2500">
                  </div>
                  <div class="form-group year">
                      <label>Год смерти:</label>
                      <input type="number" name="mother[death_year]" min="1000" max="2500">
                  </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="hideModal('addParentsModal')">Отмена</button>
                    <button type="submit" class="btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для добавления супруга -->
    <div id="addSpouseModal" class="modal">
        <div class="modal-content">
            <h3>Добавить супруга</h3>
            <form id="addSpouseForm">
                <input type="hidden" id="person_id" name="person_id">
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" name="spouse[first_name]" required>
                </div>
                <div class="form-group">
                    <label>Фамилия:</label>
                    <input type="text" name="spouse[last_name]" required>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" name="spouse[middle_name]">
                </div>


<div class="form-group">
    <label for="spouse_city">Город рождения:</label>
    <input type="text" id="spouse_city" name="spouse[city]" placeholder="Город рождения">
</div>
<div class="form-group">
    <label for="spouse_home">Город проживания:</label>
    <input type="text" id="spouse_home" name="spouse[home]" placeholder="Город проживания">
</div>


                <div class="year_pair">
                  <div class="form-group year">
                      <label>Год рождения:</label>
                      <input type="number" name="spouse[birth_year]" min="1000" max="2500">
                  </div>
                  <div class="form-group year">
                      <label>Год смерти:</label>
                      <input type="number" name="spouse[death_year]" min="1000" max="2500">
                  </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="hideModal('addSpouseModal')">Отмена</button>
                    <button type="submit" class="btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно для редактирования человека -->
    <div id="editPersonModal" class="modal">
        <div class="modal-content">
            <h3>Редактировать человека</h3>
            <form id="editPersonForm">
                <input type="hidden" id="edit_person_id" name="id">
                <div class="form-group">
                    <label>Имя:</label>
                    <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Фамилия:</label>
                    <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Отчество:</label>
                    <input type="text" id="edit_middle_name" name="middle_name">
                </div>
                <div class="form-group">
                    <label>Пол:</label>
                    <select id="edit_gender" name="gender" required>
                        <option value="male">Мужской</option>
                        <option value="female">Женский</option>
                    </select>
                </div>


<div class="form-group">
    <label for="edit_city">Город рождения:</label>
    <input type="text" id="edit_city" name="city" placeholder="Город рождения">
</div>
<div class="form-group">
    <label for="edit_home">Город проживания:</label>
    <input type="text" id="edit_home" name="home" placeholder="Город проживания">
</div>


                <div class="year_pair">
                  <div class="form-group year">
                      <label>Год рождения:</label>
                      <input type="number" id="edit_birth_year" name="birth_year" min="1000" max="2500">
                  </div>
                  <div class="form-group year">
                      <label>Год смерти:</label>
                      <input type="number" id="edit_death_year" name="death_year" min="1000" max="2500">
                  </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="hideModal('editPersonModal')">Отмена</button>
                    <button type="submit" class="btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>