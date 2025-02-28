<?php
// Подключаем конфигурацию
include('config.php');

// Получаем список студентов для выпадающего списка
$sql_students = "SELECT id, name FROM students";
$stmt_students = $pdo->prepare($sql_students);
$stmt_students->execute();
$students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

// Получаем список волонтеров из базы данных
$sql = "
    SELECT volunteers.id, students.name, volunteers.date_joined, 
           volunteers.activity_type, volunteers.hours_volunteered
    FROM volunteers
    JOIN students ON volunteers.student_id = students.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$volunteers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка добавления нового волонтера
if (isset($_POST['add_volunteer'])) {
    try {
        $pdo->beginTransaction();
        
        $student_id = $_POST['student_id'];
        $date_joined = $_POST['date_joined'];
        $activity_type = $_POST['activity_type'];
        $hours_volunteered = $_POST['hours_volunteered'];

        // Добавляем запись в таблицу volunteers
        $sql = "INSERT INTO volunteers (student_id, date_joined, activity_type, hours_volunteered) 
                VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $date_joined, $activity_type, $hours_volunteered]);

        // Обновляем статус volunteer в таблице students
        $sql = "UPDATE students SET volunteer = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);

        $pdo->commit();
        header("Location: volunteers.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Ошибка: " . $e->getMessage();
    }
}

// Обработка обновления информации волонтера
if (isset($_POST['edit_volunteer'])) {
    $id = $_POST['id'];
    $student_id = $_POST['student_id'];
    $date_joined = $_POST['date_joined'];
    $activity_type = $_POST['activity_type'];
    $hours_volunteered = $_POST['hours_volunteered'];

    $sql = "UPDATE volunteers 
            SET student_id = ?, date_joined = ?, activity_type = ?, hours_volunteered = ? 
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id, $date_joined, $activity_type, $hours_volunteered, $id]);

    header("Location: volunteers.php");
    exit;
}

// Обработка удаления волонтера
if (isset($_GET['delete_volunteer'])) {
    try {
        $pdo->beginTransaction();
        
        $id = $_GET['delete_volunteer'];

        // Получаем student_id перед удалением записи
        $sql = "SELECT student_id FROM volunteers WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $student_id = $stmt->fetchColumn();

        // Удаляем запись из таблицы volunteers
        $sql = "DELETE FROM volunteers WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        // Проверяем, есть ли еще записи для этого студента
        $sql = "SELECT COUNT(*) FROM volunteers WHERE student_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id]);
        $count = $stmt->fetchColumn();

        // Если записей больше нет, обновляем статус volunteer в таблице students
        if ($count == 0) {
            $sql = "UPDATE students SET volunteer = 0 WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
        }

        $pdo->commit();
        header("Location: volunteers.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Ошибка: " . $e->getMessage();
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link rel="stylesheet" href="index.css">
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
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>

    <div class="content">
        <header class="top-header">
            <div class="date-container">
                <i class='bx bx-calendar'></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
        </header>

        <div class="table-container">
            <h2 class="mb-3">Список волонтеров</h2>
            <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addVolunteerModal">
                Добавить волонтера
            </button>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Имя студента</th>
                        <th>Дата вступления</th>
                        <th>Тип деятельности</th>
                        <th>Часы волонтерства</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($volunteers as $volunteer): ?>
                    <tr>
                        <td><?= htmlspecialchars($volunteer['id']) ?></td>
                        <td><?= htmlspecialchars($volunteer['name']) ?></td>
                        <td><?= htmlspecialchars($volunteer['date_joined']) ?></td>
                        <td><?= htmlspecialchars($volunteer['activity_type']) ?></td>
                        <td><?= htmlspecialchars($volunteer['hours_volunteered']) ?></td>
                        <td>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" 
                                    data-bs-target="#editVolunteerModal"
                                    onclick="fillEditForm(
                                        '<?= $volunteer['id'] ?>', 
                                        '<?= $volunteer['name'] ?>', 
                                        '<?= $volunteer['date_joined'] ?>', 
                                        '<?= $volunteer['activity_type'] ?>', 
                                        '<?= $volunteer['hours_volunteered'] ?>')">
                                Редактировать
                            </button>
                            <a class="btn btn-outline-danger btn-sm" 
                               href="volunteers.php?delete_volunteer=<?= $volunteer['id'] ?>" 
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
                        <button type="submit" class="btn btn-primary">Добавить</button>
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
                            <label class="form-label">Имя студента</label>
                            <select class="form-select" name="student_id" id="edit_student_id" required>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>">
                                        <?= htmlspecialchars($student['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Дата вступления</label>
                            <input type="date" class="form-control" id="edit_date_joined" 
                                   name="date_joined" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Тип деятельности</label>
                            <input type="text" class="form-control" id="edit_activity_type" 
                                   name="activity_type" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Часы волонтерства</label>
                            <input type="number" class="form-control" id="edit_hours_volunteered" 
                                   name="hours_volunteered" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function fillEditForm(id, name, date_joined, activity_type, hours_volunteered) {
        document.getElementById('edit_id').value = id;
        // Найти и выбрать правильную опцию в select по имени студента
        const selectElement = document.getElementById('edit_student_id');
        for (let option of selectElement.options) {
            if (option.text === name) {
                option.selected = true;
                break;
            }
        }
        document.getElementById('edit_date_joined').value = date_joined;
        document.getElementById('edit_activity_type').value = activity_type;
        document.getElementById('edit_hours_volunteered').value = hours_volunteered;
    }
    </script>

</body>
</html>