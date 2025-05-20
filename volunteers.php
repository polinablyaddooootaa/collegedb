<?php
// Подключаем конфигурацию
include('config.php');
include('functions.php');

// Убедитесь, что функция существует
if (!function_exists('setNotification')) {
    function setNotification($message, $type = 'success') {
        $_SESSION['notification'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

// Запускаем сессию, если еще не запущена
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    // Получаем список студентов для выпадающего списка
    $sql_students = "SELECT id, name FROM `students` ORDER BY name";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Получаем список групп
    $sql_groups = "SELECT id, group_name FROM `groups` ORDER BY group_name";
    $stmt_groups = $pdo->prepare($sql_groups);
    $stmt_groups->execute();
    $groups = $stmt_groups->fetchAll(PDO::FETCH_ASSOC);

    // Получаем список волонтеров из базы данных
    $sql = "
        SELECT volunteers.id, students.id AS student_id, students.name, volunteers.date_joined, 
               volunteers.activity_type, volunteers.hours_volunteered, `groups`.group_name, students.group_id
        FROM `volunteers`
        INNER JOIN `students` ON volunteers.student_id = students.id
        LEFT JOIN `groups` ON students.group_id = `groups`.id
        ORDER BY volunteers.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setNotification("Ошибка базы данных: " . $e->getMessage(), 'error');
    $students = [];
    $groups = [];
    $volunteers = [];
}

// Обработка добавления нового волонтера
if (isset($_POST['add_volunteer'])) {
    try {
        $pdo->beginTransaction();
        
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $date_joined = $_POST['date_joined'];
        $activity_type = $_POST['activity_type'];
        $hours_volunteered = filter_input(INPUT_POST, 'hours_volunteered', FILTER_VALIDATE_INT);
        $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;

        if (!$student_id || !$date_joined || !$activity_type || !$hours_volunteered) {
            setNotification('Заполните все обязательные поля', 'error');
            header("Location: volunteers.php");
            exit;
        }

        // Получаем имя студента для уведомления
        $stmt = $pdo->prepare("SELECT name FROM `students` WHERE id = ?");
        $stmt->execute([$student_id]);
        $student_name = $stmt->fetchColumn();

        if (!$student_name) {
            setNotification('Студент не найден', 'error');
            header("Location: volunteers.php");
            exit;
        }

        // Добавляем запись в таблицу volunteers
        $sql = "INSERT INTO `volunteers` (student_id, date_joined, activity_type, hours_volunteered) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $date_joined, $activity_type, $hours_volunteered]);

        // Обновляем статус volunteer и group_id в таблице students
        $sql = "UPDATE `students` SET volunteer = 1, group_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_id, $student_id]);

        $pdo->commit();
        setNotification("Волонтер \"$student_name\" успешно добавлен", 'success');
        header("Location: volunteers.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setNotification('Ошибка при добавлении волонтера: ' . $e->getMessage(), 'error');
        header("Location: volunteers.php");
        exit;
    }
}

// Обработка обновления информации волонтера
if (isset($_POST['edit_volunteer'])) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $date_joined = $_POST['date_joined'];
        $activity_type = $_POST['activity_type'];
        $hours_volunteered = filter_input(INPUT_POST, 'hours_volunteered', FILTER_VALIDATE_INT);
        $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;

        if (!$id || !$student_id || !$date_joined || !$activity_type || !$hours_volunteered) {
            setNotification('Заполните все обязательные поля', 'error');
            header("Location: volunteers.php");
            exit;
        }

        // Получаем имя студента для уведомления
        $stmt = $pdo->prepare("SELECT name FROM `students` WHERE id = ?");
        $stmt->execute([$student_id]);
        $student_name = $stmt->fetchColumn();

        if (!$student_name) {
            setNotification('Студент не найден', 'error');
            header("Location: volunteers.php");
            exit;
        }

        // Обновляем информацию в таблице volunteers
        $sql = "UPDATE `volunteers` 
                SET student_id = ?, date_joined = ?, activity_type = ?, hours_volunteered = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $date_joined, $activity_type, $hours_volunteered, $id]);

        // Обновляем group_id в таблице students
        $sql = "UPDATE `students` SET group_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_id, $student_id]);

        setNotification("Информация о волонтере \"$student_name\" успешно обновлена", 'success');
        header("Location: volunteers.php");
        exit;
    } catch (Exception $e) {
        setNotification('Ошибка при обновлении информации: ' . $e->getMessage(), 'error');
        header("Location: volunteers.php");
        exit;
    }
}

// Обработка удаления волонтера
if (isset($_GET['delete_volunteer'])) {
    try {
        $pdo->beginTransaction();
        
        $id = filter_input(INPUT_GET, 'delete_volunteer', FILTER_VALIDATE_INT);
        if (!$id) {
            setNotification('Неверный ID', 'error');
            header("Location: volunteers.php");
            exit;
        }

        // Получаем информацию о волонтере перед удалением
        $sql = "SELECT v.student_id, s.name FROM `volunteers` v 
                INNER JOIN `students` s ON v.student_id = s.id 
                WHERE v.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $volunteer_info = $stmt->fetch();
        $student_id = $volunteer_info['student_id'];
        $student_name = $volunteer_info['name'];

        if (!$student_name) {
            setNotification('Волонтер не найден', 'error');
            header("Location: volunteers.php");
            exit;
        }

        // Удаляем запись из таблицы volunteers
        $sql = "DELETE FROM `volunteers` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        // Проверяем, есть ли еще записи для этого студента
        $sql = "SELECT COUNT(*) FROM `volunteers` WHERE student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        $count = $stmt->fetchColumn();

        // Если записей больше нет, обновляем статус volunteer в таблице students
        if ($count == 0) {
            $sql = "UPDATE `students` SET volunteer = 0 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
        }

        $pdo->commit();
        setNotification("Волонтер \"$student_name\" успешно удален", 'info');
        header("Location: volunteers.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        setNotification('Ошибка при удалении: ' . $e->getMessage(), 'error');
        header("Location: volunteers.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Волонтеры</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="style.css">
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
            padding: 20px;
            overflow-y: auto;
        }
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .table th {
            background-color: #f1f3f9;
            text-transform: uppercase;
        }
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
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    <div class="content">
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
                <input type="text" class="search-bar" placeholder="Поиск по достижениям...">
            </div>
        </header>
        <div class="table-responsive">
            <div class="table-container">
                <h2 class="mb-3">Список волонтеров</h2>
                <button class="btn-add btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addVolunteerModal">
                    <i class='bx bx-plus'></i> Добавить волонтера
                </button>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя студента</th>
                            <th>Группа</th>
                            <th>Дата вступления</th>
                            <th>Тип деятельности</th>
                            <th>Часы волонтерства</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($volunteers as $index => $volunteer): ?>
                        <tr style="animation-delay: <?php echo $index * 0.05; ?>s">
                            <td><?= htmlspecialchars($volunteer['id']) ?></td>
                            <td><?= htmlspecialchars($volunteer['name']) ?></td>
                            <td><?= htmlspecialchars($volunteer['group_name'] ?? 'Нет группы') ?></td>
                            <td><?= htmlspecialchars($volunteer['date_joined']) ?></td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($volunteer['activity_type']) == 'Активен' ? 'status-yes' : 'status-neutral' ?>">
                                    <?= htmlspecialchars($volunteer['activity_type']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($volunteer['hours_volunteered']) > 0 ? 'status-yes' : 'status-no' ?>">
                                    <?= htmlspecialchars($volunteer['hours_volunteered']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn edit-btn btn btn-outline-primary btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#editVolunteerModal"
                                            data-id="<?= $volunteer['id'] ?>"
                                            data-student-id="<?= $volunteer['student_id'] ?>"
                                            data-group-id="<?= $volunteer['group_id'] ?? '' ?>"
                                            data-date_joined="<?= htmlspecialchars($volunteer['date_joined']) ?>"
                                            data-activity_type="<?= htmlspecialchars($volunteer['activity_type']) ?>"
                                            data-hours_volunteered="<?= htmlspecialchars($volunteer['hours_volunteered']) ?>">
                                        <i class='bx bx-edit'></i>
                                    </button>
                                    <button class="action-btn delete-btn btn btn-outline-danger btn-sm" 
                                            onclick="confirmDelete(<?= $volunteer['id'] ?>)">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Модальное окно добавления волонтера -->
        <div class="modal fade" id="addVolunteerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Добавить волонтера</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="volunteers.php">
                            <input type="hidden" name="add_volunteer" value="1">
                            <div class="mb-3">
                                <label class="form-label">Выберите студента</label>
                                <select class="form-select" name="student_id" required>
                                    <option value="" disabled selected>Выберите студента</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>">
                                            <?= htmlspecialchars($student['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Выберите группу</label>
                                <select class="form-select" name="group_id">
                                    <option value="" selected>Без группы</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Дата вступления</label>
                                <input type="date" class="form-control" name="date_joined" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Тип деятельности</label>
                                <input type="text" class="form-control" name="activity_type" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Часы волонтерства</label>
                                <input type="number" class="form-control" name="hours_volunteered" required>
                            </div>
                            <button type="submit" class="btn btn-modal btn-submit">Добавить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно редактирования волонтера -->
        <div class="modal fade" id="editVolunteerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактировать волонтера</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="volunteers.php">
                            <input type="hidden" name="edit_volunteer" value="1">
                            <input type="hidden" id="edit_id" name="id">
                            <div class="mb-3">
                                <label class="form-label">Выберите студента</label>
                                <select class="form-select" id="edit_student_id" name="student_id" required>
                                    <option value="" disabled>Выберите студента</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>">
                                            <?= htmlspecialchars($student['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Выберите группу</label>
                                <select class="form-select" id="edit_group_id" name="group_id">
                                    <option value="" selected>Без группы</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= $group['id'] ?>"><?= htmlspecialchars($group['group_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Дата вступления</label>
                                <input type="date" class="form-control" id="edit_date_joined" name="date_joined" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Тип деятельности</label>
                                <input type="text" class="form-control" id="edit_activity_type" name="activity_type" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Часы волонтерства</label>
                                <input type="number" class="form-control" id="edit_hours_volunteered" name="hours_volunteered" required>
                            </div>
                            <button type="submit" class="btn btn-modal btn-submit">Сохранить изменения</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно подтверждения удаления -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Подтверждение удаления</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <i class='bx bx-error-circle' style="font-size: 4rem; color: #EF4444; margin-bottom: 1rem;"></i>
                        <p>Вы действительно хотите удалить этого волонтера?</p>
                        <p class="text-muted small">Это действие нельзя будет отменить</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Удалить</a>
                    </div>
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

        // Инициализация Select2
        $(document).ready(function() {
            $('#edit_student_id, #edit_group_id').select2({
                width: '100%',
                placeholder: 'Выберите значение',
                allowClear: true
            });

            // Очистка Select2 при закрытии модального окна
            $('#addVolunteerModal, #editVolunteerModal').on('hidden.bs.modal', function () {
                $('#edit_student_id, #edit_group_id').val(null).trigger('change');
            });
        });

        // Поиск по таблице
        document.querySelector('.search-bar').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        // Заполнение формы редактирования
        documentwas here document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const studentId = this.dataset.studentId;
                const groupId = this.dataset.groupId;
                const dateJoined = this.dataset.date_joined;
                const activityType = this.dataset.activity_type;
                const hoursVolunteered = this.dataset.hours_volunteered;

                document.getElementById('edit_id').value = id;
                $('#edit_student_id').val(studentId).trigger('change');
                $('#edit_group_id').val(groupId).trigger('change');
                document.getElementById('edit_date_joined').value = dateJoined;
                document.getElementById('edit_activity_type').value = activityType;
                document.getElementById('edit_hours_volunteered').value = hoursVolunteered;
            });
        });

        // Подтверждение удаления
        function confirmDelete(id) {
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.href = `volunteers.php?delete_volunteer=${id}`;
            deleteModal.show();
        }

        // Показ уведомления при загрузке страницы
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