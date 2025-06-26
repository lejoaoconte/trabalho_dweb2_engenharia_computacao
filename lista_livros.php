<?php
header('Content-Type: application/json');
require 'config.php';
require 'auth.php';

$authHeader = function_exists('apache_request_headers')
    ? (apache_request_headers()['Authorization'] ?? '')
    : ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token não enviado']);
    exit;
}
if (!jwt_decode($m[1], JWT_SECRET)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido ou expirado']);
    exit;
}

$id    = $_GET['id_livro'] ?? '';
$name  = $_GET['titulo']   ?? '';
$sql   = "SELECT id_livro, isbn, titulo, autor, categoria, status
            FROM livro
           WHERE 1=1";
$params = [];

if ($id !== '') {
    $sql .= " AND id_livro LIKE ?";
    $params[] = "%{$id}%";
}
if ($name !== '') {
    $sql .= " AND LOWER(titulo) LIKE ?";
    $params[] = '%' . strtolower($name) . '%';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$livros = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['livros' => $livros]);
