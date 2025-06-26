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

$data = json_decode(file_get_contents('php://input'), true);
$matricula    = $data['matricula']    ?? '';
$curso      = $data['curso']       ?? '';
$nome        = $data['nome']         ?? '';
$afiliacao  = $data['afiliacao']  ?? '';
$dataBloqueio   = $data['block_date']   ?? null;
$cpf         = $data['cpf']          ?? '';

if (!$matricula || !$curso || !$nome || !$afiliacao || !$cpf) {
    http_response_code(400);
    echo json_encode(['error' => 'Campos obrigatórios ausentes (matricula, curso, nome, afiliacao, cpf)']);
    exit;
}

$stmt = $pdo->prepare('INSERT INTO leitor (matricula, curso, nome, vinculo, dataBloqueio, CPF) VALUES (?, ?, ?, ?, ?, ?)');
try {
    $stmt->execute([$matricula, $curso, $nome, $afiliacao, $dataBloqueio, $cpf]);
    echo json_encode(['successo' => true, 'matricula' => $matricula]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha ao registrar leitor']);
}
