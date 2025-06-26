<?php
header('Content-Type: application/json');
require 'config.php';
require 'auth.php';

$authHeader = function_exists('apache_request_headers')
    ? (apache_request_headers()['Authorization'] ?? '')
    : ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token não enviado']);
    exit;
}
if (!jwt_decode($m[1], JWT_SECRET)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido ou expirado']);
    exit;
}

$matricula = $_GET['matricula'] ?? '';
$nome      = $_GET['nome']      ?? '';
$sql   = "SELECT matricula, curso, nome, vinculo, dataBloqueio, CPF
            FROM leitor
           WHERE 1=1";
$params = [];

if ($matricula !== '') {
    $sql .= " AND matricula LIKE ?";
    $params[] = "%{$matricula}%";
}
if ($nome !== '') {
    $sql .= " AND LOWER(nome) LIKE ?";
    $params[] = '%' . strtolower($nome) . '%';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leitores = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['leitores' => $leitores]);
