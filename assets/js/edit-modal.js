// edit-modal.js
let currentEditData = null;
let editModal = null; // Сохраняем ссылку на модальное окно

function openEditModal(rowData) {
    currentEditData = rowData;

    // Заполняем форму данными
    document.getElementById('editId').value = rowData.id || '';
    document.getElementById('editCustomer').value = rowData.customer || '';
    document.getElementById('editAddress').value = rowData.address || '';
    document.getElementById('editModel').value = rowData.model || '';
    document.getElementById('editNumber').value = rowData.serial_number || '';
    document.getElementById('editPhone').value = rowData.phone || '';
    document.getElementById('editName').value = rowData.name || '';
    document.getElementById('editLatitude').value = rowData.latitude || '';
    document.getElementById('editLongitude').value = rowData.longitude || '';

    // Показываем модальное окно
    editModal = document.getElementById('editModal');
    editModal.style.display = 'block';
}

function closeEditModal() {
    if (editModal) {
        editModal.style.display = 'none';
    }
    currentEditData = null;
    document.getElementById('editForm').reset();
}

function saveChanges() {
    const form = document.getElementById('editForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Показываем индикатор загрузки
    document.getElementById('loading').style.display = 'block';
    hideMessage();

    fetch('update_object.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            document.getElementById('loading').style.display = 'none';

            if (result.success) {
                showMessage('Изменения сохранены успешно!', 'success');
                updateTableRow(data);
                closeEditModal();
            } else {
                showMessage('Ошибка: ' + (result.error || 'Неизвестная ошибка'), 'error');
            }
        })
        .catch(error => {
            document.getElementById('loading').style.display = 'none';
            showMessage('Ошибка сети: ' + error.message, 'error');
        });
}

// Инициализация после загрузки DOM
document.addEventListener('DOMContentLoaded', function () {
    // Получаем элементы один раз
    editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');

    if (editModal) {
        // Закрытие по клику на фон модального окна
        editModal.addEventListener('click', function (event) {
            if (event.target === editModal) {
                closeEditModal();
            }
        });
    }

    // Обработка отправки формы редактирования
    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();
            saveChanges();
        });
    }

    // Горячие клавиши
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (editModal && editModal.style.display === 'block') {
                closeEditModal();
            }
        }
    });
});