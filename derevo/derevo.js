function hideModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    const form = document.getElementById(modalId === 'editPersonModal' ? 'editPersonForm' : modalId + 'Form');
    if (form) form.reset();
}

// Обработка форм
document.getElementById('addChildForm').addEventListener('submit', function(e) {
    e.preventDefault();
    addChild();
});

document.getElementById('addParentsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    addParents();
});

document.getElementById('addSpouseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    addSpouse();
});

document.getElementById('editPersonForm').addEventListener('submit', function(e) {
    e.preventDefault();
    savePerson();
});

// Функция для получения информации о человеке
function getPersonInfo(personId) {
    const formData = new FormData();
    formData.append('action', 'get_person');
    formData.append('id', personId);
    
    fetch('derevo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const person = data.person;
            // Заполняем форму редактирования
            document.getElementById('edit_person_id').value = person.id;
            document.getElementById('edit_first_name').value = person.first_name;
            document.getElementById('edit_last_name').value = person.last_name;
            document.getElementById('edit_middle_name').value = person.middle_name || '';
            document.getElementById('edit_gender').value = person.gender;
            document.getElementById('edit_birth_year').value = person.birth_year || '';
            document.getElementById('edit_death_year').value = person.death_year || '';
                        document.getElementById('edit_city').value = person.city || ''; // Город рождения
            document.getElementById('edit_home').value = person.home || ''; // Город проживания
            
            // Показываем модальное окно
            showModal('editPersonModal');
        } else {
            alert('Ошибка загрузки информации: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка загрузки информации');
    });
}

// Функция для сохранения изменений человека
function savePerson() {
    const formData = new FormData(document.getElementById('editPersonForm'));
    formData.append('action', 'save_person');
    
    fetch('derevo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Изменения сохранены!');
            hideModal('editPersonModal');
            location.reload();
        } else {
            alert('Ошибка сохранения: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ошибка сохранения');
    });
}

// AJAX функции
function addChild() {
    const formData = new FormData(document.getElementById('addChildForm'));
    formData.append('action', 'add_child');
    
    fetch('derevo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Ребенок добавлен!');
            hideModal('addChildModal');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function addParents() {
    const formData = new FormData(document.getElementById('addParentsForm'));
    formData.append('action', 'add_parents');
    
    fetch('derevo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Родители добавлены!');
            hideModal('addParentsModal');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function addSpouse() {
    const formData = new FormData(document.getElementById('addSpouseForm'));
    formData.append('action', 'add_spouse');
    
    fetch('derevo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Супруг добавлен!');
            hideModal('addSpouseModal');
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function deletePerson(id) {
    if (!confirm('Вы уверены, что хотите удалить этого человека?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_person');
    formData.append('id', id);
    
    fetch('derevo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Человек удален!');
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Закрытие модальных окон при клике вне их
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        const formId = event.target.id === 'editPersonModal' ? 'editPersonForm' : event.target.id + 'Form';
        const form = document.getElementById(formId);
        if (form) form.reset();
    }
}


// НОВОЕ В ПРИЛОЖЕНИИ

// Функция для открытия/закрытия меню настроек
function toggleMenu(event, btn) {
    // Закрываем все открытые меню
    closeAllMenus();
    
    // Открываем текущее меню
    const menu = btn.nextElementSibling;
    if (!menu || !menu.classList.contains('settings-menu')) {
        return;
    }
    
    const rect = btn.getBoundingClientRect();
    
    // Адаптивное позиционирование для мобильных
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // На мобильных центрируем меню
        menu.style.position = 'fixed';
        menu.style.top = '50%';
        menu.style.left = '50%';
        menu.style.transform = 'translate(-50%, -50%)';
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';
        menu.style.width = '80%';
        menu.style.maxWidth = '250px';
    } else {
        // На десктопе позиционируем у кнопки
        menu.style.position = 'fixed';
        menu.style.top = (rect.bottom + 5) + 'px';
        menu.style.left = (rect.left - 160) + 'px';
        menu.style.right = 'auto';
        menu.style.bottom = 'auto';
        menu.style.transform = 'none';
        menu.style.width = 'auto';
    }
    
    menu.classList.add('show');
}

// Функция для закрытия всех меню
function closeAllMenus() {
    const menus = document.querySelectorAll('.settings-menu.show');
    menus.forEach(menu => {
        menu.classList.remove('show');
        // Сбрасываем inline-стили
        menu.style.position = '';
        menu.style.top = '';
        menu.style.left = '';
        menu.style.right = '';
        menu.style.bottom = '';
        menu.style.transform = '';
        menu.style.width = '';
        menu.style.zIndex = '';
    });
}

// Обновляем функцию showAddChildModal и другие для работы с динамическими элементами
function showAddChildModal(parentId) {
    closeAllMenus();
    const input = document.getElementById('parent_id');
    if (input) {
        input.value = parentId;
        showModal('addChildModal');
    } else {
        console.error('Element parent_id not found');
        // Если элемент не найден, возможно модальное окно еще не загружено
        setTimeout(() => {
            const retryInput = document.getElementById('parent_id');
            if (retryInput) {
                retryInput.value = parentId;
                showModal('addChildModal');
            }
        }, 100);
    }
}


function showAddParentsModal(childId) {
    closeAllMenus();
    const input = document.getElementById('child_id');
    if (input) {
        input.value = childId;
        showModal('addParentsModal');
    } else {
        console.error('Element child_id not found');
        setTimeout(() => {
            const retryInput = document.getElementById('child_id');
            if (retryInput) {
                retryInput.value = childId;
                showModal('addParentsModal');
            }
        }, 100);
    }
}

function showAddSpouseModal(personId) {
    closeAllMenus();
    const input = document.getElementById('person_id');
    if (input) {
        input.value = personId;
        showModal('addSpouseModal');
    } else {
        console.error('Element person_id not found');
        setTimeout(() => {
            const retryInput = document.getElementById('person_id');
            if (retryInput) {
                retryInput.value = personId;
                showModal('addSpouseModal');
            }
        }, 100);
    }
}

function showEditPersonModal(personId) {
    closeAllMenus();
    getPersonInfo(personId);
}

// Обновляем функцию showModal
function showModal(modalId) {
    closeAllMenus();
    
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.error('Modal not found:', modalId);
    }
}

// Добавляем MutationObserver для отслеживания добавления новых элементов в DOM
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        // Если добавляются новые узлы
        if (mutation.addedNodes.length) {
            // Здесь можно выполнить дополнительные действия при добавлении новых элементов
            // Например, обновить какие-то обработчики
        }
    });
});

// Обработчик клавиши Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllMenus();
        
        // Также закрываем модальные окна
        const modals = document.querySelectorAll('.modal[style*="display: block"]');
        modals.forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

// Закрытие меню при скролле
let scrollTimeout;
window.addEventListener('scroll', function() {
    // Используем debounce для производительности
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(function() {
        closeAllMenus();
    }, 100);
}, { passive: true });

// Обработчик ресайза
window.addEventListener('resize', function() {
    closeAllMenus();
});

// Используем делегирование событий для кнопок настроек
document.addEventListener('click', function(e) {
    // Проверяем, кликнули ли по кнопке настроек
    const settingsBtn = e.target.closest('.settings-btn');
    if (settingsBtn) {
        e.stopPropagation();
        e.preventDefault();
        toggleMenu(e, settingsBtn);
        return;
    }
    
    // Проверяем, кликнули ли внутри меню
    if (e.target.closest('.settings-menu')) {
        e.stopPropagation(); // Не закрываем, если клик внутри меню
        return;
    }
    
    // В любом другом месте - закрываем все меню
    closeAllMenus();
    
    // Закрытие модальных окон при клике на overlay
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
        const formId = e.target.id === 'editPersonModal' ? 'editPersonForm' : e.target.id + 'Form';
        const form = document.getElementById(formId);
        if (form) form.reset();
    }
});

// Запускаем наблюдение за изменениями в family-tree
document.addEventListener('DOMContentLoaded', function() {
    const familyTree = document.getElementById('familyTree');
    if (familyTree) {
        observer.observe(familyTree, {
            childList: true,
            subtree: true
        });
    }
});