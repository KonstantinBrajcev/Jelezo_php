// assets/js/map.js
// Функция для обновления статистики
function updateStatusStats(resultValue) {
    // resultValue может быть строкой или числом
    const resultKey = String(resultValue);

    switch(resultKey) {
        case '0': // Работают
            statusStats['0'] += 1;
            document.getElementById('statWorking').textContent = statusStats['0'];
            document.getElementById('statWorking').style.color = '#4CAF50';
            break;
        case '1': // Замечания
            statusStats['1'] += 1;
            document.getElementById('statRemarks').textContent = statusStats['1'];
            document.getElementById('statRemarks').style.color = '#FF9800';
            break;
        case '2': // Не работают
            statusStats['2'] += 1;
            document.getElementById('statNotWorking').textContent = statusStats['2'];
            document.getElementById('statNotWorking').style.color = '#f44336';
            break;
        // default:
        //     console.warn('Unknown result value:', resultValue);
    }
}



// Глобальная функция для открытия модального окна
function openServiceModal(objectId, customer, address) {
    // console.log('Открытие модального окна ТО с карты:', { objectId, customer, address });
    // ЗАКРЫВАЕМ БАЛУН ПЕРЕД ОТКРЫТИЕМ МОДАЛЬНОГО ОКНА
    closeAllBalloons();

    // Заполняем форму
    document.getElementById('serviceObjectId').value = objectId;
    document.getElementById('serviceCustomer').value = customer || '';
    document.getElementById('serviceAddress').value = address || '';

    // Устанавливаем текущую дату
    const now = new Date();
    const tzOffset = now.getTimezoneOffset() * 60000;
    const localDate = new Date(now.getTime() - tzOffset);
    document.getElementById('serviceDate').value = localDate.toISOString().slice(0, 16);

    // Сбрасываем форму
    document.getElementById('serviceResult').value = '';
    document.getElementById('serviceComments').value = '';
    document.getElementById('fileUploadContainer').style.display = 'none';
    document.getElementById('serviceFoto').value = '';

    // Загружаем историю ТО
    loadServiceHistory(objectId);

    // Показываем модальное окно
    document.getElementById('serviceModal').style.display = 'block';
}



// Функция для открытия обслуживания из балуна
function openServiceFromMap(objectId) {
    const obj = objectsData.find(item => item.id == objectId);
    if (obj) {
        // console.log('Открытие ТО для:', obj.customer);
        openServiceModal(obj.id, obj.customer, obj.address);
    }
}



// Функция для обновления прогресс-бара после добавления ТО
function updateProgressBar(resultValue) {
    // Увеличиваем счетчик выполненных ТО
    const newCompleted = completedObjects + 1;
    const newRemaining = Math.max(0, totalObjects - newCompleted);
    const newPercentage = totalObjects > 0 ? Math.min(100, (newCompleted / totalObjects) * 100) : 0;

    // Обновляем элементы прогресс-бара
    document.getElementById('progressPercentage').textContent = newPercentage.toFixed(1) + '%';
    document.getElementById('progressFill').style.width = newPercentage.toFixed(1) + '%';
    document.getElementById('progressText').textContent = newCompleted + ' / ' + totalObjects;

    // Обновляем статистику выполнено/осталось
    const statsElements = document.querySelectorAll('.stat-value');
    if (statsElements.length >= 7) {
        statsElements[1].textContent = newCompleted; // Выполнено
        statsElements[1].style.color = '#4CAF50';
        statsElements[5].textContent = newRemaining; // Осталось
        statsElements[5].style.color = '#f44336';
    }

    // Обновляем статистику по конкретному результату
    updateStatusStats(resultValue);
}



// Обработчик изменения результата
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
    if (selectedValue === '1' || selectedValue === '2') {
        fileUploadContainer.style.display = 'block';
    } else {
        fileUploadContainer.style.display = 'none';
    }
}



// Функция закрытия модального окна
function closeServiceModal() {
    document.getElementById('serviceModal').style.display = 'none';
    document.getElementById('serviceForm').reset();
    document.getElementById('serviceHistory').innerHTML = '<div class="no-services">Загрузка истории...</div>';
}



// Добавление записи о ТО
function addServiceRecord() {
    const form = document.getElementById('serviceForm');
    const formData = new FormData(form);

    // Получаем значение результата до отправки
    const resultValue = document.getElementById('serviceResult').value;
    const objectId = document.getElementById('serviceObjectId').value;
    
    console.log(`Добавление ТО для объекта ID: ${objectId}, результат: ${resultValue}`);

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

    // Отправка данных
    fetch('/../../modules/service/add_service.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        // if (result.success) {
        //     // Обновляем прогресс-бар с учетом результата
        //     updateProgressBar(resultValue);
        //     alert('Запись о ТО успешно добавлена!');
        //     closeServiceModal();
        // } else {
        //     alert('Ошибка: ' + (result.error || 'Неизвестная ошибка'));
        // }
        if (loadingDiv) loadingDiv.style.display = 'none';

        if (result.success) {
            // Обновляем прогресс-бар с учетом результата
            updateProgressBar(resultValue);

            // УДАЛЯЕМ ОБЪЕКТ С КАРТЫ
            removeObjectFromMap(objectId);

            // ЗАКРЫВАЕМ БАЛУН, ЕСЛИ ОН ОТКРЫТ
            closeAllBalloons();
            
            // Показываем сообщение об успехе
            showMapMessage('Запись о ТО успешно добавлена!', 'success');
            
            // Закрываем модальное окно
            closeServiceModal();
        } else {
            showMapMessage('Ошибка: ' + (result.error || 'Неизвестная ошибка'), 'error');
        }
    })
    .catch(error => {
        // alert('Ошибка сети: ' + error.message);
        if (loadingDiv) loadingDiv.style.display = 'none';
        showMapMessage('Ошибка сети: ' + error.message, 'error');
    });
}


// Функция для удаления объекта с карты
function removeObjectFromMap(objectId) {
    console.log(`Пытаемся удалить объект с ID: ${objectId}`);
    console.log(`Текущее количество меток: ${objectPlacemarks.length}`);
    
    // Конвертируем в число для надежного сравнения
    const targetId = parseInt(objectId);
    
    // Ищем индекс объекта в массиве objectPlacemarks
    let placemarkIndex = -1;
    for (let i = 0; i < objectPlacemarks.length; i++) {
        const placemark = objectPlacemarks[i];
        const placemarkId = placemark.properties.get('objectId');
        console.log(`Метка ${i} имеет ID: ${placemarkId}`);
        
        if (placemarkId && parseInt(placemarkId) === targetId) {
            placemarkIndex = i;
            break;
        }
    }
    
    if (placemarkIndex !== -1) {
        // Получаем метку для удаления
        const placemarkToRemove = objectPlacemarks[placemarkIndex];
        
        // Удаляем из кластеризатора
        if (clusterer) {
            clusterer.remove(placemarkToRemove);
            console.log(`Метка удалена из кластеризатора`);
        }
        
        // Удаляем из массива
        objectPlacemarks.splice(placemarkIndex, 1);
        
        // Также удаляем из objectsData, чтобы объект больше не отображался
        const dataIndex = objectsData.findIndex(obj => parseInt(obj.id) === targetId);
        if (dataIndex !== -1) {
            objectsData.splice(dataIndex, 1);
            console.log(`Объект удален из objectsData`);
        }
        
        console.log(`Объект с ID ${objectId} успешно удален с карты. Осталось меток: ${objectPlacemarks.length}`);
    } else {
        console.log(`Объект с ID ${objectId} не найден на карте`);
        // Альтернативный метод поиска - по всем меткам в кластеризаторе
        if (clusterer) {
            const allPlacemarks = clusterer.getGeoObjects();
            console.log(`Всего меток в кластеризаторе: ${allPlacemarks.length}`);
            
            for (let i = 0; i < allPlacemarks.length; i++) {
                const placemark = allPlacemarks[i];
                const placemarkId = placemark.properties.get('objectId');
                if (placemarkId && parseInt(placemarkId) === targetId) {
                    clusterer.remove(placemark);
                    console.log(`Метка найдена и удалена из кластеризатора через альтернативный метод`);
                    break;
                }
            }
        }
    }
}

// Функция для закрытия всех балунов
function closeAllBalloons() {
    // Закрываем балун карты
    if (myMap && myMap.balloon) {
        myMap.balloon.close();
    }
    
    // Закрываем балуны всех меток
    if (objectPlacemarks) {
        objectPlacemarks.forEach(function(placemark) {
            if (placemark.balloon && placemark.balloon.isOpen()) {
                placemark.balloon.close();
            }
        });
    }
    
    // Закрываем балун кластеризатора
    if (clusterer && clusterer.balloon && clusterer.balloon.isOpen()) {
        clusterer.balloon.close();
    }
}


// Функция для показа сообщений на карте
function showMapMessage(text, type) {
    // Проверяем, есть ли уже контейнер для сообщений
    let messageContainer = document.getElementById('mapMessage');
    
    if (!messageContainer) {
        // Создаем контейнер для сообщений
        messageContainer = document.createElement('div');
        messageContainer.id = 'mapMessage';
        messageContainer.className = 'map-message';
        document.body.appendChild(messageContainer);
    }
    
    // Устанавливаем текст и тип сообщения
    messageContainer.textContent = text;
    messageContainer.className = `map-message ${type}`;
    messageContainer.style.display = 'block';
    
    // Автоматически скрываем через 3 секунды
    setTimeout(() => {
        messageContainer.style.display = 'none';
    }, 3000);
}


// Карта и связанные функции
let myMap;
let clusterer;
let objectPlacemarks = [];

// Открыть маршрут в Яндекс.Картах
function openRoute(lat, lng) {
    const url = `https://yandex.ru/maps/?rtext=~${lat},${lng}&rtt=auto`;
    window.open(url, '_blank');
}

// Функция инициализации карты
ymaps.ready(function() {
    initMap();
});

async function initMap() {
    document.getElementById('loading').style.display = 'none';
    myMap = new ymaps.Map('map', {
        center: [53.902365, 27.561709],
        zoom: 7,
        controls: ['zoomControl', 'fullscreenControl']
    });
    
    clusterer = new ymaps.Clusterer({
        preset: 'islands#invertedBlueClusterIcons',
        clusterDisableClickZoom: true,
        clusterOpenBalloonOnClick: true,
        gridSize: 64,
        maxZoom: 17
    });
    
    myMap.geoObjects.add(clusterer);
    await addObjectsToMap();

    // Закрытие балунов по клику на карту
    myMap.events.add('click', function (e) {
        myMap.balloon.close();
        objectPlacemarks.forEach(function(placemark) {
            if (placemark.balloon && placemark.balloon.isOpen()) {
                placemark.balloon.close();
            }
        });
        if (clusterer.balloon && clusterer.balloon.isOpen()) {
            clusterer.balloon.close();
        }
    });
}



// Добавление объектов на карту
function addObjectsToMap() {
    return new Promise((resolve) => {
        objectPlacemarks = [];
        objectsData.forEach((obj, index) => {
            if (obj.lat && obj.lng) {
                const coordinates = [obj.lat, obj.lng];
                const placemark = createPlacemark(obj, coordinates, index);
                // Можно добавить ID напрямую в свойства метки
                placemark.properties.set('objectId', obj.id);
                objectPlacemarks.push(placemark);
                clusterer.add(placemark);
            }
        });
        setTimeout(resolve, 300);
    });
}



// Создание метки
function createPlacemark(obj, coordinates, index) {
    let phoneLink = '';
    if (obj.phone) {
        const cleanPhone = obj.phone.replace(/\D/g, '');
        phoneLink = cleanPhone.startsWith('375') ? '+' + cleanPhone : '+375' + cleanPhone;
    }

    const template = generateBalloonTemplate(obj, coordinates, phoneLink);
    const placemark = new ymaps.Placemark(coordinates, {
        // objectId: obj.id,  // ДОБАВЬТЕ ЭТУ СТРОКУ
        balloonContentHeader: template.header,
        balloonContentBody: template.body,
        hintContent: obj.address || obj.customer || 'Объект'
    }, {
        preset: 'islands#blueCircleDotIcon',
        balloonCloseButton: true,
        balloonAutoPan: true
    });

    // Сохраняем ID объекта в свойства метки ПОСЛЕ создания
    placemark.properties.set('objectId', obj.id);

    return placemark;
}



// Инициализация формы после загрузки DOM
document.addEventListener('DOMContentLoaded', function() {
    // Обработчик отправки формы
    const serviceForm = document.getElementById('serviceForm');
    if (serviceForm) {
        serviceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            addServiceRecord();
        });
    }

    // Обработчик изменения результата
    const resultSelect = document.getElementById('serviceResult');
    if (resultSelect) {
        resultSelect.addEventListener('change', handleResultChange);
    }

    // Закрытие по клику вне окна
    const serviceModal = document.getElementById('serviceModal');
    if (serviceModal) {
        serviceModal.addEventListener('click', function(e) {
            if (e.target === serviceModal) {
                closeServiceModal();
            }
        });
    }

    // Горячие клавиши
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const serviceModal = document.getElementById('serviceModal');
            if (serviceModal && serviceModal.style.display === 'block') {
                closeServiceModal();
            }

            const fotoModal = document.getElementById('fotoModal');
            if (fotoModal && fotoModal.style.display === 'block') {
                if (typeof closeFotoModal === 'function') {
                    closeFotoModal();
                }
            }
        }
    });
});