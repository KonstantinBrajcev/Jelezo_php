// service-history-functions.js
// Общие функции для отображения истории обслуживания

// Функция отображения истории ТО (обновленная)
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
                    <td style="text-align: center; text-wrap-mode: nowrap !important;">${formatDate(service.service_date).split(',')[0]}</td>
                    <td>${service.comments ? escapeHtml(service.comments) : '-'}</td>
                    <td style="text-align: center; text-wrap-mode: nowrap !important;">
                        <span class="badge" style="background-color: ${getResultColor(service.result)};">
                            ${getResultText(service.result)}
                        </span>`;

    if (hasPhoto) {
      const fotoData = service.foto ? service.foto.toString() : '';
      html += `<span class="photo-indicator has-photo" 
                            title="${photoCount} фото" 
                            data-photo="${encodeURIComponent(fotoData)}"
                            onclick="openFotosModal(this.getAttribute('data-photo'))">
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

// Универсальная функция для открытия модального окна фото
function openFotosModal(fotoData) {
  // Проверяем, есть ли функция showFotosModal из foto-modal.js
  if (typeof window.showFotosModal === 'function') {
    window.showFotosModal(fotoData);
  } else {
    // Если функция не доступна, открываем первое фото в новой вкладке
    try {
      const decodedData = decodeURIComponent(fotoData);
      const parsed = JSON.parse(decodedData);
      const photos = Array.isArray(parsed) ? parsed : [parsed];
      const firstPhoto = photos[0];

      if (firstPhoto) {
        let fullPath = firstPhoto.trim();
        if (!fullPath.startsWith('http') && !fullPath.startsWith('/')) {
          fullPath = '/' + fullPath;
        }
        if (fullPath.startsWith('/') && !fullPath.startsWith('//')) {
          fullPath = window.location.origin + fullPath;
        }
        window.open(fullPath, '_blank');
      }
    } catch (e) {
      console.error('Ошибка при открытии фото:', e);
      alert('Не удалось открыть фотографию');
    }
  }
}