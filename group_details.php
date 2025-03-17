<?php
session_start();

// Подключаем конфигурацию и операции с базой данных
include('config.php');
require_once 'db_operations.php';
include('functions.php');

// Проверка наличия ID группы
if (!isset($_GET['id'])) {
    header('Location: groups.php');
    exit;
}

$group_id = $_GET['id'];

// Получение информации о группе
$group_sql = "SELECT * FROM groups WHERE id = ?";
$group_stmt = $pdo->prepare($group_sql);
$group_stmt->execute([$group_id]);
$group = $group_stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header('Location: groups.php');
    exit;
}

// Получение списка студентов в группе
$students_sql = "SELECT * FROM students WHERE group_id = ? ORDER BY name";
$students_stmt = $pdo->prepare($students_sql);
$students_stmt->execute([$group_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение списка месяцев для текущего года
$current_year = date('Y');
$months = [];
for ($i = 1; $i <= 12; $i++) {
    $months[$i] = [
        'number' => $i,
        'name' => date('F', mktime(0, 0, 0, $i, 10))
    ];
}

// Обработка отправки формы с пропусками
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_absences'])) {
    $student_id = $_POST['student_id'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $absences_count = $_POST['absences_count'];
    
    // Проверяем, существует ли уже такая запись
    $check_sql = "SELECT id FROM absences WHERE student_id = ? AND month = ? AND year = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$student_id, $month, $year]);
    $existing_record = $check_stmt->fetch();
    
    if ($existing_record) {
        // Обновляем существующую запись
        $update_sql = "UPDATE absences SET absences_count = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$absences_count, $existing_record['id']]);
    } else {
        // Создаем новую запись
        $insert_sql = "INSERT INTO absences (student_id, month, year, absences_count) VALUES (?, ?, ?, ?)";
        $insert_stmt = $pdo->prepare($insert_sql);
        $insert_stmt->execute([$student_id, $month, $year, $absences_count]);
    }
    
    setNotification("Данные о пропусках успешно сохранены", "success");
    header("Location: group_details.php?id=$group_id");
    exit;
}

// Функция для получения количества пропусков за месяц
function getAbsencesCount($student_id, $month, $year, $pdo) {
    $sql = "SELECT absences_count FROM absences WHERE student_id = ? AND month = ? AND year = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $month, $year]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['absences_count'] : 0;
}

// Функция для получения статистики пропусков по группе
function getGroupAbsencesStats($group_id, $pdo) {
    $sql = "SELECT 
              SUM(a.absences_count) as total_absences,
              AVG(a.absences_count) as average_absences,
              MAX(a.absences_count) as max_absences
            FROM students s
            JOIN absences a ON s.id = a.student_id
            WHERE s.group_id = ? AND a.year = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$group_id, date('Y')]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Получение статистики пропусков
$stats = getGroupAbsencesStats($group_id, $pdo);

// Функция для установки сообщения уведомления
function setNotification($message, $type = 'success') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Функция для получения статуса БРСМ
function getBrsmStatus($student_id, $pdo) {
    $sql = "SELECT COUNT(*) FROM brsm WHERE student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn() > 0 ? 'Состоит' : 'Не состоит';
}

// Функция для получения статуса волонтера
function getVolunteerStatus($student_id, $pdo) {
    $sql = "SELECT COUNT(*) FROM volunteers WHERE student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn() > 0 ? 'Активен' : 'Не участвует';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Группа <?= htmlspecialchars($group['group_name']) ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
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
            font-size: 0.85rem;
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
        .stats-container {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0);
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #4946e5;
        }
        .stat-card p {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
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
        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .absences-cell {
            width: 80px;
            text-align: center;
        }
        .absences-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 5px;
        }
        .absences-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
            outline: none;
        }
        .month-tabs {
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 20px;
        }
        .month-tab {
            padding: 10px 15px;
            margin-right: 5px;
            border: none;
            background: none;
            font-weight: 500;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .month-tab.active {
            color: #6366f1;
            border-bottom: 3px solid #6366f1;
        }
        .month-tab:hover {
            color: #6366f1;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
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
            <input type="text" class="search-bar" placeholder="Поиск по студенту...">
        </div>
    </header>

    <!-- Верхняя часть с информацией о группе -->
    <div class="group-header">
        <div>
            <h2><?= htmlspecialchars($group['group_name']) ?></h2>
            <?php if(!empty($group['department'])): ?>
                <p class="text-muted"><?= htmlspecialchars($group['department']) ?></p>
            <?php endif; ?>
        </div>
        <a href="groups.php" class="btn btn-outline-primary">
            <i class='bx bx-arrow-back'></i> Назад к группам
        </a>
    </div>

    <!-- Статистика по группе -->
    <div class="stats-container">
        <div class="stat-card">
            <h3><?= count($students) ?></h3>
            <p>Студентов</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats ? (int)$stats['total_absences'] : 0 ?></h3>
            <p>Всего пропусков в этом году</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats ? number_format($stats['average_absences'], 1) : 0 ?></h3>
            <p>Среднее количество пропусков</p>
        </div>
        <div class="stat-card">
            <h3><?= $stats ? (int)$stats['max_absences'] : 0 ?></h3>
            <p>Максимум пропусков у студента</p>
        </div>
    </div>

    <!-- Кнопка добавления студента -->
    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addStudentToGroupModal">
        <i class='bx bx-plus-circle me-1'></i> Добавить студента в группу
    </button>

    <!-- Табы месяцев -->
    <div class="month-tabs">
        <?php foreach ($months as $index => $month): ?>
            <button class="month-tab <?= $index == date('n') ? 'active' : '' ?>" data-month="<?= $index ?>">
                <?= htmlspecialchars($month['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Контейнер для таблицы студентов -->
    <?php foreach ($months as $month_index => $month): ?>
    <div class="tab-content <?= $month_index == date('n') ? 'active' : '' ?>" data-month="<?= $month_index ?>">
        <div class="table-container">
            <h4>Пропуски за <?= htmlspecialchars($month['name']) ?> <?= $current_year ?></h4>
            
            <?php if(count($students) === 0): ?>
            <div class="alert alert-info">
                В этой группе пока нет студентов. Добавьте студентов, используя кнопку выше.
            </div>
            <?php else: ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>БРСМ</th>
                        <th>Волонтер</th>
                        <th>Пропуски</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['id']) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td>
                            <span class="status-badge <?= getBrsmStatus($student['id'], $pdo) == 'Состоит' ? 'status-yes' : 'status-no' ?>">
                                <?= htmlspecialchars(getBrsmStatus($student['id'], $pdo)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= getVolunteerStatus($student['id'], $pdo) == 'Активен' ? 'status-yes' : 'status-neutral' ?>">
                                <?= htmlspecialchars(getVolunteerStatus($student['id'], $pdo)) ?>
                            </span>
                        </td>
                        <td class="absences-cell">
                            <form method="POST" class="d-inline absences-form">
                                <input type="hidden" name="save_absences" value="1">
                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                <input type="hidden" name="month" value="<?= $month_index ?>">
                                <input type="hidden" name="year" value="<?= $current_year ?>">
                                <input type="number" class="absences-input" name="absences_count" min="0" max="31" 
                                       value="<?= getAbsencesCount($student['id'], $month_index, $current_year, $pdo) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-primary save-btn">
                                    <i class='bx bx-save'></i>
                                </button>
                            </form>
                        </td>
                        <td>
                            <a href="student_profile.php?id=<?= $student['id'] ?>" class="btn btn-sm btn-outline-info">
                                <i class='bx bx-user'></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger remove-student-btn" 
                                    data-student-id="<?= $student['id'] ?>"
                                    data-student-name="<?= htmlspecialchars($student['name']) ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#removeStudentModal">
                                <i class='bx bx-user-x'></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Модальное окно добавления студента в группу -->
<div class="modal fade" id="addStudentToGroupModal" tabindex="-1" aria-labelledby="addStudentToGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentToGroupModalLabel">Добавить студента в группу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="add_student_to_group" value="1">
                    <input type="hidden" name="group_id" value="<?= $group_id ?>">
                    <div class="mb-3">
                        <label class="form-label">ФИО</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3 d-flex justify-content-between">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="brsm">
                            <label class="form-check-label">БРСМ</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="volunteer">
                            <label class="form-check-label">Волонтер</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно удаления студента из группы -->
<div class="modal fade" id="removeStudentModal" tabindex="-1" aria-labelledby="removeStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="removeStudentModalLabel">Удалить студента из группы</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить студента <strong id="student-name-to-remove"></strong> из группы?</p>
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="remove_student_from_group" value="1">
                    <input type="hidden" name="group_id" value="<?= $group_id ?>">
                    <input type="hidden" id="student_id_to_remove" name="student_id">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </div>
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

    // Код для отображения уведомлений из сессии
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

    // Поиск по студентам
    document.querySelector('.search-bar').addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });

              // Переключение табов месяцев
              document.querySelectorAll('.month-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const month = this.dataset.month;

                    // Убираем активный класс со всех табов
                    document.querySelectorAll('.month-tab').forEach(t => {
                        t.classList.remove('active');
                    });

                    // Добавляем активный класс выбранному табу
                    this.classList.add('active');

                    // Скрываем все контенты табов
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.classList.remove('active');
                    });

                    // Показываем контент выбранного таба
                    document.querySelector(`.tab-content[data-month="${month}"]`).classList.add('active');
                });
            });

            // Обработка модального окна удаления студента
            document.querySelectorAll('.remove-student-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const studentId = this.dataset.studentId;
                    const studentName = this.dataset.studentName;
                    
                    document.getElementById('student_id_to_remove').value = studentId;
                    document.getElementById('student-name-to-remove').textContent = studentName;
                });
            });

            // Автоматическое сохранение пропусков при изменении
            document.querySelectorAll('.absences-input').forEach(input => {
                input.addEventListener('change', function() {
                    const form = this.closest('form');
                    const saveBtn = form.querySelector('.save-btn');
                    
                    // Проверка введенного значения
                    const value = parseInt(this.value);
                    if (value < 0) this.value = 0;
                    if (value > 31) this.value = 31;
                    
                    // Автоматическая отправка формы
                    form.submit();
                });
            });

            // Обработка успешного добавления/удаления студента
            if (window.location.hash === '#success') {
                showNotification('Операция выполнена успешно', 'success');
                window.location.hash = '';
            }

            // Инициализация поиска при загрузке страницы
            const searchInput = document.querySelector('.search-bar');
            if (searchInput) {
                searchInput.focus();
                searchInput.value = '';
            }
        
     // Функция для обновления времени
     function updateTime() {
            const now = new Date();
            const dateText = now.toLocaleDateString();
            const timeText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            document.querySelector('.date-text').textContent = dateText;
            document.querySelector('.time-text').textContent = timeText;
        }

        // Обновляем время каждую минуту
        setInterval(updateTime, 60000);
        updateTime(); // Инициализация при загрузке
    </script>

    <!-- Инициализация всех компонентов при загрузке страницы -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация всплывающих подсказок Bootstrap
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Инициализация поповеров Bootstrap
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl)
            });

            // Обработка формы добавления студента
            const addStudentForm = document.querySelector('#addStudentToGroupModal form');
            if (addStudentForm) {
                addStudentForm.addEventListener('submit', function(e) {
                    const nameInput = this.querySelector('input[name="name"]');
                    if (!nameInput.value.trim()) {
                        e.preventDefault();
                        showNotification('Пожалуйста, введите ФИО студента', 'error');
                    }
                });
            }

            // Обработка клика вне модального окна
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    const modal = bootstrap.Modal.getInstance(e.target);
                    if (modal) modal.hide();
                }
            });

            // Очистка хэша URL при закрытии модальных окон
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function() {
                    if (window.location.hash === '#success') {
                        window.location.hash = '';
                    }
                });
            });
        });
    </script>
</body>
</html>;
        