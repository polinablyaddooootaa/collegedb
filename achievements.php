<?php
include('config.php');

$sql = "CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    achievement_date DATE NOT NULL,
    achievement TEXT NOT NULL,
    achievement_type ENUM('Достижения в общественной жизни', 'Достижения в спорте', 'Достижения в творческой деятельности', 'Достижения в исследовательской деятельности', 'Другие достижения') NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
$pdo->exec($sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $achievement_date = $_POST['achievement_date'];
    $achievement = $_POST['achievement'];
    $achievement_type = $_POST['achievement_type'];

    try {
        $insertQuery = "INSERT INTO achievements (student_id, achievement_date, achievement, achievement_type) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([$student_id, $achievement_date, $achievement, $achievement_type]);
        header("Location: achievements.php");
        exit;
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

$studentsQuery = "SELECT id, name FROM students";
$studentsStmt = $pdo->prepare($studentsQuery);
$studentsStmt->execute();
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT students.id, students.name, students.group_name, achievements.id as achievement_id, 
                 achievements.achievement_date, achievements.achievement, achievements.achievement_type
          FROM achievements
          JOIN students ON achievements.student_id = students.id
          ORDER BY achievements.achievement_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Достижения</title>
    <!-- Используем https для корректной работы -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>


    <link rel="stylesheet" href="index.css">
    <style>
        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem; 
            gap: 2rem;
        }

        .table-container {
            background: var(--card-bg);
            border-radius: 1.25rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: calc(100% - 300px) !important;
            margin-left: 300px !important;
        }

        .achievements-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            margin-left: 300px;
        }

        .achievements-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .btn-add {
            font-size: 1rem;
            padding: 0.5rem 1.5rem;
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
        <div class="search-container">
            <input type="text" class="search-bar" placeholder="Поиск...">
        </div>
    </header>

    <!-- Заголовок и кнопка -->
    <div class="achievements-header">
        <h2>Достижения учащихся</h2>
        <button class="btn btn-primary btn-add" data-bs-toggle="modal" data-bs-target="#addAchievementModal">Добавить достижение</button>
    </div>

    <!-- Таблица достижений -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ФИО</th>
                    <th>Группа</th>
                    <th>Дата</th>
                    <th>Тип достижения</th>
                    <th>Достижение</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
    <?php foreach ($achievements as $achv): ?>
    <tr>
        <td><?= htmlspecialchars($achv['name']) ?></td>
        <td><?= htmlspecialchars($achv['group_name']) ?></td>
        <td><?= htmlspecialchars($achv['achievement_date']) ?></td>
        <td><?= htmlspecialchars($achv['achievement_type']) ?></td> <!-- Здесь выводится тип достижения -->
        <td><?= htmlspecialchars($achv['achievement']) ?></td>
        <td>
            <a class="btn btn-outline-danger btn-sm" href="db_operations.php?delete_achievement=<?= $achv['achievement_id'] ?>" 
               onclick="return confirm('Вы уверены, что хотите удалить это достижение?')">
                <i class="bi bi-trash"></i>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </div>
</div>

<!-- Модальное окно добавления -->
<div class="modal fade" id="addAchievementModal" tabindex="-1" aria-labelledby="addAchievementModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAchievementModalLabel">Добавить достижение</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="achievements.php">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Студент</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="achievement_date" class="form-label">Дата</label>
                        <input type="date" class="form-control" id="achievement_date" name="achievement_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="achievement_type" class="form-label">Тип достижения</label>
                        <select class="form-select" id="achievement_type" name="achievement_type" required>
                            <option value="Достижения в общественной жизни">Достижения в общественной жизни</option>
                            <option value="Достижения в спорте">Достижения в спорте</option>
                            <option value="Достижения в творческой деятельности">Достижения в творческой деятельности</option>
                            <option value="Достижения в исследовательской деятельности">Достижения в исследовательской деятельности</option>
                            <option value="Другие достижения">Другие достижения</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="achievement" class="form-label">Достижение</label>
                        <textarea class="form-control" id="achievement" name="achievement" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Сохранить</button>
                </form>
            </div>
        </div>
    </div>
</div>


</body>
</html>
