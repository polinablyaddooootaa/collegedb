<?php
// Подключаем конфигурацию
include('config.php');
include('functions.php');

// Запускаем сессию если еще не запущена
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Функция для установки сообщения уведомления
function setNotification($message, $type = 'success') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

try {
    // Проверка существования таблиц
    $tables_check = $pdo->query("SHOW TABLES LIKE 'groups'");
    if ($tables_check->rowCount() == 0) {
        setNotification("Таблица 'groups' не найдена в базе данных", 'error');
        $groups = [];
    } else {
        // Получаем список групп (экранируем имя таблицы)
        $sql_groups = "SELECT id, group_name FROM `groups` ORDER BY group_name";
        $stmt_groups = $pdo->prepare($sql_groups);
        $stmt_groups->execute();
        $groups = $stmt_groups->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получаем список студентов
    $sql_students = "SELECT id, name FROM `students` ORDER BY name";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Получаем список членов БРСМ
    $sql = "
        SELECT brsm.id, students.id AS student_id, students.name, brsm.date_joined, `groups`.group_name, students.group_id
        FROM `brsm`
        INNER JOIN `students` ON brsm.student_id = students.id
        LEFT JOIN `groups` ON students.group_id = `groups`.id
        ORDER BY brsm.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $brsm_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    setNotification("Ошибка базы данных: " . $e->getMessage(), 'error');
    $students = [];
    $groups = [];
    $brsm_members = [];
}

// Обработка добавления нового члена
if (isset($_POST['add_member'])) {
    try {
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $date_joined = $_POST['date_joined'];
        $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;

        if (!$student_id || !$date_joined) {
            setNotification('Заполните все обязательные поля', 'error');
            header("Location: brsm.php");
            exit;
        }

        // Проверка существования студента
        $sql = "SELECT id FROM `students` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        if (!$stmt->fetch()) {
            setNotification('Студент не найден', 'error');
            header("Location: brsm.php");
            exit;
        }

        // Добавление в таблицу brsm
        $sql = "INSERT INTO `brsm` (student_id, date_joined) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $date_joined]);

        // Обновление group_id в таблице students
        if ($group_id) {
            $sql = "UPDATE `students` SET group_id = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$group_id, $student_id]);
        }

        setNotification('Участник БРСМ успешно добавлен', 'success');
        header("Location: brsm.php");
        exit;
    } catch (PDOException $e) {
        setNotification("Ошибка добавления: " . $e->getMessage(), 'error');
        header("Location: brsm.php");
        exit;
    }
}

// Обработка обновления информации члена
if (isset($_POST['edit_member'])) {
    try {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $student_id = filter_input(INPUT_POST, 'student_id', FILTER_VALIDATE_INT);
        $date_joined = $_POST['date_joined'];
        $group_id = filter_input(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?: null;

        if (!$id || !$student_id || !$date_joined) {
            setNotification('Заполните все обязательные поля', 'error');
            header("Location: brsm.php");
            exit;
        }

        // Проверка существования студента
        $sql = "SELECT id FROM `students` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        if (!$stmt->fetch()) {
            setNotification('Студент не найден', 'error');
            header("Location: brsm.php");
            exit;
        }

        // Обновление информации в таблице brsm
        $sql = "UPDATE `brsm` SET student_id = ?, date_joined = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $date_joined, $id]);

        // Обновление group_id в таблице students
        $sql = "UPDATE `students` SET group_id = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_id, $student_id]);

        setNotification('Информация об участнике БРСМ успешно обновлена', 'success');
        header("Location: brsm.php");
        exit;
    } catch (PDOException $e) {
        setNotification("Ошибка обновления: " . $e->getMessage(), 'error');
        header("Location: brsm.php");
        exit;
    }
}

// Обработка удаления члена
if (isset($_GET['delete_member'])) {
    try {
        $id = filter_input(INPUT_GET, 'delete_member', FILTER_VALIDATE_INT);
        if (!$id) {
            setNotification('Неверный ID', 'error');
            header("Location: brsm.php");
            exit;
        }

        $sql = "DELETE FROM `brsm` WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        setNotification('Участник БРСМ успешно удален', 'info');
        header("Location: brsm.php");
        exit;
    } catch (PDOException $e) {
        setNotification("Ошибка удаления: " . $e->getMessage(), 'error');
        header("Location: brsm.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Члены БРСМ</title>
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
                <h2 class="mb-3">Список членов БРСМ</h2>
                <button class="btn-add btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class='bx bx-plus-circle me-1'></i> Добавить члена БРСМ
                </button>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя студента</th>
                            <th>Группа</th>
                            <th>Дата вступления</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brsm_members as $index => $member): ?>
                        <tr style="animation-delay: <?php echo $index * 0.05; ?>s">
                            <td><?= htmlspecialchars($member['id']) ?></td>
                            <td><?= htmlspecialchars($member['name']) ?></td>
                            <td><?= htmlspecialchars($member['group_name'] ?? 'Нет группы') ?></td>
                            <td>
                                <span class="status-badge status-neutral">
                                    <?= htmlspecialchars($member['date_joined']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn edit-btn btn btn-outline-primary btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#editMemberModal"
                                            data-id="<?= $member['id'] ?>"
                                            data-student-id="<?= $member['student_id'] ?>"
                                            data-group-id="<?= $member['group_id'] ?? '' ?>"
                                            data-date_joined="<?= htmlspecialchars($member['date_joined']) ?>">
                                        <i class='bx bx-edit'></i>
                                    </button>
                                    <button class="action-btn delete-btn btn btn-outline-danger btn-sm" 
                                            onclick="confirmDelete('<?= $member['id'] ?>')">
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
        <!-- Модальное окно добавления члена -->
        <div class="modal fade" id="addMemberModal" tabindex="-1" aria-labelledby="addMemberModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMemberModalLabel">Добавить члена БРСМ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="brsm.php">
                            <input type="hidden" name="add_member" value="1">
                            <div class="mb-3">
                                <label class="form-label">Выберите студента</label>
                                <select class="form-select" name="student_id" required>
                                    <option value="" disabled selected>Выберите студента</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
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
                            <button type="submit" class="btn btn-modal btn-submit">Добавить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Модальное окно редактирования члена -->
        <div class="modal fade" id="editMemberModal" tabindex="-1" aria-labelledby="editMemberModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редактировать члена БРСМ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="brsm.php">
                            <input type="hidden" name="edit_member" value="1">
                            <input type="hidden" id="edit_id" name="id">
                            <div class="mb-3">
                                <label class="form-label">Выберите студента</label>
                                <select class="form-select" id="edit_student_id" name="student_id" required>
                                    <option value="" disabled>Выберите студента</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
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
                        <p>Вы действительно хотите удалить этого участника БРСМ?</p>
                        <p class="text-muted small">Это действие нельзя будет отменить</p>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Удалить</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            echo "<script>document.addEventListener('DOMContentLoaded', function() {
                showNotification('" . addslashes($notification['message']) . "', '" . $notification['type'] . "');
            });</script>";
            unset($_SESSION['notification']);
        }
        ?>
        <script src="js/brsm.js"></script>
    </div>
</body>
</html>