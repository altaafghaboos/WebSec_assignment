<?php
// Local development only: update credentials if your XAMPP MySQL settings differ.
$host = '127.0.0.1'; $db = 'beauty_salon_booking_system'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
       PDO::ATTR_EMULATE_PREPARES => false]);
} catch (PDOException $e) {
    http_response_code(503);
    exit('Database connection unavailable. Start MySQL in XAMPP and import the supplied SQL file.');
}
