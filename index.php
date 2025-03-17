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



// Обработка формы добавления студента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $name = $_POST['name'];
    $group_name = $_POST['group_name'];
    $brsm = isset($_POST['brsm']) ? 1 : 0;
    $volunteer = isset($_POST['volunteer']) ? 1 : 0;

    $sql = "INSERT INTO students (name, group_name, brsm, volunteer) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $group_name, $brsm, $volunteer]);

    // Расширенная запись действия добавления студента
    addAction($pdo, $_SESSION['user_id'], 'Добавление записи в таблицу студентов: ' . $name . ' (Группа: ' . $group_name . ')');

    header('Location: index.php');
    exit;
}

// Обработка удаления студента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];

    // Получаем имя студента перед удалением
    $stmt = $pdo->prepare("SELECT name, group_name FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);

    // Запись действия удаления студента
    addAction($pdo, $_SESSION['user_id'], 'Удаление записи из таблицы студентов: ' . $student['name'] . ' (Группа: ' . $student['group_name'] . ')');

    header('Location: index.php');
    exit;
}

// Обработка редактирования студента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_student'])) {
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $group_name = $_POST['group_name'];
    $brsm = isset($_POST['brsm']) ? 1 : 0;
    $volunteer = isset($_POST['volunteer']) ? 1 : 0;

    // Получаем текущие данные студента
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $oldStudent = $stmt->fetch(PDO::FETCH_ASSOC);

    $sql = "UPDATE students SET name = ?, group_name = ?, brsm = ?, volunteer = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $group_name, $brsm, $volunteer, $student_id]);

    // Создаем текст изменений
    $changes = [];
    if ($oldStudent['name'] !== $name) $changes[] = 'ФИО';
    if ($oldStudent['group_name'] !== $group_name) $changes[] = 'Группа';
    if ($oldStudent['brsm'] != $brsm) $changes[] = 'Статус БРСМ';
    if ($oldStudent['volunteer'] != $volunteer) $changes[] = 'Статус волонтера';

    if (!empty($changes)) {
        $changeText = implode(', ', $changes);
        addAction($pdo, $_SESSION['user_id'], 'Изменение записи в таблице студентов: ' . $name . ' (Измененные поля: ' . $changeText . ')');
    }

    header('Location: index.php');
    exit;
}

// Получение списка студентов
$sql = "SELECT students.id, students.name, students.group_name, 
               (CASE WHEN brsm.student_id IS NOT NULL THEN 1 ELSE 0 END) AS brsm,
               students.volunteer
        FROM students 
        LEFT JOIN brsm ON students.id = brsm.student_id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение последних действий
$actionsSql = "SELECT actions.id, actions.action, actions.timestamp, users.username,
               DATE_FORMAT(actions.timestamp, '%Y-%m-%d %H:%i:%s') as formatted_timestamp
               FROM actions
               INNER JOIN users ON actions.user_id = users.id
               ORDER BY actions.timestamp DESC 
               LIMIT 10";
$actionsStmt = $pdo->prepare($actionsSql);
$actionsStmt->execute();
$actions = $actionsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css">
    <link rel="icon" href="logo2.png" type="image/png">
</head>

<body>
    <div class="container">
    <?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->
     
        <main class="main-content">
            <header class="top-header">
            <div class="user-info">
                    <i class='bx bx-user'></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span> <!-- Имя пользователя из сессии -->
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

            <div class="cards-grid">
                <div class="card action-card">
                    <h2 class="card-title">Последние действия</h2>
                    <div class="action-list">
                        <ul>
                            <?php foreach ($actions as $action): ?>
                                <li>
                                    <span class="action-user"><?php echo htmlspecialchars($action['username']); ?> (сессия)</span>
                                    <span class="action-timestamp"><?php echo date('d.m.Y H:i:s', strtotime($action['timestamp'])); ?></span>
                                    <div class="action-detail"><?php echo htmlspecialchars($action['action']); ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <h2 class="card-title">Учащиеся</h2>
                    <div class="chart-container">
                        <canvas id="studentsChart"></canvas>
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
                        <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['id']) ?></td>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['group_name']) ?></td>
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

               

    <!-- Модальное окно будет добавлено через JavaScript -->

    <script>
  document.querySelector('.search-bar').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Получаем данные о студентах из PHP
        const students = <?= json_encode($students) ?>;

        // Конфигурация графика студентов
        const studentsCtx = document.getElementById('studentsChart').getContext('2d');

        // Создаем градиенты для разных категорий
        const totalGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
        totalGradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
        totalGradient.addColorStop(1, 'rgba(59, 130, 246, 0.2)');

        const brsmGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
        brsmGradient.addColorStop(0, 'rgba(139, 92, 246, 0.8)');
        brsmGradient.addColorStop(1, 'rgba(139, 92, 246, 0.2)');

        const volunteerGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
        volunteerGradient.addColorStop(0, 'rgba(236, 72, 153, 0.8)');
        volunteerGradient.addColorStop(1, 'rgba(236, 72, 153, 0.2)');

        const nonAffiliatedGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
        nonAffiliatedGradient.addColorStop(0, 'rgba(156, 163, 175, 0.8)');
        nonAffiliatedGradient.addColorStop(1, 'rgba(156, 163, 175, 0.2)');

        // Подготавливаем данные для графика
        const studentsData = {
            labels: ['Статистика учащихся'],
            datasets: [
                {
                    label: 'Всего учащихся',
                    data: [students.length],
                    backgroundColor: totalGradient,
                    borderColor: 'rgba(59, 130, 246, 0.5)',
                    borderWidth: 1
                },
                {
                    label: 'БРСМ',
                    data: [students.filter(s => s.brsm == 1).length],
                    backgroundColor: brsmGradient,
                    borderColor: 'rgba(139, 92, 246, 0.5)',
                    borderWidth: 1
                },
                {
                    label: 'Волонтеры',
                    data: [students.filter(s => s.volunteer == 1).length],
                    backgroundColor: volunteerGradient,
                    borderColor: 'rgba(236, 72, 153, 0.5)',
                    borderWidth: 1
                },
                {
                    label: 'Не состоят нигде',
                    data: [students.filter(s => s.brsm == 0 && s.volunteer == 0).length],
                    backgroundColor: nonAffiliatedGradient,
                    borderColor: 'rgba(156, 163, 175, 0.5)',
                    borderWidth: 1
                }
            ]
        };

        // Создаем график студентов
        const studentsChart = new Chart(studentsCtx, {
            type: 'bar',
            data: studentsData,
            options: {
    responsive: false,
    maintainAspectRatio: false,
    width: 600,  // фиксированная ширина
    height: 400, // фиксированная высота
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: 'rgba(200, 200, 200, 0.3)',
                drawBorder: true,
            },
            ticks: {
                font: {
                    size: 14
                },
                color: 'black'
            }
        },
        x: {
            grid: {
                color: 'rgba(200, 200, 200, 0.3)',
                drawBorder: true,
            },
            ticks: {
                font: {
                    size: 14
                },
                color: 'black'
            }
        }
    },
    plugins: {
        legend: {
            position: 'top'
        }
    }
}
        });

       // Функция для отображения модального окна со списком студентов
function showStudentsModal(filteredStudents, categoryTitle) {
    document.getElementById('modalTitle').textContent = categoryTitle;
    
    const modalTableBody = document.getElementById('modalTableBody');
    modalTableBody.innerHTML = '';
    
    filteredStudents.forEach(student => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${student.id}</td>
            <td>${student.name}</td>
            <td>${student.group_name}</td>
        `;
        modalTableBody.appendChild(row);
    });
    
    document.getElementById('studentsModal').style.display = 'block';
}

// Закрытие модального окна
document.querySelector('.close-modal').addEventListener('click', function() {
    document.getElementById('studentsModal').style.display = 'none';
});

// Закрытие модального окна при клике вне его области
window.addEventListener('click', function(event) {
    const modal = document.getElementById('studentsModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

studentsChart.canvas.addEventListener('click', function(event) {
    const points = studentsChart.getElementsAtEventForMode(event, 'nearest', {intersect: true}, true);
    console.log(points); // Посмотреть, какие данные приходят при клике
    if (points.length) {
        const index = points[0].index;
        const datasetIndex = points[0].datasetIndex;
        console.log(index, datasetIndex); // Отладка: проверяем индекс и datasetIndex
        
        let filteredStudents = [];
        let categoryTitle = '';

        // В зависимости от выбранной категории, фильтруем студентов
        if (datasetIndex === 0) { // Всего учащихся
            categoryTitle = 'Всего учащихся';
            filteredStudents = students;
        } else if (datasetIndex === 1) { // БРСМ
            categoryTitle = 'БРСМ';
            filteredStudents = students.filter(s => s.brsm == 1);
        } else if (datasetIndex === 2) { // Волонтеры
            categoryTitle = 'Волонтеры';
            filteredStudents = students.filter(s => s.volunteer == 1);
        } else if (datasetIndex === 3) { // Не состоят нигде
            categoryTitle = 'Не состоят нигде';
            filteredStudents = students.filter(s => s.brsm == 0 && s.volunteer == 0);
        }

        // Показываем модальное окно с фильтрованными студентами
        showStudentsModal(filteredStudents, categoryTitle);
    }
});


    </script>
</body>
</html>