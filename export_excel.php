<?php
// Включаем вывод ошибок для отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Включаем буферизацию вывода
ob_start();

// Подключаем автозагрузчик Composer
require '/home/d/d91955kx/1233/public_html/vendor/autoload.php';  // Укажите правильный путь на сервере

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Подключаем файл с функциями для работы с базой данных
include('db_operations.php');
include('students.php');

// Выполняем запрос к базе данных
$sql = "SELECT * FROM students";  // Замените на ваш SQL-запрос
$stmt = $pdo->prepare($sql);
$stmt->execute();

// Проверим, что запрос вернул данные
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$students) {
    echo "Нет данных для экспорта.";  // Сообщение, если данные не найдены
    exit;
}

// Создаем новый объект Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Устанавливаем заголовки столбцов
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'ФИО');
$sheet->setCellValue('C1', 'Группа');
$sheet->setCellValue('D1', 'БРСМ');
$sheet->setCellValue('E1', 'Волонтер');

// Заполняем данные из базы данных
$row = 2;
foreach ($students as $student) {
    $sheet->setCellValue('A' . $row, $student['id']);
    $sheet->setCellValue('B' . $row, $student['name']);
    $sheet->setCellValue('C' . $row, $student['group_name']);
    $sheet->setCellValue('D' . $row, getBrsmStatus($student['id'], $pdo));  // Замените на вашу функцию
    $sheet->setCellValue('E' . $row, getVolunteerStatus($student['id'], $pdo));  // Замените на вашу функцию
    $row++;
}

// Создаем объект Writer для записи в Excel
$writer = new Xlsx($spreadsheet);

// Очищаем буфер вывода
ob_end_clean();

// Отправляем файл в браузер для скачивания
$filename = 'students.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');

// Завершаем выполнение скрипта
exit;
?>