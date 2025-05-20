<?php
include('config.php');
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=not_authorized');
    exit;
}

// Функция для добавления действия в базу данных
function addAction($pdo, $user_id, $action) {
    $sql = "INSERT INTO actions (user_id, action) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $action]);
}

try {
    // Получение списка студентов
    $sql = "SELECT students.id, students.name, students.group_name, 
                   (CASE WHEN brsm.student_id IS NOT NULL THEN 1 ELSE 0 END) AS brsm,
                   students.volunteer
            FROM students 
            LEFT JOIN brsm ON students.id = brsm.student_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение статистики
    $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $total_brsm = $pdo->query("SELECT COUNT(*) FROM brsm")->fetchColumn();
    $total_volunteers = $pdo->query("SELECT COUNT(*) FROM students WHERE volunteer = 1")->fetchColumn();
    $total_groups = $pdo->query("SELECT COUNT(DISTINCT group_name) FROM students")->fetchColumn();
    // Новая метрика: студенты, не состоящие в БРСМ и не волонтеры
    $total_not_affiliated = $pdo->query("SELECT COUNT(*) FROM students 
                                         LEFT JOIN brsm ON students.id = brsm.student_id 
                                         WHERE brsm.student_id IS NULL AND students.volunteer = 0")->fetchColumn();

} catch (PDOException $e) {
    // Логирование ошибки
    error_log("Database error: " . $e->getMessage());
    $students = [];
    $actions = [];
    $total_students = $total_brsm = $total_volunteers = $total_groups = $total_not_affiliated = 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo2.png" type="image/png">
    <style>
        .content {
            margin-left: 300px;
        }
        .metrics-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px 0;
        }
        .metrics-scroll {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            width: 100%;
        }
        .dashboard-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .table th {
            text-align: left;
        }

        /* Адаптивность для средних экранов (планшеты) */
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
            }
            .dashboard-card {
                min-width: 100%;
                max-width: 100%;
            }
        }

        /* Адаптивность для маленьких экранов (мобильные) */
        @media (max-width: 576px) {
            .metrics-container {
                padding: 10px 0;
            }
            .metrics-scroll {
                gap: 15px;
            }
            .dashboard-card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php'); ?>
        <main class="content">
            <header class="top-header">
                <div class="user-info">
                    <i class='bx bx-user'></i>
                    <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Гость'); ?></span>
                </div>
                <div class="date-container">
                    <i class='bx bx-calendar'></i>
                    <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                    <span class="time-text"><?php echo date('H:i'); ?></span>
                </div>
                <div class="search-container">
                    <input type="text" class="search-bar" placeholder="Поиск по студентам">
                </div>
            </header>

            <!-- Metrics cards -->
            <div class="metrics-container">
                <div class="metrics-scroll">
                    <div class="dashboard-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Всего студентов</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_students); ?></p>
                                <p class="text-sm text-green-500 flex items-center">
                                    <i class="fas fa-arrow-up mr-1"></i> Общее количество
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Членов БРСМ</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_brsm); ?></p>
                                <p class="text-sm text-green-500 flex items-center">
                                    <i class="fas fa-check-circle mr-1"></i> Активные
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-id-card text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Волонтеров</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_volunteers); ?></p>
                                <p class="text-sm text-yellow-500 flex items-center">
                                    <i class="fas fa-hands-helping mr-1"></i> Активные
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-hands-helping text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Групп</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $total_groups; ?></p>
                                <p class="text-sm text-purple-500">Всего групп</p>
                            </div>
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-layer-group text-2xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="dashboard-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Не состоят нигде</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo number_format($total_not_affiliated); ?></p>
                                <p class="text-sm text-red-500 flex items-center">
                                    <i class="fas fa-times-circle mr-1"></i> Не активны
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-red-100 text-red-600">
                                <i class="fas fa-user-times text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h3 class="card-title">Список студентов</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ФИО</th>
                            <th>Группа</th>
                            <th>БРСМ</th>
                            <th>Волонтер</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                        <tr style="animation-delay: <?php echo $index * 0.05; ?>s">
                            <td><?= htmlspecialchars($student['id']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['group_name'] ?? 'Нет группы') ?></td>
                            <td><span class="status-badge <?= $student['brsm'] ? 'status-yes' : 'status-no' ?>">
                                <?= $student['brsm'] ? 'Да' : 'Нет' ?>
                            </span></td>
                            <td>
                                <span class="status-badge <?= $student['volunteer'] ? 'status-yes' : 'status-neutral' ?>">
                                    <?= $student['volunteer'] ? 'Активен' : 'Нет' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Поиск по таблице
        document.querySelector('.search-bar').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    </script>
</body>
</html>