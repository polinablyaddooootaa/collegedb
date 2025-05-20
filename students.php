<?php
session_start();

// Подключаем конфигурацию и операции с базой данных
include('config.php');
require_once 'db_operations.php';
include('functions.php');

// Количество студентов на странице
$studentsPerPage = 10;

// Определяем текущую страницу и сортировку
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) $currentPage = 1;
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortOrder = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';

// Проверяем допустимые столбцы для сортировки
$allowedSortColumns = ['id', 'name', 'group_name', 'brsm', 'volunteer'];
if (!in_array($sortColumn, $allowedSortColumns)) {
    $sortColumn = 'id';
}

// Маппинг столбцов для SQL-запроса
$sortColumnMap = [
    'id' => 'students.id',
    'name' => 'students.name',
    'group_name' => 'groups.group_name',
    'brsm' => '(SELECT COUNT(*) FROM brsm WHERE brsm.student_id = students.id)',
    'volunteer' => '(SELECT COUNT(*) FROM volunteers WHERE volunteers.student_id = students.id)'
];

// Получаем общее количество студентов
$sqlCount = "SELECT COUNT(*) FROM students";
$stmt = $pdo->prepare($sqlCount);
$stmt->execute();
$totalStudents = $stmt->fetchColumn();

// Вычисляем общее количество страниц
$totalPages = ceil($totalStudents / $studentsPerPage);

// Вычисляем смещение для SQL-запроса
$offset = ($currentPage - 1) * $studentsPerPage;

// Получаем список студентов с информацией о группах для текущей страницы с учетом сортировки
$sql = "SELECT students.*, `groups`.group_name 
        FROM students 
        LEFT JOIN `groups` ON students.group_id = `groups`.id
        ORDER BY " . $sortColumnMap[$sortColumn] . " " . strtoupper($sortOrder) . " LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $studentsPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Удаление студента
if (isset($_GET['delete_student'])) {
    $id = $_GET['delete_student'];
    $sql = "DELETE FROM students WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $redirectUrl = "students.php?page=$currentPage&sort=$sortColumn&order=$sortOrder";
    header("Location: $redirectUrl");
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
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo2.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .pagination-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        .pagination .page-item.active .page-link {
            background-color:rgb(63, 20, 133);
         
            color: white;
        }
        .pagination .page-link {
            color:rgb(63, 20, 133);
        }
        .pagination .page-link:hover {
            background-color: #e5e7eb;
            color:rgb(63, 20, 133);
        }
        /* Стили для сортировки */
        .sortable {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
              color:rgb(63, 20, 133);
            transition: color 0.3s ease;
            text-decoration: none; /* Убирает подчеркивание */
        }
        .sortable:hover {
            color:rgb(94, 78, 119);
        }
        .sortable a {
              color:rgb(63, 20, 133);
        text-decoration: none; /* Убедитесь, что подчеркивание убрано для <a> */
    }
    .sortable a:hover {
        
        text-decoration: none; /* Убирает подчеркивание при наведении */
    }
        .sort-icon {
            font-size: 1rem;
            color:rgb(25, 10, 53);
        }
        .sort-icon.active {
            color:rgb(63, 20, 133);
        }
    
    </style>
</head>
<body>
<?php include('sidebar.php'); ?>

<div class="content">
    <!-- Верхний заголовок -->
    <header class="top-header">
         <div class="user-info">
                    <i class='bx bx-user'></i>
                    <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Гость'); ?></span>
                </div>
                <div class="date-container">
                    <i class='bx bx-calendar'></i>
                    <span class="date-text"><?php echo date('m/d/Y'); ?></span>
                    <span class="time-text"><?php echo date('H:i'); ?></span>
                </div>
                <div class="search-container">
                    <input type="text" class="search-bar" placeholder="Поиск по студентам">
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
                <a href="export_excel.php" class="btn-add btn-primary-custom" id="exportBtn">
                    <i class='bx bx-download'></i> Экспорт в Excel
                </a>
                <button class="btn-add btn-primary-custom" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class='bx bx-upload'></i> Импорт из Excel
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <?php
                        // Функция для генерации URL сортировки
                        function getSortUrl($column, $currentSort, $currentOrder) {
                            $order = ($currentSort === $column && $currentOrder === 'asc') ? 'desc' : 'asc';
                            return "students.php?sort=$column&order=$order";
                        }
                        ?>
                        <th>
                            <a href="<?= getSortUrl('id', $sortColumn, $sortOrder) ?>" class="sortable">
                                ID
                                <i class='bx <?= $sortColumn === 'id' ? ($sortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down') : 'bx-chevron-up' ?> sort-icon <?= $sortColumn === 'id' ? 'active' : '' ?>'></i>
                            </a>
                        </th>
                        <th>
                            <a href="<?= getSortUrl('name', $sortColumn, $sortOrder) ?>" class="sortable">
                                ФИО
                                <i class='bx <?= $sortColumn === 'name' ? ($sortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down') : 'bx-chevron-up' ?> sort-icon <?= $sortColumn === 'name' ? 'active' : '' ?>'></i>
                            </a>
                        </th>
                        <th>
                            <a href="<?= getSortUrl('group_name', $sortColumn, $sortOrder) ?>" class="sortable">
                                Группа
                                <i class='bx <?= $sortColumn === 'group_name' ? ($sortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down') : 'bx-chevron-up' ?> sort-icon <?= $sortColumn === 'group_name' ? 'active' : '' ?>'></i>
                            </a>
                        </th>
                        <th>
                            <a href="<?= getSortUrl('brsm', $sortColumn, $sortOrder) ?>" class="sortable">
                                БРСМ
                                <i class='bx <?= $sortColumn === 'brsm' ? ($sortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down') : 'bx-chevron-up' ?> sort-icon <?= $sortColumn === 'brsm' ? 'active' : '' ?>'></i>
                            </a>
                        </th>
                        <th>
                            <a href="<?= getSortUrl('volunteer', $sortColumn, $sortOrder) ?>" class="sortable">
                                Волонтер
                                <i class='bx <?= $sortColumn === 'volunteer' ? ($sortOrder === 'asc' ? 'bx-chevron-up' : 'bx-chevron-down') : 'bx-chevron-up' ?> sort-icon <?= $sortColumn === 'volunteer' ? 'active' : '' ?>'></i>
                            </a>
                        </th>
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

        <!-- Пагинация -->
        <div class="pagination-container">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="students.php?page=<?= $currentPage - 1 ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>" aria-label="Previous">
                            <span aria-hidden="true">«</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="students.php?page=<?= $i ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="students.php?page=<?= $currentPage + 1 ?>&sort=<?= $sortColumn ?>&order=<?= $sortOrder ?>" aria-label="Next">
                            <span aria-hidden="true">»</span>
                        </a>
                    </li>
                </ul>
            </nav>
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
 <script src="js/students.js"></script>
       <?php
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            echo "<script>document.addEventListener('DOMContentLoaded', function() {
                showNotification('" . addslashes($notification['message']) . "', '" . $notification['type'] . "');
            });</script>";
            unset($_SESSION['notification']);
        }
        ?>


</body>
</html>