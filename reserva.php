<?php
header('Content-Type: application/json');
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
$matricula  = $data['matricula'] ?? '';
$idLivro    = $data['id_livro']   ?? '';

if (!$matricula || !$idLivro) {
    http_response_code(400);
    echo json_encode(['error' => 'Campos obrigatórios ausentes (matricula, id_livro)']);
    exit;
}

$reservationId = uniqid('res_');
$stmt = $pdo->prepare('INSERT INTO reservar (id_reserva, fk_leitor, fk_livro, data) VALUES (?, ?, ?, CURDATE())');

try {
    $stmt->execute([$reservationId, $matricula, $idLivro]);
    echo json_encode(['sucesso' => true, 'id_reserva' => $reservationId]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao reservar livro']);
}
