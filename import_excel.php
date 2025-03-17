<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключаем автозагрузчик Composer
require '/home/d/d91955kx/1233/public_html/vendor/autoload.php';  // Укажите правильный путь на сервере

use PhpOffice\PhpSpreadsheet\IOFactory;

// Подключаем файл с функциями для работы с базой данных
include('db_operations.php');  // Убедитесь, что у вас есть этот файл и он корректно настроен

// Проверка загрузки файла
if (isset($_FILES['excelFile']) && $_FILES['excelFile']['error'] == UPLOAD_ERR_OK) {
    $inputFileName = $_FILES['excelFile']['tmp_name'];

    // Загружаем файл Excel
    $spreadsheet = IOFactory::load($inputFileName);
    $sheet = $spreadsheet->getActiveSheet();

    // Читаем данные из файла
    $rows = $sheet->toArray(null, true, true, true);

    // Удаляем заголовки столбцов
    array_shift($rows);

    // Подготовка SQL-запроса для вставки данных
    $sql = "INSERT INTO students (id, name, group_name, brsm, volunteer) 
            VALUES (:id, :name, :group_name, :brsm, :volunteer)";
    $stmt = $pdo->prepare($sql);

    // Вставляем данные в базу данных
    foreach ($rows as $row) {
        $data = [
            ':id' => $row['A'],
            ':name' => $row['B'],
            ':group_name' => $row['C'],
            ':brsm' => 0,  // Значение по умолчанию для brsm
            ':volunteer' => 0  // Значение по умолчанию для volunteer
        ];
        $stmt->execute($data);
    }

    header("Location: students.php");
    exit;
} 
?>