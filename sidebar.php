<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Стили для боковой панели */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

       

        .sidebar {
            width: 250px;
            background: linear-gradient(rgba(94, 74, 138, 0.9), rgba(30, 5, 85, 0.9)), 
                        url('office_background.jpg') center/cover no-repeat;
            color: #ecf0f1;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .logo img {
            width: 150px;
            margin: 20px auto;
            display: block;
        }

        .nav-menu {
            display: flex;
            flex-direction: column;
            padding: 0;
            list-style: none;
            flex-grow: 1;
        }

        .nav-item {
            display: block;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
            cursor: pointer;
        }

        .nav-item:hover,
        .nav-item.active {
            background-color: rgb(60, 45, 92);
            color: #fff;
        }

        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            cursor: pointer;
        }

        .dropdown-menu {
            display: none;
            flex-direction: column;
            position: relative;
            background-color: transparent;
            width: 100%;
            z-index: 1000;
        }

        .dropdown-menu .dropdown-item {
            padding: 10px 20px;
            color: #ecf0f1;
            text-decoration: none;
        }

        .dropdown-menu .dropdown-item:hover {
            background-color: rgb(119, 94, 173);
            color: #fff;
        }

        .dropdown.open .dropdown-menu {
            display: flex;
        }

        .logout-container {
            margin-top: auto;
        }

        /* Кнопка гамбургер-меню */
        .hamburger {
            display: none;
            font-size: 24px;
            color: #333;
            background: none;
            border: none;
            cursor: pointer;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
        }

        /* Адаптивные стили */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .hamburger {
                display: block;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }

        @media (min-width: 769px) {
            .sidebar {
                transform: translateX(0);
            }

            .hamburger {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="hamburger"><i class='bx bx-menu'></i></button>
        <aside class="sidebar">
            <div class="logo">
                <img src="logo1.png" alt="Logo 1">
            </div>
            <nav class="nav-menu">
                <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                    <i class='bx bx-home-alt'></i> Главная
                </a>
                <a href="groups.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'groups.php') ? 'active' : ''; ?>">
                    <i class='bx bx-group'></i> Группы
                </a>
                <div class="dropdown">
                    <a href="#" class="nav-item dropdown-toggle">
                        <i class='bx bx-user'></i> Учащиеся
                    </a>
                    <div class="dropdown-menu">
                        <a href="students.php" class="dropdown-item">Все учащиеся</a>
                        <a href="brsm.php" class="dropdown-item">БРСМ</a>
                        <a href="volunteers.php" class="dropdown-item">Волонтеры</a>
                    </div>
                </div>
                <a href="achievements.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'achievements.php') ? 'active' : ''; ?>">
                    <i class='bx bx-trophy'></i> Достижения
                </a>
                <div class="dropdown">
                    <a href="#" class="nav-item dropdown-toggle">
                        <i class='bx bx-file'></i> Приказы
                    </a>
                    <div class="dropdown-menu">
                        <a href="otchet.php" class="dropdown-item">Сертификаты</a>
                        <a href="notif.php" class="dropdown-item">Уведомления</a>
                        <a href="orders.php" class="dropdown-item">Взыскания</a>
                        <a href="neusp.php" class="dropdown-item">Неуспевающие</a>
                    </div>
                </div>
            </nav>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="logout-container">
                    <a href="logout.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'logout.php') ? 'active' : ''; ?>">
                        <i class='bx bx-log-out'></i> Выйти
                    </a>
                </div>
            <?php endif; ?>
        </aside>
        <!-- Основной контент должен быть добавлен здесь -->
    </div>

    <script>
        // Управление гамбургер-меню
        const hamburger = document.querySelector('.hamburger');
        const sidebar = document.querySelector('.sidebar');

        hamburger.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Закрытие боковой панели при клике вне её
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.sidebar') && !e.target.closest('.hamburger')) {
                sidebar.classList.remove('active');
            }
        });

        // Управление раскрывающимися списками
        document.querySelectorAll('.dropdown-toggle').forEach((dropdownToggle) => {
            dropdownToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const parent = dropdownToggle.parentElement;
                const isOpen = parent.classList.contains('open');

                // Закрываем все остальные раскрывающиеся списки
                document.querySelectorAll('.dropdown').forEach((dropdown) => {
                    dropdown.classList.remove('open');
                });

                // Открываем/закрываем текущий список
                if (!isOpen) {
                    parent.classList.add('open');
                }
            });
        });

        // Закрытие раскрывающихся списков при клике вне их
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown').forEach((dropdown) => {
                    dropdown.classList.remove('open');
                });
            }
        });
    </script>
</body>
</html>