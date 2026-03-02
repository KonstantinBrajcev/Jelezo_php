// assets/js/balloon-template.js

function generateBalloonTemplate(obj, coordinates, phoneLink) {
  // Создаем элементы в стиле index.php
  let addressHtml = '';
  if (obj.address && obj.address.trim() !== '') {
    const encodedAddress = encodeURIComponent(obj.address);
    addressHtml = `
      <a href="https://yandex.ru/maps/?text=${encodedAddress}" 
         target="_blank" 
         class="address-link balloon-link">
        ${escapeHtml(obj.address)}
      </a>
    `;
  } else {
    addressHtml = '<span class="no-address balloon-no-data">нет адреса</span>';
  }

  let phoneHtml = '';
  if (obj.phone && obj.phone.trim() !== '') {
    const cleanPhone = obj.phone.replace(/\D/g, '');
    phoneHtml = `
      <a href="tel:+${cleanPhone}" class="phone-link balloon-link">
        ${escapeHtml(obj.phone)}
      </a>
    `;
  } else {
    phoneHtml = '<span class="no-phone balloon-no-data">нет телефона</span>';
  }

  const modelHtml = obj.model && obj.model.trim() !== ''
    ? escapeHtml(obj.model)
    : '<span class="balloon-no-data">не указана</span>';

  const nameHtml = obj.name && obj.name.trim() !== ''
    ? escapeHtml(obj.name)
    : '<span class="balloon-no-data">не указан</span>';

  return {
    header: `<div class="balloon-title">${escapeHtml(obj.customer || 'Объект')}</div>`,
    body: `
      <div class="balloon-content">
        <div class="balloon-field">
          <span class="balloon-label">Адрес:</span>
          <div class="balloon-value">${addressHtml}</div>
        </div>
        
        <div class="balloon-field">
          <span class="balloon-label">Модель:</span>
          <div class="balloon-value">${modelHtml}</div>
        </div>
        
        <div class="balloon-field">
          <span class="balloon-label">Телефон:</span>
          <div class="balloon-value">${phoneHtml}</div>
        </div>
        
        <div class="balloon-field">
          <span class="balloon-label">Контакт:</span>
          <div class="balloon-value">${nameHtml}</div>
        </div>
        
        <div class="balloon-actions">
          <button onclick="openServiceFromMap(${obj.id})" 
                  class="service-btn balloon-service-btn"
                  title="Добавить запись о техническом обслуживании">
            <i class="fas fa-wrench"></i> Добавить ТО
          </button>
        </div>
      </div>
    `
  };
}

// Простая функция для открытия обслуживания
function openServiceFromMap(objectId) {
  // Ищем объект в глобальном массиве objectsData из map.php
  if (typeof objectsData !== 'undefined') {
    const obj = objectsData.find(item => item.id == objectId);
    if (obj) {
      console.log('Открытие ТО для:', obj.customer);
      // Вызываем глобальную функцию
      if (typeof window.openServiceModal === 'function') {
        window.openServiceModal(obj.id, obj.customer, obj.address);
      } else {
        console.error('Функция openServiceModal не найдена');
      }
    }
  }
}

// Функция экранирования HTML
function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}