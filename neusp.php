<?php
// Подключаем конфигурацию
include('config.php');
session_start();

// Получаем список студентов с их специальностями
$sql = "SELECT s.id, s.name, g.group_name, sp.id as specialty_id, sp.name as specialty 
        FROM students s 
        JOIN `groups` g ON s.group_id = g.id 
        JOIN specialties sp ON g.specialty_id = sp.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список специальностей
$sql = "SELECT id, name FROM specialties";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список учебных предметов
$sql = "SELECT id, name, specialty_id FROM subjects";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генерация приказа о взыскании</title>

    <script src="\libs\pizzip-master\dist\pizzip.js"></script>
    <script src="\libs\docxtemplater-latest.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="icon" href="logo2.png" type="image/png">
    <link rel="stylesheet" href="index.css">
    <style>
        body, html { margin: 0; font-family: 'Inter', sans-serif; background-color: #f4f7fc; }
        .wrapper { display: flex; height: 100vh; }
        .content { margin-left: 260px; flex-grow: 1; padding: 20px; overflow-y: auto; }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .date-container { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .date-text, .time-text { color: #64748b; }
        .form-container { background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); }
        .btn-generate { font-size: 1rem; padding: 0.5rem 1.5rem;  background: linear-gradient(135deg, #4946e5 0%, #636ff1 100%); border: none; color: white; }

        .student-dropdown {
            position: relative;
            width: 100%;
        }
        .dropdown-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #fff;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            border-radius: 4px;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .dropdown-content.show {
            display: block;
        }
        .student-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .student-item:hover {
            background-color: #f8f9fa;
        }
        .student-checkbox {
            margin-right: 8px;
        }
        .selected-students {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            max-height: 150px;
            overflow-y: auto;
        }
        .selected-tag {
            display: inline-block;
            background-color: #e9ecef;
            padding: 4px 8px;
            margin: 2px;
            border-radius: 4px;
        }
        .remove-tag {
            margin-left: 5px;
            cursor: pointer;
            color: #dc3545;
        }
        .search-input-container {
            position: relative;
        }
        .search-icon {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .dropdown-search {
            width: 100%;
            padding: 8px 12px 8px 35px;
            border: 1px solid #ced4da;
            border-radius: 4px 4px 0 0;
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
                <i class="bx bx-calendar"></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Поиск по достижениям...">
            </div>
        </header>

        <div class="form-container mb-5">
            <h1>Генерация приказа о взыскании</h1>
            <button class="btn btn-generate" data-bs-toggle="modal" data-bs-target="#generateOrderModal">Создать приказ</button>
        </div>
    </div>

    <!-- Модальное окно -->
    <div class="modal fade" id="generateOrderModal" tabindex="-1" aria-labelledby="generateOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- Используем modal-xl для большего размера -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateOrderModalLabel">Создание приказа о взыскании</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="generateOrderForm">
                        <!-- Секция для выбора даты приказа -->
                        <div class="form-group mb-3">
                            <label for="orderDate">Дата приказа:</label>
                            <input type="date" id="orderDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Секция для выбора студента -->
                        <div class="form-group mb-3">
                            <label for="studentSelect">Студент:</label>
                            <select id="studentSelect" class="form-select" required>
                                <option value="" disabled selected>Выберите студента</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?= $student['id'] ?>" data-specialty="<?= htmlspecialchars($student['specialty']) ?>">
                                        <?= htmlspecialchars($student['name']) ?> - <?= htmlspecialchars($student['group_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Поля, которые заполняются автоматически при выборе студента -->
                        <div class="form-group mb-3">
                            <label for="specialty">Специальность:</label>
                            <input type="text" id="specialty" class="form-control" readonly>
                        </div>

                        <!-- Секция для выбора пола студента -->
                        <div class="form-group mb-3">
                            <label for="sexSelect">Пол студента:</label>
                            <select id="sexSelect" class="form-select" required>
                                <option value="" disabled selected>Выберите пол</option>
                                <option value="son_daughter">Ваш сын / Ваша дочь</option>
                                <option value="student_sex">учащийся / учащаяся</option>
                            </select>
                        </div>

                        <!-- Секция для выбора курса -->
                        <div class="form-group mb-3">
                            <label for="courseSelect">Курс:</label>
                            <select id="courseSelect" class="form-select" required>
                                <option value="" disabled selected>Выберите курс</option>
                                <option value="1">1 курс</option>
                                <option value="2">2 курс</option>
                                <option value="3">3 курс</option>
                                <option value="4">4 курс</option>
                            </select>
                        </div>

                        <!-- Секция для выбора месяца и года -->
                        <div class="form-group mb-3">
                            <label for="monthSelect">Месяц:</label>
                            <select id="monthSelect" class="form-select" required>
                                <option value="" disabled selected>Выберите месяц</option>
                                <option value="январь">Январь</option>
                                <option value="февраль">Февраль</option>
                                <option value="март">Март</option>
                                <option value="апрель">Апрель</option>
                                <option value="май">Май</option>
                                <option value="июнь">Июнь</option>
                                <option value="июль">Июль</option>
                                <option value="август">Август</option>
                                <option value="сентябрь">Сентябрь</option>
                                <option value="октябрь">Октябрь</option>
                                <option value="ноябрь">Ноябрь</option>
                                <option value="декабрь">Декабрь</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="yearInput">Год:</label>
                            <input type="number" id="yearInput" class="form-control" value="2025" min="2000" max="2100" required>
                        </div>

                        <!-- Секция для выбора учебного предмета -->
                        <div class="form-group mb-3">
                            <label for="subjectSelect">Учебный предмет:</label>
                            <select id="subjectSelect" class="form-select" required>
                                <option value="" disabled selected>Выберите учебный предмет</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?= $subject['id'] ?>" data-specialty="<?= htmlspecialchars($subject['specialty_id']) ?>">
                                        <?= htmlspecialchars($subject['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-generate" onclick="generateOrder()">Скачать приказ</button>
                </div>
            </div>
        </div>
    </div>

<script>
let students = <?php echo json_encode($students); ?>;
let subjects = <?php echo json_encode($subjects); ?>;

$(document).ready(function() {
    $('#studentSelect').on('change', function() {
        let selectedStudentId = $(this).val();
        let selectedStudent = students.find(student => student.id == selectedStudentId);
        if (selectedStudent) {
            $('#specialty').val(selectedStudent.specialty);
        }
    });
    
    $('#studentSelect').on('change', function() {
        let specialty = students.find(x=> x.specialty_id
```php name=index.php
let specialtyId = selectedStudent.specialty_id;
        if (specialtyId) {
            $('#subjectSelect').empty().append('<option value="" disabled selected>Выберите учебный предмет</option>');
            subjects.filter(subject => subject.specialty_id == specialtyId).forEach(subject => {
                $('#subjectSelect').append(`<option value="${subject.id}">${subject.name}</option>`);
            });
        }
    });
});

// Функция генерации приказа
function generateOrder() {
    const orderDate = $('#orderDate').val();
    const studentSelect = $('#studentSelect option:selected');
    const studentName = studentSelect.text().split(' - ')[0];
    const specialty = $('#specialty').val();
    const sexSelect = $('#sexSelect').val();
    const course = $('#courseSelect').val();
    const month = $('#monthSelect').val();
    const year = $('#yearInput').val();
    const subjectSelect = $('#subjectSelect option:selected').text();

    const data = {
        date: orderDate,
        sex: sexSelect === 'son_daughter' ? 'Ваш сын' : 'Ваша дочь',
        student: studentName,
        sex2: sexSelect === 'son_daughter' ? 'учащийся' : 'учащаяся',
        course: `${course} курса`,
        speciality: specialty,
        month: month,
        year: year,
        subject: subjectSelect,
        max: 'н/а'
    };

    const templateFile = "par_template.docx";

    fetch(templateFile)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.arrayBuffer();
        })
        .then(data => {
            const zip = new PizZip(data);
            const doc = new Docxtemplater(zip);

            doc.setData(data);

            try {
                doc.render();
            } catch (error) {
                console.error(error);
                alert("Ошибка при генерации документа.");
            }

            const out = doc.getZip().generate({ type: "blob" });
            saveAs(out, "prikaz_o_vzyskanii.docx");
        })
        .catch(error => {
            console.error('Ошибка загрузки шаблона:', error);
            alert('Ошибка загрузки шаблона: ' + (error.message || error));
        });
}
</script>

</body>
</html>