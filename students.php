<?php
// Подключаем конфигурацию
include('config.php');

// Получаем список студентов из базы
$sql = "SELECT * FROM students";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Студенты</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="index.css"> <!-- Подключение стилей -->
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
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border: none;
            color: white;
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #fda085 0%, #f6d365 100%);
        }
    </style>
</head>
<body>


    <?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->
     

    <!-- Основной контент -->
    <div class="content">
        <!-- Верхний заголовок -->
        <header class="top-header">
            <div class="date-container">
                <i class='bx bx-calendar'></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Поиск...">
            </div>
        </header>

        <!-- Контейнер для таблицы студентов -->
        <div class="table-container">
            <h2 class="mb-3">Список студентов</h2>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">Добавить студента</button>
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
                        <td><span class="status-badge <?= $student['brsm'] ? 'status-yes' : 'status-no' ?>">
                            <?= $student['brsm'] ? 'Да' : 'Нет' ?>
                        </span></td>
                        <td><span class="status-badge <?= $student['volunteer'] ? 'status-yes' : 'status-neutral' ?>">
                            <?= $student['volunteer'] ? 'Активен' : 'Не участвует' ?>
                        </span></td>
                        <td>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editStudentModal" 
                                    onclick="fillEditForm('<?= $student['id'] ?>', '<?= $student['name'] ?>', '<?= $student['group_name'] ?>', 
                                    <?= $student['brsm'] ?>, <?= $student['volunteer'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a class="btn btn-outline-danger btn-sm" href="db_operations.php?delete_student=<?= $student['id'] ?>" 
                               onclick="return confirm('Вы уверены, что хотите удалить?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальные окна добавления и редактирования студента -->

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

<!-- Модальное окно редактирования студента -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактировать студента</h5>
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
// Функция для заполнения формы редактирования
function fillEditForm(id, name, group, brsm, volunteer) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_group').value = group;
    document.getElementById('edit_brsm').checked = brsm;
    document.getElementById('edit_volunteer').checked = volunteer;
}
</script>

</body>
</html>
