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

// Получаем список студентов для выпадающего списка
$sql_students = "SELECT id, name FROM students";
$stmt_students = $pdo->prepare($sql_students);
$stmt_students->execute();
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

// Получаем список членов БРСМ из базы данных
$sql = "
    SELECT brsm.id, students.name, brsm.date_joined
    FROM brsm
    JOIN students ON brsm.student_id = students.id";  // Присоединяем таблицу студентов по student_id
$stmt = $pdo->prepare($sql);
$stmt->execute();
$brsm_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка добавления нового члена
if (isset($_POST['add_member'])) {
    $student_id = $_POST['student_id'];
    $date_joined = $_POST['date_joined'];

    $sql = "INSERT INTO brsm (student_id, date_joined) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $date_joined]);

    // Устанавливаем уведомление
    setNotification('Участник БРСМ успешно добавлен', 'success');

    header("Location: brsm.php"); // Перенаправляем обратно на страницу после добавления
    exit;
}

// Обработка обновления информации члена
if (isset($_POST['edit_member'])) {
    $id = $_POST['id'];
    $student_id = $_POST['student_id'];
    $date_joined = $_POST['date_joined'];

    $sql = "UPDATE brsm SET student_id = ?, date_joined = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $date_joined, $id]);

    // Устанавливаем уведомление
    setNotification('Информация об участнике БРСМ успешно обновлена', 'success');

    header("Location: brsm.php"); // Перенаправляем обратно на страницу после обновления
    exit;
}

// Обработка удаления члена
if (isset($_GET['delete_member'])) {
    $id = $_GET['delete_member'];

    $sql = "DELETE FROM brsm WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    // Устанавливаем уведомление
    setNotification('Участник БРСМ успешно удален', 'info');

    header("Location: brsm.php"); // Перенаправляем обратно на страницу после удаления
    exit;
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
        .btn-add {
            font-size: 1rem;
            padding: 0.5rem 1.5rem;
            background: linear-gradient(135deg, #4946e5 0%, #636ff1 100%);
            border: none;
            border-radius: 15px;
            margin-bottom: 20px;
            color: white;
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
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->

    <div class="content">
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
                    <input type="text" class="search-bar" placeholder="Поиск по достижениям...">
                </div>
        </header>

        <!-- Контейнер для таблицы БРСМ -->
        <div class="table-container">
            <h2 class="mb-3">Список членов БРСМ</h2>
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addMemberModal">
            <i class='bx bx-plus-circle me-1'></i> Добавить члена БРСМ
            </button>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя студента</th>
                        <th>Дата вступления</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brsm_members as $member): ?>
                    <tr>
                        <td><?= htmlspecialchars($member['id']) ?></td>
                        <td><?= htmlspecialchars($member['name']) ?></td>  <!-- Теперь выводится имя студента -->
                        <td><?= htmlspecialchars($member['date_joined']) ?></td>
                        <td>
                            <!-- Кнопка для редактирования -->
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMemberModal"
                                    onclick="fillEditForm('<?= $member['id'] ?>', '<?= $member['name'] ?>', '<?= $member['date_joined'] ?>')">
                                Редактировать
                            </button>
                            <!-- Кнопка для удаления -->
                            <a class="btn btn-outline-danger btn-sm" href="brsm.php?delete_member=<?= $member['id'] ?>" 
                               onclick="return confirm('Вы уверены, что хотите удалить?')">
                                Удалить
                            </a>
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
                            <label class="form-label">Дата вступления</label>
                            <input type="date" class="form-control" name="date_joined" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Добавить</button>
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
                            <label class="form-label">Имя студента</label>
                            <input type="text" class="form-control" id="edit_student_id" name="student_id" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Дата вступления</label>
                            <input type="date" class="form-control" id="edit_date_joined" name="date_joined" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Функция для заполнения формы редактирования
    function fillEditForm(id, student_id, date_joined) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_student_id').value = student_id;
        document.getElementById('edit_date_joined').value = date_joined;
    }
    document.querySelector('.search-bar').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
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
    </script>
    
    <!-- Код для отображения уведомлений из сессии -->
    <script>
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
    </script>

</body>
</html>