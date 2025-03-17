<?php
// Подключаем конфигурацию
include('config.php');
session_start();

// Получаем список студентов
$sql = "SELECT id, name, group_name FROM students";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        .punishment-section {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.punishment-section h5 {
    margin-bottom: 15px;
    color: #4946e5;
}

.selected-students {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px;
    background-color: white;
    margin-top: 10px;
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
                    <!-- Секция для замечаний -->
                    <div class="punishment-section mb-4">
                        <h5>Замечание</h5>
                        <div class="form-group mb-3">
                            <div class="student-dropdown" aria-labelledby="warningStudentsLabel">
                                <div class="search-input-container">
                                    <i class='bx bx-search search-icon'></i>
                                    <input type="text" class="dropdown-search" id="warningStudentSearch" placeholder="Поиск студентов для замечания...">
                                </div>
                                <div class="dropdown-content" id="warningStudentsList">
                                    <?php foreach ($students as $student): ?>
                                        <div class="student-item" data-id="<?= $student['id'] ?>" data-name="<?= htmlspecialchars($student['name']) ?>" data-group="<?= htmlspecialchars($student['group_name']) ?>">
                                            <input type="checkbox" class="student-checkbox warning-student-checkbox" id="warning_student_<?= $student['id'] ?>">
                                            <label for="warning_student_<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)</label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="selected-students" id="selectedWarningStudents">
                                    <!-- Выбранные студенты для замечания -->
                                </div>
                            </div>
                        </div>
                        <div id="warningHoursContainer" class="form-group mb-3">
                            <!-- Поля для ввода часов (замечание) -->
                        </div>
                    </div>

                    <!-- Секция для выговоров -->
                    <div class="punishment-section mb-4">
                        <h5>Выговор</h5>
                        <div class="form-group mb-3">
                            <div class="student-dropdown" aria-labelledby="reprimandStudentsLabel">
                                <div class="search-input-container">
                                    <i class='bx bx-search search-icon'></i>
                                    <input type="text" class="dropdown-search" id="reprimandStudentSearch" placeholder="Поиск студентов для выговора...">
                                </div>
                                <div class="dropdown-content" id="reprimandStudentsList">
                                    <?php foreach ($students as $student): ?>
                                        <div class="student-item" data-id="<?= $student['id'] ?>" data-name="<?= htmlspecialchars($student['name']) ?>" data-group="<?= htmlspecialchars($student['group_name']) ?>">
                                            <input type="checkbox" class="student-checkbox reprimand-student-checkbox" id="reprimand_student_<?= $student['id'] ?>">
                                            <label for="reprimand_student_<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)</label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="selected-students" id="selectedReprimandStudents">
                                    <!-- Выбранные студенты для выговора -->
                                </div>
                            </div>
                        </div>
                        <div id="reprimandHoursContainer" class="form-group mb-3">
                            <!-- Поля для ввода часов (выговор) -->
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="reasonSelect">Причина взыскания:</label>
                        <select id="reasonSelect" class="form-select">
                            <option value="неявки без уважительных причин на учебные занятия">Неявки без уважительных причин на учебные занятия</option>
                            <option value="систематические опоздания на учебные занятия">Систематические опоздания на учебные занятия</option>
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
// Глобальные переменные
let students = [];
let selectedWarningStudentsIds = new Set();
let selectedReprimandStudentsIds = new Set();
const currentDate = "2025-03-17 16:51:03";
const currentUser = "polinablyaddooootaa";

$(document).ready(function() {
    students = <?php echo json_encode($students); ?>;

    // Показать выпадающий список при фокусе
    $('#warningStudentSearch, #reprimandStudentSearch').on('focus', function() {
        $(this).closest('.student-dropdown').find('.dropdown-content').addClass('show');
    });

    // Закрыть при клике вне списка
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.student-dropdown').length) {
            $('.dropdown-content').removeClass('show');
        }
    });

    // Показать список при клике на поле поиска
    $('.dropdown-search').on('click', function() {
        $(this).closest('.student-dropdown').find('.dropdown-content').addClass('show');
    });

    // Функция инициализации поиска и выбора студентов
    function initializeStudentSelection(searchId, listId, selectedIdsSet, selectedContainerId, hoursContainerId, checkboxClass) {
        // Поиск студентов
        $(`#${searchId}`).on('input', function() {
            const searchText = $(this).val().toLowerCase();
            $(`#${listId} .student-item`).each(function() {
                const studentName = $(this).data('name').toLowerCase();
                const studentGroup = $(this).data('group').toLowerCase();
                const visible = studentName.includes(searchText) || studentGroup.includes(searchText);
                $(this).toggle(visible);
            });
        });

        // Обработка выбора студента
        $(`#${listId} .student-item`).on('click', function(e) {
            if (e.target.type === 'checkbox') return;
            
            const checkbox = $(this).find(`.${checkboxClass}`);
            const studentId = $(this).data('id');
            const studentName = $(this).data('name');
            const studentGroup = $(this).data('group');
            
            checkbox.prop('checked', !checkbox.prop('checked'));
            
            if (checkbox.prop('checked')) {
                if (!selectedIdsSet.has(studentId)) {
                    // Удаляем студента из другого списка, если он там есть
                    const otherSet = selectedIdsSet === selectedWarningStudentsIds ? 
                        selectedReprimandStudentsIds : selectedWarningStudentsIds;
                    const otherContainerId = selectedContainerId === 'selectedWarningStudents' ? 
                        'selectedReprimandStudents' : 'selectedWarningStudents';
                    
                    if (otherSet.has(studentId)) {
                        otherSet.delete(studentId);
                        $(`#${otherContainerId} .selected-tag[data-id="${studentId}"]`).remove();
                        updateHoursInputs(otherSet, otherContainerId === 'selectedWarningStudents' ? 
                            'warningHoursContainer' : 'reprimandHoursContainer');
                    }
                    
                    selectedIdsSet.add(studentId);
                    $(`#${selectedContainerId}`).append(
                        `<div class="selected-tag" data-id="${studentId}">
                            ${studentName} (${studentGroup})
                            <span class="remove-tag" data-id="${studentId}">&times;</span>
                        </div>`
                    );
                    updateHoursInputs(selectedIdsSet, hoursContainerId);
                }
            } else {
                removeStudent(studentId, selectedIdsSet, selectedContainerId, hoursContainerId);
            }
        });

        // Обработка клика по чекбоксу
        $(`.${checkboxClass}`).on('change', function() {
            const studentItem = $(this).closest('.student-item');
            const studentId = studentItem.data('id');
            const studentName = studentItem.data('name');
            const studentGroup = studentItem.data('group');
            
            if ($(this).prop('checked')) {
                if (!selectedIdsSet.has(studentId)) {
                    selectedIdsSet.add(studentId);
                    $(`#${selectedContainerId}`).append(
                        `<div class="selected-tag" data-id="${studentId}">
                            ${studentName} (${studentGroup})
                            <span class="remove-tag" data-id="${studentId}">&times;</span>
                        </div>`
                    );
                    updateHoursInputs(selectedIdsSet, hoursContainerId);
                }
            } else {
                removeStudent(studentId, selectedIdsSet, selectedContainerId, hoursContainerId);
            }
        });
    }

    // Функция удаления студента
    function removeStudent(studentId, selectedIdsSet, selectedContainerId, hoursContainerId) {
        selectedIdsSet.delete(studentId);
        $(`#${selectedContainerId} .selected-tag[data-id="${studentId}"]`).remove();
        updateHoursInputs(selectedIdsSet, hoursContainerId);
    }

    // Обновление полей для ввода часов
    function updateHoursInputs(selectedIdsSet, containerId) {
        const container = $(`#${containerId}`);
        container.empty();
        
        selectedIdsSet.forEach(studentId => {
            const student = students.find(s => s.id == studentId);
            
            const studentHoursInput = $(`
                <div class="form-group mb-3">
                    <label for="hoursInput_${containerId}_${student.id}">
                        ${student.name} (группа ${student.group_name}): Количество часов пропущено:
                    </label>
                    <input type="number" id="hoursInput_${containerId}_${student.id}" 
                           class="form-control" min="1">
                </div>
            `);
            container.append(studentHoursInput);
        });
    }

    // Обработка удаления по клику на крестик
    $(document).on('click', '.remove-tag', function() {
        const studentId = $(this).data('id');
        const tagContainer = $(this).closest('.selected-students');
        const containerId = tagContainer.attr('id');
        const selectedIdsSet = containerId === 'selectedWarningStudents' ? 
            selectedWarningStudentsIds : selectedReprimandStudentsIds;
        const hoursContainerId = containerId === 'selectedWarningStudents' ? 
            'warningHoursContainer' : 'reprimandHoursContainer';
        
        removeStudent(studentId, selectedIdsSet, containerId, hoursContainerId);
        $(`#student_${studentId}`).prop('checked', false);
    });

    // Инициализация для обоих списков
    initializeStudentSelection(
        'warningStudentSearch',
        'warningStudentsList',
        selectedWarningStudentsIds,
        'selectedWarningStudents',
        'warningHoursContainer',
        'warning-student-checkbox'
    );

    initializeStudentSelection(
        'reprimandStudentSearch',
        'reprimandStudentsList',
        selectedReprimandStudentsIds,
        'selectedReprimandStudents',
        'reprimandHoursContainer',
        'reprimand-student-checkbox'
    );
});

// Функция генерации приказа
function generateOrder() {
    const warningStudents = Array.from(selectedWarningStudentsIds).map(studentId => {
        const student = students.find(s => s.id == studentId);
        const hours = $(`#hoursInput_warningHoursContainer_${student.id}`).val();

        if (!hours || hours <= 0) {
            alert(`Пожалуйста, укажите количество часов пропуска для ${student.name} (замечание)`);
            throw new Error(`Missing or invalid hours for ${student.name}`);
        }

        return {
            number: student.id,
            name: student.name,
            group: student.group_name,
            reason: $('#reasonSelect').val(),
            hours: hours,
            punishmentType: 'ЗАМЕЧАНИЕ',
            currentDate: currentDate,
            currentUser: currentUser
        };
    });

    const reprimandStudents = Array.from(selectedReprimandStudentsIds).map(studentId => {
        const student = students.find(s => s.id == studentId);
        const hours = $(`#hoursInput_reprimandHoursContainer_${student.id}`).val();

        if (!hours || hours <= 0) {
            alert(`Пожалуйста, укажите количество часов пропуска для ${student.name} (выговор)`);
            throw new Error(`Missing or invalid hours for ${student.name}`);
        }

        return {
            number: student.id,
            name: student.name,
            group: student.group_name,
            reason: $('#reasonSelect').val(),
            hours: hours,
            punishmentType: 'ВЫГОВОР',
            currentDate: currentDate,
            currentUser: currentUser
        };
    });

    if (warningStudents.length === 0 && reprimandStudents.length === 0) {
        alert("Пожалуйста, выберите хотя бы одного студента");
        return;
    }

    const templateFile = "order_template.docx";

    fetch(templateFile)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.arrayBuffer();
        })
        .then(data => {
            const zip = new PizZip(data);
            const doc = new Docxtemplater(zip);

            doc.setData({
                warningStudents: warningStudents,
                reprimandStudents: reprimandStudents,
                currentDate: currentDate,
                currentUser: currentUser
            });

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

