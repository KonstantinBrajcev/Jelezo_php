// Обработка удаления записи
async function deleteProblem(id) {
  if (!confirm('Вы уверены, что хотите удалить эту проблему?')) {
    return;
  }
  const formData = new FormData();
  formData.append('action', 'delete');
  formData.append('id', id);
  try {
    const response = await fetch('problem.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if (response.ok) {
      showNotification('Проблема успешно удалена');
      setTimeout(() => location.reload(), 1500);
    } else {
      showNotification('Ошибка при удалении', 'error');
    }
  } catch (error) {
    console.error('Ошибка:', error);
    showNotification('Ошибка при отправке запроса', 'error');
  }
}

// Функции для модального окна добавления
function openAddModal() {
  document.getElementById('addModal').style.display = 'block';
  document.getElementById('addForm').reset();
}

function closeAddModal() {
  document.getElementById('addModal').style.display = 'none';
}

// Функции для модального окна редактирования
async function openEditModal(id) {
  try {
    const response = await fetch(`problem.php?get_problem=${id}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    const problem = await response.json();
    document.getElementById('edit_id').value = problem.id;
    document.getElementById('edit_name').value = problem.name;
    document.getElementById('editModal').style.display = 'block';
  } catch (error) {
    showNotification('Ошибка при загрузке данных', 'error');
  }
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

// Отправка формы добавления через AJAX
async function submitAddForm(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  try {
    const response = await fetch('problem.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if (response.ok) {
      closeAddModal();
      showNotification('Проблема успешно добавлена');
      setTimeout(() => location.reload(), 1500);
    } else {
      const error = await response.text();
      console.error('Ошибка:', error);
      showNotification('Ошибка при добавлении', 'error');
    }
  } catch (error) {
    console.error('Ошибка:', error);
    showNotification('Ошибка при отправке запроса', 'error');
  }
  return false;
}

// Отправка формы редактирования через AJAX
async function submitEditForm(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  try {
    const response = await fetch('problem.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if (response.ok) {
      closeEditModal();
      showNotification('Проблема успешно обновлена');
      setTimeout(() => location.reload(), 1500);
    } else {
      const error = await response.text();
      console.error('Ошибка:', error);
      showNotification('Ошибка при обновлении', 'error');
    }
  } catch (error) {
    console.error('Ошибка:', error);
    showNotification('Ошибка при отправке запроса', 'error');
  }
  return false;
}

// Обработка переключения статуса через AJAX
async function toggleComplete(event, form) {
  event.preventDefault();
  const formData = new FormData(form);
  try {
    const response = await fetch('problem.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if (response.ok) {
      showNotification('Статус обновлен');
      setTimeout(() => location.reload(), 500);
    }
  } catch (error) {
    showNotification('Ошибка при обновлении статуса', 'error');
  }
  return false;
}

// Обработка изменения приоритета через AJAX
async function movePriority(event, form) {
  event.preventDefault();
  const formData = new FormData(form);
  try {
    const response = await fetch('problem.php', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    if (response.ok) {
      showNotification('Приоритет обновлен');
      setTimeout(() => location.reload(), 500);
    }
  } catch (error) {
    showNotification('Ошибка при изменении приоритета', 'error');
  }
  return false;
}

// Функция для показа уведомлений
function showNotification(message, type = 'success') {
  const notification = document.createElement('div');
  notification.className = 'notification';
  notification.style.backgroundColor = type === 'success' ? '#4CAF50' : '#f44336';
  notification.textContent = message;
  document.body.appendChild(notification);
  setTimeout(() => {
    notification.remove();
  }, 3000);
}

// Закрытие модальных окон при клике вне их
window.onclick = function (event) {
  const addModal = document.getElementById('addModal');
  const editModal = document.getElementById('editModal');
  if (event.target === addModal) {
    closeAddModal();
  }
  if (event.target === editModal) {
    closeEditModal();
  }
}

// Закрытие по Escape
document.addEventListener('keydown', function (event) {
  if (event.key === 'Escape') {
    closeAddModal();
    closeEditModal();
  }
});



// YJDJT
// Функция для сворачивания/разворачивания завершенных задач
function toggleCompletedTasks() {
    const completedBody = document.getElementById('completedTasksBody');
    const icon = document.querySelector('#accordionIcon i');
    
    if (completedBody.style.display === 'none') {
        completedBody.style.display = '';
        if (icon) {
            icon.className = 'fas fa-chevron-up';
        }
    } else {
        completedBody.style.display = 'none';
        if (icon) {
            icon.className = 'fas fa-chevron-down';
        }
    }
}

// Обновленная функция переключения статуса
async function toggleComplete(event, form) {
    event.preventDefault();
    const formData = new FormData(form);
    
    // Сохраняем ID задачи и статус для последующего обновления UI
    const taskId = formData.get('id');
    const button = form.querySelector('button');
    const isCurrentlyCompleted = button.classList.contains('status-completed');
    
    try {
        const response = await fetch('problem.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            showNotification('Статус обновлен');
            
            // Обновляем UI без перезагрузки страницы
            if (isCurrentlyCompleted) {
                // Перемещаем из завершенных в активные
                moveTaskToActive(taskId);
            } else {
                // Перемещаем из активных в завершенные
                moveTaskToCompleted(taskId);
            }
        }
    } catch (error) {
        showNotification('Ошибка при обновлении статуса', 'error');
    }
    return false;
}

// Функция перемещения задачи в активные
function moveTaskToActive(taskId) {
    const taskRow = document.querySelector(`tr[data-id="${taskId}"]`);
    if (taskRow && taskRow.classList.contains('completed-row')) {
        // Клонируем строку
        const newRow = taskRow.cloneNode(true);
        
        // Изменяем классы
        newRow.classList.remove('completed-row');
        newRow.classList.add('active-row');
        newRow.setAttribute('data-status', 'active');
        
        // Изменяем стиль текста
        const nameCell = newRow.querySelector('td:first-child');
        nameCell.classList.remove('completed');
        nameCell.style.textDecoration = 'none';
        nameCell.style.opacity = '1';
        
        // Изменяем кнопку статуса
        const statusButton = newRow.querySelector('.status-badge');
        statusButton.classList.remove('status-completed');
        statusButton.classList.add('status-active');
        statusButton.innerHTML = '<i class="fas fa-times"></i>';
        
        // Удаляем старую строку
        taskRow.remove();
        
        // Вставляем перед строкой аккордеона
        const accordionRow = document.getElementById('accordionRow');
        const tbody = document.querySelector('#problemTable tbody');
        tbody.insertBefore(newRow, accordionRow);
        
        // Обновляем счетчик
        updateCompletedCount();
        
        // Если завершенных задач больше нет, скрываем аккордеон
        const remainingCompleted = document.querySelectorAll('.completed-row').length;
        if (remainingCompleted === 0) {
            const accordion = document.getElementById('accordionRow');
            const completedBody = document.getElementById('completedTasksBody');
            if (accordion) accordion.remove();
            if (completedBody) completedBody.remove();
        }
    }
}

// Функция перемещения задачи в завершенные
function moveTaskToCompleted(taskId) {
    const taskRow = document.querySelector(`tr[data-id="${taskId}"]`);
    if (taskRow && taskRow.classList.contains('active-row')) {
        // Клонируем строку
        const newRow = taskRow.cloneNode(true);
        
        // Изменяем классы
        newRow.classList.remove('active-row');
        newRow.classList.add('completed-row');
        newRow.setAttribute('data-status', 'completed');
        
        // Изменяем стиль текста
        const nameCell = newRow.querySelector('td:first-child');
        nameCell.classList.add('completed');
        nameCell.style.textDecoration = 'line-through';
        nameCell.style.opacity = '0.7';
        
        // Убираем кнопки приоритета
        const priorityCell = newRow.querySelector('td:last-child');
        priorityCell.innerHTML = '<span class="priority-disabled"></span>';
        
        // Изменяем кнопку статуса
        const statusButton = newRow.querySelector('.status-badge');
        statusButton.classList.remove('status-active');
        statusButton.classList.add('status-completed');
        statusButton.innerHTML = '<i class="fas fa-check"></i>';
        
        // Удаляем старую строку
        taskRow.remove();
        
        // Добавляем в завершенные задачи
        const completedBody = document.getElementById('completedTasksBody');
        
        // Если аккордеона нет, создаем его
        if (!completedBody) {
            location.reload(); // Перезагружаем страницу для корректного отображения
            return;
        }
        
        completedBody.appendChild(newRow);
        
        // Обновляем счетчик
        updateCompletedCount();
        
        // Если аккордеон был скрыт, показываем его
        if (completedBody.style.display === 'none') {
            const icon = document.querySelector('#accordionIcon i');
            if (icon) icon.className = 'fas fa-chevron-down';
        }
    }
}

// Функция обновления счетчика завершенных задач
function updateCompletedCount() {
    const completedCount = document.querySelectorAll('.completed-row').length;
    const countSpan = document.querySelector('#accordionRow span');
    if (countSpan) {
        countSpan.innerHTML = completedCount;
    }
    
    // Если нет завершенных задач, удаляем аккордеон
    if (completedCount === 0) {
        const accordion = document.getElementById('accordionRow');
        const completedBody = document.getElementById('completedTasksBody');
        if (accordion) accordion.remove();
        if (completedBody) completedBody.remove();
    }
}

// Функция для сохранения состояния аккордеона в localStorage
function saveAccordionState() {
    const completedBody = document.getElementById('completedTasksBody');
    if (completedBody) {
        localStorage.setItem('completedTasksExpanded', completedBody.style.display !== 'none');
    }
}

// Функция для загрузки состояния аккордеона
function loadAccordionState() {
    const completedBody = document.getElementById('completedTasksBody');
    const isExpanded = localStorage.getItem('completedTasksExpanded') === 'true';
    
    if (completedBody && isExpanded) {
        completedBody.style.display = '';
        const icon = document.querySelector('#accordionIcon i');
        if (icon) icon.className = 'fas fa-chevron-up';
    }
}

// Обновленная функция удаления
async function deleteProblem(id) {
    if (!confirm('Вы уверены, что хотите удалить эту проблему?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    try {
        const response = await fetch('problem.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            showNotification('Проблема успешно удалена');
            // Удаляем строку из таблицы
            const taskRow = document.querySelector(`tr[data-id="${id}"]`);
            if (taskRow) {
                taskRow.remove();
                updateCompletedCount();
            }
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('Ошибка при удалении', 'error');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        showNotification('Ошибка при отправке запроса', 'error');
    }
}

// Загружаем состояние аккордеона при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    loadAccordionState();
    
    // Сохраняем состояние при клике на аккордеон
    const accordionRow = document.getElementById('accordionRow');
    if (accordionRow) {
        accordionRow.addEventListener('click', saveAccordionState);
    }
});