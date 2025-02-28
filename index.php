<?php
include('config.php');
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    // Если пользователь не авторизован, перенаправляем на страницу входа с ошибкой
    header('Location: login.php?error=not_authorized');
    exit;
}

// Обновленный SQL-запрос с JOIN с таблицей brsm
$sql = "SELECT students.id, students.name, students.group_name, 
               (CASE WHEN brsm.student_id IS NOT NULL THEN 1 ELSE 0 END) AS brsm,
               students.volunteer, COUNT(achievements.id) AS achievement_count
        FROM students 
        LEFT JOIN achievements ON students.id = achievements.student_id 
        LEFT JOIN brsm ON students.id = brsm.student_id  -- Используем таблицу brsm
        GROUP BY students.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$achievementsData = [];
$studentsData = [];
foreach ($students as $student) { 
    $achievementsData[] = $student['achievement_count'];
    $studentsData[] = $student['id'];
}
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
</head>

<body>
    <div class="container">
    <?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->
     
        <main class="main-content">
            <header class="top-header">
                <div class="date-container">
                    <i class='bx bx-calendar'></i>
                    <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                    <span class="time-text"><?php echo date('H:i'); ?></span>
                </div>
            </header>

            <div class="cards-grid">
                <div class="card achievement-card">
                    <h2 class="card-title">Достижения</h2>
                    <div class="chart-container">
                        <canvas id="achievementsChart"></canvas>
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
                            <th>Достижения</th>
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
                            <td><?= htmlspecialchars($student['achievement_count']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Модальное окно будет добавлено через JavaScript -->

    <script>
        // Добавляем модальное окно в HTML после контейнера таблицы
        document.querySelector('.table-container').insertAdjacentHTML('afterend', `
        <div id="studentsModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
            <div class="modal-content" style="position: relative; background: white; margin: 10% auto; padding: 20px; width: 80%; max-width: 800px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <span class="close-modal" style="position: absolute; right: 20px; top: 20px; font-size: 24px; cursor: pointer; color: #666;">&times;</span>
                <h2 id="modalTitle" style="margin-bottom: 20px; color: #333;"></h2>
                <div id="modalContent" style="max-height: 400px; overflow-y: auto;">
                    <table class="table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Группа</th>
                                <th>Достижения</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        `);

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
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                onClick: (event, elements) => {
                    if (elements.length === 0) return;
                    
                    const index = elements[0].datasetIndex;
                    let filteredStudents = [];
                    let categoryTitle = '';
                    
                    // Фильтруем студентов в зависимости от выбранной категории
                    switch(index) {
                        case 0: // Всего учащихся
                            filteredStudents = students;
                            categoryTitle = 'Все учащиеся';
                            break;
                        case 1: // БРСМ
                            filteredStudents = students.filter(s => s.brsm == 1);
                            categoryTitle = 'Члены БРСМ';
                            break;
                        case 2: // Волонтеры
                            filteredStudents = students.filter(s => s.volunteer == 1);
                            categoryTitle = 'Волонтеры';
                            break;
                        case 3: // Не состоят нигде
                            filteredStudents = students.filter(s => s.brsm == 0 && s.volunteer == 0);
                            categoryTitle = 'Не состоят в организациях';
                            break;
                    }
                    
                    // Отображаем модальное окно со списком студентов
                    showStudentsModal(filteredStudents, categoryTitle);
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
                    <td>${student.achievement_count}</td>
                `;
                modalTableBody.appendChild(row);
            });
            
            document.getElementById('studentsModal').style.display = 'block';
        }

        // Функция для отображения детальной информации о студенте
        function showStudentDetails(student) {
            document.getElementById('modalTitle').textContent = `${student.name} - Подробная информация`;
            
            const modalTableBody = document.getElementById('modalTableBody');
            modalTableBody.innerHTML = `
                <tr>
                    <td>${student.id}</td>
                    <td>${student.name}</td>
                    <td>${student.group_name}</td>
                    <td>${student.achievement_count}</td>
                </tr>
            `;
            
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
    </script>
</body>
</html>