<?php
include('config.php');
include('functions.php');
session_start(); // Начинаем сессию
// После session_start()
if (!function_exists('setNotification')) {
    function setNotification($message, $type = 'success') {
        $_SESSION['notification'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}
// Проверка на авторизованного пользователя
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Перенаправляем на страницу входа, если пользователь не авторизован
    exit();
}

$sql = "CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    achievement_date DATE NOT NULL,
    achievement TEXT NOT NULL,
    achievement_type ENUM('Достижения в общественной жизни', 
                         'Достижения в спорте', 
                         'Достижения в творческой деятельности', 
                         'Достижения в исследовательской деятельности',
                         'Достижения в конкурсах профессионального мастерства и технического творчества',
                         'Другие достижения') NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
$pdo->exec($sql);

// Обработка добавления нового достижения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_achievement'])) {
        // Обработка редактирования
        $id = $_POST['achievement_id'];
        $student_id = $_POST['student_id'];
        $achievement_date = $_POST['achievement_date'];
        $achievement = $_POST['achievement'];
        $achievement_type = $_POST['achievement_type'];

        try {
            // Получаем имя студента для уведомления
            $stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $student_name = $stmt->fetchColumn();

            $updateQuery = "UPDATE achievements SET 
                           student_id = ?, 
                           achievement_date = ?, 
                           achievement = ?, 
                           achievement_type = ? 
                           WHERE id = ?";
            $stmt = $pdo->prepare($updateQuery);
            $stmt->execute([$student_id, $achievement_date, $achievement, $achievement_type, $id]);
            
            setNotification("Достижение студента \"$student_name\" успешно обновлено", 'success');
            addAction($pdo, $_SESSION['user_id'], "Добавлено достижение для студента \"$student_name\"");
            header("Location: achievements.php");
            exit;
        } catch (PDOException $e) {
            setNotification("Ошибка при обновлении достижения: " . $e->getMessage(), 'error');
            header("Location: achievements.php");
            exit;
        }
    } else {
        // Обработка добавления
        $student_id = $_POST['student_id'];
        $achievement_date = $_POST['achievement_date'];
        $achievement = $_POST['achievement'];
        $achievement_type = $_POST['achievement_type'];

        try {
            // Получаем имя студента для уведомления
            $stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $student_name = $stmt->fetchColumn();

            $insertQuery = "INSERT INTO achievements (student_id, achievement_date, achievement, achievement_type) 
                           VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($insertQuery);
            $stmt->execute([$student_id, $achievement_date, $achievement, $achievement_type]);
            
            setNotification("Достижение для студента \"$student_name\" успешно добавлено", 'success');
            header("Location: achievements.php");
            exit;
        } catch (PDOException $e) {
            setNotification("Ошибка при добавлении достижения: " . $e->getMessage(), 'error');
            header("Location: achievements.php");
            exit;
        }
    }
}

// Получение списка студентов
$studentsQuery = "SELECT id, name FROM students";
$studentsStmt = $pdo->prepare($studentsQuery);
$studentsStmt->execute();
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Получение списка достижений
$query = "SELECT students.id, students.name, students.group_name, 
                 achievements.id as achievement_id, 
                 achievements.achievement_date, 
                 achievements.achievement, 
                 achievements.achievement_type
          FROM achievements
          JOIN students ON achievements.student_id = students.id
          ORDER BY achievements.achievement_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Достижения студентов</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="index.css">
    <link rel="icon" href="logo2.png" type="image/png">
    
    <style>
        body, html {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f4f7fc;
        }
        
        .wrapper {
            display: flex;
            height: 100vh;
        }

        .content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
/* Стили для уведомлений */
.notification {
    position: fixed;
    bottom: 20px;
    left: 20px;
    background-color: white;
    border-radius: 10px;
    padding: 15px;
    min-width: 300px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.5s ease;
    z-index: 1050;
}

.notification.show {
    transform: translateY(0);
    opacity: 1;
}

.notification-success {
    border-left: 4px solid #28a745;
}

.notification-error {
    border-left: 4px solid #dc3545;
}

.notification-info {
    border-left: 4px solid #17a2b8;
}

.notification-icon {
    margin-right: 15px;
    font-size: 1.5rem;
}

.notification-success .notification-icon {
    color: #28a745;
}

.notification-error .notification-icon {
    color: #dc3545;
}

.notification-info .notification-icon {
    color: #17a2b8;
}

.notification-message {
    font-size: 14px;
}
        .fixed-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: #f4f7fc;
            padding: 20px 20px 0 20px;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0 20px;
        }
     
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .date-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .date-text, .time-text {
            color: #64748b;
        }
        .table {
            table-layout: fixed; /* Фиксированная ширина столбцов */
        }
        
        .search-container input {
            padding: 0.75rem 1rem;
            width: 400px;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
        }

        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 180px);
            overflow-y: auto;
            margin: 0 20px 20px 20px;
            flex-grow: 1;
        }

        .achievements-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin: 0 20px 1rem 20px;
            gap: 0.8rem;
        }

        .achievements-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        /* Стили для ячейки с достижением */
        .achievement-cell {
            max-width: 300px; /* Максимальная ширина */
            white-space: pre-wrap; /* Сохраняет переносы строк */
            word-wrap: break-word; /* Перенос длинных слов */
            min-width: 200px; /* Минимальная ширина */
        }

        /* Задаем ширину для остальных столбцов */
        .table th:nth-child(1) { width: 20%; } /* ФИО */
        .table th:nth-child(2) { width: 10%; } /* Группа */
        .table th:nth-child(3) { width: 10%; } /* Дата */
        .table th:nth-child(4),
        .table td:nth-child(4) {
            text-align: center;
            vertical-align: middle;
        }

        .table th:nth-child(5) { width: 35%; } /* Достижение */
        .table th:nth-child(6) { width: 10%; } /* Действия */
        
        .form-control[name="achievement"], 
        .form-control[name="edit_achievement"] {
            min-height: 100px; /* Минимальная высота */
            resize: vertical; /* Разрешаем вертикальное изменение размера */
        }

        .table th {
            background-color: #f1f3f9;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Обновленный стиль для бейджа типа достижения */
        .achievement-type-badge {
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            max-width: 100%;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.2;
        }

        .type-public { background-color: #e3f2fd; color: #1976d2; }
        .type-sport { background-color: #e8f5e9; color: #2e7d32; }
        .type-creative { background-color: #fff3e0; color: #f57c00; }
        .type-research { background-color: #f3e5f5; color: #7b1fa2; }
        .type-professional { background-color: #e8eaf6; color: #3f51b5; }
        .type-other { background-color: #eeeeee; color: #616161; }

        .btn-add {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            color: white;
            padding: 0.625rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .btn-add:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
        }

        .btn-edit {
            color: #0d6efd;
            background-color: #e6f0ff;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .btn-edit:hover {
            background-color: #cce0ff;
            color: #0a58ca;
        }

        .btn-delete {
            color: #dc2626;
            background-color: #fee2e2;
            border: none;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .btn-delete:hover {
            background-color: #fecaca;
            color: #b91c1c;
        }

        .table-container::-webkit-scrollbar {
            width: 6px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .modal-content {
            border-radius: 1rem;
        }

        .modal-header {
            border-bottom: 2px solid #f3f4f6;
            padding: 1.25rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.625rem;
            border: 1px solid #e5e7eb;
        }

        .form-control:focus, .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>

    <div class="content">
        <div class="fixed-header">
            <header class="top-header">
                <div class="user-info">
                    <i class='bx bx-user'></i>
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span> <!-- Имя пользователя из сессии -->
                </div>
                <div class="date-container">
                    <i class='bx bx-calendar'></i>
                    <span class="date-text"><?php echo date('Y-m-d'); ?></span>
                    <span class="time-text"><?php echo date('H:i:s'); ?></span>
                </div>
                <div class="search-container">
                    <input type="text" class="search-bar" placeholder="Поиск по достижениям...">
                </div>
            </header>

            <div class="achievements-header">
                <h2>Достижения учащихся</h2>
                <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                    <i class='bx bx-plus-circle me-1'></i> Добавить достижение
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>Дата</th>
                        <th>Тип достижения</th>
                        <th>Достижение</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($achievements as $achv): ?>
                        <?php 
                            $typeClass = '';
                            switch ($achv['achievement_type']) {
                                case 'Достижения в общественной жизни':
                                    $typeClass = 'type-public';
                                    break;
                                case 'Достижения в спорте':
                                    $typeClass = 'type-sport';
                                    break;
                                case 'Достижения в творческой деятельности':
                                    $typeClass = 'type-creative';
                                    break;
                                case 'Достижения в исследовательской деятельности':
                                    $typeClass = 'type-research';
                                    break;
                                case 'Достижения в конкурсах профессионального мастерства и технического творчества':
                                    $typeClass = 'type-professional';
                                    break;
                                default:
                                    $typeClass = 'type-other';
                            }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($achv['name']) ?></td>
                            <td><?= htmlspecialchars($achv['group_name']) ?></td>
                            <td><?= date('d.m.Y', strtotime($achv['achievement_date'])) ?></td>
                            <td class="achievement-type-td">
                                <span class="achievement-type-badge <?= $typeClass ?>">
                                    <?= htmlspecialchars($achv['achievement_type']) ?>
                                </span>
                            </td>
                            <td class="achievement-cell"><?= nl2br(htmlspecialchars($achv['achievement'])) ?></td>
                            <td>
                                <button class="btn btn-edit me-2" onclick="fillEditForm(<?= 
                                    htmlspecialchars(json_encode([
                                        'id' => $achv['achievement_id'],
                                        'student_id' => $achv['id'],
                                        'name' => $achv['name'],
                                        'date' => $achv['achievement_date'],
                                        'type' => $achv['achievement_type'],
                                        'achievement' => $achv['achievement']
                                    ])) 
                                ?>)" data-bs-toggle="modal" data-bs-target="#editAchievementModal">
                                    <i class='bx bx-edit-alt'></i>
                                </button>
                                <button class="btn btn-delete" onclick="if(confirm('Вы уверены, что хотите удалить это достижение?')) window.location.href='db_operations.php?delete_achievement=<?= $achv['achievement_id'] ?>'">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Модальное окно добавления -->
    <div class="modal fade" id="addAchievementModal" tabindex="-1" aria-labelledby="addAchievementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAchievementModalLabel">Добавить достижение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="achievements.php">
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Студент</label>
                            <select class="form-select" id="student_id" name="student_id" required>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="achievement_date" class="form-label">Дата</label>
                            <input type="date" class="form-control" id="achievement_date" name="achievement_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="achievement_type" class="form-label">Тип достижения</label>
                            <select class="form-select" id="achievement_type" name="achievement_type" required>
                                <option value="Достижения в общественной жизни">Достижения в общественной жизни</option>
                                <option value="Достижения в спорте">Достижения в спорте</option>
                                <option value="Достижения в творческой деятельности">Достижения в творческой деятельности</option>
                                <option value="Достижения в исследовательской деятельности">Достижения в исследовательской деятельности</option>
                                <option value="Достижения в конкурсах профессионального мастерства и технического творчества">Достижения в конкурсах профессионального мастерства</option>
                                <option value="Другие достижения">Другие достижения</option>
                            </select>
                                </div>
                              
                        <div class="mb-3">
                            <label for="edit_achievement" class="form-label">Достижение</label>
                            <textarea class="form-control" id="edit_achievement" name="achievement" rows="3" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-add">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Функция для отображения уведомлений
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-icon">
            ${type === 'success' ? '<i class="bx bx-check"></i>' : 
             type === 'error' ? '<i class="bx bx-x"></i>' : 
             '<i class="bx bx-info-circle"></i>'}
        </div>
        <div class="notification-message">${message}</div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 5000);
}

<?php
// Проверяем, есть ли уведомление в сессии
if (isset($_SESSION['notification'])) {
    $notification = $_SESSION['notification'];
    echo "document.addEventListener('DOMContentLoaded', function() {
        showNotification('" . addslashes($notification['message']) . "', '" . $notification['type'] . "');
    });";
    
    // Удаляем уведомление из сессии после отображения
    unset($_SESSION['notification']);
}
?>
        // Функция для заполнения формы редактирования
        function fillEditForm(data) {
            document.getElementById('edit_achievement_id').value = data.id;
            document.getElementById('edit_student_id').value = data.student_id;
            document.getElementById('edit_achievement_date').value = data.date;
            document.getElementById('edit_achievement_type').value = data.type;
            document.getElementById('edit_achievement').value = data.achievement;
        }

        // Обновление времени
        function updateTime() {
            const now = new Date();
            const dateText = now.toLocaleDateString('ru-RU');
            const timeText = now.toLocaleTimeString('ru-RU');
            document.querySelector('.date-text').textContent = dateText;
            document.querySelector('.time-text').textContent = timeText;
        }
        
        // Обновляем время каждую секунду
        setInterval(updateTime, 1000);

        // Инициализация поиска
        document.querySelector('.search-bar').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Установка текущей даты в поле даты при открытии модального окна добавления
        document.getElementById('addAchievementModal').addEventListener('show.bs.modal', function () {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('achievement_date').value = today;
        });
    </script>

</body>
</html>