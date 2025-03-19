<?php
ob_start(); // Начало буферизации вывода

include('config.php');

// Проверка подключения
if (!$pdo) {
    die("Ошибка подключения к базе данных!");
}

// Добавление или редактирование студента
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? null;
    $group_name = $_POST['group_name'] ?? null;
    $brsm = isset($_POST['brsm']) ? 1 : 0;
    $volunteer = isset($_POST['volunteer']) ? 1 : 0;

    // Убедитесь, что функция существует
    if (!function_exists('setNotification')) {
        function setNotification($message, $type = 'success') {
            $_SESSION['notification'] = [
                'message' => $message,
                'type' => $type
            ];
        }
    }

    // Запускаем сессию, если еще не запущена
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if ($name && $group_name) {
        if (isset($_POST['add_student'])) {
            $sql = "INSERT INTO students (name, group_name)
                    VALUES (:name, :group_name)";
            $notification_message = 'Студент успешно добавлен';
        } elseif (isset($_POST['edit_student'])) {
            $id = $_POST['id'];
            $sql = "UPDATE students SET name = :name, group_name = :group_name 
                    WHERE id = :id";
            $notification_message = 'Информация о студенте успешно обновлена';
        }

        try {
            $stmt = $pdo->prepare($sql);
            if (isset($_POST['edit_student'])) {
                $stmt->bindParam(':id', $id);
            }
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':group_name', $group_name);
           
            $stmt->execute();

            // Получаем ID вставленного или обновленного студента
            if (isset($_POST['add_student'])) {
                $student_id = $pdo->lastInsertId();
            } elseif (isset($_POST['edit_student'])) {
                $student_id = $id;
            }

            // Обновляем статус БРСМ
            $sql = "DELETE FROM brsm WHERE student_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
            if ($brsm) {
                $sql = "INSERT INTO brsm (student_id, date_joined) VALUES (?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$student_id]);
            }

            // Обновляем статус волонтера
            $sql = "DELETE FROM volunteers WHERE student_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);
            if ($volunteer) {
                $sql = "INSERT INTO volunteers (student_id, date_joined, activity_type, hours_volunteered) VALUES (?, NOW(), '', 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$student_id]);
            }
           
            // Устанавливаем уведомление об успешном действии
            setNotification($notification_message, 'success');
            
            header("Location: students.php");
            exit();
        } catch (PDOException $e) {
            // Устанавливаем уведомление об ошибке
            setNotification('Ошибка: ' . $e->getMessage(), 'error');
            header("Location: students.php");
            exit();
        }
    } else {
       
    }
}

// Удаление студента
if (isset($_GET['delete_student'])) {
    $id = $_GET['delete_student'];

    // Убедитесь, что функция существует
    if (!function_exists('setNotification')) {
        function setNotification($message, $type = 'success') {
            $_SESSION['notification'] = [
                'message' => $message,
                'type' => $type
            ];
        }
    }

    // Запускаем сессию, если еще не запущена
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    try {
        // Получаем имя студента перед удалением для уведомления
        $stmt = $pdo->prepare("SELECT name FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $student_name = $stmt->fetchColumn();

        // Начинаем транзакцию
        $pdo->beginTransaction();




        // Удаляем связанные записи из таблицы brsm
        $sql = "DELETE FROM brsm WHERE student_id = :student_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':student_id', $id);
        $stmt->execute();

        // Удаляем связанные записи из таблицы volunteers
        $sql = "DELETE FROM volunteers WHERE student_id = :student_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':student_id', $id);
        $stmt->execute();

        // Удаляем запись из таблицы students
        $sql = "DELETE FROM students WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        // Подтверждаем транзакцию
        $pdo->commit();

        // Устанавливаем уведомление об успешном удалении
        setNotification("Студент \"$student_name\" успешно удален", 'info');

        header("Location: students.php");
        exit();
    } catch (PDOException $e) {
        // Откатываем транзакцию в случае ошибки
        $pdo->rollBack();
        
        // Устанавливаем уведомление об ошибке
        setNotification('Ошибка при удалении: ' . $e->getMessage(), 'error');
        
        header("Location: students.php");
        exit();
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

// Добавление новой группы
function addGroup($name, $students_count, $year, $faculty) {
    global $pdo;
    $sql = "INSERT INTO groups (group_name, students_count, year, faculty) VALUES (:group_name, :students_count, :year, :faculty)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':group_name', $name);
        $stmt->bindParam(':students_count', $students_count);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':faculty', $faculty);
        $stmt->execute();
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}

// Редактирование существующей группы
function editGroup($id, $name, $students_count, $year, $faculty) {
    global $pdo;
    $sql = "UPDATE groups SET group_name = :group_name, students_count = :students_count, year = :year, faculty = :faculty WHERE id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':group_name', $name);
        $stmt->bindParam(':students_count', $students_count);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':faculty', $faculty);
        $stmt->execute();
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
}

// Удаление группы
function deleteGroup($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM groups WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    } catch (PDOException $e) {
        echo "Ошибка удаления: " . $e->getMessage();
    }
}

// Получение всех групп
function getAllGroups() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM groups");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Ошибка при получении групп: " . $e->getMessage();
    }
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
            exit();
        }
        echo "Ошибка удаления: " . $e->getMessage();
    }
}

ob_end_flush(); // Конец буферизации вывода
?>