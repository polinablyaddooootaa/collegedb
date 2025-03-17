<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css"> <!-- Подключение стилей -->
</head>
<body>
<aside class="sidebar">
    <div class="logo">
        <img src="logo1.png" alt="Logo 1">
    </div>
    <nav class="nav-menu">
        <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class='bx bx-home-alt'></i> Главная
        </a>
        <a href="students.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'students.php') ? 'active' : ''; ?>">
            <i class='bx bx-user'></i> Учащиеся
        </a>
        <a href="otchet.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'otchet.php') ? 'active' : ''; ?>">
            <i class='bx bx-group'></i> Создание сертификатов
        </a>
        <a href="volunteers.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'volunteers.php') ? 'active' : ''; ?>">
            <i class='bx bx-heart'></i> Волонтеры
        </a>
        <a href="brsm.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'brsm.php') ? 'active' : ''; ?>">
            <i class='bx bx-flag'></i> БРСМ
        </a>
        <a href="achievements.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'achievements.php') ? 'active' : ''; ?>">
            <i class='bx bx-trophy'></i> Достижения
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Кнопка выхода -->
            <a href="logout.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'logout.php') ? 'active' : ''; ?>">
                <i class='bx bx-log-out'></i> Выйти
            </a>
        <?php endif; ?>
    </nav>
</aside>
</body>
</html>
