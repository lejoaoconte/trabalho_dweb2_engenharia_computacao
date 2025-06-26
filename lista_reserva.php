<?php
header('Content-Type: application/json');
require 'config.php';
require 'auth.php';

$authHeader = function_exists('apache_request_headers')
    ? (apache_request_headers()['Authorization'] ?? '')
    : ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(['error'=>'Token not provided']); exit;
}
if (!jwt_decode($m[1], JWT_SECRET)) {
    http_response_code(401);
    echo json_encode(['error'=>'Invalid or expired token']); exit;
}

// --- Build filters ---
$matricula = $_GET['matricula']   ?? '';
$reader    = $_GET['nome']        ?? '';
$bookId    = $_GET['id_livro']    ?? '';
$bookTitle = $_GET['titulo']      ?? '';

$sql = "
SELECT r.id_reserva,
       l.matricula,
       l.nome        AS leitor_nome,
       b.id_livro,
       b.titulo      AS livro_titulo,
       r.data        AS data_reserva
  FROM reservar r
  JOIN leitor l ON r.fk_leitor = l.matricula
  JOIN livro  b ON r.fk_livro  = b.id_livro
 WHERE 1=1";
$params = [];

if ($matricula !== '') {
    $sql .= " AND l.matricula LIKE ?";
    $params[] = "%{$matricula}%";
}
if ($reader !== '') {
    $sql .= " AND LOWER(l.nome) LIKE ?";
    $params[] = '%'.strtolower($reader).'%';
}
if ($bookId !== '') {
    $sql .= " AND b.id_livro LIKE ?";
    $params[] = "%{$bookId}%";
}
if ($bookTitle !== '') {
    $sql .= " AND LOWER(b.titulo) LIKE ?";
    $params[] = '%'.strtolower($bookTitle).'%';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['reservas' => $reservas]);
