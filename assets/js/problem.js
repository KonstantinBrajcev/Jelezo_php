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