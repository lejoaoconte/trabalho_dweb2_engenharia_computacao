<?php
header('Content-Type: application/json');
require 'config.php';
require 'auth.php';

$authHeader = function_exists('apache_request_headers')
    ? (apache_request_headers()['Authorization'] ?? '')
    : ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token não enviado']);
    exit;
}
$jwt = $matches[1];
$tokenData = jwt_decode($jwt, JWT_SECRET);
if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido ou expirado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$matricula = $input['matricula'] ?? '';
$idLivro   = $input['id_livro']   ?? '';
if (!$matricula || !$idLivro) {
    http_response_code(400);
    echo json_encode(['erro' => 'Campos obrigatórios ausentes (matricula, id_livro)']);
    exit;
}

$stmt = $pdo->prepare('SELECT dataBloqueio FROM leitor WHERE matricula = ?');
$stmt->execute([$matricula]);
$reader = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$reader) {
    http_response_code(404);
    echo json_encode(['erro' => 'Leitor não encontrado']);
    exit;
}
if ($reader['dataBloqueio']) {
    $blockDate = new DateTime($reader['dataBloqueio']);
    $today     = new DateTime('today');
    if ($blockDate >= $today) {
        http_response_code(403);
        echo json_encode(['erro' => 'Leitor está bloqueado até ' . $blockDate->format('Y-m-d')]);
        exit;
    }
}

$stmt = $pdo->prepare('SELECT id_reserva, fk_livro, fk_leitor FROM reservar WHERE fk_livro = ?');
$stmt->execute([$idLivro]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($reservations) > 0 && $reservations[0]['fk_leitor'] !== $matricula) {
    http_response_code(403);
    echo json_encode(['erro' => 'Não é possível emprestar: você tem outras reservas ativas']);
    exit;
}

$stmt = $pdo->prepare('SELECT id_reserva, fk_livro, fk_leitor FROM reservar WHERE fk_livro = ? AND fk_leitor = ?');
$stmt->execute([$idLivro, $matricula]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($reservations) > 0 && $reservations[0]['fk_leitor'] === $matricula) {
    try {
        $del = $pdo->prepare('DELETE FROM reservar WHERE fk_leitor = ? AND fk_livro = ?');
        $del->execute([$matricula, $idLivro]);
    } catch (Exception $e) {
    }
}

$emprestimoId = uniqid('emprestimo_');
$dataRetornoEstimado = (new DateTime())->modify('+15 days')->format('Y-m-d');

try {
    $ins = $pdo->prepare('
        INSERT INTO pegar_emprestado 
          (id_emprestimo, fk_leitor, fk_livro, data, data_estimada_devolucao)
        VALUES (?, ?, ?, CURDATE(), ?)
    ');
    $ins->execute([$emprestimoId, $matricula, $idLivro, $dataRetornoEstimado]);

    echo json_encode([
        'success'       => true,
        'id_emprestimo'       => $emprestimoId,
        'data_estimada_devolucao'      => $dataRetornoEstimado
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao emprestar livro']);
}
