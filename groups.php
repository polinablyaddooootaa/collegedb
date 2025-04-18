<?php
session_start();
include('config.php');
include('functions.php');
if (isset($_GET['view_group']) && !empty($_GET['view_group'])) {
    $group_name = $_GET['view_group'];

    // Получаем данные о группе
    $group_sql = "SELECT * FROM `groups` WHERE group_name = ?";
    $group_stmt = $pdo->prepare($group_sql);
    $group_stmt->execute([$group_name]);
    $selected_group = $group_stmt->fetch(PDO::FETCH_ASSOC);

    if ($selected_group) {
        // Получаем студентов группы
        $students_sql = "SELECT s.*, 
                        (SELECT COALESCE(COUNT(*), 0) FROM brsm WHERE student_id = s.id) as brsm_status,
                        (SELECT COALESCE(COUNT(*), 0) FROM volunteers WHERE student_id = s.id) as volunteer_status
                        FROM students s 
                        WHERE s.group_id = ?
                        ORDER BY s.name";
        $students_stmt = $pdo->prepare($students_sql);
        $students_stmt->execute([$selected_group['id']]);
        $group_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        setNotification("Группа не найдена.", "error");
        header("Location: groups.php");
        exit;
    }
}
try {
    // Проверка, если выбран конкретный студент для просмотра
    if (isset($_GET['view_student']) && !empty($_GET['view_student'])) {
        $student_id = $_GET['view_student'];

        // Получение информации о студенте
        $student_sql = "SELECT s.*, g.group_name 
                        FROM students s
                        LEFT JOIN `groups` g ON s.group_id = g.id
                        WHERE s.id = ?";
        $student_stmt = $pdo->prepare($student_sql);
        $student_stmt->execute([$student_id]);
        $student = $student_stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Получение уведомлений
            $notifications_sql = "SELECT * FROM notifications 
                                 WHERE student_id = ? 
                                 ORDER BY date_sent DESC";
            $notifications_stmt = $pdo->prepare($notifications_sql);
            $notifications_stmt->execute([$student_id]);
            $notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Отображение только профиля студента
            ?>
            <!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Профиль студента</title>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
                <link rel="stylesheet" href="index.css"> <!-- Подключение стилей -->
                <style>
                    body, html {
                        margin: 0;
                        font-family: 'Inter', sans-serif;
                        background-color: #f4f7fc;
                    }
                    .container {
                        margin: 20px auto;
                        max-width: 800px;
                        background-color: white;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <a href="groups.php" class="btn btn-outline-secondary mb-4">
                        <i class='bx bx-arrow-back'></i> Назад к группам
                    </a>
                    <h2 class="mb-3">Профиль студента: <?= htmlspecialchars($student['name']) ?></h2>
                    
                 
                    
                    <!-- Уведомления -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">История уведомлений</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($notifications) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Дата отправки</th>
                                                <th>Тип</th>
                                                <th>Содержание</th>
                                                <th>Создано</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($notifications as $notification): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(date('d.m.Y', strtotime($notification['date_sent']))) ?></td>
                                                    <td>
                                                        <?php 
                                                        $type_label = '';
                                                        switch ($notification['type']) {
                                                            case 'absence':
                                                                $type_label = '<span class="badge bg-danger">Пропуски</span>';
                                                                break;
                                                            default:
                                                                $type_label = '<span class="badge bg-secondary">Другое</span>';
                                                        }
                                                        echo $type_label;
                                                        ?>
                                                    </td>
                                                    <td><?= nl2br(htmlspecialchars($notification['content'])) ?></td>
                                                    <td><?= htmlspecialchars($notification['created_by']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">Уведомлений не найдено.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit; // Прекращаем выполнение, чтобы остальной код не рендерился
        }
    }

    // Обычная логика для отображения групп
    $sql = "SELECT g.id, g.group_name, g.curator, g.course, COUNT(s.id) as student_count, sp.name as specialty_name
            FROM `groups` g 
            LEFT JOIN students s ON g.id = s.group_id 
            LEFT JOIN specialties sp ON g.specialty_id = sp.id
            GROUP BY g.id
            ORDER BY g.group_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка выполнения запроса: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Группы</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="index.css"> <!-- Подключение стилей -->
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
        /* Стили для основного контента */
        .content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
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
        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f1f3f9;
            text-transform: uppercase;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        .status-yes {
            background-color: #d4edda;
            color: #155724;
        }
        .status-no {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-neutral {
            background-color: #fff3cd;
            color: #856404;
        }
        .btn-add {
            font-size: 1rem;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #4946e5 0%, #636ff1 100%);
            border: none;
            text-decoration: none;
            border-radius: 15px;
            margin-bottom: 20px;
            color: white;
        }
        .btn-add:hover {
            background: linear-gradient(135deg, #636ff1 0%, #4946e5 100%);
        }
        .modal-content {
            border-radius: 1rem;
            background-color: #ffffff;
            border: none;
        }

        .modal-header {
            border-bottom: 2px solid #f3f4f6;
            padding: 1.25rem;
            background-color: #f9fafb;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-control, .form-select {
            border-radius: 0.5rem;
            padding: 0.625rem;
            border: 1px solid #e5e7eb;
            background-color: #fafafa;
        }

        .form-control:focus, .form-select:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
        }

        .btn-primary {
            background-color: #6366f1;
            border-color: #6366f1;
            font-weight: bold;
            border-radius: 0.5rem;
            padding: 0.75rem;
            transition: background-color 0.3s, transform 0.3s;
        }

        .btn-close {
            background-color: transparent;
            border: none;
            font-size: 1.25rem;
            color: #6366f1;
            transition: color 0.3s;
        }

        .btn-close:hover {
            color: #4f46e5;
        }
        
        .back-button {
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .group-name {
            cursor: pointer;
            color: #4946e5;
            text-decoration: underline;
        }
        
        .group-name:hover {
            color: #636ff1;
        }
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>

    <!-- Основной контент -->
    <div class="content">
        <!-- Верхний заголовок -->
        <header class="top-header">
            <div class="user-info">
                <i class='bx bx-user'></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="date-container">
                <i class='bx bx-calendar'></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Поиск...">
            </div>
        </header>
   <!-- Значок настроек -->
<div class="settings-icon">
    <i class='bx bx-cog' data-bs-toggle="modal" data-bs-target="#settingsModal"></i>
</div>

<!-- Модальное окно настроек -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel">Настройки</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="action" value="add_specialty">
                    <div class="mb-3">
                        <label class="form-label">Название специальности</label>
                        <input type="text" class="form-control" name="specialty_name" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Добавить специальность</button>
                </form>
                <hr>
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="action" value="add_subject">
                    <div class="mb-3">
                        <label class="form-label">Название учебного предмета</label>
                        <input type="text" class="form-control" name="subject_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Специальность</label>
                        <select class="form-select" name="specialty_id" required>
                            <?php
                            $specialties = $pdo->query("SELECT * FROM specialties")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($specialties as $specialty) {
                                echo "<option value=\"" . htmlspecialchars($specialty['id']) . "\">" . htmlspecialchars($specialty['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Добавить учебный предмет</button>
                </form>
            </div>
        </div>
    </div>
</div>

        <?php if ($selected_group): ?>
            <!-- Отображение студентов выбранной группы -->
            <div class="table-container">
                <a href="groups.php" class="btn btn-outline-secondary back-button">
                    <i class='bx bx-arrow-back'></i> Назад к списку групп
                </a>
                <h2 class="mb-3">Студенты группы <?= htmlspecialchars($selected_group['group_name']) ?></h2>
                <p>Куратор: <?= htmlspecialchars($selected_group['curator']) ?></p>
                
                <?php if (count($group_students) > 0): ?>
                    <table class="table table-hover">
                        <thead>
                        <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>БРСМ</th>
                                <th>Волонтер</th>
                                <th>Действия</th>
                            </tr>
<!-- В цикле foreach для вывода данных -->
<td><?= htmlspecialchars($group['course']) ?></td>
                        </thead>
                        <tbody>
    <?php foreach ($group_students as $student): ?>
        <tr>
            <td><?= htmlspecialchars($student['id']) ?></td>
            <td><?= htmlspecialchars($student['name']) ?></td>
            <td>
                <span class="status-badge <?= $student['brsm_status'] > 0 ? 'status-yes' : 'status-no' ?>">
                    <?= $student['brsm_status'] > 0 ? 'Состоит' : 'Не состоит' ?>
                </span>
            </td>
            <td>
                <span class="status-badge <?= $student['volunteer_status'] > 0 ? 'status-yes' : 'status-neutral' ?>">
                    <?= $student['volunteer_status'] > 0 ? 'Активен' : 'Не участвует' ?>
                </span>
            </td>
            <td>
                <a href="groups.php?view_student=<?= $student['id'] ?>" class="btn btn-outline-primary btn-sm">
                    <i class='bx bx-user-circle'></i> Профиль
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">В этой группе пока нет студентов.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Список групп -->
            <div class="table-container">
                <h2 class="mb-3">Группы</h2>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                    <i class='bx bx-plus-circle me-1'></i> Добавить группу
                </button>
                
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название группы</th>
                            <th>Куратор</th>
                            <th>Специальность</th>
                            <th>Кол-во студентов</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                            <tr>
                                <td><?= htmlspecialchars($group['id']) ?></td>
                                <td>
                                    <a class="group-name" href="groups.php?view_group=<?= urlencode($group['group_name']) ?>">
                                        <?= htmlspecialchars($group['group_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($group['curator']) ?></td>
                                <td><?= htmlspecialchars($group['specialty_name']) ?></td>
                                <td><?= htmlspecialchars($group['student_count']) ?></td>
                                <td>
                                <button class="btn btn-outline-primary btn-sm" 
        data-bs-toggle="modal" 
        data-bs-target="#editGroupModal"
        data-id="<?= $group['id'] ?>"
        data-name="<?= htmlspecialchars($group['group_name']) ?>"
        data-curator="<?= htmlspecialchars($group['curator']) ?>"
        data-course="<?= htmlspecialchars($group['course']) ?>"
        data-specialty="<?= htmlspecialchars($group['specialty_name']) ?>">
    <i class='bx bx-edit-alt'></i>
</button>
                                    <a class="btn btn-outline-danger btn-sm" href="groups.php?delete_group=<?= $group['id'] ?>" 
                                    onclick="return confirm('Вы уверены, что хотите удалить группу?')">
                                        <i class='bx bx-trash'></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<!-- Модальное окно добавления группы -->
<div class="modal fade" id="addGroupModal" tabindex="-1" aria-labelledby="addGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGroupModalLabel">Добавить группу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="action" value="add_group">
                    <div class="mb-3">
                        <label class="form-label">Название группы</label>
                        <input type="text" class="form-control" name="group_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Куратор</label>
                        <input type="text" class="form-control" name="curator" required>
                    </div>
                    <div class="mb-3">
    <label class="form-label">Курс</label>
    <select class="form-select" name="course" required>
        <option value="1">1 курс</option>
        <option value="2">2 курс</option>
        <option value="3">3 курс</option>
        <option value="4">4 курс</option>
    </select>
</div>
                    <div class="mb-3">
                        <label class="form-label">Специальность</label>
                        <select class="form-select" name="specialty_id" required>
                            <?php
                            $specialties = $pdo->query("SELECT * FROM specialties")->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($specialties as $specialty) {
                                echo "<option value=\"" . htmlspecialchars($specialty['id']) . "\">" . htmlspecialchars($specialty['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования группы -->
<div class="modal fade" id="editGroupModal" tabindex="-1" aria-labelledby="editGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGroupModalLabel">Редактировать группу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="action" value="edit_group">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label class="form-label">Название группы</label>
                        <input type="text" class="form-control" id="edit_group_name" name="group_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Куратор</label>
                        <input type="text" class="form-control" id="edit_curator" name="curator" required>
                    </div>
                    <div class="mb-3">
    <label class="form-label">Курс</label>
    <select class="form-select" id="edit_course" name="course" required>
        <option value="1">1 курс</option>
        <option value="2">2 курс</option>
        <option value="3">3 курс</option>
        <option value="4">4 курс</option>
    </select>
</div>
                    <div class="mb-3">
                        <label class="form-label">Специальность</label>
                        <select class="form-select" id="edit_specialty_id" name="specialty_id" required>
                            <?php
                            foreach ($specialties as $specialty) {
                                echo "<option value=\"" . htmlspecialchars($specialty['id']) . "\">" . htmlspecialchars($specialty['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Функция для отображения уведомлений
    function showNotification(message, type = 'success') {
        // Create notification element
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
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 500); // Wait for fade out animation to complete
        }, 5000);
    }

    // Поиск по таблице
    document.querySelector('.search-bar').addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });

    // Заполнение формы редактирования
// Заполнение формы редактирования
document.querySelectorAll('.btn-outline-primary').forEach(button => {
    button.addEventListener('click', function() {
        if (this.dataset.id) {
            const groupId = this.dataset.id;
            const groupName = this.dataset.name;
            const curator = this.dataset.curator;
            const course = this.dataset.course; // Добавить получение курса

            document.getElementById('edit_id').value = groupId;
            document.getElementById('edit_group_name').value = groupName;
            document.getElementById('edit_curator').value = curator;
            document.getElementById('edit_course').value = course; // Установить значение курса
        }
    });
});
 // Отображение уведомлений из сессии
 <?php
           if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            echo "document.addEventListener('DOMContentLoaded', function() {
                showNotification('" . addslashes($notification['message']) . "', '" . $notification['type'] . "');
            });";
            
            // Удаляем уведомление из сессии после отображения
            unset($_SESSION['notification']);
        }
    ?>
</script>

</body>
</html>