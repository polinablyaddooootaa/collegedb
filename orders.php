<?php
include('config.php');
session_start();

// Ensure user is authenticated
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

try {
    // Set PDO to throw exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch students
    $sql_students = "SELECT s.id, s.name, COALESCE(g.group_name, 'Без группы') as group_name 
                    FROM students s 
                    LEFT JOIN `groups` g ON s.group_id = g.id";
    $stmt_students = $pdo->prepare($sql_students);
    $stmt_students->execute();
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

    // Log if no data is returned
    if (empty($students)) {
        error_log("No students found in the database.");
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("Ошибка подключения к базе данных: " . htmlspecialchars($e->getMessage()));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Генерация приказа о дисциплинарном взыскании</title>
    <script src="/libs/pizzip-master/dist/pizzip.js"></script>
    <script src="/libs/docxtemplater-latest.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="icon" href="logo2.png" type="image/png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/orders.css">
    <style>
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .dropdown-content.show {
            display: block;
        }
        .student-item {
            padding: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .student-item:hover {
            background-color: #f5f5f5;
        }
        .selected-tag {
            display: inline-flex;
            align-items: center;
            background-color: #e9ecef;
            padding: 5px 10px;
            margin: 5px;
            border-radius: 4px;
        }
        .remove-tag {
            margin-left: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .search-input-container {
            position: relative;
        }
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .dropdown-search {
            padding-left: 35px;
        }
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .punishment-section {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                <i class='bx bx-calendar'></i>
                <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                <span class="time-text"><?php echo date('H:i'); ?></span>
            </div>
            <div class="search-container">
                <input type="text" class="search-bar" placeholder="Поиск по достижениям...">
            </div>
        </header>

        <div class="form-container">
            <h1 class="page-title">Генерация приказа о дисциплинарном взыскании</h1>
            
            <?php if (empty($students)): ?>
                <div class="alert alert-warning">Нет студентов в базе данных. Пожалуйста, добавьте студентов.</div>
            <?php endif; ?>

            <form id="generateOrderForm">
                <div class="form-group mb-4">
                    <label for="orderDate">Дата приказа:</label>
                    <input type="date" id="orderDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    <div id="orderDateError" class="error-message"></div>
                </div>

                <div class="form-group mb-4">
                    <label for="absenceMonth">Месяц пропуска:</label>
                    <select id="absenceMonth" class="form-select" required>
                        <option value="январе">Январь</option>
                        <option value="феврале">Февраль</option>
                        <option value="марте">Март</option>
                        <option value="апреле">Апрель</option>
                        <option value="мае">Май</option>
                        <option value="июне">Июнь</option>
                        <option value="июле">Июль</option>
                        <option value="августе">Август</option>
                        <option value="сентябре">Сентябрь</option>
                        <option value="октябре">Октябрь</option>
                        <option value="ноябре">Ноябрь</option>
                        <option value="декабре">Декабрь</option>
                    </select>
                    <div id="absenceMonthError" class="error-message"></div>
                </div>

                <div class="form-group mb-4">
                    <label for="absenceYear">Год пропуска:</label>
                    <input type="number" id="absenceYear" class="form-control" value="2025" min="2000" max="2100" required>
                    <div id="absenceYearError" class="error-message"></div>
                </div>

                <div class="punishment-section mb-4">
                    <h5>Замечание</h5>
                    <div class="form-group mb-3">
                        <div class="student-dropdown" aria-labelledby="studentLabel">
                            <div class="search-input-container">
                                <i class='bx bx-search search-icon'></i>
                                <input type="text" class="dropdown-search form-control" id="studentSearch" placeholder="Поиск студентов для замечания...">
                            </div>
                            <div class="dropdown-content" id="studentList">
                                <?php foreach ($students as $student): ?>
                                    <div class="student-item" 
                                         data-id="<?= $student['id'] ?>" 
                                         data-name="<?= htmlspecialchars($student['name']) ?>" 
                                         data-group="<?= htmlspecialchars($student['group_name']) ?>">
                                        <input type="checkbox" 
                                               class="student-checkbox" 
                                               id="student_<?= $student['id'] ?>">
                                        <label for="student_<?= $student['id'] ?>">
                                            <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="selected-students" id="selectedStudents"></div>
                        </div>
                        <div id="studentSelectError" class="error-message"></div>
                    </div>
                    <div id="hoursContainer" class="form-group mb-3"></div>
                </div>

                <div class="form-footer">
                    <button type="button" class="btn btn-primary" onclick="generateOrder()">Скачать приказ</button>
                </div>
            </form>
        </div>
    </div>

<script>
// Global variables
let students = <?php echo json_encode($students); ?>;
let selectedStudentIds = new Set();

// Function to infer gender from name
function inferGender(name) {
    const lastChar = name.trim().slice(-1).toLowerCase();
    return (lastChar === 'а' || lastChar === 'я') ? 'учащейся' : 'учащемуся';
}

$(document).ready(function() {
    // Debug: Log students array
    console.log('Students loaded:', students);

    // Ensure group_name is always defined
    students = students.map(student => ({
        ...student,
        group_name: student.group_name || 'Без группы'
    }));

    // Show dropdown on focus or click
    $('#studentSearch').on('focus click', function(e) {
        e.stopPropagation();
        $('#studentList').addClass('show');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.student-dropdown').length) {
            $('#studentList').removeClass('show');
        }
    });

    // Search students
    $('#studentSearch').on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $('#studentList .student-item').each(function() {
            const studentName = $(this).data('name').toLowerCase();
            const studentGroup = $(this).data('group').toLowerCase();
            const visible = studentName.includes(searchText) || studentGroup.includes(searchText);
            $(this).toggle(visible);
        });
    });

    // Handle student selection
    $('#studentList .student-item').on('click', function(e) {
        if (e.target.type === 'checkbox') return;
        
        const checkbox = $(this).find('.student-checkbox');
        const studentId = $(this).data('id');
        const studentName = $(this).data('name');
        const studentGroup = $(this).data('group');
        const gender = inferGender(studentName);
        
        checkbox.prop('checked', !checkbox.prop('checked'));
        
        if (checkbox.prop('checked')) {
            if (!selectedStudentIds.has(studentId)) {
                selectedStudentIds.add(studentId);
                $('#selectedStudents').append(
                    `<div class="selected-tag" data-id="${studentId}">
                        ${studentName} (${studentGroup}, ${gender})
                        <span class="remove-tag" data-id="${studentId}">×</span>
                    </div>`
                );
                updateHoursInputs();
            }
        } else {
            removeStudent(studentId);
        }
    });

    // Handle checkbox change
    $('.student-checkbox').on('change', function() {
        const studentItem = $(this).closest('.student-item');
        const studentId = studentItem.data('id');
        const studentName = studentItem.data('name');
        const studentGroup = studentItem.data('group');
        const gender = inferGender(studentName);
        
        if ($(this).prop('checked')) {
            if (!selectedStudentIds.has(studentId)) {
                selectedStudentIds.add(studentId);
                $('#selectedStudents').append(
                    `<div class="selected-tag" data-id="${studentId}">
                        ${studentName} (${studentGroup}, ${gender})
                        <span class="remove-tag" data-id="${studentId}">×</span>
                    </div>`
                );
                updateHoursInputs();
            }
        } else {
            removeStudent(studentId);
        }
    });

    // Function to remove student
    function removeStudent(studentId) {
        selectedStudentIds.delete(studentId);
        $(`#selectedStudents .selected-tag[data-id="${studentId}"]`).remove();
        updateHoursInputs();
        $(`#student_${studentId}`).prop('checked', false);
    }

    // Update hours inputs
    function updateHoursInputs() {
        const container = $('#hoursContainer');
        container.empty();
        
        selectedStudentIds.forEach(studentId => {
            const student = students.find(s => s.id == studentId);
            const studentHoursInput = $(`
                <div class="form-group mb-3">
                    <label for="hoursInput_${student.id}">
                        ${student.name} (группа ${student.group_name}): Количество часов пропущено:
                    </label>
                    <input type="number" id="hoursInput_${student.id}" 
                           class="form-control" min="1" required>
                    <div id="hoursInput_${student.id}_Error" class="error-message"></div>
                </div>
            `);
            container.append(studentHoursInput);
        });
    }

    // Handle remove tag
    $(document).on('click', '.remove-tag', function() {
        const studentId = $(this).data('id');
        removeStudent(studentId);
    });
});

// Generate order function
function generateOrder() {
    // Clear previous errors
    $('.error-message').text('');

    // Validate inputs
    const orderDate = $('#orderDate').val();
    if (!orderDate) {
        $('#orderDateError').text('Пожалуйста, выберите дату приказа');
        return;
    }

    const absenceMonth = $('#absenceMonth').val();
    if (!absenceMonth) {
        $('#absenceMonthError').text('Пожалуйста, выберите месяц пропуска');
        return;
    }

    const absenceYear = $('#absenceYear').val();
    if (!absenceYear || absenceYear < 2000 || absenceYear > 2100) {
        $('#absenceYearError').text('Пожалуйста, введите корректный год');
        return;
    }

    // Collect students
    const selectedStudents = Array.from(selectedStudentIds).map((studentId, index) => {
        const student = students.find(s => s.id == studentId);
        if (!student) {
            alert('Ошибка: один из выбранных студентов не найден');
            throw new Error('Student not found');
        }
        const hours = $(`#hoursInput_${studentId}`).val();
        if (!hours || hours <= 0) {
            $(`#hoursInput_${studentId}_Error`).text('Пожалуйста, укажите количество часов (>0)');
            throw new Error(`Missing or invalid hours for ${student.name}`);
        }
        return {
            number: index + 1,
            name: student.name,
            sex: inferGender(student.name),
            group: student.group_name,
            hours: hours,
            month: absenceMonth,
            year: absenceYear
        };
    });

    // Validate students
    if (selectedStudents.length === 0) {
        $('#studentSelectError').text('Пожалуйста, выберите хотя бы одного студента');
        return;
    }

    const dateObj = new Date(orderDate);
    const year = dateObj.getFullYear();
    const formattedDate = dateObj.toLocaleDateString('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    }).split('.').join('.');

    const templateFile = "prikaz_template.docx";

    console.log('Generating document with data:', {
        date: formattedDate,
        year: year,
        students: selectedStudents
    });

    fetch(templateFile)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.arrayBuffer();
        })
        .then(data => {
            const zip = new PizZip(data);
            const doc = new Docxtemplater(zip, {
                paragraphLoop: true,
                linebreaks: true
            });

            doc.setData({
                date: formattedDate,
                year: year,
                students: selectedStudents
            });

            try {
                doc.render();
            } catch (error) {
                console.error('Rendering error:', error);
                alert("Ошибка при генерации документа: " + error.message);
                return;
            }

            const out = doc.getZip().generate({ type: "blob" });
            saveAs(out, `prikaz_${formattedDate}.docx`);
        })
        .catch(error => {
            console.error('Template loading error:', error);
            alert('Ошибка загрузки шаблона: ' + error.message);
        });
}
</script>
</body>
</html>