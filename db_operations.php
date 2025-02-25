<?php
// Подключаем конфигурацию (где создаётся $pdo)
include('config.php');

// Проверка подключения
if (!$pdo) {
    die("Ошибка подключения к базе данных!");
}

/**
 * ФУНКЦИЯ: Добавить студента
 * 1) Добавляем запись в таблицу students
 * 2) Если пользователь отметил чекбокс "brsm", то добавляем запись в brsm и пишем её id в поле students.brsm
 */
function addStudent($pdo, $name, $group_name, $brsmChecked, $volunteer, $achievements) {
    // 1) Создаём запись в students
    $sql = "INSERT INTO students (name, group_name, volunteer, achievements) 
            VALUES (:name, :group_name, :volunteer, :achievements)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'        => $name,
        ':group_name'  => $group_name,
        ':volunteer'   => $volunteer,
        ':achievements'=> $achievements
    ]);
    
    // Получаем id вставленного студента
    $studentId = $pdo->lastInsertId();

    // 2) Если чекбокс "brsm" установлен, создаём запись в brsm
    if ($brsmChecked == 1) {
        // Допустим, дату вступления берём текущую
        $dateJoined = date('Y-m-d');
        
        // Добавляем запись в brsm
        $insertBrsmSQL = "INSERT INTO brsm (student_id, date_joined) VALUES (:student_id, :date_joined)";
        $stmtBrsm = $pdo->prepare($insertBrsmSQL);
        $stmtBrsm->execute([
            ':student_id' => $studentId,
            ':date_joined' => $dateJoined
        ]);
        
        // Получаем id записи в brsm
        $brsmId = $pdo->lastInsertId();
        
        // Запишем brsmId в поле students.brsm
        $updateStudentSQL = "UPDATE students SET brsm = :brsm_id WHERE id = :student_id";
        $stmtUpd = $pdo->prepare($updateStudentSQL);
        $stmtUpd->execute([
            ':brsm_id'     => $brsmId,
            ':student_id'  => $studentId
        ]);
    }
}

/**
 * ФУНКЦИЯ: Редактировать (обновить) студента
 * 1) Обновляем запись в students
 * 2) Если "brsm" = 1, то проверяем, есть ли запись в brsm. Если нет - создаём.
 *    Если "brsm" = 0, то удаляем запись из brsm и обнуляем поле students.brsm
 */
function editStudent($pdo, $id, $name, $group_name, $brsmChecked, $volunteer, $achievements) {
    // Сначала обновляем данные в students
    $sql = "UPDATE students 
            SET name = :name, group_name = :group_name, volunteer = :volunteer, achievements = :achievements
            WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name'         => $name,
        ':group_name'   => $group_name,
        ':volunteer'    => $volunteer,
        ':achievements' => $achievements,
        ':id'           => $id
    ]);

    // Далее логика по brsm
    if ($brsmChecked == 1) {
        // Проверим, есть ли уже запись в brsm
        $checkSQL = "SELECT id FROM brsm WHERE student_id = :sid";
        $stmtCheck = $pdo->prepare($checkSQL);
        $stmtCheck->execute([':sid' => $id]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            // Записи нет - значит создаём
            $dateJoined = date('Y-m-d');
            $insertBrsmSQL = "INSERT INTO brsm (student_id, date_joined) VALUES (:student_id, :date_joined)";
            $stmtInsert = $pdo->prepare($insertBrsmSQL);
            $stmtInsert->execute([
                ':student_id' => $id,
                ':date_joined' => $dateJoined
            ]);
            $brsmId = $pdo->lastInsertId();

            // Обновим поле students.brsm
            $updStud = $pdo->prepare("UPDATE students SET brsm = :brsmId WHERE id = :id");
            $updStud->execute([
                ':brsmId' => $brsmId,
                ':id'     => $id
            ]);
        } else {
            // Запись уже есть => ничего не делаем или можем обновить date_joined, если нужно
            // $existing['id'] - это brsm.id
            // Можно также проверить, что в поле students.brsm действительно записан $existing['id']
            $updStud = $pdo->prepare("UPDATE students SET brsm = :brsmId WHERE id = :id");
            $updStud->execute([
                ':brsmId' => $existing['id'],
                ':id'     => $id
            ]);
        }
    } else {
        // brsmChecked = 0 => удаляем запись из brsm, если есть
        $deleteBrsmSQL = "DELETE FROM brsm WHERE student_id = :sid";
        $stmtDel = $pdo->prepare($deleteBrsmSQL);
        $stmtDel->execute([':sid' => $id]);

        // Обнулим поле brsm в students
        $updStud = $pdo->prepare("UPDATE students SET brsm = NULL WHERE id = :id");
        $updStud->execute([':id' => $id]);
    }
}

/**
 * ФУНКЦИЯ: Удалить студента
 * 1) Удаляем запись из brsm (если есть)
 * 2) Удаляем запись из students
 */
function deleteStudent($pdo, $id) {
    // Сначала удалим членство в brsm, если оно есть
    $deleteBrsmSQL = "DELETE FROM brsm WHERE student_id = :sid";
    $stmtBrsm = $pdo->prepare($deleteBrsmSQL);
    $stmtBrsm->execute([':sid' => $id]);

    // Удаляем студента
    $sql = "DELETE FROM students WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
}

/**
 * ======================
 *        ЛОГИКА POST
 * ======================
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Собираем данные из формы
    $name        = $_POST['name'] ?? '';
    $group_name  = $_POST['group_name'] ?? '';
    $brsmChecked = isset($_POST['brsm']) ? 1 : 0;
    $volunteer   = isset($_POST['volunteer']) ? 1 : 0;
    $achievements= $_POST['achievement'] ?? '';

    // Добавление нового студента
    if (isset($_POST['add_student'])) {
        addStudent($pdo, $name, $group_name, $brsmChecked, $volunteer, $achievements);
        header("Location: students.php");
        exit();
    }

    // Редактирование существующего студента
    if (isset($_POST['edit_student'])) {
        $id = $_POST['id'];
        editStudent($pdo, $id, $name, $group_name, $brsmChecked, $volunteer, $achievements);
        header("Location: students.php");
        exit();
    }
}

/**
 * ======================
 *       ЛОГИКА GET
 * ======================
 */
// Удаление студента
if (isset($_GET['delete_student'])) {
    $id = $_GET['delete_student'];
    deleteStudent($pdo, $id);
    header("Location: students.php");
    exit();
}


/**
 * ========  НИЖЕ – функции для регистрации/авторизации пользователей и управления группами ========
 */

// Функция регистрации пользователя
function registerUser($username, $email, $password, $secret_code) {
    global $pdo;
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, secret_code, created_at) 
            VALUES (:username, :email, :password, :secret_code, NOW())";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':secret_code', $secret_code);
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Функция авторизации пользователя
function authenticateUser($username, $password, $secret_code) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND secret_code = :secret_code");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':secret_code', $secret_code);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}


// Добавление группы
if (isset($_POST['add_group'])) {
    $group_name = $_POST['group_name'];
    $curator = $_POST['curator'];
    $count_students = $_POST['count_students'];

    if (!empty($group_name) && !empty($curator) && !empty($count_students)) {
        $sql = "INSERT INTO `groups` (group_name, curator, count_students) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_name, $curator, $count_students]);

        header('Location: groups.php');
        exit();
    } else {
        echo "Ошибка: все поля должны быть заполнены!";
    }
}


// Редактирование группы
if (isset($_POST['edit_group'])) {
    $id_group = $_POST['id_group'];
    $group_name = $_POST['group_name'];
    $curator = $_POST['curator'];
    $count_students = $_POST['count_students'];

    if (!empty($id_group) && !empty($group_name) && !empty($curator) && !empty($count_students)) {
        $sql = "UPDATE `groups` SET group_name = ?, curator = ?, count_students = ? WHERE id_group = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$group_name, $curator, $count_students, $id_group]);
        header('Location: groups.php');
        exit();
    } else {
        echo "Ошибка: Все поля должны быть заполнены!";
    }
}

// Удаление группы
if (isset($_GET['delete_group'])) {
    $id_group = $_GET['delete_group'];

    if (!empty($id_group)) {
        $sql = "DELETE FROM `groups` WHERE id_group = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_group]);
    }
    header('Location: groups.php');
    exit();
}
