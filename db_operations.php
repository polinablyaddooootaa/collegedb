<?php
// Подключаем конфигурацию
include('config.php');

// Проверка подключения
if (!$pdo) {
    die("Ошибка подключения к базе данных!");
}

// Добавление или редактирование студента
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $group_name = $_POST['group_name'];
    $brsm = isset($_POST['brsm']) ? 1 : 0;
    $volunteer = isset($_POST['volunteer']) ? 1 : 0;
    $achviment = $_POST['achievement']; // Исправлено название поля

    if (isset($_POST['add_student'])) {
        $sql = "INSERT INTO students (name, group_name, brsm, volunteer, achievements) 
                VALUES (:name, :group_name, :brsm, :volunteer, :achievements)";
    } elseif (isset($_POST['edit_student'])) {
        $id = $_POST['id'];
        $sql = "UPDATE students SET name = :name, group_name = :group_name, brsm = :brsm, 
                volunteer = :volunteer, achievements = :achievements WHERE id = :id";
    }

    try {
        $stmt = $pdo->prepare($sql);
        if (isset($_POST['edit_student'])) {
            $stmt->bindParam(':id', $id);
        }
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':group_name', $group_name);
        $stmt->bindParam(':brsm', $brsm);
        $stmt->bindParam(':volunteer', $volunteer);
        $stmt->bindParam(':achievements', $achviment);  // Исправлено: привязка к правильному параметру
        $stmt->execute();
        
        header("Location: students.php");
        exit();
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}

// Удаление студента
if (isset($_GET['delete_student'])) {
    $id = $_GET['delete_student'];

    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        header("Location: students.php");
        exit();
    } catch (PDOException $e) {
        echo "Ошибка удаления: " . $e->getMessage();
    }
}

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






// Удаление члена БРСМ
if (isset($_GET['remove_brsm'])) {
    try {
        $brsm_id = filter_var($_GET['remove_brsm'], FILTER_VALIDATE_INT);
        
        if (!$brsm_id) {
            throw new Exception("Некорректный ID записи");
        }

        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Получаем student_id перед удалением записи
        $getStudentSql = "SELECT student_id FROM brsm WHERE id = ?";
        $getStudentStmt = $pdo->prepare($getStudentSql);
        $getStudentStmt->execute([$brsm_id]);
        $student = $getStudentStmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Удаляем запись из таблицы БРСМ
            $deleteSql = "DELETE FROM brsm WHERE id = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([$brsm_id]);

            // Обновляем статус в таблице students
            $updateSql = "UPDATE students SET brsm = 0 WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$student['student_id']]);

            // Подтверждаем транзакцию
            $pdo->commit();
            $_SESSION['success'] = "Член БРСМ успешно исключен";
        } else {
            throw new Exception("Запись не найдена");
        }

    } catch (Exception $e) {
        // Если произошла ошибка, отменяем все изменения
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Ошибка при исключении: " . $e->getMessage();
    }

    // Всегда перенаправляем обратно на страницу БРСМ
    header("Location: brsm.php");
    exit();
}





// Добавление группы
if (isset($_POST['add_group'])) {
    $group_name = $_POST['group_name'];
    $curator = $_POST['curator'];
    $count_students = $_POST['count_students'];

    // Проверка на пустые значения
    if (!empty($group_name) && !empty($curator) && !empty($count_students)) {
        // Вставка новой группы
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

    // Проверка, чтобы все поля были заполнены
    if (!empty($id_group) && !empty($group_name) && !empty($curator) && !empty($count_students)) {
        // Обновление данных о группе
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
        // Удаление группы
        $sql = "DELETE FROM `groups` WHERE id_group = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_group]);
    }
    header('Location: groups.php');
    exit();
}
// Удаление достижения
if (isset($_GET['delete_achievement'])) {
    $achievement_id = $_GET['delete_achievement'];

    try {
        $stmt = $pdo->prepare("DELETE FROM achievements WHERE id = :id");
        $stmt->bindParam(':id', $achievement_id);
        $stmt->execute();
        
        // Для AJAX-запросов
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }
        
        header("Location: achievements.php");
        exit();
    } catch (PDOException $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
        echo "Ошибка удаления: " . $e->getMessage();
    }
}
?>
