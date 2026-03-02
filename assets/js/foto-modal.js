// assets/js/foto-modal.js - Функционал модального окна с фотографиями
// Глобальные переменные
let fotoModalEscapeHandler = null;
let currentPhotoIndex = 0;
let currentPhotos = [];

// Открытие модального окна с фото
function showFotosModal(fotoData) {
  try {
    console.log('Получены данные фото:', fotoData); // Для отладки
    // Закрываем предыдущее модальное окно если открыто
    closeFotoModal();

    // Показываем индикатор загрузки
    document.getElementById('fotoLoading').style.display = 'block';
    document.getElementById('fotoGallery').innerHTML = '';
    document.getElementById('noFotosMessage').style.display = 'none';

    // Показываем модальное окно
    document.getElementById('fotoModal').style.display = 'block';

    // Парсим данные фото
    let photos = [];
    try {
      // Декодируем если данные закодированы
      let decodedData = fotoData;
      if (fotoData.includes('%')) {
        decodedData = decodeURIComponent(fotoData);
      }

      const parsed = JSON.parse(decodedData);
      photos = Array.isArray(parsed) ? parsed : [parsed];
    } catch (e) {
      // Если не JSON, значит это строка с путем к фото
      photos = [fotoData];
    }

    // Фильтруем пустые значения
    photos = photos.filter(photo => photo && photo.trim() !== '');

    // Сохраняем фото для просмотрщика
    currentPhotos = photos;

    // Загружаем фото с небольшой задержкой для плавности
    setTimeout(() => {
      displayFotos(photos);
    }, 100);

    // Добавляем обработчик Escape
    addFotoModalEscapeHandler();

  } catch (error) {
    console.error('Ошибка при открытии модального окна с фото:', error);
    document.getElementById('fotoLoading').style.display = 'none';
    document.getElementById('noFotosMessage').style.display = 'block';
  }
}

// Отображение фото в галерее
function displayFotos(photos) {
  const gallery = document.getElementById('fotoGallery');
  const loading = document.getElementById('fotoLoading');
  const noFotosMessage = document.getElementById('noFotosMessage');

  loading.style.display = 'none';

  if (!photos || photos.length === 0) {
    noFotosMessage.style.display = 'block';
    return;
  }

  let galleryHTML = '';

  photos.forEach((photo, index) => {
    // Формируем полный путь к фото
    let fullPath = preparePhotoPath(photo);

    // Создаем элемент фото с возможностью клика для открытия
    galleryHTML += `
      <div class="foto-item" data-index="${index}">
        <div class="foto-image-container" onclick="openPhotoViewer(${index})">
          <img src="${fullPath}" 
               alt="Фото ТО ${index + 1}"
               onerror="this.onerror=null; this.src='data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22150%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23f0f0f0%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2214%22 fill=%22%23999%22%3EИзображение не найдено%3C/text%3E%3C/svg%3E'">
          <div class="foto-overlay">
            <i class="fas fa-expand"></i>
          </div>
        </div>
        <div class="foto-actions">
          <a href="${fullPath}" 
             target="_blank" 
             class="download-foto-btn" 
             download="фото_то_${index + 1}.jpg">
            <i class="fas fa-download"></i> Скачать
          </a>
        </div>
      </div>
    `;
  });

  gallery.innerHTML = galleryHTML;
  gallery.style.display = 'grid';
}

// Открытие просмотрщика фото
function openPhotoViewer(index) {
  if (!currentPhotos || currentPhotos.length === 0) return;

  currentPhotoIndex = index;
  const photoPath = preparePhotoPath(currentPhotos[index]);

  // Создаем модальное окно просмотрщика
  const viewerModal = document.createElement('div');
  viewerModal.id = 'photoViewerModal';
  viewerModal.className = 'photo-viewer-modal';

  viewerModal.innerHTML = `
    <div class="photo-viewer-content" onclick="event.stopPropagation()">
      <div class="photo-viewer-header">
        <span class="photo-counter">${currentPhotoIndex + 1} / ${currentPhotos.length}</span>
        <button class="close-viewer-btn" onclick="closePhotoViewer()">&times;</button>
      </div>
      
      <div class="photo-viewer-body">
        <button class="nav-btn prev-btn" onclick="navigatePhoto(-1)" ${currentPhotoIndex === 0 ? 'disabled' : ''}>
          <i class="fas fa-chevron-left"></i>
        </button>
        
        <div class="photo-container">
          <img src="${photoPath}" 
               alt="Фото ${currentPhotoIndex + 1}"
               id="viewerPhoto"
               onerror="this.onerror=null; this.src='data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22600%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23f0f0f0%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-family=%22Arial%22 font-size=%2216%22 fill=%22%23999%22%3EИзображение не найдено%3C/text%3E%3C/svg%3E'">
          <div class="photo-loading">
            <i class="fas fa-spinner fa-spin"></i>
          </div>
        </div>
        
        <button class="nav-btn next-btn" onclick="navigatePhoto(1)" ${currentPhotoIndex === currentPhotos.length - 1 ? 'disabled' : ''}>
          <i class="fas fa-chevron-right"></i>
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(viewerModal);
  document.body.style.overflow = 'hidden';

  // Добавляем обработчики для просмотрщика
  const viewerPhoto = document.getElementById('viewerPhoto');
  const loadingDiv = viewerModal.querySelector('.photo-loading');

  viewerPhoto.onload = function () {
    loadingDiv.style.display = 'none';
    this.style.opacity = '1';
  };

  viewerPhoto.onerror = function () {
    loadingDiv.style.display = 'none';
  };

  // Обработчик Escape для просмотрщика
  const escapeHandler = (e) => {
    if (e.key === 'Escape') {
      closePhotoViewer();
    }
  };
  document.addEventListener('keydown', escapeHandler);
  viewerModal._escapeHandler = escapeHandler;

  // Обработчик стрелок для навигации
  const arrowHandler = (e) => {
    if (e.key === 'ArrowLeft') {
      navigatePhoto(-1);
    } else if (e.key === 'ArrowRight') {
      navigatePhoto(1);
    }
  };
  document.addEventListener('keydown', arrowHandler);
  viewerModal._arrowHandler = arrowHandler;

  // Обработчик закрытия по клику на фон
  viewerModal.addEventListener('click', (e) => {
    if (e.target === viewerModal) {
      closePhotoViewer();
    }
  });
}

// Закрытие просмотрщика фото
function closePhotoViewer() {
  const viewerModal = document.getElementById('photoViewerModal');
  if (viewerModal) {
    // Удаляем обработчики
    if (viewerModal._escapeHandler) {
      document.removeEventListener('keydown', viewerModal._escapeHandler);
    }
    if (viewerModal._arrowHandler) {
      document.removeEventListener('keydown', viewerModal._arrowHandler);
    }

    // Удаляем модальное окно
    if (viewerModal.parentNode) {
      viewerModal.parentNode.removeChild(viewerModal);
    }

    // Восстанавливаем прокрутку
    document.body.style.overflow = '';
  }
}

// Навигация по фото
function navigatePhoto(direction) {
  const newIndex = currentPhotoIndex + direction;

  if (newIndex >= 0 && newIndex < currentPhotos.length) {
    currentPhotoIndex = newIndex;
    const photoPath = preparePhotoPath(currentPhotos[newIndex]);
    const viewerPhoto = document.getElementById('viewerPhoto');
    const loadingDiv = document.querySelector('.photo-loading');
    const counter = document.querySelector('.photo-counter');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');

    // Показываем индикатор загрузки
    if (loadingDiv) loadingDiv.style.display = 'flex';
    if (viewerPhoto) viewerPhoto.style.opacity = '0';

    // Обновляем счетчик
    if (counter) counter.textContent = `${currentPhotoIndex + 1} / ${currentPhotos.length}`;

    // Обновляем кнопки навигации
    if (prevBtn) prevBtn.disabled = currentPhotoIndex === 0;
    if (nextBtn) nextBtn.disabled = currentPhotoIndex === currentPhotos.length - 1;

    // Обновляем фото
    if (viewerPhoto) {
      viewerPhoto.src = photoPath;
    }

    // Обновляем ссылку для скачивания
    const downloadLink = document.querySelector('.viewer-download-btn');
    if (downloadLink) {
      downloadLink.href = photoPath;
      downloadLink.download = `фото_то_${currentPhotoIndex + 1}.jpg`;
    }
  }
}

// Подготовка пути к фото
function preparePhotoPath(photoPath) {
  if (!photoPath) return '';

  const cleanPath = photoPath.trim();

  // Если уже абсолютный URL
  if (cleanPath.startsWith('http://') || cleanPath.startsWith('https://')) {
    return cleanPath;
  }

  // Если путь начинается со слеша
  if (cleanPath.startsWith('/')) {
    return window.location.origin + cleanPath;
  }

  // Относительный путь
  return window.location.origin + '/assets/uploads/service_photos/' + cleanPath.replace(/^\/+/, '');
}

// Закрытие модального окна с фото
function closeFotoModal() {
  const modal = document.getElementById('fotoModal');
  if (modal) {
    modal.style.display = 'none';
  }

  // Очищаем галерею
  document.getElementById('fotoGallery').innerHTML = '';
  document.getElementById('fotoLoading').style.display = 'none';
  document.getElementById('noFotosMessage').style.display = 'none';

  // Удаляем обработчик Escape
  removeFotoModalEscapeHandler();
}

// Добавление обработчика Escape
function addFotoModalEscapeHandler() {
  // Удаляем предыдущий обработчик если есть
  removeFotoModalEscapeHandler();

  // Создаем новый обработчик
  fotoModalEscapeHandler = (e) => {
    if (e.key === 'Escape') {
      closeFotoModal();
    }
  };

  // Добавляем обработчик
  document.addEventListener('keydown', fotoModalEscapeHandler);
}

// Удаление обработчика Escape
function removeFotoModalEscapeHandler() {
  if (fotoModalEscapeHandler) {
    document.removeEventListener('keydown', fotoModalEscapeHandler);
    fotoModalEscapeHandler = null;
  }
}

// Инициализация модального окна с фото
function initFotoModal() {
  // Обработчик закрытия по клику на фон
  const modal = document.getElementById('fotoModal');
  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeFotoModal();
      }
    });
  }

  // Добавляем глобальные функции
  window.showFotosModal = showFotosModal;
  window.closeFotoModal = closeFotoModal;
  window.openPhotoViewer = openPhotoViewer;
  window.closePhotoViewer = closePhotoViewer;
  window.navigatePhoto = navigatePhoto;
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', initFotoModal);