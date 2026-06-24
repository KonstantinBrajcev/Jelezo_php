<?php
// document.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../modules/auth/auth.php';

checkAuth();

$currentUser = getCurrentUser();

// ПОДКЛЮЧЕНИЕ К БД SQLite3
try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // // ПРОВЕРКА СУЩЕСТВОВАНИЯ ТАБЛИЦЫ
    // $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='LIFTEH_document'");
    // $tableExists = $tableCheck->fetch();
    
    // if (!$tableExists) {
    //     // Таблица не существует - выводим сообщение об ошибке
    //     die("Ошибка: Таблица LIFTEH_document не найдена в базе данных. Пожалуйста, создайте таблицу.");
    // }
    
    // ПОЛУЧАЕМ ДАННЫЕ ИЗ ТАБЛИЦЫ
    $stmt = $pdo->query("SELECT * FROM LIFTEH_document ORDER BY id DESC");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Обработка AJAX запросов
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($isAjax) {
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        // Обработка добавления документа
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_document') {
            $name = trim($_POST['name']);
            $category = trim($_POST['category']);
            
            $link = '';
            if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../assets/uploads/document/';
                
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['document_file']['name']));
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filePath)) {
                    $link = '/assets/uploads/document/' . $fileName;
                }
            }
            
            if ($name && $category && $link) {
                $stmt = $pdo->prepare("INSERT INTO LIFTEH_document (name, link, category) VALUES (?, ?, ?)");
                $stmt->execute([$name, $link, $category]);
                echo json_encode(['success' => true, 'message' => 'Документ успешно добавлен']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Заполните все поля или выберите файл']);
            }
            exit;
        }
        
        // Получение списка документов
        if (isset($_GET['action']) && $_GET['action'] === 'get_documents') {
            $stmt = $pdo->query("SELECT id, name, link, category FROM LIFTEH_document ORDER BY id DESC");
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($documents);
            exit;
        }
        
        // Удаление документа
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_document') {
            $id = intval($_POST['id']);
            
            $stmt = $pdo->prepare("SELECT link FROM LIFTEH_document WHERE id = ?");
            $stmt->execute([$id]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($document) {
                if (strpos($document['link'], '/assets/') === 0) {
                    $filePath = __DIR__ . '/../..' . $document['link'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                $stmt = $pdo->prepare("DELETE FROM LIFTEH_document WHERE id = ?");
                $stmt->execute([$id]);
                
                echo json_encode(['success' => true, 'message' => 'Документ удален']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Документ не найден']);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<?php $pageTitle = 'Документы'; include __DIR__ . '/../../includes/header.php'; ?>


<body>
    <?php include __DIR__ . '/../../modules/admin/user_panel.php'; ?>

    <div class="container">
        <div class="document-header">
            <div>
                <button class="add-document-btn" onclick="openModal()">
                    <i class="fas fa-plus"></i> Добавить
                </button>
            </div>
        </div>
        
        <div>
            <table class="document-table" id="documentTable">
                <thead>
                    <tr>
                        <!-- <th style="width: 5%;">ID</th> -->
                        <th>Наименование</th>
                        <th style="width: 10%;">Категория</th>
                        <th style="width: 5%;">Ссылка</th>
                        <th style="width: 5%;">Действия</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (count($documents) > 0): ?>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <!-- <td><?= htmlspecialchars($doc['id']) ?></td> -->
                                <td><strong><?= htmlspecialchars($doc['name']) ?></strong></td>
                                <td><span class="category-badge"><?= htmlspecialchars($doc['category']) ?></span></td>
                                <td>
                                    <a href="<?= htmlspecialchars($doc['link']) ?>" target="_blank" class="document-link">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                                <td>
                                    <button class="delete-btn" onclick="deleteDocument(<?= $doc['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-state">📭 Документы не найдены</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="count">Найдено записей: <?= count($documents); ?></div>
    </div>

    <!-- Модальное окно -->
    <div id="documentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Добавление документ</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="documentForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Название документа *</label>
                        <input type="text" name="name" id="docName" required placeholder="Например: Постановление №123 от 01.01.2024">
                    </div>
                    <div class="form-group">
                        <label>Категория *</label>
                        <select name="category" id="docCategory" required>
                            <option value="">Выберите категорию</option>
                            <option value="Постановление">Постановление</option>
                            <option value="Указ">Указ</option>
                            <option value="Закон">Закон</option>
                            <option value="Регламент">Регламент</option>
                            <option value="Приказ">Приказ</option>
                            <option value="Инструкция">Инструкция</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Файл документа (PDF, DOC, DOCX) *</label>
                        <input type="file" name="document_file" id="docFile" accept=".pdf,.doc,.docx,.txt" required>
                        <small style="color: #666;">Поддерживаемые форматы: PDF, DOC, DOCX, TXT</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Отмена</button>
                    <button type="submit" class="btn-save">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('documentModal').style.display = 'block';
            document.getElementById('documentForm').reset();
        }
        
        function closeModal() {
            document.getElementById('documentModal').style.display = 'none';
        }
        
        document.getElementById('documentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'add_document');
            
            const submitBtn = document.querySelector('.btn-save');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Сохранение...';
            
            fetch('', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showToast('Ошибка при добавлении документа', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Сохранить';
            });
        });
        
        function deleteDocument(id) {
            if (confirm('Вы уверены, что хотите удалить этот документ? Файл будет удален с сервера.')) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=delete_document&id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    showToast('Ошибка при удалении документа', 'error');
                });
            }
        }
        
        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast-notification toast-${type}`;
            toast.innerHTML = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('documentModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>