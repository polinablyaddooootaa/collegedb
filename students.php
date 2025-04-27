<?php

session_start();

// Подключаем конфигурацию и операции с базой данных
include('config.php');
require_once 'db_operations.php';
include('functions.php');

// Получаем список студентов с информацией о группах
$sql = "SELECT students.*, `groups`.group_name 
        FROM students 
        LEFT JOIN `groups` ON students.group_id = `groups`.id
        ORDER BY students.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Удаление студента
if (isset($_GET['delete_student'])) {
    $id = $_GET['delete_student'];
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: students.php");
    exit;
}

// Получение списка групп
$sql = "SELECT * FROM `groups` ORDER BY group_name";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getBrsmStatus($student_id, $pdo) {
    $sql = "SELECT COUNT(*) FROM brsm WHERE student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn() > 0 ? 'Состоит' : 'Не состоит';
}

function getVolunteerStatus($student_id, $pdo) {
    $sql = "SELECT COUNT(*) FROM volunteers WHERE student_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_id]);
    return $stmt->fetchColumn() > 0 ? 'Активен' : 'Не участвует';
}



?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Студенты</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="index.css"> <!-- Подключение стилей -->
    <link rel="stylesheet" href="style.css"> <!-- Подключение стилей -->
    <link rel="icon" href="logo2.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
  
</head>
<body>

<?php include('sidebar.php'); ?>

<div class="content">
    <!-- Верхний заголовок -->
    <header class="top-header">
        <div class="user-info">
            <i class='bx bx-user'></i>
            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
        </div>
        <div class="date-container">
            <i class='bx bx-calendar'></i>
            <span class="date-text"><?php echo date('d.m.Y'); ?></span>
            <i class='bx bx-time' style="margin-left: 10px;"></i>
            <span class="time-text"><?php echo date('H:i'); ?></span>
        </div>
        <div class="search-container">
            <input type="text" class="search-bar" id="studentSearch" placeholder="Поиск по студенту...">
        </div>
    </header>

    <!-- Контейнер для таблицы студентов -->
    <div class="table-container">
        <div class="table-header">
            <h2>Список студентов</h2>
            <div class="btn-group">
                <button class="btn-add btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                    <i class='bx bx-plus'></i> Добавить студента
                </button>
                <a href="export_excel.php" class="btn-add btn-success-custom" id="exportBtn">
                    <i class='bx bx-download'></i> Экспорт в Excel
                </a>
                <button class="btn-add btn-info-custom" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class='bx bx-upload'></i> Импорт из Excel
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Группа</th>
                        <th>БРСМ</th>
                        <th>Волонтер</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $index => $student): ?>
                    <tr style="animation-delay: <?php echo $index * 0.05; ?>s">
                        <td><?= htmlspecialchars($student['id']) ?></td>
                        <td><?= htmlspecialchars($student['name']) ?></td>
                        <td><?= htmlspecialchars($student['group_name']) ?></td>
                        <td>
                            <span class="status-badge <?= getBrsmStatus($student['id'], $pdo) == 'Состоит' ? 'status-yes' : 'status-no' ?>">
                                <?= htmlspecialchars(getBrsmStatus($student['id'], $pdo)) ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?= getVolunteerStatus($student['id'], $pdo) == 'Активен' ? 'status-yes' : 'status-neutral' ?>">
                                <?= htmlspecialchars(getVolunteerStatus($student['id'], $pdo)) ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn edit-btn" data-bs-toggle="modal" data-bs-target="#editStudentModal"
                                    data-id="<?= $student['id'] ?>"
                                    data-name="<?= htmlspecialchars($student['name']) ?>"
                                    data-group-id="<?= htmlspecialchars($student['group_id']) ?>"
                                    data-group="<?= htmlspecialchars($student['group_name']) ?>"
                                    data-brsm="<?= getBrsmStatus($student['id'], $pdo) == 'Состоит' ? '1' : '0' ?>"
                                    data-volunteer="<?= getVolunteerStatus($student['id'], $pdo) == 'Активен' ? '1' : '0' ?>">
                                    <i class='bx bx-edit'></i>
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?= $student['id'] ?>)">
                                    <i class='bx bx-trash'></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Модальное окно добавления студента -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addStudentModalLabel">Добавить студента</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="add_student" value="1">
                    <div class="mb-3">
                        <label class="form-label">ФИО студента</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Выберите группу</label>
                        <select class="form-select" name="group_id" required>
                            <option value="" disabled selected>Выберите группу</option>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['id'] ?>">
                                    <?= htmlspecialchars($group['group_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="checkbox-container">
                        <div class="checkbox-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="brsm" name="brsm">
                                <label class="form-check-label" for="brsm">Состоит в БРСМ</label>
                            </div>
                        </div>
                        <div class="checkbox-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="volunteer" name="volunteer">
                                <label class="form-check-label" for="volunteer">Активный волонтер</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-modal btn-submit">
                        <i class='bx bx-plus-circle me-2'></i> Добавить студента
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования студента -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">Редактировать студента</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="edit_student" value="1">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label class="form-label">ФИО студента</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Группа</label>
                        <select class="form-select" id="edit_group_id" name="group_id" required>
                            <?php foreach ($groups as $group): ?>
                                <option value="<?= $group['id'] ?>">
                                    <?= htmlspecialchars($group['group_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="checkbox-container">
                        <div class="checkbox-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_brsm" name="brsm">
                                <label class="form-check-label" for="edit_brsm">Состоит в БРСМ</label>
                            </div>
                        </div>
                        <div class="checkbox-item">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_volunteer" name="volunteer">
                                <label class="form-check-label" for="edit_volunteer">Активный волонтер</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-modal btn-submit">
                        <i class='bx bx-save me-2'></i> Сохранить изменения
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно импорта Excel -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Импорт из Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="import_excel.php" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="excelFile" class="form-label">Выберите файл Excel</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="excelFile" name="excelFile" accept=".xlsx" required>
                            <label for="excelFile" class="input-group-text btn-primary-custom" style="cursor: pointer;">
                                <i class='bx bx-file me-1'></i> Обзор
                            </label>
                        </div>
                        <div class="form-text mt-2">
                            <i class='bx bx-info-circle me-1'></i> Поддерживаются файлы формата .xlsx
                        </div>
                    </div>
                    <button type="submit" class="btn btn-modal btn-submit">
                        <i class='bx bx-upload me-2'></i> Импортировать данные
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Подтверждение удаления</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <i class='bx bx-error-circle' style="font-size: 4rem; color: #EF4444; margin-bottom: 1rem;"></i>
                <p>Вы действительно хотите удалить этого студента?</p>
                <p class="text-muted small">Это действие нельзя будет отменить</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Удалить</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Добавляем анимацию для строк таблицы
    document.addEventListener('DOMContentLoaded', function() {
        // Задержка появления строк таблицы
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            setTimeout(() => {
                row.style.opacity = '1';
            }, index * 50);
        });
        
        // Время на часах в реальном времени
        setInterval(() => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.querySelector('.time-text').textContent = hours + ':' + minutes;
        }, 1000);
        
        // Инициализация Select2 для выбора групп
        if ($.fn.select2) {
            $('.form-select').select2({
                width: '100%',
                dropdownParent: $('.modal-body'),
                placeholder: "Выберите группу...",
                allowClear: true
            });
        }
        
        // Поиск по таблице
        document.getElementById('studentSearch').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchText)) {
                    row.style.display = '';
                    // Подсветка найденного текста (опционально)
                    if (searchText.length > 0) {
                        row.classList.add('highlight');
                    } else {
                        row.classList.remove('highlight');
                    }
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    // Функция для отображения уведомлений
    function showNotification(message, type = 'success', title = '') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        
        // Заголовки по умолчанию в зависимости от типа
        if (!title) {
            if (type === 'success') title = 'Успешно!';
            else if (type === 'error') title = 'Ошибка!';
            else if (type === 'info') title = 'Информация';
        }
        
        // Set notification content
        notification.innerHTML = `
            <div class="notification-icon">
                ${type === 'success' ? '<i class="bx bx-check"></i>' : 
                 type === 'error' ? '<i class="bx bx-x"></i>' : 
                 '<i class="bx bx-info-circle"></i>'}
            </div>
            <div class="notification-content">
                <div class="notification-title">${title}</div>
                <div class="notification-message">${message}</div>
            </div>
            <button class="notification-close">
                <i class="bx bx-x"></i>
            </button>
        `;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Add event listener to close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 500);
        });
        
        // Trigger animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 500);
            }
        }, 5000);
    }
    
    // Код для отображения уведомлений из сессии
    <?php
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        echo "document.addEventListener('DOMContentLoaded', function() {
            showNotification('" . addslashes($notification['message']) . "', '" . $notification['type'] . "');
        });";
        
        // Удаляем уведомление из сессии после отображения
        unset($_SESSION['notification']);
    }
    ?>
    
    // Функция для заполнения формы редактирования
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            const studentId = this.dataset.id;
            const studentName = this.dataset.name;
            const studentGroupId = this.dataset.groupId;
            const studentBrsm = this.dataset.brsm === '1';
            const studentVolunteer = this.dataset.volunteer === '1';
            
            document.getElementById('edit_id').value = studentId;
            document.getElementById('edit_name').value = studentName;
            
            // Установка значения выпадающего списка групп
            const groupSelect = document.getElementById('edit_group_id');
            for (let i = 0; i < groupSelect.options.length; i++) {
                if (groupSelect.options[i].value === studentGroupId) {
                    groupSelect.selectedIndex = i;
                    break;
                }
            }
            
            // Обновление Select2 если используется
            if ($.fn.select2) {
                $('#edit_group_id').trigger('change');
            }
            
            document.getElementById('edit_brsm').checked = studentBrsm;
            document.getElementById('edit_volunteer').checked = studentVolunteer;
        });
    });
    
    // Функция подтверждения удаления
    function confirmDelete(id) {
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        document.getElementById('confirmDeleteBtn').href = `students.php?delete_student=${id}`;
        deleteModal.show();
    }
    
    // Проверка перед экспортом
    document.getElementById('exportBtn').addEventListener('click', function(e) {
        // Проверяем, если таблица пуста
        let rows = document.querySelectorAll('table tbody tr');
        if (rows.length === 0) {
            e.preventDefault(); // Отменяем действие по умолчанию (перенаправление)
            showNotification('Нет данных для экспорта!', 'error');
        } else {
            // Показываем уведомление об успешном экспорте
            setTimeout(() => {
                showNotification('Файл успешно экспортирован!', 'success');
            }, 1000);
        }
    });
    
    // Эффект наведения для кнопок
    document.querySelectorAll('.btn-add').forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.classList.add('pulse');
        });
        
        btn.addEventListener('mouseleave', function() {
            this.classList.remove('pulse');
        });
    });
</script>

</body>
</html>