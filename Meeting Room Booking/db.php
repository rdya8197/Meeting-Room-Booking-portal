<?php
header('Content-Type: application/json; charset=utf-8');

$host = 'localhost';
$dbname = 'hospital_booking';
$username = 'root';
$password = ''; // ضع كلمة مرور السيرفر إن وجدت

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'فشل الاتصال بقاعدة البيانات']);
    exit;
}
?>