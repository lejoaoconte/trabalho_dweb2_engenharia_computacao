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

$input = json_decode(file_get_contents('php://input'), true);
$loanId = $input['loan_id'] ?? '';
if (!$loanId) {
    http_response_code(400);
    echo json_encode(['error' => 'Campo obrigatório ausente (loan_id)']);
    exit;
}

$stmt = $pdo->prepare('
    SELECT fk_leitor, fk_livro, data_estimada_devolucao, data_devolucao
      FROM pegar_emprestado
     WHERE id_emprestimo = ?
');
$stmt->execute([$loanId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$loan) {
    http_response_code(404);
    echo json_encode(['error' => 'Empréstimo não encontrado']);
    exit;
}
if ($loan['data_devolucao'] !== null) {
    http_response_code(400);
    echo json_encode(['error' => 'Este livro já foi devolvido']);
    exit;
}

$today    = new DateTime('today');
$dueDate  = new DateTime($loan['data_estimada_devolucao']);
$overdueDays = 0;
if ($today > $dueDate) {
    $interval    = $dueDate->diff($today);
    $overdueDays = $interval->days;
}

$upd = $pdo->prepare('
    UPDATE pegar_emprestado
       SET data_devolucao = CURDATE()
     WHERE id_emprestimo = ?
');
$upd->execute([$loanId]);

$newBlockDate = null;
if ($overdueDays > 0) {
    $chk = $pdo->prepare('SELECT dataBloqueio FROM leitor WHERE matricula = ?');
    $chk->execute([$loan['fk_leitor']]);
    $reader = $chk->fetch(PDO::FETCH_ASSOC);

    $baseDate = $today;
    if (!empty($reader['dataBloqueio'])) {
        $existing = new DateTime($reader['dataBloqueio']);
        if ($existing > $today) {
            $baseDate = $existing;
        }
    }

    $baseDate->modify('+' . $overdueDays . ' days');
    $newBlockDate = $baseDate->format('Y-m-d');

    $updBlk = $pdo->prepare('
        UPDATE leitor
           SET dataBloqueio = ?
         WHERE matricula = ?
    ');
    $updBlk->execute([$newBlockDate, $loan['fk_leitor']]);
}

echo json_encode([
    'successo'       => true,
    'id_emprestimo'       => $loanId,
    'data_devolucao'  => $overdueDays,
    'bloqueado_ate_data'   => $newBlockDate 
]);
