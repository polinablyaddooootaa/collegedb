<?php
require 'vendor/autoload.php';
include('config.php');
include('db_operations.php');
include('students.php');

use PhpOffice\PhpWord\PhpWord;

$sql = "SELECT * FROM students";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$phpWord = new PhpWord();
$section = $phpWord->addSection();

$table = $section->addTable();

// Добавляем заголовок таблицы
$table->addRow();
$table->addCell()->addText('ID');
$table->addCell()->addText('ФИО');
$table->addCell()->addText('Группа');
$table->addCell()->addText('БРСМ');
$table->addCell()->addText('Волонтер');

// Добавляем данные
foreach ($students as $student) {
    $table->addRow();
    $table->addCell()->addText($student['id']);
    $table->addCell()->addText($student['name']);
    $table->addCell()->addText($student['group_name']);
    $table->addCell()->addText(getBrsmStatus($student['id'], $pdo));
    $table->addCell()->addText(getVolunteerStatus($student['id'], $pdo));
}

$filename = 'students.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$phpWord->save('php://output');
exit;
?>