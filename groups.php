<?php
$selected_group = null;

session_start();
include('config.php');
include('db_operations.php');

// Убедимся, что PDO выбрасывает исключения
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Проверка просмотра группы
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

    // Обработка удаления группы
    if (isset($_GET['delete_group']) && !empty($_GET['delete_group'])) {
        $group_id = filter_var($_GET['delete_group'], FILTER_VALIDATE_INT);

        if ($group_id === false) {
            setNotification("Неверный ID группы.", "error");
            header("Location: groups.php");
            exit;
        }

        // Проверяем, есть ли студенты в группе
        $check_students_sql = "SELECT COUNT(*) FROM students WHERE group_id = ?";
        $check_students_stmt = $pdo->prepare($check_students_sql);
        $check_students_stmt->execute([$group_id]);
        $student_count = $check_students_stmt->fetchColumn();

        if ($student_count > 0) {
            setNotification("Нельзя удалить группу, так как в ней есть студенты.", "error");
            header("Location: groups.php");
            exit;
        }

        // Удаляем группу
        $delete_sql = "DELETE FROM `groups` WHERE id = ?";
        $delete_stmt = $pdo->prepare($delete_sql);
        $delete_stmt->execute([$group_id]);

        setNotification("Группа успешно удалена.", "success");
        header("Location: groups.php");
        exit;
        }

    // Проверка просмотра студента
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

            // Отображение профиля студента
            ?>
            <!DOCTYPE html>
            <html lang="ru">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Профиль студента</title>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
                <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
                <link rel="stylesheet" href="index.css">
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
                                                <th>Действие</th>
                                                <th>Создано</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($notifications as $index => $notification): ?>
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
                                                    <td>
                                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#notificationModal<?= $index ?>">
                                                            Ознакомиться подробнее
                                                        </button>
                                                    </td>
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

                <!-- Модальные окна для каждого уведомления -->
                <?php foreach ($notifications as $index => $notification): ?>
                    <div class="modal fade" id="notificationModal<?= $index ?>" tabindex="-1" aria-labelledby="notificationModalLabel<?= $index ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="notificationModalLabel<?= $index ?>">Детали уведомления</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Дата:</strong> <?= htmlspecialchars(date('d.m.Y', strtotime($notification['date_sent']))) ?></p>
                                    <p><strong>Тип:</strong> 
                                        <?php 
                                        switch ($notification['type']) {
                                            case 'absence':
                                                echo 'Пропуски';
                                                break;
                                            default:
                                                echo 'Другое';
                                        }
                                        ?>
                                    </p>
                                    <p><strong>Содержание:</strong></p>
                                    <p><?= nl2br(htmlspecialchars($notification['content'])) ?></p>
                                    <p><strong>Создано:</strong> <?= htmlspecialchars($notification['created_by']) ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </body>
            </html>
            <?php
            exit;
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
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo2.png" type="image/png">
</head>
<body>
<?php include('sidebar.php'); ?>
    <div class="content">
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
        
        <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="settingsModalLabel">Настройки</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="groups.php">
                            <input type="hidden" name="action" value="add_specialty">
                            <div class="mb-3">
                                <label class="form-label">Название специальности</label>
                                <input type="text" class="form-control" name="specialty_name" required>
                            </div>
                            <button type="submit" class="btn btn-modal btn-submit">Добавить специальность</button>
                        </form>
                        <hr>
                        <form method="POST" action="groups.php">
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
                            <button type="submit" class="btn btn-modal btn-submit">Добавить учебный предмет</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selected_group): ?>
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
            <div class="table-container">
                <h2 class="mb-3">Группы</h2>
                <button class="btn-add btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                    <i class='bx bx-plus-circle me-1'></i> Добавить группу
                </button>
                <button class="btn-add btn-primary-custom" data-bs-toggle="modal" data-bs-target="#settingsModal">
                    <i class='bx bx-plus-circle me-1'></i> Настройки групп
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

        document.querySelector('.search-bar').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        document.querySelectorAll('.btn-outline-primary').forEach(button => {
            button.addEventListener('click', function() {
                if (this.dataset.id) {
                    const groupId = this.dataset.id;
                    const groupName = this.dataset.name;
                    const curator = this.dataset.curator;
                    const course = this.dataset.course;

                    document.getElementById('edit_id').value = groupId;
                    document.getElementById('edit_group_name').value = groupName;
                    document.getElementById('edit_curator').value = curator;
                    document.getElementById('edit_course').value = course;
                }
            });
        });

        <?php
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            echo "document.addEventListener('DOMContentLoaded', function() {
                showNotification('" . addslashes($notification['message']) . "', '" . $notification['type'] . "');
            });";
            unset($_SESSION['notification']);
        }
        ?>
    </script>
</body>
</html>