// assets/js/service-modal.js
// Функционал модального окна технического обслуживания

// --------- ТЕХНИЧЕСКОЕ ОБСЛУЖИВАНИЕ ----------
let currentServiceObjectId = null;

// ========== ОСНОВНЫЕ ФУНКЦИИ ==========

// Открытие модального окна для ТО
function openServiceModal(objectId, customer, address) {
  console.log('openServiceModal:', { objectId, customer, address });
  currentServiceObjectId = objectId;

  // Заполняем форму
  document.getElementById('serviceObjectId').value = objectId;
  document.getElementById('serviceCustomer').value = customer;
  document.getElementById('serviceAddress').value = address;
  document.getElementById('serviceDate').value = getLocalDateTime();
  document.getElementById('serviceResult').value = '0';
  document.getElementById('serviceComments').value = 'В исправном состоянии';
  document.getElementById('fileUploadContainer').style.display = 'none';
  document.getElementById('serviceFoto').value = '';

  // Загружаем историю ТО
  loadServiceHistory(objectId);

  // Показываем модальное окно
  document.getElementById('serviceModal').style.display = 'block';
}

// Закрытие модального окна ТО
function closeServiceModal() {
  document.getElementById('serviceModal').style.display = 'none';
  currentServiceObjectId = null;
  document.getElementById('serviceForm').reset();
  document.getElementById('serviceHistory').innerHTML = '<div class="no-services">Загрузка истории...</div>';
}

// Обработчик изменения результата ТО
function handleResultChange() {
  const resultSelect = document.getElementById('serviceResult');
  const commentsTextarea = document.getElementById('serviceComments');
  const fileUploadContainer = document.getElementById('fileUploadContainer');

  const selectedValue = resultSelect.value;
  const selectedText = resultSelect.options[resultSelect.selectedIndex].text;

  // Автоматически обновляем комментарии
  const previousResultTexts = ['В исправном состоянии', 'Требуется устранить замечания', 'Не работает'];
  const currentComment = commentsTextarea.value.trim();
  const containsPreviousResult = previousResultTexts.some(text => currentComment === text);

  if (selectedValue && (currentComment === '' || containsPreviousResult)) {
    commentsTextarea.value = selectedText;
  }

  // Показываем/скрываем поле для загрузки файлов
  fileUploadContainer.style.display = (selectedValue === '1' || selectedValue === '2') ? 'block' : 'none';
}

// Загрузка истории ТО
function loadServiceHistory(objectId) {
  fetch(`/modules/service/get_service_history.php?object_id=${objectId}`)
    .then(response => {
      if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
      return response.text();
    })
    .then(text => {
      try {
        const data = JSON.parse(text);
        displayServiceHistory(data);
      } catch (e) {
        console.error('Ошибка парсинга JSON:', text);
        displayError('Ошибка формата данных от сервера');
      }
    })
    .catch(error => {
      console.error('Ошибка при выполнении запроса:', error);
      displayError('Ошибка сети: ' + error.message);
    });
}

// Отображение истории ТО
function displayServiceHistory(data) {
  const historyDiv = document.getElementById('serviceHistory');
  if (!historyDiv) return;

  if (!data || !data.success || !Array.isArray(data.services)) {
    const errorMsg = data?.error ? `Ошибка: ${escapeHtml(data.error)}` : 'Нет данных для отображения';
    historyDiv.innerHTML = `<div class="no-services">${errorMsg}</div>`;
    return;
  }

  if (data.services.length === 0) {
    historyDiv.innerHTML = '<div class="no-services">Нет записей о техническом обслуживании</div>';
    return;
  }

  let html = '<label style="transform: scale(.85) translateY(-.9rem) translateX(.25rem); padding-bottom: 10px;">История обслуживаний:</label>';
  html += `<div class="table-responsive">
                <table style="margin-bottom: 0;" class="table table-sm table-hover">
                <tbody>`;

  data.services.forEach((service) => {
    const photoCount = getPhotoCount(service.foto);
    const hasPhoto = photoCount > 0;

    html += `<tr>
                    <td style="text-align: center; text-wrap-mode: nowrap;">${formatDate(service.service_date).split(',')[0]}</td>
                    <td>${service.comments ? escapeHtml(service.comments) : '-'}</td>
                    <td style="text-align: center; text-wrap-mode: nowrap;">
                        <span class="badge" style="background-color: ${getResultColor(service.result)};">
                            ${getResultText(service.result)}
                        </span>`;

    if (hasPhoto) {
      // Используем новую функцию для открытия фото
      const fotoData = service.foto ? service.foto.toString() : '';
      html += `<span class="photo-indicator has-photo" 
                            title="${photoCount} фото" 
                            data-photo='${fotoData.replace(/'/g, "&#39;")}'
                            onclick="showFotosModal(this.getAttribute('data-photo'))">
                        <i class="fas fa-camera"></i>
                        ${photoCount > 1 ? `<span class="photo-count">${photoCount}</span>` : ''}
                    </span>`;
    } else {
      html += `<span class="photo-indicator no-photo" title="Нет фото">
                        <i class="fas fa-times"></i>
                    </span>`;
    }

    html += `</td></tr>`;
  });

  html += `</tbody></table></div>`;
  historyDiv.innerHTML = html;
}

// Добавление записи о ТО
function addServiceRecord() {
  const form = document.getElementById('serviceForm');
  const formData = new FormData(form);

  // Добавляем дополнительные данные
  formData.append('user_id', 1);

  // Получаем дату и преобразуем
  const dateInput = document.getElementById('serviceDate');
  if (dateInput.value) {
    const formattedDate = dateInput.value.replace('T', ' ') + ':00';
    formData.set('service_date', formattedDate);
  }

  // Показываем индикатор загрузки
  const loadingDiv = document.getElementById('loading');
  if (loadingDiv) loadingDiv.style.display = 'block';
  hideMessage();

  // Отправка данных
  fetch('/modules/service/add_service.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(result => {
      if (loadingDiv) loadingDiv.style.display = 'none';

      if (result.success) {
        showMessage('Запись о ТО успешно добавлена!', 'success');
        // Сначала обновляем историю в модальном окне
        loadServiceHistory(currentServiceObjectId);
        // Затем обновляем кнопку и счетчик
        updateServiceButton(currentServiceObjectId).then(() => {
          // И только после обновления кнопки закрываем модальное окно
          setTimeout(() => {
            closeServiceModal();
          }, 500); // Даем время увидеть обновленную историю
        });
      } else {
        showMessage('Ошибка: ' + (result.error || 'Неизвестная ошибка'), 'error');
      }
    })
    .catch(error => {
      if (loadingDiv) loadingDiv.style.display = 'none';
      showMessage('Ошибка сети: ' + error.message, 'error');
    });
}

// Обновление кнопки ТО в таблице
function updateServiceButton(objectId) {
  return new Promise((resolve, reject) => {
    const row = document.querySelector(`tr[data-id="${objectId}"]`);
    if (!row) {
      resolve();
      return;
    }

    const serviceBtn = row.querySelector('.service-btn');
    if (!serviceBtn) {
      resolve();
      return;
    }

    fetch(`/modules/service/get_service_count.php?object_id=${objectId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          let badge = serviceBtn.querySelector('.service-badge');
          if (data.count > 0) {
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'service-badge';
              serviceBtn.appendChild(badge);
            }
            badge.textContent = data.count;
          } else if (badge) {
            badge.remove();
          }
        }
        resolve();
      })
      .catch(error => {
        console.error('Ошибка:', error);
        resolve(); // Все равно резолвим, чтобы не блокировать закрытие
      });
  });
}

// ========== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ==========

// Получение локальной даты и времени
function getLocalDateTime(date = new Date()) {
  const tzOffset = date.getTimezoneOffset() * 60000;
  const localDate = new Date(date.getTime() - tzOffset);
  return localDate.toISOString().slice(0, 16);
}

// Форматирование даты
function formatDate(dateString) {
  if (!dateString) return 'Дата не указана';

  try {
    let date;
    if (dateString.includes('T')) {
      date = new Date(dateString);
    } else if (dateString.includes(' ')) {
      date = new Date(dateString.replace(' ', 'T'));
    } else {
      date = new Date(dateString);
    }

    if (isNaN(date.getTime())) {
      return dateString;
    }

    return date.toLocaleString('ru-RU', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    console.error('Ошибка форматирования даты:', e);
    return dateString;
  }
}

// Получение цвета для результата
function getResultColor(result) {
  const colors = {
    0: '#4CAF50',
    1: '#FF9800',
    2: '#F44336'
  };
  return colors[result] || '#666';
}

// Получение текста для результата
function getResultText(result) {
  return String(result);
}

// Экранирование HTML
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Отображение ошибки
function displayError(message) {
  const historyDiv = document.getElementById('serviceHistory');
  if (historyDiv) {
    historyDiv.innerHTML = `<div class="no-services" style="color: #f44336;">${escapeHtml(message)}</div>`;
  }
}

// Получение количества фото
function getPhotoCount(fotoData) {
  if (!fotoData || fotoData === 'null' || fotoData === 'undefined') return 0;

  try {
    const parsed = JSON.parse(fotoData);
    if (Array.isArray(parsed)) {
      return parsed.filter(photo => photo && photo.trim() !== '').length;
    } else if (typeof parsed === 'string' && parsed.trim() !== '') {
      return 1;
    }
    return 0;
  } catch (e) {
    return (typeof fotoData === 'string' && fotoData.trim() !== '') ? 1 : 0;
  }
}

// ========== ИНИЦИАЛИЗАЦИЯ ==========

// Инициализация формы ТО
function initServiceForm() {
  // Обработчик отправки формы
  const serviceForm = document.getElementById('serviceForm');
  if (serviceForm) {
    serviceForm.addEventListener('submit', function (e) {
      e.preventDefault();
      addServiceRecord();
    });
  }

  // Обработчик закрытия по клику вне окна
  const serviceModal = document.getElementById('serviceModal');
  if (serviceModal) {
    serviceModal.addEventListener('click', function (e) {
      if (e.target === serviceModal) closeServiceModal();
    });
  }

  // Обработчик изменения результата
  const resultSelect = document.getElementById('serviceResult');
  if (resultSelect) {
    resultSelect.addEventListener('change', handleResultChange);
  }
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', initServiceForm);