<?php
// Подключаем конфигурацию
include('config.php');

// Получаем список групп из базы
$sql = "SELECT * FROM `groups`";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Группы</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
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

        <!-- Контейнер для таблицы групп -->
        <div class="table-container">
            <h2 class="mb-3">Список групп</h2>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addGroupModal">Добавить группу</button>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название группы</th>
                        <th>Куратор</th>
                        <th>Количество студентов</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                    <tr>
                        <td><?= htmlspecialchars($group['id_group']) ?></td>
                        <td><?= htmlspecialchars($group['group_name']) ?></td>
                        <td><?= htmlspecialchars($group['curator']) ?></td>
                        <td><?= htmlspecialchars($group['count_students']) ?></td>
                        <td>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editGroupModal" 
                                    onclick="fillEditForm('<?= $group['id_group'] ?>', '<?= $group['group_name'] ?>', '<?= $group['curator'] ?>', 
                                    <?= $group['count_students'] ?>)">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a class="btn btn-outline-danger btn-sm" href="db_operations.php?delete_group=<?= $group['id_group'] ?>" 
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

<!-- Модальные окна добавления и редактирования группы -->

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
                        <label class="form-label">Куратор</label>
                        <input type="text" class="form-control" name="curator" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Количество студентов</label>
                        <input type="number" class="form-control" name="count_students" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
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
                <h5 class="modal-title">Редактировать группу</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="edit_group" value="1">
                    <input type="hidden" id="edit_id_group" name="id_group">
                    <div class="mb-3">
                        <label class="form-label">Название группы</label>
                        <input type="text" class="form-control" id="edit_group_name" name="group_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Куратор</label>
                        <input type="text" class="form-control" id="edit_curator" name="curator" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Количество студентов</label>
                        <input type="number" class="form-control" id="edit_count_students" name="count_students" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Функция для заполнения формы редактирования
function fillEditForm(id_group, group_name, curator, count_students) {
    document.getElementById('edit_id_group').value = id_group;
    document.getElementById('edit_group_name').value = group_name;
    document.getElementById('edit_curator').value = curator;
    document.getElementById('edit_count_students').value = count_students;
}
</script>

</body>
</html>