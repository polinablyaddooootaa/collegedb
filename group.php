<?php
// Подключаем конфигурацию
include('config.php');

// Получаем список групп из базы данных
$sql = "SELECT * FROM groups";
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

    <?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->

    <div class="content">
        <!-- Верхний заголовок -->
        <header class="top-header">
            <div class="date-container">
                <i class='bx bx-calendar'></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
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
                        <th>ФИО куратора</th>
                        <th>Количество учащихся</th>
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
                            <label class="form-label">ФИО куратора</label>
                            <input type="text" class="form-control" name="curator" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Количество учащихся</label>
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
                        <input type="hidden" id="edit_id" name="id_group">
                        <div class="mb-3">
                            <label class="form-label">Название группы</label>
                            <input type="text" class="form-control" id="edit_group_name" name="group_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ФИО куратора</label>
                            <input type="text" class="form-control" id="edit_curator" name="curator" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Количество учащихся</label>
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
    function fillEditForm(id, groupName, curator, studentCount) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_group_name').value = groupName;
        document.getElementById('edit_curator').value = curator;
        document.getElementById('edit_count_students').value = studentCount;
    }
    </script>

</body>
</html>
