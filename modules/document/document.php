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
    
    // ПОЛУЧАЕМ ВСЕ ДОКУМЕНТЫ
    $stmt = $pdo->query("SELECT * FROM LIFTEH_document ORDER BY nomer DESC");
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ПОЛУЧАЕМ УНИКАЛЬНЫЕ КАТЕГОРИИ ДЛЯ ЗАКЛАДОК
    $stmt = $pdo->query("SELECT DISTINCT category FROM LIFTEH_document ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ГРУППИРУЕМ ДОКУМЕНТЫ ПО КАТЕГОРИЯМ
    $groupedDocuments = [];
    foreach ($documents as $doc) {
        $groupedDocuments[$doc['category']][] = $doc;
    }
    
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
            $nomer = trim($_POST['nomer']);
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
                $stmt = $pdo->prepare("INSERT INTO LIFTEH_document (nomer, name, link, category) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nomer, $name, $link, $category]);
                echo json_encode(['success' => true, 'message' => 'Документ успешно добавлен']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Заполните все поля или выберите файл']);
            }
            exit;
        }
        
        // Получение списка документов
        if (isset($_GET['action']) && $_GET['action'] === 'get_documents') {
            $stmt = $pdo->query("SELECT * FROM LIFTEH_document ORDER BY nomer");
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

<style>
    /* Стили для закладок */
    .tabs-container {
        margin-bottom: 20px;
    }
    
    .tabs {
        display: flex;
        gap: 5px;
        border-bottom: 2px solid #ddd;
        padding-bottom: 0;
        flex-wrap: wrap;
    }
    
    .tab-btn {
        padding: 10px 20px;
        background: #f5f5f5;
        border: none;
        border-radius: 5px 5px 0 0;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #555;
        transition: all 0.3s;
        position: relative;
    }
    
    .tab-btn:hover {
        background: #e8e8e8;
    }
    
    .tab-btn.active {
        background: #4CAF50;
        color: white;
    }
    
    .tab-btn .badge {
        background: #ff6b6b;
        color: white;
        border-radius: 50%;
        padding: 2px 8px;
        font-size: 12px;
        margin-left: 5px;
    }
    
    .tab-btn.active .badge {
        background: white;
        color: #4CAF50;
    }
    
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .tab-content.active {
        display: block;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #999;
        font-size: 16px;
    }
</style>

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
        
        <!-- ЗАКЛАДКИ -->
        <div class="tabs-container">
            <div class="tabs" id="tabsContainer">
                <!-- Закладка "Все" -->
                <button class="tab-btn active" data-tab="all">
                    Все
                    <span class="badge"><?= count($documents) ?></span>
                </button>
                
                <!-- Закладки по категориям -->
                <?php foreach ($categories as $category): ?>
                    <button class="tab-btn" data-tab="<?= htmlspecialchars($category) ?>">
                        <?= htmlspecialchars($category) ?>
                        <span class="badge"><?= isset($groupedDocuments[$category]) ? count($groupedDocuments[$category]) : 0 ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- КОНТЕНТ ЗАКЛАДОК -->
        <div id="tabContentContainer">
            <!-- Вкладка "Все" -->
            <div class="tab-content active" data-tab="all">
                <table class="document-table">
                    <thead>
                        <tr>
                            <th>Номер</th>
                            <th>Наименование</th>
                            <th style="width: 5%;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($documents) > 0): ?>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($doc['nomer']) ?></td>
                                    <td><strong><?= htmlspecialchars($doc['name']) ?></strong></td>
                                    <td style="text-align: center;">
                                        <a href="<?= htmlspecialchars($doc['link']) ?>" target="_blank" class="document-link">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <button style="margin-left: 5px;" class="delete-btn" onclick="deleteDocument(<?= $doc['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="empty-state">📭 Документы не найдены</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Вкладки по категориям -->
            <?php foreach ($categories as $category): ?>
                <div class="tab-content" data-tab="<?= htmlspecialchars($category) ?>">
                    <table class="document-table">
                        <thead>
                            <tr>
                                <th>Номер</th>
                                <th>Наименование</th>
                                <th style="width: 5%;">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($groupedDocuments[$category]) && count($groupedDocuments[$category]) > 0): ?>
                                <?php foreach ($groupedDocuments[$category] as $doc): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($doc['nomer']) ?></td>
                                        <td><strong><?= htmlspecialchars($doc['name']) ?></strong></td>
                                        <td style="text-align: center;">
                                            <a href="<?= htmlspecialchars($doc['link']) ?>" target="_blank" class="document-link">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <button style="margin-left: 5px;" class="delete-btn" onclick="deleteDocument(<?= $doc['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="empty-state">📭 Нет документов в этой категории</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="count">Найдено записей: <?= count($documents); ?></div>
    </div>

    <!-- Модальное окно (без изменений) -->
    <div id="documentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Добавление документа</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="documentForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Номер документа *</label>
                        <input type="text" name="nomer" id="docNomer" required placeholder="Например: ГОСТ №123">
                    </div>
                    <div class="form-group">
                        <label>Название документа *</label>
                        <input type="text" name="name" id="docName" required placeholder="Например: О проведении экспертиз">
                    </div>
                    <div class="form-group">
                        <label>Категория *</label>
                        <select name="category" id="docCategory" required>
                            <option value="">Выберите категорию</option>
                            <option value="Постановление">Постановление</option>
                            <option value="Внутренний">Внутренний</option>
                            <option value="Регламент">Регламент</option>
                            <option value="ГОСТ">ГОСТ</option>
                            <option value="ТКП">ТКП</option>
                            <option value="СНиП">СНиП</option>
                            <option value="СТБ">СТБ</option>
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
        // Логика работы закладок
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Убираем активный класс у всех кнопок
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    // Добавляем активный класс нажатой кнопке
                    this.classList.add('active');
                    
                    // Получаем имя вкладки
                    const tabName = this.getAttribute('data-tab');
                    
                    // Скрываем все содержимое вкладок
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Показываем нужное содержимое
                    document.querySelector(`.tab-content[data-tab="${tabName}"]`).classList.add('active');
                });
            });
        });
        
        // Остальные функции без изменений
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