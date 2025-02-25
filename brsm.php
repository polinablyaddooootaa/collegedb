<?php
// Подключаем конфигурацию
include('config.php');

// Тестовый запрос для проверки соединения (для отладки, потом можно убрать)
try {
    $stmtTest = $pdo->query("SELECT NOW() AS now");
    $rowTest = $stmtTest->fetch(PDO::FETCH_ASSOC);
    // echo "Соединение установлено. Текущее время: " . $rowTest['now'];
} catch (PDOException $e) {
    die("Ошибка запроса: " . $e->getMessage());
}

// Получаем список студентов, состоящих в БРСМ
$sql = "SELECT s.*, b.date_joined 
        FROM students s
        INNER JOIN brsm b ON s.id = b.student_id
        ORDER BY s.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список всех студентов для заполнения выпадающего списка
$sqlAll = "SELECT id, name, group_name FROM students ORDER BY name ASC";
$stmtAll = $pdo->prepare($sqlAll);
$stmtAll->execute();
$allStudents = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Члены БРСМ</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="index.css"> <!-- Подключение стилей -->
  <style>
    body, html {
      margin: 0;
      font-family: 'Inter', sans-serif;
      background-color: #f4f7fc;
    }
    .wrapper {
      display: flex;
      height: 100vh;
    }
    /* Стили для основного контента */
    .content {
      margin-left: 260px;
      flex-grow: 1;
      padding: 20px;
      overflow-y: auto;
    }
    .top-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    .date-container {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    .date-text, .time-text {
      color: #64748b;
    }
    .search-container input {
      padding: 0.75rem 1rem;
      width: 400px;
      border-radius: 0.75rem;
      border: 1px solid #e2e8f0;
    }
    .table-container {
      background-color: white;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }
    .table th {
      background-color: #f1f3f9;
      text-transform: uppercase;
    }
    .status-badge {
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 0.9rem;
      font-weight: bold;
    }
    .status-yes {
      background-color: #d4edda;
      color: #155724;
    }
    .status-no {
      background-color: #f8d7da;
      color: #721c24;
    }
    .btn-add {
      font-size: 1rem;
      padding: 0.5rem 1.5rem;
      background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
      border: none;
      color: white;
    }
    .btn-add:hover {
      background: linear-gradient(135deg, #fda085 0%, #f6d365 100%);
    }
  </style>
</head>
<body>
  <?php include('sidebar.php'); ?>  <!-- Подключение бокового меню -->

  <!-- Основной контент -->
  <div class="content">
    <!-- Верхний заголовок -->
    <header class="top-header">
      <div class="date-container">
        <i class='bx bx-calendar'></i>
        <span class="date-text"><?php echo date('m/d/Y'); ?></span>
        <span class="time-text"><?php echo date('H:i'); ?></span>
      </div>
      <div class="search-container">
        <input type="text" class="search-bar" placeholder="Поиск...">
      </div>
    </header>

    <!-- Контейнер для таблицы членов БРСМ -->
    <div class="table-container">
      <h2 class="mb-3">Список членов БРСМ</h2>
      <!-- Кнопка для добавления нового членства -->
      <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addBrsmModal">Добавить запись в БРСМ</button>
      <table class="table table-hover">
        <thead>
          <tr>
            <th>ID студента</th>
            <th>ФИО</th>
            <th>Группа</th>
            <th>Дата вступления</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($members)): ?>
            <?php foreach ($members as $member): ?>
              <tr>
                <td><?= htmlspecialchars($member['id']) ?></td>
                <td><?= htmlspecialchars($member['name']) ?></td>
                <td><?= htmlspecialchars($member['group_name']) ?></td>
                <td><?= htmlspecialchars($member['date_joined']) ?></td>
                <td>
                  <a class="btn btn-outline-danger btn-sm" 
                     href="db_operations.php?delete_brsm=<?= $member['id'] ?>" 
                     onclick="return confirm('Удалить запись о членстве?')">
                    Удалить
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center">Нет записей о членстве в БРСМ</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Модальное окно для добавления записи в БРСМ -->
<div class="modal fade" id="addBrsmModal" tabindex="-1" aria-labelledby="addBrsmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addBrsmModalLabel">Добавить запись в БРСМ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Форма для добавления записи, выбираем студента из выпадающего списка -->
                <form method="POST" action="db_operations.php">
                    <input type="hidden" name="add_brsm" value="1">
                    <div class="mb-3">
                        <label class="form-label">Выберите студента</label>
                        <select class="form-select" name="student_id" required>
                            <option value="">-- Выберите студента --</option>
                            <?php foreach ($allStudents as $student): ?>
                                <option value="<?= htmlspecialchars($student['id']) ?>">
                                    <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['group_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Дата вступления</label>
                        <input type="date" class="form-control" name="date_joined" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </form>
            </div>
        </div>
    </div>
</div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
