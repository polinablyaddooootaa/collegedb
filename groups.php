<?php
session_start();

// Подключаем конфигурацию и операции с базой данных
include('config.php');
require_once 'db_operations.php';
include('functions.php');

// Получение списка групп с количеством студентов
$sql = "SELECT g.id, g.group_name, g.department, COUNT(s.id) as student_count 
        FROM groups g
        LEFT JOIN students s ON g.id = s.group_id
        GROUP BY g.id
        ORDER BY g.group_name";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Удаление группы
if (isset($_GET['delete_group'])) {
    $id = $_GET['delete_group'];
    
    // Проверяем, есть ли студенты в этой группе
    $check_sql = "SELECT COUNT(*) FROM students WHERE group_id = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$id]);
    $student_count = $check_stmt->fetchColumn();
    
    if ($student_count > 0) {
        setNotification("Невозможно удалить группу, в которой есть студенты", "error");
    } else {
        $sql = "DELETE FROM groups WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        setNotification("Группа успешно удалена", "success");
    }
    
    header("Location: groups.php");
    exit;
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
    <title>Группы</title>

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
        .group-card {
            border-radius: 15px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .group-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .group-card-header {
            background-color: #f1f3f9;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .group-card-body {
            padding: 15px 20px;
        }
        .group-stat {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .group-stat i {
            margin-right: 10px;
            color: #6366f1;
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
            <input type="text" class="search-bar" placeholder="Поиск по группе...">
        </div>
    </header>

    <!-- Контейнер для таблицы групп -->
    <div class="table-container">
        <h2 class="mb-3">Список групп</h2>
        <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addGroupModal">
            <i class='bx bx-plus-circle me-1'></i> Добавить группу
        </button>

        <div class="row">
            <?php foreach ($groups as $group): ?>
            <div class="col-md-4">
                <div class="group-card">
                    <div class="group-card-header">
                        <h5><?= htmlspecialchars($group['group_name']) ?></h5>
                    </div>
                    <div class="group-card-body">
                        <?php if(!empty($group['department'])): ?>
                        <div class="group-stat">
                            <i class='bx bx-building'></i>
                            <span><?= htmlspecialchars($group['department']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="group-stat">
                            <i class='bx bx-user-plus'></i>
                            <span><?= htmlspecialchars($group['student_count']) ?> студентов</span>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <a href="group_details.php?id=<?= $group['id'] ?>" class="btn btn-sm btn-primary">
                                <i class='bx bx-show'></i> Подробнее
                            </a>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                    data-bs-target="#editGroupModal"
                                    data-id="<?= $group['id'] ?>"
                                    data-name="<?= htmlspecialchars($group['group_name']) ?>"
                                    data-department="<?= htmlspecialchars($group['department'] ?? '') ?>">
                                <i class='bx bx-edit-alt'></i>
                            </button>
                            <a class="btn btn-sm btn-outline-danger" href="groups.php?delete_group=<?= $group['id'] ?>" 
                               onclick="return confirm('Вы уверены, что хотите удалить группу?')">
                               <i class='bx bx-trash'></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($groups) === 0): ?>
        <div class="alert alert-info">
            Нет добавленных групп. Создайте новую группу, нажав на кнопку "Добавить группу".
        </div>
        <?php endif; ?>
    </div>
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
                    <input type="hidden" name="add_group" value="1">
                    <div class="mb-3">
                        <label class="form-label">Название группы</label>
                        <input type="text" class="form-control" name="group_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Отделение/Факультет</label>
                        <input type="text" class="form-control" name="department">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><button type="submit" class="btn btn-primary w-100">Добавить</button>
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
                    <input type="hidden" name="edit_group" value="1">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label class="form-label">Название группы</label>
                        <input type="text" class="form-control" id="edit_group_name" name="group_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Отделение/Факультет</label>
                        <input type="text" class="form-control" id="edit_department" name="department">
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

    // Поиск по группам
    document.querySelector('.search-bar').addEventListener('input', function(e) {
        const searchText = e.target.value.toLowerCase();
        document.querySelectorAll('.group-card').forEach(card => {
            const groupName = card.querySelector('h5').textContent.toLowerCase();
            const department = card.querySelector('.bx-building')?.nextElementSibling?.textContent.toLowerCase() || '';
            
            if (groupName.includes(searchText) || department.includes(searchText)) {
                card.closest('.col-md-4').style.display = '';
            } else {
                card.closest('.col-md-4').style.display = 'none';
            }
        });
    });

    // Функция для заполнения формы редактирования
    document.querySelectorAll('.btn-outline-primary').forEach(button => {
        button.addEventListener('click', function() {
            const groupId = this.dataset.id;
            const groupName = this.dataset.name;
            const department = this.dataset.department;

            document.getElementById('edit_id').value = groupId;
            document.getElementById('edit_group_name').value = groupName;
            document.getElementById('edit_department').value = department;
        });
    });
</script>

</body>
</html>