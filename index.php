<?php

include('config.php');
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    // Если пользователь не авторизован, перенаправляем на страницу входа с ошибкой
    header('Location: login.php?error=not_authorized');
    exit;
}
$sql = "SELECT students.id, students.name, students.group_name, students.brsm, students.volunteer, COUNT(achievements.id) AS achievement_count 
        FROM students 
        LEFT JOIN achievements ON students.id = achievements.student_id 
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
                    <h3 class="card-title">Достижения</h3>
                    <div class="chart-container">
                        <canvas id="achievementsChart"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <h3 class="card-title">Учащиеся</h3>
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
                            <td>
                                <span class="status-badge <?= $student['brsm'] ? 'status-yes' : 'status-no' ?>">
                                    <?= $student['brsm'] ? 'Состоит' : 'Нет' ?>
                                </span>
                            </td>
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

    <script>
// Add modal HTML after your table container
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

// Chart configuration
const studentsCtx = document.getElementById('studentsChart').getContext('2d');
// Create gradient for non-affiliated students
const nonAffiliatedGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
nonAffiliatedGradient.addColorStop(0, 'rgba(156, 163, 175, 0.8)'); // Серый
nonAffiliatedGradient.addColorStop(1, 'rgba(156, 163, 175, 0.2)');  // Серый

// Get student counts for non-affiliated students
const nonAffiliatedStudents = <?= count(array_filter($students, function($s) { return $s['brsm'] == 0 && $s['volunteer'] == 0; })) ?>;

// Create gradients
const totalGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
totalGradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
totalGradient.addColorStop(1, 'rgba(59, 130, 246, 0.2)');

const brsmGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
brsmGradient.addColorStop(0, 'rgba(139, 92, 246, 0.8)');
brsmGradient.addColorStop(1, 'rgba(139, 92, 246, 0.2)');

const volunteerGradient = studentsCtx.createLinearGradient(0, 0, 0, 200);
volunteerGradient.addColorStop(0, 'rgba(236, 72, 153, 0.8)');
volunteerGradient.addColorStop(1, 'rgba(236, 72, 153, 0.2)');

// Get student counts
const totalStudents = <?= count($students) ?>;
const brsmStudents = <?= count(array_filter($students, function($s) { return $s['brsm'] == 1; })) ?>;
const volunteerStudents = <?= count(array_filter($students, function($s) { return $s['volunteer'] == 1; })) ?>;

// Store students data for modal
const studentsData = <?= json_encode($students) ?>;

// Create chart
const studentsChart = new Chart(studentsCtx, {
    type: 'bar',
    data: {
        labels: ['Статистика учащихся'],
        datasets: [
            {
                label: 'Всего учащихся',
                data: [totalStudents],
                backgroundColor: totalGradient,
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                borderRadius: 5,
            },
            {
                label: 'БРСМ',
                data: [brsmStudents],
                backgroundColor: brsmGradient,
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 2,
                borderRadius: 5,
            },
            {
                label: 'Волонтеры',
                data: [volunteerStudents],
                backgroundColor: volunteerGradient,
                borderColor: 'rgba(236, 72, 153, 1)',
                borderWidth: 2,
                borderRadius: 5,
            },
            {
                label: 'Не состоят нигде',
                data: [nonAffiliatedStudents],
                backgroundColor: nonAffiliatedGradient,
                borderColor: 'rgba(156, 163, 175, 1)',
                borderWidth: 2,
                borderRadius: 5,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    display: true,
                    drawBorder: false,
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        },
        onClick: (event, elements) => {
            if (elements.length > 0) {
                const datasetIndex = elements[0].datasetIndex;
                showStudentsModal(datasetIndex);
            }
        }
    }
});

// Modal functionality
function showStudentsModal(datasetIndex) {
    const modal = document.getElementById('studentsModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalTableBody = document.getElementById('modalTableBody');
    let filteredStudents = [];
    
    switch(datasetIndex) {
        case 0: // Все учащиеся
            modalTitle.textContent = 'Все учащиеся';
            filteredStudents = studentsData;
            break;
        case 1: // БРСМ
            modalTitle.textContent = 'Учащиеся в БРСМ';
            filteredStudents = studentsData.filter(student => student.brsm == 1);
            break;
        case 2: // Волонтеры
            modalTitle.textContent = 'Учащиеся-волонтеры';
            filteredStudents = studentsData.filter(student => student.volunteer == 1);
            break;
            case 3: // Не состоят нигде
            modalTitle.textContent = 'Учащиеся, не состоящие ни в БРСМ, ни в волонтерах';
            filteredStudents = studentsData.filter(student => student.brsm == 0 && student.volunteer == 0);
            break;
    }

    // Generate table content
    modalTableBody.innerHTML = filteredStudents.map(student => `
        <tr>
            <td>${student.id}</td>
            <td>${student.name}</td>
            <td>${student.group_name}</td>
            <td>${student.achievement_count}</td>
        </tr>
    `).join('');

    modal.style.display = 'block';
}

// Close modal functionality
document.querySelector('.close-modal').addEventListener('click', () => {
    document.getElementById('studentsModal').style.display = 'none';
});

window.addEventListener('click', (event) => {
    const modal = document.getElementById('studentsModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});
    </script>
</body>
</html>