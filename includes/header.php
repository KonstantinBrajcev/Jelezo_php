<!-- /includes/header.php -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Сайт'; ?></title>
    <!-- Favicon -->
    <link rel="icon" href="/assets/ico/favicon.ico" type="image/x-icon">

    <!-- Общие стили -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    

    <?php
    // Получаем текущий URL без параметров и без расширения
    $currentPage = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $currentPage = rtrim($currentPage, '/'); // Убираем trailing slash
    
    // Если пустая строка или корень - это index
    if ($currentPage === '' || $currentPage === '/') {
        $currentPage = '/index.php';
    }
    ?>

    <!-- СТРАНИЦА ТАБЛИЦА -->
    <?php if ($currentPage == '/index.php'): ?>
        <link rel="stylesheet" href="/assets/css/index_styles.css">
        <!-- Общий стиль модалок -->
        <link rel="stylesheet" href="/assets/css/modal-styles.css">
    <?php endif; ?>
    
    <!-- СТРАНИЦА КАРТА -->
    <?php if ($currentPage == '/map.php'): ?>
        <!-- Яндекс.Карты API -->
        <!-- <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU"></script> -->
        <script src="https://api-maps.yandex.ru/2.1/?apikey=b0a03b93-14f2-4e5a-b38a-25ee1d5296e0&lang=ru_RU" type="text/javascript"></script>
        <link rel="stylesheet" href="/assets/css/map_styles.css">
        <!-- Общий стиль модалок -->
        <link rel="stylesheet" href="/assets/css/modal-styles.css">
    <?php endif; ?>

    <!-- СТРАНИЦА ДОГОВОРА -->
    <?php if ($currentPage == '/dogovor.php'): ?>
        <link rel="stylesheet" href="/assets/css/dogovor_styles.css">
        <!-- Общий стиль модалок -->
        <!-- <link rel="stylesheet" href="modal-styles.css"> -->
    <?php endif; ?>
    
    <!-- СТРАНИЦА ПРОБЛЕМЫ -->
    <?php if ($currentPage == '/problem.php'): ?>
        <link rel="stylesheet" href="/assets/css/problem_styles.css">
        <!-- Общий стиль модалок -->
        <link rel="stylesheet" href="/assets/css/modal-styles.css">
    <?php endif; ?>



    <!-- В header.php добавьте: -->
    <?php if ($currentPage == '/charts.php'): ?>
        <link rel="stylesheet" href="/assets/css/charts_styles.css">
    <?php endif; ?>
    <!-- <link rel="stylesheet" href="modal-styles.css"> -->

    <!-- ============ ПОДКЛЮЧЕНИЕ СКРИПТОВ ============ -->
    <!-- 1. СНАЧАЛА jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- 2. ПОТОМ Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ============================================ -->
</head>