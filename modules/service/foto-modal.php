<!-- foto-modal.php -->
<div id="fotoModal" class="modal">
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="modal-header">
            <div class="modal-title">Фотографии ТО</div>
            <button class="close-modal" onclick="closeFotoModal()">&times;</button>
        </div>
        
        <!-- Контейнер для галереи фото -->
        <div id="fotoGallery" class="foto-gallery">
            <!-- Фото будут добавляться динамически -->
        </div>
        
        <!-- Индикатор загрузки -->
        <div id="fotoLoading" class="loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> Загрузка фотографий...
        </div>
        
        <!-- Сообщение если нет фото -->
        <div id="noFotosMessage" class="no-fotos-message" style="display: none;">
            <i class="fas fa-image"></i>
            <p>Нет доступных фотографий</p>
        </div>
    </div>
</div>

<style>
/* Дополнительные стили для фото-модалки */
.foto-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    padding: 10px;
    max-height: 70vh;
    overflow-y: auto;
}

.foto-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.2s ease;
}

.foto-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.foto-item img {
    width: 100%;
    height: 250px;
    object-fit: contain;
    /* background: linear-gradient(45deg, #f5f5f5 25%, #e8e8e8 25%, #e8e8e8 50%, #f5f5f5 50%, #f5f5f5 75%, #e8e8e8 75%, #e8e8e8); */
    background-size: 20px 20px;
    border-radius: 4px;
    margin-bottom: 10px;
}

.download-foto-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 16px;
    background: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.2s;
}

.download-foto-btn:hover {
    background: #45a049;
    color: white;
    text-decoration: none;
}

.no-fotos-message {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.no-fotos-message i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ccc;
}

/* Адаптивность */
@media (max-width: 768px) {
    .foto-gallery {
        /* grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); */
        gap: 10px;
    }
    
    .foto-item img {
        height: 120px;
    }
}



/* Стили для контейнера фото в галерее */
.foto-image-container {
  position: relative;
  cursor: pointer;
  border-radius: 6px;
  overflow: hidden;
  transition: transform 0.3s ease;
}

.foto-image-container:hover {
  transform: scale(1.02);
}

.foto-image-container:hover .foto-overlay {
  opacity: 1;
}

.foto-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.foto-overlay i {
  color: white;
  font-size: 24px;
  background: rgba(0, 0, 0, 0.5);
  padding: 10px;
  border-radius: 50%;
}

.foto-actions {
  margin-top: 10px;
  text-align: center;
}

/* Стили для просмотрщика фото */
.photo-viewer-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.9);
  z-index: 10001;
  display: flex;
  align-items: center;
  justify-content: center;
}

.photo-viewer-content {
  background: #1a1a1a;
  border-radius: 8px;
  width: 95%;
  max-width: 1200px;
  max-height: 95vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.photo-viewer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: #2d2d2d;
  border-bottom: 1px solid #444;
}

.photo-counter {
  color: #fff;
  font-size: 16px;
  font-weight: 500;
}

.close-viewer-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 28px;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.close-viewer-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.photo-viewer-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  min-height: 400px;
}

.photo-container {
  position: relative;
  max-width: 90%;
  max-height: 70vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.photo-container img {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.photo-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
}

.photo-loading i {
  color: #fff;
  font-size: 40px;
}

.nav-btn {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  margin: 0 15px;
  transition: background-color 0.2s;
}

.nav-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.nav-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.photo-viewer-footer {
  padding: 15px 20px;
  background: #2d2d2d;
  border-top: 1px solid #444;
  display: flex;
  justify-content: center;
  gap: 15px;
}

.viewer-download-btn,
.viewer-close-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 4px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  transition: background-color 0.2s;
  border: none;
  min-width: 120px;
}

.viewer-download-btn {
  background: #4CAF50;
  color: white;
}

.viewer-download-btn:hover {
  background: #45a049;
}

.viewer-close-btn {
  background: #666;
  color: white;
}

.viewer-close-btn:hover {
  background: #777;
}

/* Адаптивность для просмотрщика */
@media (max-width: 768px) {
  .photo-viewer-content {
    width: 100%;
    height: 100%;
    border-radius: 0;
    max-height: 100vh;
  }
  
  .photo-viewer-body {
    padding: 10px;
  }
  
  .nav-btn {
    position: fixed;
    top: 50%;
    transform: translateY(-50%);
    margin: 0;
    width: 40px;
    height: 40px;
  }
  
  .prev-btn {
    left: 10px;
  }
  
  .next-btn {
    right: 10px;
  }
  
  .photo-viewer-footer {
    flex-direction: column;
    gap: 10px;
  }
  
  .viewer-download-btn,
  .viewer-close-btn {
    width: 100%;
  }
}

/* Стили для жестов на мобильных устройствах */
@media (hover: none) and (pointer: coarse) {
  .foto-image-container {
    cursor: default;
  }
  
  .nav-btn {
    width: 60px;
    height: 60px;
    font-size: 24px;
  }
}
</style>