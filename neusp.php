<?php
// Подключаем конфигурацию
include('config.php');
session_start();

// Получаем список студентов
$sql = "SELECT 
    students.id,
    students.name,
    `groups`.group_name as group_name,
    specialties.name as specialty_name
FROM students
INNER JOIN `groups` ON students.group_id = `groups`.id
INNER JOIN specialties ON `groups`.specialty_id = specialties.id";

$stmt = $pdo->prepare($sql);
try {
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Ошибка при получении списка студентов: " . $e->getMessage());
    $students = [];
}

// Получаем список специальностей
$sql = "SELECT id, name FROM specialties";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список предметов
$sql = "SELECT id, name FROM subjects";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генерация уведомления об успеваемости</title>

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
        
        /* Стили для выпадающего списка с поиском */
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
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            border-radius: 6px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .dropdown-content.show {
            display: block;
        }
        .student-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }
        .student-item:last-child {
            border-bottom: none;
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
            color: #4946e5;
        }
        
        .selected-student {
            margin-top: 10px;
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .subject-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px 12px;
            background-color: white;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            transition: all 0.2s ease;
        }
        
        .subject-item:hover {
            background-color: #f8f9fa;
            border-color: #c6c9cc;
        }
        
        .remove-subject {
            color: #dc3545;
            cursor: pointer;
            margin-left: auto;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .remove-subject:hover {
            background-color: #f8d7da;
        }
        
        #preview-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border: 1px solid #dee2e6;
        }
        
        /* Дополнительные стили для карточек студентов */
        .student-card {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 8px;
            transition: all 0.2s ease-in-out;
            cursor: pointer;
        }

        .student-card:hover {
            background-color: #f8f9fa;
        }

        .student-card.active {
            border-color: #4946e5;
            background-color: #f0f0ff;
        }

        /* Стили для кнопок выбора пола */
        .btn-group .btn-outline-primary.active {
            background-color: #4946e5;
            color: white;
        }

        .btn-group .btn-outline-danger.active {
            background-color: #dc3545;
            color: white;
        }

        /* Стили для навигации между студентами */
        #prevStudent, #nextStudent {
            padding: 0.25rem 0.5rem;
        }

        /* Бейдж с количеством предметов */
        .badge.bg-info {
            background-color: #0dcaf0 !important;
            font-weight: normal;
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
                <span class="date-text"><?php echo date('d.m.Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Поиск...">
            </div>
        </header>

        <div class="form-container mb-5">
            <h1>Генерация уведомления об успеваемости</h1>
            <p class="text-muted">Создайте уведомление о низкой успеваемости студента</p>
            <button class="btn btn-generate" data-bs-toggle="modal" data-bs-target="#generateNotificationModal">Создать уведомление</button>
        </div>
    </div>

    <!-- Модальное окно -->
    <div class="modal fade" id="generateNotificationModal" tabindex="-1" aria-labelledby="generateNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="generateNotificationModalLabel">Уведомление о низкой успеваемости</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="generateNotificationForm">
                        <!-- Секция для выбора даты -->
                        <div class="form-group mb-3">
                            <label for="notificationDate">Дата уведомления:</label>
                            <input type="date" id="notificationDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <!-- Секция для выбора студента -->
                        <div class="form-section mb-4">
                            <h5>Информация о студентах</h5>
                            
                            <div class="form-group mb-3">
                                <label>Список студентов:</label>
                                <div class="student-dropdown">
                                    <div class="search-input-container">
                                        <i class='bx bx-search search-icon'></i>
                                        <input type="text" class="dropdown-search" id="studentSearch" placeholder="Добавить студента...">
                                    </div>
                                    <div class="dropdown-content" id="studentsList">
    <?php foreach ($students as $student): ?>
        <div class="student-item" 
             data-id="<?= $student['id'] ?>" 
             data-name="<?= htmlspecialchars($student['name']) ?>" 
             data-group="<?= htmlspecialchars($student['group_name']) ?>"
             data-specialty="<?= htmlspecialchars($student['specialty_name']) ?>">
            <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)
        </div>
    <?php endforeach; ?>
</div>
                                    <div class="selected-student" id="selectedStudent">
                                        <!-- Выбранные студенты будут здесь -->
                                        <div class="p-2 text-muted">Студенты не выбраны</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="studentGender" class="d-block">Пол текущего студента:</label>
                                        <div class="btn-group" role="group" aria-label="Выберите пол">
                                            <button type="button" id="genderMaleBtn" class="btn btn-outline-primary active">
                                                <i class='bx bx-male-sign'></i> Мужской
                                            </button>
                                            <button type="button" id="genderFemaleBtn" class="btn btn-outline-danger">
                                                <i class='bx bx-female-sign'></i> Женский
                                            </button>
                                        </div>
                                        <select id="studentGender" class="form-select d-none">
                                            <option value="male">Мужской</option>
                                            <option value="female">Женский</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="studentCourse">Курс:</label>
                                        <select id="studentCourse" class="form-select">
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3 d-none">
    <label for="specialtySelect">Специальность:</label>
    <select id="specialtySelect" class="form-select">
        <?php foreach ($specialties as $specialty): ?>
            <option value="<?= htmlspecialchars($specialty['name']) ?>"><?= htmlspecialchars($specialty['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<!-- Добавим отображение специальности -->
<div class="form-group mb-3">
    <label>Специальность:</label>
    <div id="specialtyDisplay" class="form-control-plaintext">
        <!-- Здесь будет отображаться специальность -->
    </div>
</div>

                        <!-- Секция для выбора периода -->
                        <div class="form-section mb-4">
                            <h5>Период оценки успеваемости</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="assessmentMonth">Месяц:</label>
                                        <select id="assessmentMonth" class="form-select">
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
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="assessmentYear">Год:</label>
                                        <input type="number" id="assessmentYear" class="form-control" value="<?php echo date('Y'); ?>" min="2000" max="2100">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Секция для предметов с низкой успеваемостью -->
                        <div class="form-section mb-4">
                            <h5>Предметы с низкой успеваемостью</h5>
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label for="subjectSelect">Выберите предмет:</label>
                                        <select id="subjectSelect" class="form-select">
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?= htmlspecialchars($subject['name']) ?>"><?= htmlspecialchars($subject['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="subjectGrade">Оценка:</label>
                                        <select id="subjectGrade" class="form-select">
                                            <option value="н/а">н/а</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-primary mb-3" id="addSubjectBtn">+</button>
                                </div>
                            </div>

                            <div id="selectedSubjects" class="mb-3">
                                <!-- Список выбранных предметов -->
                            </div>
                        </div>

                        <!-- Предпросмотр уведомления -->
                        <div class="form-section mb-4">
                            <h5 class="d-flex justify-content-between align-items-center">
                                <span>Предпросмотр уведомления</span>
                                <div class="btn-group">
                                    <button id="prevStudent" class="btn btn-sm btn-outline-secondary"><i class='bx bx-chevron-left'></i></button>
                                    <button id="nextStudent" class="btn btn-sm btn-outline-secondary"><i class='bx bx-chevron-right'></i></button>
                                </div>
                            </h5>
                            <div id="preview-container">
                                <p><span id="preview-date"></span> № 14-03/</p>
                                <p id="preview-content"></p>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                    <button type="button" class="btn btn-outline-primary me-2" id="generateAllBtn">Скачать все уведомления</button>
                    <button type="button" class="btn btn-generate" id="generateBtn">Скачать уведомление</button>
                </div>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    let selectedStudents = [];
    let currentStudentIndex = -1;
    let templateCache = null;
    
    // Установка текущей даты в формате YYYY-MM-DD
    const currentDate = '2025-04-13';
    $('#notificationDate').val(currentDate);

    // Предварительная загрузка шаблона
    fetch("par_template.docx")
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

    // Показать выпадающий список студентов при фокусе
    $('#studentSearch').on('focus', function() {
        $('#studentsList').addClass('show');
    });

    // Закрыть выпадающий список при клике вне него
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.student-dropdown').length) {
            $('.dropdown-content').removeClass('show');
        }
    });

    // Поиск студентов
    $('#studentSearch').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $('#studentsList .student-item').each(function() {
            const studentName = $(this).data('name').toLowerCase();
            const studentGroup = $(this).data('group').toLowerCase();
            const visible = studentName.includes(searchText) || studentGroup.includes(searchText);
            $(this).toggle(visible);
        });
    });

    // Функция определения пола по имени
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
$('#studentsList .student-item').on('click', function() {
    const studentId = $(this).data('id');
    const studentName = $(this).data('name');
    const studentGroup = $(this).data('group');
    const specialty = $(this).data('specialty');
    
    // Отладочный вывод
    console.log('Selected student:', {
        id: studentId,
        name: studentName,
        group: studentGroup,
        specialty: specialty
    });
    
    const exists = selectedStudents.some(student => student.id === studentId);
    if (exists) {
        alert('Этот студент уже добавлен в список');
        $('#studentsList').removeClass('show');
        $('#studentSearch').val('');
        return;
    }
    
    const newStudent = {
        id: studentId,
        name: studentName,
        group: studentGroup,
        specialty: specialty, // Убедитесь, что это значение не undefined
        gender: guessGender(studentName),
        subjects: []
    };
    
    selectedStudents.push(newStudent);
    currentStudentIndex = selectedStudents.length - 1;
    
    // Обновляем отображение специальности
    $('#specialtyDisplay').text(specialty || 'Специальность не указана');
    
    updateStudentsList();
    showStudentSubjects(currentStudentIndex);
    updateGenderSelection();
    
    $('#studentsList').removeClass('show');
    $('#studentSearch').val('');
});

    // Обновление списка выбранных студентов
    function updateStudentsList() {
        $('#selectedStudent').empty();
        
        if (selectedStudents.length === 0) {
            $('#selectedStudent').html('<div class="p-2 text-muted">Студенты не выбраны</div>');
            return;
        }
        
        selectedStudents.forEach((student, index) => {
            const isActive = index === currentStudentIndex ? 'active bg-light' : '';
            const genderIcon = student.gender === 'male' ? 
                '<i class="bx bx-male-sign text-primary"></i>' : 
                '<i class="bx bx-female-sign text-danger"></i>';
            
            $('#selectedStudent').append(`
                <div class="p-2 d-flex align-items-center justify-content-between student-card ${isActive}" data-index="${index}">
                    <div>
                        ${genderIcon} <strong>${student.name}</strong> 
                        <small class="text-muted">(${student.group})</small>
                    </div>
                    <div>
                        <span class="badge bg-info">${student.subjects.length} предметов</span>
                        <button class="btn btn-sm btn-outline-danger delete-student ms-2" data-index="${index}">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
            `);
        });
    }

    // Переключение между студентами
    $(document).on('click', '.student-card', function(e) {
        if ($(e.target).hasClass('delete-student') || $(e.target).closest('.delete-student').length) {
            return;
        }
        
        const index = $(this).data('index');
        currentStudentIndex = index;
        
        const student = selectedStudents[currentStudentIndex];
        $('#specialtyDisplay').text(student.specialty);
        
        updateStudentsList();
        showStudentSubjects(index);
        updateGenderSelection();
    });

    // Удаление студента
    $(document).on('click', '.delete-student', function(e) {
        e.stopPropagation();
        const index = $(this).data('index');
        
        selectedStudents.splice(index, 1);
        
        if (selectedStudents.length === 0) {
            currentStudentIndex = -1;
            clearSubjectsList();
            $('#specialtyDisplay').text('');
        } else {
            currentStudentIndex = Math.min(index, selectedStudents.length - 1);
            showStudentSubjects(currentStudentIndex);
            $('#specialtyDisplay').text(selectedStudents[currentStudentIndex].specialty);
        }
        
        updateStudentsList();
        updatePreview();
    });

    // Показать предметы выбранного студента
    function showStudentSubjects(index) {
        if (index < 0 || index >= selectedStudents.length) {
            clearSubjectsList();
            return;
        }
        
        const student = selectedStudents[index];
        
        updateGenderSelection();
        $('#specialtyDisplay').text(student.specialty);
        
        $('#selectedSubjects').empty();
        
        student.subjects.forEach((item, subIndex) => {
            $('#selectedSubjects').append(`
                <div class="subject-item">
                    <div>${item.subject} - ${item.grade}</div>
                    <div class="remove-subject" data-index="${subIndex}">
                        <i class='bx bx-x'></i>
                    </div>
                </div>
            `);
        });
        
        updatePreview();
    }

    // Обновление выбора пола
    function updateGenderSelection() {
        if (currentStudentIndex >= 0 && currentStudentIndex < selectedStudents.length) {
            $('#studentGender').val(selectedStudents[currentStudentIndex].gender);
            
            $('#genderMaleBtn').removeClass('active');
            $('#genderFemaleBtn').removeClass('active');
            
            if (selectedStudents[currentStudentIndex].gender === 'male') {
                $('#genderMaleBtn').addClass('active');
            } else {
                $('#genderFemaleBtn').addClass('active');
            }
        }
    }

    // Визуальный выбор пола
    $('#genderMaleBtn').on('click', function() {
        if (currentStudentIndex >= 0) {
            selectedStudents[currentStudentIndex].gender = 'male';
            $('#studentGender').val('male').trigger('change');
            updateGenderSelection();
            updatePreview();
        }
    });
    
    $('#genderFemaleBtn').on('click', function() {
        if (currentStudentIndex >= 0) {
            selectedStudents[currentStudentIndex].gender = 'female';
            $('#studentGender').val('female').trigger('change');
            updateGenderSelection();
            updatePreview();
        }
    });

    // Стандартный выбор пола (hidden select)
    $('#studentGender').on('change', function() {
        if (currentStudentIndex >= 0) {
            selectedStudents[currentStudentIndex].gender = $(this).val();
            updateGenderSelection();
            updatePreview();
        }
    });

    // Очистка списка предметов
    function clearSubjectsList() {
        $('#selectedSubjects').empty();
    }

    // Добавление предмета
    $('#addSubjectBtn').on('click', function() {
        if (currentStudentIndex < 0) {
            alert('Пожалуйста, выберите студента перед добавлением предметов');
            return;
        }
        
        const subject = $('#subjectSelect').val();
        const grade = $('#subjectGrade').val();
        
        const exists = selectedStudents[currentStudentIndex].subjects.some(item => item.subject === subject);
        if (exists) {
            alert('Этот предмет уже добавлен в список');
            return;
        }
        
        selectedStudents[currentStudentIndex].subjects.push({
            subject: subject,
            grade: grade
        });
        
        showStudentSubjects(currentStudentIndex);
        updatePreview();
    });

    // Удаление предмета
    $(document).on('click', '.remove-subject', function() {
        const index = $(this).data('index');
        
        if (currentStudentIndex >= 0) {
            selectedStudents[currentStudentIndex].subjects.splice(index, 1);
            showStudentSubjects(currentStudentIndex);
            updatePreview();
        }
    });

    // Обновление предпросмотра
    function updatePreview() {
        const date = $('#notificationDate').val();
        const formattedDate = date ? new Date(date).toLocaleDateString('ru-RU') : '';
        $('#preview-date').text(formattedDate);
        
        if (currentStudentIndex < 0 || selectedStudents.length === 0) {
            $('#preview-content').text('Выберите студента для просмотра уведомления.');
            return;
        }
        
        const student = selectedStudents[currentStudentIndex];
        
        if (student.subjects.length === 0) {
            $('#preview-content').text('Добавьте предметы с низкой успеваемостью.');
            return;
        }
        
        const course = $('#studentCourse').val();
        const month = $('#assessmentMonth').val();
        const year = $('#assessmentYear').val();
        
        let sexText = student.gender === 'male' ? 'Ваш сын' : 'Ваша дочь';
        let sex2Text = student.gender === 'male' ? 'учащийся' : 'учащаяся';
        
        let subjectsText = student.subjects
            .map(item => `«${item.subject}» - ${item.grade}`)
            .join(', ');
        
        const content = `Сообщаем, что ${sexText}, ${student.name}, ${sex2Text} ${course} курса дневной формы получения образования специальности «${student.specialty}», по результатам текущей успеваемости за ${month} ${year} года имеет средний балл ниже трех по следующим учебным предметам: ${subjectsText}.`;
        
        $('#preview-content').text(content);
    }

    // События для обновления предпросмотра при изменении полей
    $('#notificationDate, #studentCourse, #assessmentMonth, #assessmentYear').on('change', updatePreview);

    // Навигация между студентами
    $('#prevStudent, #nextStudent').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!selectedStudents.length) return;
        
        if (this.id === 'prevStudent') {
            currentStudentIndex = (currentStudentIndex - 1 + selectedStudents.length) % selectedStudents.length;
        } else {
            currentStudentIndex = (currentStudentIndex + 1) % selectedStudents.length;
        }
        
        updateStudentsList();
        showStudentSubjects(currentStudentIndex);
        
        const student = selectedStudents[currentStudentIndex];
        $('#specialtyDisplay').text(student.specialty);
    });

    // Генерация одного документа
    $('#generateBtn').on('click', function() {
        if (currentStudentIndex < 0 || !selectedStudents.length) {
            alert('Пожалуйста, выберите студента');
            return;
        }
        
        const student = selectedStudents[currentStudentIndex];
        
        if (!student.subjects.length) {
            alert('Добавьте предметы с низкой успеваемостью');
            return;
        }
        
        if (!templateCache) {
            alert('Шаблон документа еще не загружен. Пожалуйста, подождите несколько секунд и попробуйте снова.');
            return;
        }

        const date = $('#notificationDate').val();
        const course = $('#studentCourse').val();
        const month = $('#assessmentMonth').val();
        const year = $('#assessmentYear').val();

        const templateData = {
            date: date ? new Date(date).toLocaleDateString('ru-RU') : '',
            sex: student.gender === 'male' ? 'Ваш сын' : 'Ваша дочь',
            student: student.name,
            sex2: student.gender === 'male' ? 'учащийся' : 'учащаяся',
            course: course,
            speciality: student.specialty,
            month: month,
            year: year,
            subject: student.subjects.map(s => `«${s.subject}»`).join(', '),
            max: student.subjects[student.subjects.length - 1].grade
        };

        try {
            const zip = new PizZip(templateCache);
            const doc = new Docxtemplater(zip);
            doc.setData(templateData);
            doc.render();
            
            const out = doc.getZip().generate({ type: "blob" });
            const fileName = `uvedomlenie_uspevaemost_${student.name.replace(/\s+/g, '_')}.docx`;
            
            saveAs(out, fileName);
        } catch (error) {
            console.error('Ошибка при генерации документа:', error);
            alert(`Ошибка при генерации документа для ${student.name}`);
        }
    });

    // Генерация всех документов
    $('#generateAllBtn').on('click', function() {
        if (!selectedStudents.length) {
            alert('Пожалуйста, выберите хотя бы одного студента');
            return;
        }
        
        if (!templateCache) {
            alert('Шаблон документа еще не загружен. Пожалуйста, подождите несколько секунд и попробуйте снова.');
            return;
        }

        const date = $('#notificationDate').val();
        const course = $('#studentCourse').val();
        const month = $('#assessmentMonth').val();
        const year = $('#assessmentYear').val();
        
        const zip = new JSZip();
        
        selectedStudents.forEach(student => {
            if (!student.subjects.length) {
                alert(`У студента ${student.name} нет выбранных предметов`);
                return;
            }

            const templateData = {
                date: date ? new Date(date).toLocaleDateString('ru-RU') : '',
                sex: student.gender === 'male' ? 'Ваш сын' : 'Ваша дочь',
                student: student.name,
                sex2: student.gender === 'male' ? 'учащийся' : 'учащаяся',
                course: course,
                speciality: student.specialty,
                month: month,
                year: year,
                subject: student.subjects.map(s => `«${s.subject}»`).join(', '),
                max: student.subjects[student.subjects.length - 1].grade
            };

            try {
                const docZip = new PizZip(templateCache);
                const doc = new Docxtemplater(docZip);
                doc.setData(templateData);
                doc.render();
                
                const out = doc.getZip().generate({ type: "blob" });
                const fileName = `uvedomlenie_uspevaemost_${student.name.replace(/\s+/g, '_')}.docx`;
                
                zip.file(fileName, out);
            } catch (error) {
                console.error('Ошибка при генерации документа:', error);
                alert(`Ошибка при генерации документа для ${student.name}`);
            }
        });

        zip.generateAsync({ type: "blob" })
            .then(function(content) {
                saveAs(content, "uvedomleniya_uspevaemost.zip");
            })
            .catch(function(error) {
                console.error('Ошибка при создании архива:', error);
                alert('Ошибка при создании архива с документами');
            });
    });
});
</script>

</body>
</html>