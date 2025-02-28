<?php
// Подключаем конфигурацию базы данных
include('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $group_name = $_POST['group_name'];
    $brsm = isset($_POST['brsm']) ? 1 : 0;
    $volunteer = isset($_POST['volunteer']) ? 1 : 0;
    $achievements = $_POST['achievements'];

    // SQL-запрос на добавление студента
    $sql = "INSERT INTO students (name, group_name, brsm, volunteer, achievements) 
            VALUES (:name, :group_name, :brsm, :volunteer, :achievements)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':group_name', $group_name);
    $stmt->bindParam(':brsm', $brsm);
    $stmt->bindParam(':volunteer', $volunteer);
    $stmt->bindParam(':achievements', $achievements);

    try {
        $stmt->execute();
        // Успешное добавление, перенаправляем обратно
        header("Location: students.php?success=1");
        exit;
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
} else {
    // Если кто-то попытается открыть файл напрямую
    header("Location: students.php");
    exit;
}
?>
