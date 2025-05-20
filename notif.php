<?php
// Подключаем конфигурацию
include('config.php');
session_start();

// Получаем список студентов с информацией о группе, специальности и курсе
$sql = "SELECT 
    students.id,
    students.name,
    `groups`.group_name,
    specialties.name as specialty_name,
    `groups`.course
FROM students
INNER JOIN `groups` ON students.group_id = `groups`.id
INNER JOIN specialties ON `groups`.specialty_id = specialties.id";

$stmt = $pdo->prepare($sql);
try {
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($students)) {
        error_log("No students found. Check specialties table or join conditions.");
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $students = []; // Fallback to empty array to prevent fatal errors
}

// Получаем список специальностей
$sql = "SELECT id, name FROM specialties";
$stmt = $pdo->prepare($sql);
try {
    $stmt->execute();
    $specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($specialties)) {
        error_log("No specialties found. Check specialties table.");
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $specialties = []; // Fallback to empty array
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генерация уведомления о пропусках</title>

    <script src="/libs/pizzip-master/dist/pizzip.js"></script>
    <script src="/libs/docxtemplater-latest.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">

    <link rel="icon" href="logo2.png" type="image/png">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body, html { margin: 0; font-family: 'Inter', sans-serif; background-color: #f4f7fc; }
        .wrapper { display: flex; height: 100vh; }
        .content { margin-left: 260px; flex-grow: 1; padding: 20px; overflow-y: auto; }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .date-container { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; background-color: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .date-text, .time-text { color: #64748b; }
        .form-container { background-color: white; border-radius: 10px; padding: 20px; box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1); }
        .btn-generate { font-size: 1rem; padding: 0.5rem 1.5rem; background: linear-gradient(135deg, #4946e5 0%, #636ff1 100%); border: none; color: white; }
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
            border-radius: 4px;
        }
        .form-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .form-section h5 {
            margin-bottom: 15px;
            color: rgb(73, 29, 112);
        }
        .selected-students-container {
            margin-top: 10px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            max-height: 300px;
            overflow-y: auto;
        }
        .selected-student-item {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .student-info {
            flex-grow: 1;
        }
        .gender-selector {
            display: flex;
            align-items: center;
            margin-right: 10px;
        }
        .gender-btn {
            padding: 2px 8px;
            border: 1px solid #ced4da;
            background-color: #f8f9fa;
            margin: 0 2px;
            border-radius: 4px;
            cursor: pointer;
        }
        .gender-btn.active {
            background-color: rgb(83, 37, 136);
            color: white;
            border-color: #4946e5;
        }
        .remove-btn {
            color: rgb(53, 34, 129);
            cursor: pointer;
            margin-left: 10px;
        }
        .preview-item {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .preview-header {
            font-weight: bold;
            margin-bottom: 5px;
        }
        #preview-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
            max-height: 400px;
            overflow-y: auto;
        }
        .add-student-btn {
            width: 100%;
            padding: 10px;
            text-align: center;
            background-color: #e9ecef;
            border: 1px dashed #ced4da;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
        .add-student-btn:hover {
            background-color: #f1f3f5;
        }
        .generation-progress {
            margin-top: 15px;
            display: none;
        }
        .student-selection-area {
            margin-top: 15px;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <?php include('sidebar.php'); ?>
    
    <div class="content">
        <header class="top-header">
            <div class="user-info">
                <i class='bx bx-user'></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
            <div class="date-container">
                <i class="bx bx-calendar"></i>
                <span class="date-text"><?php echo date('d.m.Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Поиск...">
            </div>
        </header>

        <div class="form-container mb-5">
            <h1>Генерация уведомления о пропусках</h1>
            <p class="text-muted">Создайте уведомление о пропусках занятий для одного или нескольких студентов</p>

            <form id="generateNotificationForm">
                <!-- Дата уведомления -->
                <div class="form-group mb-3">
                    <label for="notificationDate">Дата уведомления:</label>
                    <input type="date" id="notificationDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Информация о студентах -->
                <div class="form-section mb-4">
                    <h5>Информация о студентах</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="studentCourse">Курс:</label>
                                <select id="studentCourse" class="form-select" disabled>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="specialtySelect">Специальность:</label>
                                <select id="specialtySelect" class="form-select" disabled>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?= htmlspecialchars($specialty['name']) ?>"><?= htmlspecialchars($specialty['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>Выбранные студенты:</label>
                        <div class="selected-students-container" id="selectedStudents">
                            <div class="text-center p-3 text-muted" id="noStudentsMessage">
                                Нет выбранных студентов
                            </div>
                        </div>
                    </div>

                    <!-- Интегрированный выбор студентов -->
                    <div class="student-selection-area">
                        <label>Добавить студентов:</label>
                        <div class="student-dropdown">
                            <div class="search-input-container">
                                <i class='bx bx-search search-icon'></i>
                                <input type="text" class="dropdown-search" id="studentSearch" placeholder="Поиск студента...">
                            </div>
                            <div class="dropdown-content" id="studentsList">
                                <?php foreach ($students as $student): ?>
                                    <div class="student-item" 
                                         data-id="<?= $student['id'] ?>" 
                                         data-name="<?= htmlspecialchars($student['name']) ?>" 
                                         data-group="<?= htmlspecialchars($student['group_name']) ?>" 
                                         data-specialty="<?= htmlspecialchars($student['specialty_name']) ?>" 
                                         data-course="<?= htmlspecialchars($student['course']) ?>">
                                        <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Предпросмотр уведомлений -->
                <div class="form-section mb-4">
                    <h5>Предпросмотр уведомлений</h5>
                    <div id="preview-container">
                        <div class="text-center p-3 text-muted" id="noPreviewMessage">
                            Выберите студентов для предпросмотра уведомлений
                        </div>
                    </div>

                    <div class="generation-progress" id="generationProgress">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                        <p class="text-center mt-2" id="generationStatus">Подготовка...</p>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn-add btn-primary-custom" id="generateBtn">Скачать уведомления</button>
                </div>
            </form>
        </div>
    </div>
<script>
$(document).ready(function() {
    let selectedStudents = [];
    let templateCache = null;
    
    // Загрузка шаблона заранее
    fetch("absence_template.docx")
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.arrayBuffer();
        })
        .then(data => {
            templateCache = data;
        })
        .catch(error => {
            console.error('Ошибка предварительной загрузки шаблона:', error);
        });

    // Показать/скрыть выпадающий список при клике на поле поиска
    $('#studentSearch').on('focus', function() {
        $('#studentsList').addClass('show');
    });

    // Скрыть выпадающий список при клике вне него
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.student-dropdown').length) {
            $('#studentsList').removeClass('show');
        }
    });

    // Поиск студентов
    $('#studentSearch').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $('#studentsList').addClass('show');
        $('#studentsList .student-item').each(function() {
            const studentName = $(this).data('name').toLowerCase();
            const studentGroup = $(this).data('group').toLowerCase();
            const visible = studentName.includes(searchText) || studentGroup.includes(searchText);
            $(this).toggle(visible);
        });
    });

    // Функция для определения предполагаемого пола по имени
    function guessGender(name) {
        const parts = name.split(' ');
        if (parts.length < 2) return 'male';
        
        const lastName = parts[0];
        const firstName = parts[1];
        
        if (lastName.endsWith('ова') || lastName.endsWith('ева') || 
            lastName.endsWith('ина') || lastName.endsWith('ая') ||
            firstName.endsWith('а') || firstName.endsWith('я')) {
            return 'female';
        }
        return 'male';
    }

    // Выбор студента
    $('#studentsList').on('click', '.student-item', function() {
        const studentId = $(this).data('id');
        const studentName = $(this).data('name');
        const studentGroup = $(this).data('group');
        const specialty = $(this).data('specialty');
        const course = $(this).data('course');
        
        const alreadySelected = selectedStudents.some(s => s.id === studentId);
        if (alreadySelected) {
            alert('Этот студент уже добавлен в список');
            return;
        }
        
        const guessedGender = guessGender(studentName);
        
        const newStudent = {
            id: studentId,
            name: studentName,
            group: studentGroup,
            specialty: specialty,
            course: course,
            gender: guessedGender
        };
        
        selectedStudents.push(newStudent);
        
        // Автоматически устанавливаем специальность и курс
        $('#specialtySelect').val(specialty);
        $('#studentCourse').val(course);
        
        updateSelectedStudentsList();
        updatePreview();
        
        $('#studentSearch').val('');
        $('#studentsList').removeClass('show');
    });
    
    // Обновление списка выбранных студентов
    function updateSelectedStudentsList() {
        if (selectedStudents.length > 0) {
            $('#noStudentsMessage').hide();
            const $container = $('#selectedStudents');
            $container.find('.selected-student-item').remove();
            
            selectedStudents.forEach((student, index) => {
                const $item = $(`
                    <div class="selected-student-item" data-index="${index}">
                        <div class="student-info">
                            <strong>${student.name}</strong> (${student.group})
                        </div>
                        <div class="gender-selector">
                            <div class="gender-btn male-btn ${student.gender === 'male' ? 'active' : ''}" data-gender="male">М</div>
                            <div class="gender-btn female-btn ${student.gender === 'female' ? 'active' : ''}" data-gender="female">Ж</div>
                        </div>
                        <div class="remove-btn">
                            <i class='bx bx-trash'></i>
                        </div>
                    </div>
                `);
                $container.append($item);
            });
        } else {
            $('#noStudentsMessage').show();
            $('#specialtySelect').val('');
            $('#studentCourse').val('');
        }
    }
    
    // Обработка выбора пола
    $('#selectedStudents').on('click', '.gender-btn', function() {
        const $this = $(this);
        const index = $this.closest('.selected-student-item').data('index');
        const gender = $this.data('gender');
        
        selectedStudents[index].gender = gender;
        
        $this.closest('.gender-selector').find('.gender-btn').removeClass('active');
        $this.addClass('active');
        
        updatePreview();
    });
    
    // Удаление студента из списка
    $('#selectedStudents').on('click', '.remove-btn', function() {
        const index = $(this).closest('.selected-student-item').data('index');
        selectedStudents.splice(index, 1);
        updateSelectedStudentsList();
        updatePreview();
        
        // Обновляем поля специальности и курса, если есть выбранные студенты
        if (selectedStudents.length > 0) {
            $('#specialtySelect').val(selectedStudents[0].specialty);
            $('#studentCourse').val(selectedStudents[0].course);
        }
    });

    // Обновление предпросмотра
    function updatePreview() {
        const $container = $('#preview-container');
        $container.empty();
        
        if (selectedStudents.length === 0) {
            $container.html(`
                <div class="text-center p-3 text-muted" id="noPreviewMessage">
                    Выберите студентов для предпросмотра уведомлений
                </div>
            `);
            return;
        }
        
        const date = $('#notificationDate').val();
        const formattedDate = date ? new Date(date).toLocaleDateString('ru-RU') : '';
        
        selectedStudents.forEach(student => {
            let sexText = student.gender === 'male' ? 'Ваш сын' : 'Ваша дочь';
            let sex2Text = student.gender === 'male' ? 'учащийся' : 'учащаяся';
            
            const content = `Сообщаем, что ${sexText}, ${student.name}, ${sex2Text} ${student.course} курса дневной формы получения образования специальности «${student.specialty}», допускает пропуски учебных занятий без уважительных причин.\n\tРассматривается вопрос о применении меры дисциплинарного взыскания.`;
            
            $container.append(`
                <div class="preview-item">
                    <div class="preview-header">${student.name}</div>
                    <p>${formattedDate} № 14-03/</p>
                    <p>${content}</p>
                </div>
            `);
        });
    }

    // События для обновления предпросмотра
    $('#notificationDate').on('change', updatePreview);

    // Предотвращение отправки формы
    $('#generateNotificationForm').on('submit', function(e) {
        e.preventDefault();
        return false;
    });

    // Генерация документов
    $('#generateBtn').on('click', function() {
        if (selectedStudents.length === 0) {
            alert('Пожалуйста, выберите хотя бы одного студента');
            return;
        }
        
        if (!templateCache) {
            alert('Шаблон документа еще не загружен. Пожалуйста, подождите несколько секунд и попробуйте снова.');
            return;
        }
        
        const date = $('#notificationDate').val();
        const formattedDate = date ? new Date(date).toLocaleDateString('ru-RU') : '';
        
        $('#generationProgress').show();
        const $progressBar = $('.progress-bar');
        const $generationStatus = $('#generationStatus');
        
        const zip = new JSZip();
        let processedCount = 0;
        const totalCount = selectedStudents.length;

        function saveNotificationToDatabase(student, content, type) {
            return $.ajax({
                url: 'save_notification.php',
                type: 'POST',
                data: {
                    student_id: student.id,
                    content: content,
                    type: type,
                    date_sent: $('#notificationDate').val()
                },
                dataType: 'json'
            });
        }

        const savePromises = selectedStudents.map(student => {
            let sexText = student.gender === 'male' ? 'Ваш сын' : 'Ваша дочь';
            let sex2Text = student.gender === 'male' ? 'учащийся' : 'учащаяся';
            
            const content = `Сообщаем, что ${sexText}, ${student.name}, ${sex2Text} ${student.course} курса дневной формы получения образования специальности «${student.specialty}», допускает пропуски учебных занятий без уважительных причин. Рассматривается вопрос о применении меры дисциплинарного взыскания.`;
            
            return saveNotificationToDatabase(student, content, 'absence');
        });
        
        selectedStudents.forEach(student => {
            let sex = student.gender === 'male' ? 'Ваш сын' : 'Ваша дочь';
            let sex2 = student.gender === 'male' ? 'учащийся' : 'учащаяся';
            
            const templateData = {
                date: formattedDate,
                sex: sex,
                student: student.name,
                sex2: sex2,
                course: student.course,
                speciality: student.specialty
            };
            
            const zipData = new PizZip(templateCache);
            const doc = new Docxtemplater(zipData);
            
            doc.setData(templateData);
            
            try {
                doc.render();
                const out = doc.getZip().generate({ type: "blob" });
                const lastName = student.name.split(' ')[0];
                const fileName = `uvedomlenie_propuski_${lastName}.docx`;
                
                zip.file(fileName, out);
                
                processedCount++;
                const progress = Math.round((processedCount / totalCount) * 100);
                $progressBar.css('width', `${progress}%`);
                $generationStatus.text(`Обработано ${processedCount} из ${totalCount} документов`);
                
                if (processedCount === totalCount) {
                    $generationStatus.text('Завершение и подготовка архива...');
                    
                    Promise.all(savePromises)
                        .then(() => {
                            zip.generateAsync({ type: "blob" })
                                .then(function(content) {
                                    saveAs(content, "uvedomleniya_propuski.zip");
                                    setTimeout(() => {
                                        $('#generationProgress').hide();
                                        $progressBar.css('width', '0%');
                                        alert('Документы успешно созданы и загружены.');
                                    }, 500);
                                });
                        })
                        .catch(error => {
                            console.error('Error saving notifications:', error);
                            $('#generationProgress').hide();
                            alert('Ошибка при сохранении уведомлений в базе данных');
                        });
                }
            } catch (error) {
                console.error(error);
                $('#generationProgress').hide();
                alert(`Ошибка при генерации документа для студента ${student.name}: ${error.message || 'Неизвестная ошибка'}`);
            }
        });
    });
});
</script>
</body>
</html>