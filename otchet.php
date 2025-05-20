
<?php

// Подключаем конфигурацию
include('config.php');
session_start();
// Получаем список студентов и их достижения, сгруппированные по категориям
$sql = "SELECT students.id, students.name,
        GROUP_CONCAT(CASE WHEN achievement_type = 'Достижения в общественной жизни' THEN achievement END) AS public_achievements,
        GROUP_CONCAT(CASE WHEN achievement_type = 'Достижения в спорте' THEN achievement END) AS sports_achievements,
        GROUP_CONCAT(CASE WHEN achievement_type = 'Достижения в творческой деятельности' THEN achievement END) AS creative_achievements,
        GROUP_CONCAT(CASE WHEN achievement_type = 'Достижения в исследовательской деятельности' THEN achievement END) AS research_achievements,
        GROUP_CONCAT(CASE WHEN achievement_type = 'Другие достижения' THEN achievement END) AS another_achievements,
        GROUP_CONCAT(CASE WHEN achievement_type = 'Достижения в конкурсах профессионального мастерства и технического творчества' THEN achievement END) AS master_achievements
    FROM achievements
    JOIN students ON achievements.student_id = students.id
    GROUP BY students.id";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генерация сертификата</title>

    <script src="\libs\pizzip-master\dist\pizzip.js"></script>
    <script src="\libs\docxtemplater-latest.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="index.css">

    <link rel="stylesheet" href="style.css">
    <style>

         
    </style>
       <link rel="icon" href="logo2.png" type="image/png">
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
                <i class="bx bx-calendar"></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
            <div class="search-container">
                    <input type="text" class="search-bar" placeholder="Поиск по достижениям...">
                </div>
        </header>

        <div class="form-container mb-5">
            <h1>Генерация сертификата</h1>

            <div class="form-group mb-3">
                <label for="studentSelect">Выберите ученика:</label>
                <select id="studentSelect" class="form-select">
                    <option value="">-- Выберите ученика --</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="issueDate">Дата выдачи:</label>
                <input type="date" id="issueDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <button onclick="generateWord()" class="btn-add btn-primary-custom">Сгенерировать сертификат</button>
        </div>

        <div class="form-container">
            <h1>Генерация сертификатов для группы</h1>

            <div class="form-group mb-3">
                <label for="groupStudentSelect">Выберите учеников:</label>
                <select id="groupStudentSelect" class="form-select" multiple>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-3">
                <label for="groupIssueDate">Дата выдачи:</label>
                <input type="date" id="groupIssueDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <button onclick="generateGroupCertificates()" class="btn-add btn-primary-custom">Сгенерировать сертификаты</button>
        </div>
    </div>
    
<script>
        const students = <?php echo json_encode($students); ?>;
    </script>
   
    <script src="js/otchet.js"></script>
</body>
</html>