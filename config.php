<?php
// Параметры подключения к базе данных
$host = 'localhost'; // или 'd91955kx.beget.tech'
$dbname = 'd91955kx_college'; // Имя вашей базы данных
$username = 'd91955kx_college'; // Логин пользователя
$password = 'Pol1nka001'; // Пароль пользователя

try {
    // Создаем подключение к базе данных с кодировкой UTF-8
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>
