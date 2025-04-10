<?php
session_start();
include('config.php');
include('functions.php');
ob_start(); // Начало буферизации вывода

// Проверка подключения
if (!$pdo) {
    die("Ошибка подключения к базе данных!");
}

// Функция установки уведомлений
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

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Добавление или редактирование студента
    if (isset($_POST['add_student']) || isset($_POST['edit_student'])) {
        $name = $_POST['name'] ?? null;
        $group_id = $_POST['group_id'] ?? null;
        $brsm = isset($_POST['brsm']) ? 1 : 0;
        $volunteer = isset($_POST['volunteer']) ? 1 : 0;

        if ($name && $group_id) {
            try {
                // Проверяем существование группы
                $check_group = "SELECT id FROM `groups` WHERE id = ?";
                $stmt = $pdo->prepare($check_group);
                $stmt->execute([$group_id]);
                if (!$stmt->fetch()) {
                    throw new PDOException('Выбранная группа не существует');
                }

                if (isset($_POST['add_student'])) {
                    $sql = "INSERT INTO students (name, group_id) VALUES (:name, :group_id)";
                    $notification_message = 'Студент успешно добавлен';
                } elseif (isset($_POST['edit_student'])) {
                    $id = $_POST['id'];
                    $sql = "UPDATE students SET name = :name, group_id = :group_id WHERE id = :id";
                    $notification_message = 'Информация о студенте успешно обновлена';
                }

                $stmt = $pdo->prepare($sql);
                if (isset($_POST['edit_student'])) {
                    $stmt->bindParam(':id', $id);
                }
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':group_id', $group_id);
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
            setNotification('Ошибка: необходимо указать имя студента и группу', 'error');
            header("Location: students.php");
            exit();
        }
    }

    // Добавление новой группы
    if (isset($_POST['action']) && $_POST['action'] == 'add_group') {
        $group_name = $_POST['group_name'];
        $curator = $_POST['curator'];
        $specialty_id = $_POST['specialty_id'];

        if (!empty($group_name) && !empty($curator) && !empty($specialty_id)) {
            try {
                $sql = "INSERT INTO `groups` (group_name, curator, specialty_id) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$group_name, $curator, $specialty_id]);

                setNotification("Группа успешно добавлена.", "success");
                header("Location: groups.php");
                exit();
            } catch (PDOException $e) {
                setNotification("Ошибка при добавлении группы: " . $e->getMessage(), "error");
                header("Location: groups.php");
                exit();
            }
        } else {
            setNotification("Необходимо указать название группы, куратора и специальность.", "error");
            header("Location: groups.php");
            exit();
        }
    }

    // Редактирование существующей группы
    if (isset($_POST['action']) && $_POST['action'] == 'edit_group') {
        $group_id = $_POST['id'];
        $group_name = $_POST['group_name'];
        $curator = $_POST['curator'];
        $specialty_id = $_POST['specialty_id'];

        if (!empty($group_id) && !empty($group_name) && !empty($curator) && !empty($specialty_id)) {
            try {
                $sql = "UPDATE `groups` SET group_name = ?, curator = ?, specialty_id = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$group_name, $curator, $specialty_id, $group_id]);

                setNotification("Группа успешно обновлена.", "success");
                header("Location: groups.php");
                exit();
            } catch (PDOException $e) {
                setNotification("Ошибка при обновлении группы: " . $e->getMessage(), "error");
                header("Location: groups.php");
                exit();
            }
        } else {
            setNotification("Необходимо указать ID группы, название группы, куратора и специальность.", "error");
            header("Location: groups.php");
            exit();
        }
    }

    // Добавление новой специальности
    if (isset($_POST['action']) && $_POST['action'] == 'add_specialty') {
        $specialty_name = $_POST['specialty_name'];

        if (!empty($specialty_name)) {
            try {
                $sql = "INSERT INTO specialties (name) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$specialty_name]);

                setNotification("Специальность успешно добавлена.", "success");
                header("Location: groups.php");
                exit();
            } catch (PDOException $e) {
                setNotification("Ошибка при добавлении специальности: " . $e->getMessage(), "error");
                header("Location: groups.php");
                exit();
            }
        } else {
            setNotification("Необходимо указать название специальности.", "error");
            header("Location: groups.php");
            exit();
        }
    }

    // Добавление нового учебного предмета
    if (isset($_POST['action']) && $_POST['action'] == 'add_subject') {
        $subject_name = $_POST['subject_name'];
        $specialty_id = $_POST['specialty_id'];

        if (!empty($subject_name) && !empty($specialty_id)) {
            try {
                $sql = "INSERT INTO subjects (name, specialty_id) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$subject_name, $specialty_id]);

                setNotification("Учебный предмет успешно добавлен.", "success");
                header("Location: groups.php");
                exit();
            } catch (PDOException $e) {
                setNotification("Ошибка при добавлении учебного предмета: " . $e->getMessage(), "error");
                header("Location: groups.php");
                exit();
            }
        } else {
            setNotification("Необходимо указать название учебного предмета и специальность.", "error");
            header("Location: groups.php");
            exit();
        }
    }
}

// Обработка GET-запросов
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Удаление студента
    if (isset($_GET['delete_student'])) {
        $id = $_GET['delete_student'];

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

    // Удаление группы
    if (isset($_GET['delete_group'])) {
        $group_id = $_GET['delete_group'];

        try {
            $sql = "DELETE FROM `groups` WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$group_id]);

            setNotification("Группа успешно удалена.", "success");
            header("Location: groups.php");
            exit();
        } catch (PDOException $e) {
            setNotification("Ошибка при удалении группы: " . $e->getMessage(), "error");
            header("Location: groups.php");
            exit();
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
                exit();
            }

            setNotification("Достижение успешно удалено.", "success");
            header("Location: achievements.php");
            exit();
        } catch (PDOException $e) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit();
            }
            setNotification("Ошибка удаления: " . $e->getMessage(), "error");
            header("Location: achievements.php");
            exit();
        }
    }

    // Исключение члена БРСМ
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
                setNotification("Член БРСМ успешно исключен", 'info');
            } else {
                throw new Exception("Запись не найдена");
            }
        } catch (Exception $e) {
            // Если произошла ошибка, отменяем все изменения
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setNotification("Ошибка при исключении: " . $e->getMessage(), 'error');
        }

        // Всегда перенаправляем обратно на страницу БРСМ
        header("Location: brsm.php");
        exit();
    }
}

// Функции для аутентификации и регистрации пользователей
function registerUser($username, $email, $password, $secret_code) {
    global $pdo;

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, email, password, secret_code, created_at) VALUES (:username, :email, :password, :secret_code, NOW())";

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

ob_end_flush(); // Конец буферизации вывода
?>