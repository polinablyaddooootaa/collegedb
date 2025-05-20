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
            addAction($pdo, $_SESSION['user_id'], "Обновлено достижение для студента \"$student_name\"");
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
 
    <link rel="stylesheet" href="style.css"> <!-- Подключение стилей -->
    <link rel="icon" href="logo2.png" type="image/png">
    
    <style>
      
       
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
                <button class="btn-add btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
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
                            <label for="achievement" class="form-label">Достижение</label>
                            <textarea class="form-control" id="achievement" name="achievement" rows="3" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-modal btn-submit">Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div class="modal fade" id="editAchievementModal" tabindex="-1" aria-labelledby="editAchievementModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAchievementModalLabel">Редактировать достижение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="achievements.php">
                        <input type="hidden" name="achievement_id" id="edit_achievement_id">
                        <div class="mb-3">
                            <label for="edit_student_id" class="form-label">Студент</label>
                            <select class="form-select" id="edit_student_id" name="student_id" required>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_achievement_date" class="form-label">Дата</label>
                            <input type="date" class="form-control" id="edit_achievement_date" name="achievement_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_achievement_type" class="form-label">Тип достижения</label>
                            <select class="form-select" id="edit_achievement_type" name="achievement_type" required>
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
                        <input type="hidden" name="edit_achievement" value="1">
                        <div class="text-end">
                            <button type="submit" class="btn btn-modal btn-submit">Сохранить изменения</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
    <script>
        // Функция для заполнения формы редактирования
        function fillEditForm(data) {
            document.getElementById('edit_achievement_id').value = data.id;
            document.getElementById('edit_student_id').value = data.student_id;
            document.getElementById('edit_achievement_date').value = data.date;
            document.getElementById('edit_achievement_type').value = data.type;
            document.getElementById('edit_achievement').value = data.achievement;
        }

        // Функция отображения уведомлений (предполагается, что она определена в achievements.js)
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = 'notification ' + (type === 'success' ? 'notification-success' : 'notification-error');
            notification.innerHTML = `
                <i class='bx bx-${type === 'success' ? 'check-circle' : 'x-circle'} notification-icon'></i>
                <span class="notification-message">${message}</span>
            `;
            document.body.appendChild(notification);

            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }
    </script>
    <script src="js/achievements.js"></script>
</body>
</html>