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

                <div class="punishment-section mb-4">
                    <h5>Замечание</h5>
                    <div class="form-group mb-3">
                        <div class="student-dropdown" aria-labelledby="studentLabel">
                            <div class="search-input-container">
                                <i class='bx bx-search search-icon'></i>
                                <input type="text" class="dropdown-search form-control" id="warningStudentSearch" placeholder="Поиск студентов для замечания...">
                            </div>
                            <div class="dropdown-content" id="warningStudentList">
                                <?php foreach ($students as $student): ?>
                                    <div class="student-item" 
                                         data-id="<?= $student['id'] ?>" 
                                         data-name="<?= htmlspecialchars($student['name']) ?>" 
                                         data-group="<?= htmlspecialchars($student['group_name']) ?>">
                                        <input type="checkbox" 
                                               class="student-checkbox" 
                                               id="warning_student_<?= $student['id'] ?>">
                                        <label for="warning_student_<?= $student['id'] ?>">
                                            <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="selected-students" id="selectedWarningStudents"></div>
                        </div>
                        <div id="warningStudentSelectError" class="error-message"></div>
                    </div>
                    <div id="warningReasonContainer" class="form-group mb-3"></div>
                </div>

                <div class="punishment-section mb-4">
                    <h5>Выговор</h5>
                    <div class="form-group mb-3">
                        <div class="student-dropdown" aria-labelledby="reprimandStudentLabel">
                            <div class="search-input-container">
                                <i class='bx bx-search search-icon'></i>
                                <input type="text" class="dropdown-search form-control" id="reprimandStudentSearch" placeholder="Поиск студентов для выговора...">
                            </div>
                            <div class="dropdown-content" id="reprimandStudentList">
                                <?php foreach ($students as $student): ?>
                                    <div class="student-item" 
                                         data-id="<?= $student['id'] ?>" 
                                         data-name="<?= htmlspecialchars($student['name']) ?>" 
                                         data-group="<?= htmlspecialchars($student['group_name']) ?>">
                                        <input type="checkbox" 
                                               class="student-checkbox" 
                                               id="reprimand_student_<?= $student['id'] ?>">
                                        <label for="reprimand_student_<?= $student['id'] ?>">
                                            <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="selected-students" id="selectedReprimandStudents"></div>
                        </div>
                        <div id="reprimandStudentSelectError" class="error-message"></div>
                    </div>
                    <div id="reprimandReasonContainer" class="form-group mb-3"></div>
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
let selectedWarningStudentIds = new Set();
let selectedReprimandStudentIds = new Set();

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

    // Set up warning students dropdown
    setupStudentDropdown(
        '#warningStudentSearch',
        '#warningStudentList',
        selectedWarningStudentIds,
        '#selectedWarningStudents',
        'warning_student_',
        updateWarningReasonInputs
    );

    // Set up reprimand students dropdown
    setupStudentDropdown(
        '#reprimandStudentSearch',
        '#reprimandStudentList',
        selectedReprimandStudentIds,
        '#selectedReprimandStudents',
        'reprimand_student_',
        updateReprimandReasonInputs
    );
});

// Function to set up student dropdowns
function setupStudentDropdown(searchSelector, listSelector, selectedIds, selectedContainerSelector, idPrefix, updateCallback) {
    // Show dropdown on focus or click
    $(searchSelector).on('focus click', function(e) {
        e.stopPropagation();
        $(listSelector).addClass('show');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest(searchSelector).length && !$(e.target).closest(listSelector).length) {
            $(listSelector).removeClass('show');
        }
    });

    // Search students
    $(searchSelector).on('input', function() {
        const searchText = $(this).val().toLowerCase();
        $(`${listSelector} .student-item`).each(function() {
            const studentName = $(this).data('name').toLowerCase();
            const studentGroup = $(this).data('group').toLowerCase();
            const visible = studentName.includes(searchText) || studentGroup.includes(searchText);
            $(this).toggle(visible);
        });
    });

    // Handle student selection
    $(`${listSelector} .student-item`).on('click', function(e) {
        if (e.target.type === 'checkbox') return;
        
        const checkbox = $(this).find('.student-checkbox');
        const studentId = $(this).data('id');
        const studentName = $(this).data('name');
        const studentGroup = $(this).data('group');
        const gender = inferGender(studentName);
        
        checkbox.prop('checked', !checkbox.prop('checked'));
        
        if (checkbox.prop('checked')) {
            if (!selectedIds.has(studentId)) {
                selectedIds.add(studentId);
                $(selectedContainerSelector).append(
                    `<div class="selected-tag" data-id="${studentId}">
                        ${studentName} (${studentGroup}, ${gender})
                        <span class="remove-tag" data-id="${studentId}">×</span>
                    </div>`
                );
                updateCallback();
            }
        } else {
            removeStudent(studentId, selectedIds, selectedContainerSelector, idPrefix, updateCallback);
        }
    });

    // Handle checkbox change
    $(`${listSelector} .student-checkbox`).on('change', function() {
        const studentItem = $(this).closest('.student-item');
        const studentId = studentItem.data('id');
        const studentName = studentItem.data('name');
        const studentGroup = studentItem.data('group');
        const gender = inferGender(studentName);
        
        if ($(this).prop('checked')) {
            if (!selectedIds.has(studentId)) {
                selectedIds.add(studentId);
                $(selectedContainerSelector).append(
                    `<div class="selected-tag" data-id="${studentId}">
                        ${studentName} (${studentGroup}, ${gender})
                        <span class="remove-tag" data-id="${studentId}">×</span>
                    </div>`
                );
                updateCallback();
            }
        } else {
            removeStudent(studentId, selectedIds, selectedContainerSelector, idPrefix, updateCallback);
        }
    });

    // Handle remove tag
    $(document).on('click', `${selectedContainerSelector} .remove-tag`, function() {
        const studentId = $(this).data('id');
        removeStudent(studentId, selectedIds, selectedContainerSelector, idPrefix, updateCallback);
    });
}

// Function to remove student
function removeStudent(studentId, selectedIds, containerSelector, idPrefix, updateCallback) {
    selectedIds.delete(studentId);
    $(`${containerSelector} .selected-tag[data-id="${studentId}"]`).remove();
    updateCallback();
    $(`#${idPrefix}${studentId}`).prop('checked', false);
}

// Update warning reason inputs
function updateWarningReasonInputs() {
    const container = $('#warningReasonContainer');
    container.empty();
    
    selectedWarningStudentIds.forEach(studentId => {
        const student = students.find(s => s.id == studentId);
        if (!student) return;
        
        const studentReasonInput = $(`
            <div class="form-group mb-3">
                <label for="warningReason_${student.id}">
                    ${student.name} (группа ${student.group_name}): Причина замечания:
                </label>
                <input type="text" id="warningReason_${student.id}" 
                       class="form-control" required
                       placeholder="например, за неявки на учебные занятия без уважительных причин">
                <div id="warningReason_${student.id}_Error" class="error-message"></div>
            </div>
        `);
        container.append(studentReasonInput);
    });
}

// Update reprimand reason inputs
function updateReprimandReasonInputs() {
    const container = $('#reprimandReasonContainer');
    container.empty();
    
    selectedReprimandStudentIds.forEach(studentId => {
        const student = students.find(s => s.id == studentId);
        if (!student) return;
        
        const studentReasonInput = $(`
            <div class="form-group mb-3">
                <label for="reprimandReason_${student.id}">
                    ${student.name} (группа ${student.group_name}): Причина выговора:
                </label>
                <input type="text" id="reprimandReason_${student.id}" 
                       class="form-control" required
                       placeholder="например, за систематические пропуски занятий">
                <div id="reprimandReason_${student.id}_Error" class="error-message"></div>
            </div>
        `);
        container.append(studentReasonInput);
    });
}

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

    // Format date for display
    const dateObj = new Date(orderDate);
    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const year = dateObj.getFullYear();
    const formattedDate = `${day}.${month}.${year}`;

    // Check if at least one student is selected
    if (selectedWarningStudentIds.size === 0 && selectedReprimandStudentIds.size === 0) {
        $('#warningStudentSelectError').text('Пожалуйста, выберите хотя бы одного студента для замечания или выговора');
        return;
    }

    // Collect warning students
    const warningStudents = [];
    let hasWarningError = false;
    
    // Convert Set to Array for proper indexing
    const warningStudentIdArray = Array.from(selectedWarningStudentIds);
    
    warningStudentIdArray.forEach((studentId, index) => {
        const student = students.find(s => s.id == studentId);
        if (!student) {
            alert('Ошибка: один из выбранных студентов не найден');
            hasWarningError = true;
            return;
        }
        
        const reason = $(`#warningReason_${studentId}`).val();
        if (!reason) {
            $(`#warningReason_${studentId}_Error`).text('Пожалуйста, укажите причину замечания');
            hasWarningError = true;
            return;
        }
        
        warningStudents.push({
            number: index + 1, // Sequential number starting from 1
            name: student.name,
            sex: inferGender(student.name),
            group: student.group_name,
            reason: reason
        });
    });
    
    if (hasWarningError) return;

    // Collect reprimand students
    const reprimandStudents = [];
    let hasReprimandError = false;
    
    // Convert Set to Array for proper indexing
    const reprimandStudentIdArray = Array.from(selectedReprimandStudentIds);
    
    reprimandStudentIdArray.forEach((studentId, index) => {
        const student = students.find(s => s.id == studentId);
        if (!student) {
            alert('Ошибка: один из выбранных студентов не найден');
            hasReprimandError = true;
            return;
        }
        
        const reason = $(`#reprimandReason_${studentId}`).val();
        if (!reason) {
            $(`#reprimandReason_${studentId}_Error`).text('Пожалуйста, укажите причину выговора');
            hasReprimandError = true;
            return;
        }
        
        reprimandStudents.push({
            number: index + 1, // Sequential number starting from 1
            name: student.name,
            sex: inferGender(student.name),
            group: student.group_name,
            reason: reason
        });
    });
    
    if (hasReprimandError) return;

    const templateFile = "prikaz_template.docx";

    console.log('Generating document with data:', {
        date: formattedDate,
        year: year,
        warningStudents: warningStudents,
        reprimandStudents: reprimandStudents
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
                warningStudents: warningStudents,
                reprimandStudents: reprimandStudents
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