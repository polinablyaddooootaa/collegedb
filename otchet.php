<?php
// Подключаем конфигурацию
include('config.php');

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
    <link rel="stylesheet" href="index.css">
    <style>
        body, html { margin: 0; font-family: 'Inter', sans-serif; background-color: #f4f7fc; }
        .wrapper { display: flex; height: 100vh; }
        .content { margin-left: 260px; flex-grow: 1; padding: 20px; overflow-y: auto; }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .date-container { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .date-text, .time-text { color: #64748b; }
        .form-container { background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); }
        .btn-generate { font-size: 1rem; padding: 0.5rem 1.5rem; background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); border: none; color: white; }
        .btn-generate:hover { background: linear-gradient(135deg, #fda085 0%, #f6d365 100%); }
    </style>
</head>
<body>

    <?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->
    
    <div class="content">
        <header class="top-header">
            <div class="date-container">
                <i class="bx bx-calendar"></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
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

            <button onclick="generateWord()" class="btn btn-generate">Сгенерировать сертификат</button>
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

            <button onclick="generateGroupCertificates()" class="btn btn-generate">Сгенерировать сертификаты</button>
        </div>
    </div>

    <script>
        const students = <?php echo json_encode($students); ?>;

        function generateWord() {
            const studentId = document.getElementById('studentSelect').value;
            const issueDate = document.getElementById('issueDate').value;

            if (!studentId || !issueDate) {
                alert("Пожалуйста, выберите ученика и укажите дату выдачи.");
                return;
            }

            const student = students.find(s => s.id == studentId);

            // Форматирование даты в российский формат
            const formattedDate = new Date(issueDate).toLocaleDateString('ru-RU');

            const templateFile = "/serf.docx"; // Укажите путь к шаблону

            fetch(templateFile)
                .then(response => response.arrayBuffer())
                .then(data => {
                    const zip = new PizZip(data);
                    const doc = new Docxtemplater(zip);

                    doc.setData({
                        first_name: student.name,
                        issue_date: formattedDate, // Новый тег для даты выдачи
                        public_achievements: student.public_achievements || 'Нет достижений',
                        sports_achievements: student.sports_achievements || 'Нет достижений',
                        creative_achievements: student.creative_achievements || 'Нет достижений',
                        research_achievements: student.research_achievements || 'Нет достижений',
                        another_achievements: student.another_achievements || 'Нет достижений',
                        master_achievements: student.master_achievements || 'Нет достижений'
                    });

                    try {
                        doc.render();
                    } catch (error) {
                        console.error(error);
                        alert("Ошибка при генерации документа.");
                    }

                    const out = doc.getZip().generate({ type: "blob" });
                    saveAs(out, "sertifikat.docx");
                })
                .catch(error => {
                    console.error('Ошибка загрузки шаблона:', error);
                    alert('Ошибка загрузки шаблона: ' + (error.message || error));
                });
        }

        function generateGroupCertificates() {
            const selectedStudentIds = Array.from(document.getElementById('groupStudentSelect').selectedOptions).map(option => option.value);
            const issueDate = document.getElementById('groupIssueDate').value;

            if (selectedStudentIds.length === 0 || !issueDate) {
                alert("Пожалуйста, выберите учеников и укажите дату выдачи.");
                return;
            }

            const templateFile = "/serf.docx"; // Укажите путь к шаблону

            selectedStudentIds.forEach(studentId => {
                const student = students.find(s => s.id == studentId);

                // Форматирование даты в российский формат
                const formattedDate = new Date(issueDate).toLocaleDateString('ru-RU');

                fetch(templateFile)
                    .then(response => response.arrayBuffer())
                    .then(data => {
                        const zip = new PizZip(data);
                        const doc = new Docxtemplater(zip);

                        doc.setData({
                            first_name: student.name,
                            issue_date: formattedDate, // Новый тег для даты выдачи
                            public_achievements: student.public_achievements || 'Нет достижений',
                            sports_achievements: student.sports_achievements || 'Нет достижений',
                            creative_achievements: student.creative_achievements || 'Нет достижений',
                            research_achievements: student.research_achievements || 'Нет достижений',
                            another_achievements: student.another_achievements || 'Нет достижений',
                            master_achievements: student.master_achievements || 'Нет достижений'
                        });

                        try {
                            doc.render();
                        } catch (error) {
                            console.error(error);
                            alert("Ошибка при генерации документа.");
                        }

                        const out = doc.getZip().generate({ type: "blob" });
                        saveAs(out, student.name + "_sertifikat.docx");
                    })
                    .catch(error => {
                        console.error('Ошибка загрузки шаблона:', error);
                        alert('Ошибка загрузки шаблона: ' + (error.message || error));
                    });
            });
        }
    </script>

</body>
</html>