<?php
// Подключение к базе данных
try {
    $db = new PDO('sqlite:derevo.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Обработка AJAX запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_child':
                addChild($db);
                break;
            case 'add_parents':
                addParents($db);
                break;
            case 'add_spouse':
                addSpouse($db);
                break;
            case 'update_person':
                updatePerson($db);
                break;
            case 'delete_person':
                deletePerson($db);
                break;
            case 'get_tree':
                getTree($db);
                break;
            case 'get_person':
                getPerson($db);
                break;
            case 'save_person':
                savePerson($db);
                break;
        }
    }
    exit;
}

// Функция добавления ребенка
function addChild($db) {
    $parent_id = $_POST['parent_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'];
    $gender = $_POST['gender'];
    $birth_year = $_POST['birth_year'];
    $death_year = $_POST['death_year'];
    $city = $_POST['city']; // Город рождения
    $home = $_POST['home']; // Город проживания
    
    // Добавляем нового человека
    $stmt = $db->prepare("INSERT INTO people (first_name, last_name, middle_name, gender, birth_year, death_year, city, home) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$first_name, $last_name, $middle_name, $gender, $birth_year, $death_year, $city, $home]);
    $child_id = $db->lastInsertId();
    
    // Находим пару родителя
    $stmt = $db->prepare("SELECT c.id as couple_id, 
                                 CASE WHEN p.gender = 'male' THEN c.husband_id ELSE c.wife_id END as spouse_id
                          FROM people p
                          LEFT JOIN couples c ON (p.gender = 'male' AND c.husband_id = p.id) OR (p.gender = 'female' AND c.wife_id = p.id)
                          WHERE p.id = ?");
    $stmt->execute([$parent_id]);
    $couple = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Создаем связь родитель-ребенок
    $stmt = $db->prepare("INSERT INTO relationships (parent_id, child_id, couple_id) VALUES (?, ?, ?)");
    $stmt->execute([$parent_id, $child_id, $couple['couple_id'] ?? null]);
    
    // Если есть второй родитель, добавляем связь и с ним
    if ($couple['spouse_id']) {
        $stmt->execute([$couple['spouse_id'], $child_id, $couple['couple_id']]);
    }
    
    echo json_encode(['success' => true, 'child_id' => $child_id]);
}

// Функция добавления родителей
function addParents($db) {
    $child_id = $_POST['child_id'];
    $father_data = $_POST['father'];
    $mother_data = $_POST['mother'];
    
    // Добавляем отца
    $stmt = $db->prepare("INSERT INTO people (first_name, last_name, middle_name, gender, birth_year, death_year, city, home) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$father_data['first_name'], $father_data['last_name'], $father_data['middle_name'], 'male', $father_data['birth_year'], $father_data['death_year'], $father_data['city'],
    $father_data['home']]);
    $father_id = $db->lastInsertId();
    
    // Добавляем мать
    $stmt->execute([$mother_data['first_name'], $mother_data['last_name'], $mother_data['middle_name'], 'female', $mother_data['birth_year'], $mother_data['death_year'], $mother_data['city'],
    $mother_data['home']]);
    $mother_id = $db->lastInsertId();
    
    // Создаем пару
    $stmt = $db->prepare("INSERT INTO couples (husband_id, wife_id) VALUES (?, ?)");
    $stmt->execute([$father_id, $mother_id]);
    $couple_id = $db->lastInsertId();
    
    // Создаем связи родитель-ребенок
    $stmt = $db->prepare("INSERT INTO relationships (parent_id, child_id, couple_id) VALUES (?, ?, ?)");
    $stmt->execute([$father_id, $child_id, $couple_id]);
    $stmt->execute([$mother_id, $child_id, $couple_id]);
    
    echo json_encode(['success' => true, 'father_id' => $father_id, 'mother_id' => $mother_id]);
}

// Функция добавления супруга
function addSpouse($db) {
    $person_id = $_POST['person_id'];
    $spouse_data = $_POST['spouse'];
    
    // Получаем информацию о текущем человеке
    $stmt = $db->prepare("SELECT gender FROM people WHERE id = ?");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Добавляем супруга
    $stmt = $db->prepare("INSERT INTO people (first_name, last_name, middle_name, gender, birth_year, death_year, city, home) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $spouse_data['first_name'],
        $spouse_data['last_name'],
        $spouse_data['middle_name'],
        $person['gender'] == 'male' ? 'female' : 'male',
        $spouse_data['birth_year'],
        $spouse_data['death_year'],
        $spouse_data['city'],
        $spouse_data['home']
    ]);
    $spouse_id = $db->lastInsertId();
    
    // Создаем пару
    if ($person['gender'] == 'male') {
        $stmt = $db->prepare("INSERT INTO couples (husband_id, wife_id) VALUES (?, ?)");
        $stmt->execute([$person_id, $spouse_id]);
    } else {
        $stmt = $db->prepare("INSERT INTO couples (husband_id, wife_id) VALUES (?, ?)");
        $stmt->execute([$spouse_id, $person_id]);
    }
    
    echo json_encode(['success' => true, 'spouse_id' => $spouse_id]);
}

// Функция обновления информации о человеке (старая версия для отдельных полей)
function updatePerson($db) {
    $id = $_POST['id'];
    $field = $_POST['field'];
    $value = $_POST['value'];
    
    $stmt = $db->prepare("UPDATE people SET $field = ? WHERE id = ?");
    $stmt->execute([$value, $id]);
    
    echo json_encode(['success' => true]);
}

// Функция получения информации о человеке
function getPerson($db) {
    $id = $_POST['id'];
    
    $stmt = $db->prepare("SELECT * FROM people WHERE id = ?");
    $stmt->execute([$id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($person) {
        echo json_encode(['success' => true, 'person' => $person]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Человек не найден']);
    }
}

// Функция сохранения информации о человеке (полное редактирование)
function savePerson($db) {
    $id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $middle_name = $_POST['middle_name'];
    $gender = $_POST['gender'];
    $birth_year = $_POST['birth_year'];
    $death_year = $_POST['death_year'];
    $city = $_POST['city'];
    $home = $_POST['home'];
    
    $stmt = $db->prepare("UPDATE people SET first_name = ?, last_name = ?, middle_name = ?, gender = ?, birth_year = ?, death_year = ?, city = ?, home = ? WHERE id = ?");
    $stmt->execute([$first_name, $last_name, $middle_name, $gender, $birth_year, $death_year, $city, $home, $id]);
    
    echo json_encode(['success' => true]);
}

// Функция удаления человека
function deletePerson($db) {
    $id = $_POST['id'];
    
    // Проверяем, есть ли у человека связи
    $stmt = $db->prepare("SELECT COUNT(*) FROM relationships WHERE child_id = ? OR parent_id = ?");
    $stmt->execute([$id, $id]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Нельзя удалить человека, у которого есть связи']);
        return;
    }
    
    $stmt = $db->prepare("DELETE FROM people WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true]);
}

// Функция для получения дерева (AJAX)
function getTree($db) {
    $tree = [];
    
    // Получаем всех людей
    $stmt = $db->query("SELECT * FROM people ORDER BY id");
    $people = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем все связи родитель-ребенок
    $stmt = $db->query("
        SELECT r.*, c.husband_id, c.wife_id 
        FROM relationships r
        LEFT JOIN couples c ON r.couple_id = c.id
        ORDER BY r.child_id
    ");
    $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Получаем все пары
    $stmt = $db->query("SELECT * FROM couples");
    $couples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Организуем данные для дерева
    foreach ($people as $person) {
        $tree[$person['id']] = [
            'person' => $person,
            'children' => [],
            'parents' => [],
            'spouse' => null
        ];
    }
    
    // Добавляем связи родитель-ребенок
    foreach ($relationships as $rel) {
        if (!isset($tree[$rel['child_id']])) continue;
        if (!isset($tree[$rel['parent_id']])) continue;
        
        // Добавляем родителя к ребенку
        if (!in_array($rel['parent_id'], $tree[$rel['child_id']]['parents'])) {
            $tree[$rel['child_id']]['parents'][] = $rel['parent_id'];
        }
        
        // Добавляем ребенка к родителю
        if (!in_array($rel['child_id'], $tree[$rel['parent_id']]['children'])) {
            $tree[$rel['parent_id']]['children'][] = $rel['child_id'];
        }
    }
    
    // Добавляем супругов
    foreach ($couples as $couple) {
        if (isset($tree[$couple['husband_id']])) {
            $tree[$couple['husband_id']]['spouse'] = $couple['wife_id'];
        }
        if (isset($tree[$couple['wife_id']])) {
            $tree[$couple['wife_id']]['spouse'] = $couple['husband_id'];
        }
    }
    
    echo json_encode(['success' => true, 'tree' => $tree]);
}

// Функция для получения HTML дерева
function renderTree($db) {
    // Находим все корневые пары (пары, где оба супруга не имеют родителей)
    $stmt = $db->query("
        SELECT DISTINCT c.id as couple_id, 
               h.id as husband_id, h.first_name as h_first, h.last_name as h_last, h.middle_name as h_middle,
               h.birth_year as h_birth, h.death_year as h_death,
               w.id as wife_id, w.first_name as w_first, w.last_name as w_last, w.middle_name as w_middle,
               w.birth_year as w_birth, w.death_year as w_death
        FROM couples c
        LEFT JOIN people h ON c.husband_id = h.id
        LEFT JOIN people w ON c.wife_id = w.id
        WHERE (h.id NOT IN (SELECT child_id FROM relationships) 
               OR h.id IS NULL)
          AND (w.id NOT IN (SELECT child_id FROM relationships) 
               OR w.id IS NULL)
        ORDER BY c.id
    ");
    
    $rootCouples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Если нет корневых пар, создаем изначальную пару
    if (empty($rootCouples)) {
        // Находим первую пару
        $stmt = $db->query("
            SELECT c.id as couple_id, 
                   h.id as husband_id, h.first_name as h_first, h.last_name as h_last, h.middle_name as h_middle,
                   h.birth_year as h_birth, h.death_year as h_death,
                   w.id as wife_id, w.first_name as w_first, w.last_name as w_last, w.middle_name as w_middle,
                   w.birth_year as w_birth, w.death_year as w_death
            FROM couples c
            LEFT JOIN people h ON c.husband_id = h.id
            LEFT JOIN people w ON c.wife_id = w.id
            ORDER BY c.id LIMIT 1
        ");
        $rootCouples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Выводим дерево для каждой корневой пары
    $output = '';
    $processedPeople = []; // Массив для отслеживания уже обработанных людей
    
    foreach ($rootCouples as $couple) {
        // Создаем массив для мужа
        $husband = null;
        if ($couple['husband_id']) {
            $husband = [
                'id' => $couple['husband_id'],
                'first_name' => $couple['h_first'],
                'last_name' => $couple['h_last'],
                'middle_name' => $couple['h_middle'],
                'birth_year' => $couple['h_birth'],
                'death_year' => $couple['h_death'],
                'gender' => 'male'
            ];
        }
        
        // Создаем массив для жены
        $wife = null;
        if ($couple['wife_id']) {
            $wife = [
                'id' => $couple['wife_id'],
                'first_name' => $couple['w_first'],
                'last_name' => $couple['w_last'],
                'middle_name' => $couple['w_middle'],
                'birth_year' => $couple['w_birth'],
                'death_year' => $couple['w_death'],
                'gender' => 'female'
            ];
        }
        
        // Отображаем пару и их детей
        if ($husband && !in_array($husband['id'], $processedPeople)) {
            $output .= renderPersonWithChildren($db, $husband, $wife, 0, $processedPeople);
        } elseif ($wife && !in_array($wife['id'], $processedPeople)) {
            $output .= renderPersonWithChildren($db, $wife, null, 0, $processedPeople);
        }
    }
    
    return $output;
}

// Рекурсивная функция для отображения человека и его детей
function renderPersonWithChildren($db, $person, $spouse, $level, &$processedPeople) {
    if (!$person || in_array($person['id'], $processedPeople)) {
        return '';
    }
    
    $processedPeople[] = $person['id'];
    
    // Если супруг не передан, ищем его в базе
    if (!$spouse && $person['id']) {
        $stmt = $db->prepare("
            SELECT spouse.* 
            FROM people p
            LEFT JOIN couples c ON (p.gender = 'male' AND c.husband_id = p.id) OR (p.gender = 'female' AND c.wife_id = p.id)
            LEFT JOIN people spouse ON (p.gender = 'male' AND spouse.id = c.wife_id) OR (p.gender = 'female' AND spouse.id = c.husband_id)
            WHERE p.id = ?
        ");
        $stmt->execute([$person['id']]);
        $spouse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Если нашли супруга, добавляем его в обработанные
        if ($spouse && !in_array($spouse['id'], $processedPeople)) {
            $processedPeople[] = $spouse['id'];
        }
    }
    
    // Получаем детей этой пары
    $children = [];
    if ($person['id'] && $spouse && $spouse['id']) {
        // Получаем ID пары
        $stmt = $db->prepare("
            SELECT id FROM couples 
            WHERE (husband_id = ? AND wife_id = ?) 
               OR (husband_id = ? AND wife_id = ?)
        ");
        $stmt->execute([$person['id'], $spouse['id'], $spouse['id'], $person['id']]);
        $couple = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($couple) {
            $stmt = $db->prepare("
                SELECT DISTINCT p.* 
                FROM people p
                INNER JOIN relationships r ON p.id = r.child_id
                WHERE r.couple_id = ?
                ORDER BY p.birth_year, p.id
            ");
            $stmt->execute([$couple['id']]);
            $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif ($person['id']) {
        // Если супруга нет, ищем детей только от этого человека
        $stmt = $db->prepare("
            SELECT DISTINCT p.* 
            FROM people p
            INNER JOIN relationships r ON p.id = r.child_id
            WHERE r.parent_id = ?
            ORDER BY p.birth_year, p.id
        ");
        $stmt->execute([$person['id']]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Получаем родителей текущего человека (если они есть)
    $stmt = $db->prepare("
        SELECT p.* 
        FROM people p
        INNER JOIN relationships r ON p.id = r.parent_id
        WHERE r.child_id = ?
        ORDER BY p.gender DESC
    ");
    $stmt->execute([$person['id']]);
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Формируем HTML
        $output = '<div class="family-branch">';
    
    // Отображаем родителей (если есть и если мы не на слишком высоком уровне)
    if ($parents && $level < 3) {
        $output .= '<div class="parents">';
        foreach ($parents as $parent) {
            if (!in_array($parent['id'], $processedPeople)) {
                $output .= renderPerson($parent, false);
                $processedPeople[] = $parent['id'];
            }
        }
        $output .= '</div>';
    }
    
    // Отображаем текущую пару
    $output .= '<div class="couple">';
    $output .= renderPerson($person, true);
    if ($spouse && !in_array($spouse['id'], $processedPeople)) {
        $output .= renderPerson($spouse, true);
        $processedPeople[] = $spouse['id'];
    }
    $output .= '</div>';
    
    // Отображаем детей
    if ($children) {
        $output .= '<div class="children">';
        
        // Вместо группировки по парам, просто отображаем всех детей
        foreach ($children as $child) {
            // Пропускаем уже обработанных детей
            if (in_array($child['id'], $processedPeople)) {
                continue;
            }
            
            // Для каждого ребенка ищем его/ее супруга
            $stmt = $db->prepare("
                SELECT spouse.* 
                FROM people p
                LEFT JOIN couples c ON (p.gender = 'male' AND c.husband_id = p.id) OR (p.gender = 'female' AND c.wife_id = p.id)
                LEFT JOIN people spouse ON (p.gender = 'male' AND spouse.id = c.wife_id) OR (p.gender = 'female' AND spouse.id = c.husband_id)
                WHERE p.id = ?
            ");
            $stmt->execute([$child['id']]);
            $childSpouse = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Отображаем ребенка и его супруга (если есть)
            $output .= renderPersonWithChildren($db, $child, $childSpouse, $level + 1, $processedPeople);
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>';
    return $output;
}

// Функция для отображения одного человека
function renderPerson($person, $showFullControls = true) {
    if (!$person || !isset($person['id'])) {
        return '';
    }
    
    $years = '';
    if ($person['birth_year']) {
        $years = $person['birth_year'];
        if ($person['death_year']) {
            $years .= ' - ' . $person['death_year'];
        }
    }
    
    // Добавляем информацию о городах
    $cityInfo = '';
    if (!empty($person['city']) || !empty($person['home'])) {
        $cityInfo = '<div class="city-info">';
        if (!empty($person['city'])) {
            $cityInfo .= '<div><span class="label">Род.:</span> ' . htmlspecialchars($person['city']) . '</div>';
        }
        if (!empty($person['home'])) {
            $cityInfo .= '<div><span class="label">Жив.:</span> ' . htmlspecialchars($person['home']) . '</div>';
        }
        $cityInfo .= '</div>';
    }

    return '
    <div class="person ' . $person['gender'] . '" data-id="' . $person['id'] . '">
        <div class="person-header">
            <h3>' . htmlspecialchars($person['last_name']) . '<br>' . 
                    htmlspecialchars($person['first_name']) . '<br>' . 
                    htmlspecialchars($person['middle_name']) . '
            </h3>
            <div class="settings-container">
                <button class="settings-btn" onclick="toggleMenu(event, this)">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                  <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492M5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0"/>
                  <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115z"/>
                </svg>
                </button>
                <div class="settings-menu">
                    <button class="add-child" onclick="showAddChildModal(' . $person['id'] . ')">+ Ребенок</button>
                    <button class="add-parents" onclick="showAddParentsModal(' . $person['id'] . ')">+ Родители</button>
                    <button class="add-spouse" onclick="showAddSpouseModal(' . $person['id'] . ')">+ Супруг</button>
                    <button class="edit-person" onclick="showEditPersonModal(' . $person['id'] . ')">✏️ Редактировать</button>
                    <button class="delete-person" onclick="deletePerson(' . $person['id'] . ')">🗑️ Удалить</button>
                </div>
            </div>
        </div>
        <div class="years">' . $years . '</div>
        ' . $cityInfo . '
    </div>';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Родословное дерево</title>
<link rel="stylesheet" href="derevo.css">
</head>
<body>
    <div class="container">
    <div class="header-window">    
      <h1 style="text-align: center;">Родословное дерево</h1>
      <button class="refresh-btn p-1" onclick="location.reload()">Обновить</button>
    </div>    
    <div class="family-tree" id="familyTree">
        <div class="tree-container">
            <?php echo renderTree($db); ?>
        </div>
      </div>

    </div>

  <?php include 'modal.php'; ?>

  <script src="derevo.js"></script>
</body>
</html>