<?php
require 'config.php';
require 'auth.php';

$authHeader = function_exists('apache_request_headers')
    ? (apache_request_headers()['Authorization'] ?? '')
    : ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token não enviado']);
    exit;
}
$jwt = $matches[1];
$tokenData = jwt_decode($jwt, JWT_SECRET);
if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['error' => 'Token inválido ou expirado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método inválido']);
    exit;
}

$data      = json_decode(file_get_contents('php://input'), true);
$isbn      = $data['isbn']      ?? '';
$titulo     = $data['titulo']     ?? '';
$autor    = $data['autor']    ?? '';
$categoria  = $data['categoria']  ?? '';
$status    = isset($data['status']) ? (int)$data['status'] : 1;

if (!$isbn || !$titulo || !$autor || !$categoria) {
    http_response_code(400);
    echo json_encode(['error' => 'Campos obrigatórios ausentes (isbn, titulo, autor, categoria)']);
    exit;
}

$bookId = uniqid('book_');
$stmt = $pdo->prepare('INSERT INTO livro (id_livro, isbn, titulo, autor, categoria, status) VALUES (?, ?, ?, ?, ?, ?)');
try {
    $stmt->execute([$bookId, $isbn, $titulo, $autor, $categoria, $status]);
    echo json_encode(['successo' => true, 'id_livro' => $bookId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao registrar livro']);
}
