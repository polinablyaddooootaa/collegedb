<?php
// Подключаем конфигурацию
include('config.php');
session_start();

// Получаем список студентов
$sql = "SELECT 
    students.id,
    students.name,
    `groups`.group_name as group_name,
    specialties.name as specialty_name,
    `groups`.course
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">

    <link rel="icon" href="logo2.png" type="image/png">
    <link rel="stylesheet" href="neusp.css">
    <link rel="stylesheet" href="style.css">
    <style>
      
    </style>
</head>
<body>

<?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->

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
        <h1>Генерация уведомления об успеваемости</h1>
        <p class="text-muted">Создайте уведомление о низкой успеваемости студента</p>

        <form id="generateNotificationForm">
            <!-- Дата уведомления -->
            <div class="form-group mb-3">
                <label for="notificationDate">Дата уведомления:</label>
                <input type="date" id="notificationDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
            </div>

            <!-- Студенты -->
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
                                data-specialty="<?= htmlspecialchars($student['specialty_name']) ?>"
                                data-course="<?= htmlspecialchars($student['course']) ?>">
                                <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="selected-student" id="selectedStudent">
                        <div class="p-2 text-muted">Студенты не выбраны</div>
                    </div>
                </div>
            </div>

            <div class="row">
              
                <div class="col-md-6">
                    <label for="studentCourse">Курс:</label>
                    <select id="studentCourse" class="form-select mb-3" disabled>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
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
            <div class="form-group mb-3">
                <label>Специальность:</label>
                <div id="specialtyDisplay" class="form-control-plaintext"></div>
            </div>

            <!-- Период -->
            <h5>Период оценки успеваемости</h5>
            <div class="row">
                <div class="col-md-6">
                    <label for="assessmentMonth">Месяц:</label>
                    <select id="assessmentMonth" class="form-select mb-3">
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
                <div class="col-md-6">
                    <label for="assessmentYear">Год:</label>
                    <input type="number" id="assessmentYear" class="form-control mb-3" value="<?php echo date('Y'); ?>" min="2000" max="2100">
                </div>
            </div>

            <!-- Предметы -->
            <h5>Предметы с низкой успеваемостью</h5>
            <div class="row mb-3">
                <div class="col-md-8">
                    <label for="subjectSelect">Выберите предмет:</label>
                    <select id="subjectSelect" class="form-select">
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= htmlspecialchars($subject['name']) ?>"><?= htmlspecialchars($specialty['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="subjectGrade">Оценка:</label>
                    <select id="subjectGrade" class="form-select">
                        <option value="н/а">н/а</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-primary" id="addSubjectBtn">+</button>
                </div>
            </div>
            <div id="selectedSubjects" class="mb-3"></div>

            <!-- Предпросмотр -->
            <h5 class="d-flex justify-content-between align-items-center">
                <span>Предпросмотр уведомления</span>
                <div class="btn-group">
                    <button id="prevStudent" class="btn btn-sm btn-outline-secondary"><i class='bx bx-chevron-left'></i></button>
                    <button id="nextStudent" class="btn btn-sm btn-outline-secondary"><i class='bx bx-chevron-right'></i></button>
                </div>
            </h5>
            <div id="preview-container" class="mb-3">
                <p><span id="preview-date"></span> № 14-03/</p>
                <p id="preview-content"></p>
            </div>

            <!-- Кнопки -->
            <div class="d-flex justify-content-end">
                <button type="button" class="btn-add btn-primary-custom" id="generateAllBtn">Скачать все уведомления</button>
            </div>
        </form>
    </div>
</div>
</body>

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
        const course = $(this).data('course');
        
        // Отладочный вывод
        console.log('Selected student:', {
            id: studentId,
            name: studentName,
            group: studentGroup,
            specialty: specialty,
            course: course
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
            specialty: specialty,
            course: course,
            gender: guessGender(studentName),
            subjects: []
        };
        
        selectedStudents.push(newStudent);
        currentStudentIndex = selectedStudents.length - 1;
        
        // Обновляем отображение специальности и курса
        $('#specialtyDisplay').text(specialty || 'Специальность не указана');
        $('#studentCourse').val(course);
        
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
        $('#studentCourse').val(student.course);
        
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
            $('#studentCourse').val('');
        } else {
            currentStudentIndex = Math.min(index, selectedStudents.length - 1);
            showStudentSubjects(currentStudentIndex);
            $('#specialtyDisplay').text(selectedStudents[currentStudentIndex].specialty);
            $('#studentCourse').val(selectedStudents[currentStudentIndex].course);
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
        $('#studentCourse').val(student.course);
        
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
        
        const course = student.course; // Use the student's course from the group
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
    $('#notificationDate, #assessmentMonth, #assessmentYear').on('change', updatePreview);

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
        $('#studentCourse').val(student.course);
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
        const course = student.course;
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
                course: student.course,
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