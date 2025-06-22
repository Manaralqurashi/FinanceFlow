<?php
$host = '127.0.0.1';
$db = 'expense_tracker';
$user = 'root';
$pass = '';

try {
    // Create a new PDO instance and set error mode to exception
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Server error: Unable to connect to the database']);
    exit();
}
?>