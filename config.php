<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'biblioteca');
define('DB_USER', 'zelead');
define('DB_PASS', 'password');

define('JWT_SECRET', 'your_super_secret_key');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Database connection failed']);
    exit;
}
?>

