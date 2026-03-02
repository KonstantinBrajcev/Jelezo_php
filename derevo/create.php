<?php
try {
    // Создаем базу данных SQLite
    $db = new PDO('sqlite:derevo.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Таблица людей
    $db->exec("CREATE TABLE IF NOT EXISTS people (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        middle_name TEXT,
        birth_year INTEGER,
        death_year INTEGER,
        gender TEXT CHECK(gender IN ('male', 'female'))
    )");
    
    // Таблица пар (супружеские отношения)
    $db->exec("CREATE TABLE IF NOT EXISTS couples (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        husband_id INTEGER,
        wife_id INTEGER,
        FOREIGN KEY (husband_id) REFERENCES people(id),
        FOREIGN KEY (wife_id) REFERENCES people(id)
    )");
    
    // Таблица связей родитель-ребенок
    $db->exec("CREATE TABLE IF NOT EXISTS relationships (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        parent_id INTEGER NOT NULL,
        child_id INTEGER NOT NULL,
        couple_id INTEGER,
        FOREIGN KEY (parent_id) REFERENCES people(id),
        FOREIGN KEY (child_id) REFERENCES people(id),
        FOREIGN KEY (couple_id) REFERENCES couples(id)
    )");
    
    // Создаем изначальных родителей
    $stmt = $db->prepare("INSERT INTO people (first_name, last_name, middle_name, gender) VALUES (?, ?, ?, ?)");
    
    // Добавляем мужа
    $stmt->execute(['Михаил', 'Климов', 'Дмитриевич', 'male']);
    $husband_id = $db->lastInsertId();
    
    // Добавляем жену
    $stmt->execute(['Мария', 'Климова', 'Дмитриевна', 'female']);
    $wife_id = $db->lastInsertId();
    
    // Создаем пару
    $db->exec("INSERT INTO couples (husband_id, wife_id) VALUES ($husband_id, $wife_id)");
    
    echo "База данных успешно создана! Добавлены первоначальные родители.<br>";
    echo "Муж: Климов Михаил Дмитриевич (ID: $husband_id)<br>";
    echo "Жена: Климова Мария Дмитриевна (ID: $wife_id)<br>";
    echo '<a href="derevo.php">Перейти к родословному дереву</a>';
    
} catch(PDOException $e) {
    die("Ошибка создания базы данных: " . $e->getMessage());
}
?>