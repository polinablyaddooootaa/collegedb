<?php

session_start();


// Подключаем конфигурацию и операции с базой данных
include('config.php');
require_once 'db_operations.php';
include('functions.php');


// Получение списка студентов
$sql = "SELECT * FROM students";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Удаление студента
if (isset($_GET['delete_student'])) {
    $id = $_GET['delete_student'];
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: students.php");
    exit;
}

// Получаем список студентов из базы
$sql = "SELECT * FROM students";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getBrsmStatus($student_id, $pdo) {
    $sql = "SELECT COUNT(*) FROM brsm WHERE student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn() > 0 ? 'Состоит' : 'Не состоит';
}

function getVolunteerStatus($student_id, $pdo) {
    $sql = "SELECT COUNT(*) FROM volunteers WHERE student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn() > 0 ? 'Активен' : 'Не участвует';
}
// Функция для установки сообщения уведомления
function setNotification($message, $type = 'success') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Студенты</title>

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

.form-check {
    margin-bottom: 1rem;
}

.form-check-input {
    border-radius: 0.375rem;
    margin-right: 10px;
}

.form-check-label {
    font-size: 16px;
    font-weight: 500;
    color: #4b5563;
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

.d-flex .form-check {
    flex: 1;
    display: flex;
    align-items: center;
}

.d-flex .form-check input {
    margin-right: 10px;
}

    </style>
</head>
<body>

<?php

include('sidebar.php');  // Подключаем сайдбар после session_start()
?>


    <!-- Основной контент -->
    <div class="content">
        <!-- Верхний заголовок -->
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
                    <input type="text" class="search-bar" placeholder="Поиск по студенту...">
                </div>
        </header>

        <!-- Контейнер для таблицы студентов -->
        <div class="table-container">
            <h2 class="mb-3">Список студентов</h2>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addStudentModal">
            <i class='bx bx-plus-circle me-1'></i> Добавить студента
            </button>
            <a href="export_excel.php" class="btn-add" id="exportBtn">Экспорт в Excel</a>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#importModal">Импорт из Excel</button>


     
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>БРСМ</th>
                        <th>Волонтер</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($students as $student): ?>
<tr>
    <td><?= htmlspecialchars($student['id']) ?></td>
    <td><?= htmlspecialchars($student['name']) ?></td>
    <td><?= htmlspecialchars($student['group_name']) ?></td>
    <td><span class="status-badge <?= getBrsmStatus($student['id'], $pdo) == 'Состоит' ? 'status-yes' : 'status-no' ?>">
        <?= htmlspecialchars(getBrsmStatus($student['id'], $pdo)) ?>
    </span></td>
    <td><span class="status-badge <?= getVolunteerStatus($student['id'], $pdo) == 'Активен' ? 'status-yes' : 'status-neutral' ?>">
        <?= htmlspecialchars(getVolunteerStatus($student['id'], $pdo)) ?>
    </span></td>
    <td>
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editStudentModal"
                data-id="<?= $student['id'] ?>"
                data-name="<?= htmlspecialchars($student['name']) ?>"
                data-group="<?= htmlspecialchars($student['group_name']) ?>"
                data-brsm="<?= getBrsmStatus($student['id'], $pdo) == 'Состоит' ? '1' : '0' ?>"
                data-volunteer="<?= getVolunteerStatus($student['id'], $pdo) == 'Активен' ? '1' : '0' ?>">
                <i class='bx bx-edit-alt'></i>
        </button>
        <a class="btn btn-outline-danger btn-sm" href="students.php?delete_student=<?= $student['id'] ?>" 
           onclick="return confirm('Вы уверены, что хотите удалить?')">
           <i class='bx bx-trash'></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно добавления студента -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Добавить студента</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="add_student" value="1">
                    <div class="mb-3">
                        <label class="form-label">ФИО</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Группа</label>
                        <input type="text" class="form-control" name="group_name" required>
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


<!-- Модальное окно редактирования студента -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Добавить студента</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="add_student" value="1">
                    <div class="mb-3">
                        <label class="form-label">ФИО</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Группа</label>
                        <input type="text" class="form-control" name="group_name" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="brsm">
                        <label class="form-check-label">БРСМ</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="volunteer">
                        <label class="form-check-label">Волонтер</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>



<!-- Модальное окно импорта Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Импорт из Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="import_excel.php" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="excelFile" class="form-label">Выберите файл Excel</label>
                        <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Импортировать</button>
                </form>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Редактировать студента</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="db_operations.php">
                        <input type="hidden" name="edit_student" value="1">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">ФИО</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Группа</label>
                            <input type="text" class="form-control" id="edit_group" name="group_name" required>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_brsm" name="brsm">
                            <label class="form-check-label">БРСМ</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_volunteer" name="volunteer">
                            <label class="form-check-label">Волонтер</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
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
    document.querySelector('.search-bar').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
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
// Функция для заполнения формы редактирования
// Функция для заполнения формы редактирования старыми данными
function fillEditForm(id, name, group, brsm, volunteer) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_group').value = group;
            document.getElementById('edit_brsm').checked = brsm;
            document.getElementById('edit_volunteer').checked = volunteer;
        }

        // Пример использования функции fillEditForm при открытии модального окна
        document.querySelectorAll('.btn-outline-primary').forEach(button => {
            button.addEventListener('click', function() {
                const studentId = this.dataset.id;
                const studentName = this.dataset.name;
                const studentGroup = this.dataset.group;
                const studentBrsm = this.dataset.brsm === '1';
                const studentVolunteer = this.dataset.volunteer === '1';

                fillEditForm(studentId, studentName, studentGroup, studentBrsm, studentVolunteer);
            });
        });
// Проверка перед экспортом
document.addEventListener('DOMContentLoaded', function() {
    // Проверка перед экспортом
    document.getElementById('exportBtn').addEventListener('click', function(e) {
        // Проверяем, если таблица пуста (или какие-либо другие условия)
        let rows = document.querySelectorAll('table tbody tr');
        if (rows.length === 0) {
            e.preventDefault(); // Отменяем действие по умолчанию (перенаправление)
            alert('Нет данных для экспорта!');
        }
    });
});
</script>

</body>
</html>