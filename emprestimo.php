<?php
// Endpoint responsável por processar o empréstimo de um livro
header('Content-Type: application/json');
require 'config.php'; // Inclui as configurações de banco de dados
require 'auth.php';   // Inclui as funções de autenticação JWT

// Recupera o token JWT do cabeçalho da requisição
$authHeader = function_exists('apache_request_headers')
    ? (apache_request_headers()['Authorization'] ?? '')
    : ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

// Verifica se o token foi enviado
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token não enviado']);
    exit;
}
$jwt = $matches[1];
// Valida o token JWT recebido
$tokenData = jwt_decode($jwt, JWT_SECRET);
if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido ou expirado']);
    exit;
}

// Permite apenas requisições do tipo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método inválido']);
    exit;
}

// Lê o corpo da requisição (espera-se um JSON)
$input = json_decode(file_get_contents('php://input'), true);
$matricula = $input['matricula'] ?? '';
$idLivro   = $input['id_livro']   ?? '';
// Verifica se os campos obrigatórios foram informados
if (!$matricula || !$idLivro) {
    http_response_code(400);
    echo json_encode(['erro' => 'Campos obrigatórios ausentes (matricula, id_livro)']);
    exit;
}

// Verifica se o leitor existe e se está bloqueado
$stmt = $pdo->prepare('SELECT dataBloqueio FROM leitor WHERE matricula = ?');
$stmt->execute([$matricula]);
$reader = $stmt->fetch(PDO::FETCH_ASSOC);
// Se o leitor não for encontrado, retorna mensagem de erro
if (!$reader) {
    http_response_code(404);
    echo json_encode(['erro' => 'Leitor não encontrado']);
    exit;
}
if ($reader['dataBloqueio']) {
    $blockDate = new DateTime($reader['dataBloqueio']);
    $today     = new DateTime('today');
    // Se o leitor está bloqueado, não pode emprestar, retorna mensagem de erro
    if ($blockDate >= $today) {
        http_response_code(403);
        echo json_encode(['erro' => 'Leitor está bloqueado até ' . $blockDate->format('Y-m-d')]);
        exit;
    }
}

// Verifica se o livro está emprestado
$stmt = $pdo->prepare('SELECT * FROM pegar_emprestado WHERE fk_livro = ? AND data_devolucao IS NULL');
$stmt->execute([$idLivro]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);
if ($loan) {
    http_response_code(403);
    echo json_encode(['erro' => 'Livro já emprestado']);
    exit;
}


// Verifica se o livro está reservado para outro leitor
$stmt = $pdo->prepare('SELECT id_reserva, fk_livro, fk_leitor FROM reservar WHERE fk_livro = ?');
$stmt->execute([$idLivro]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($reservations) > 0 && $reservations[0]['fk_leitor'] !== $matricula) {
    http_response_code(403);
    echo json_encode(['erro' => 'Não é possível emprestar: você tem outras reservas ativas']);
    exit;
}

// Se o livro está reservado para o próprio leitor, remove a reserva
$stmt = $pdo->prepare('SELECT id_reserva, fk_livro, fk_leitor FROM reservar WHERE fk_livro = ? AND fk_leitor = ?');
$stmt->execute([$idLivro, $matricula]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($reservations) > 0 && $reservations[0]['fk_leitor'] === $matricula) {
    try {
        $del = $pdo->prepare('DELETE FROM reservar WHERE fk_leitor = ? AND fk_livro = ?');
        $del->execute([$matricula, $idLivro]);
    } catch (Exception $e) {
        // Em caso de erro ao remover a reserva, segue normalmente
    }
}

// Gera um ID único para o empréstimo e define a data estimada de devolução (15 dias)
$emprestimoId = uniqid('emprestimo_');
$dataRetornoEstimado = (new DateTime())->modify('+15 days')->format('Y-m-d');

try {
    // Insere o novo empréstimo no banco de dados
    $ins = $pdo->prepare('
        INSERT INTO pegar_emprestado 
          (id_emprestimo, fk_leitor, fk_livro, data, data_estimada_devolucao)
        VALUES (?, ?, ?, CURDATE(), ?)
    ');
    $ins->execute([$emprestimoId, $matricula, $idLivro, $dataRetornoEstimado]);

    // Retorna sucesso, id do empréstimo e data estimada de devolução
    echo json_encode([
        'success'       => true,
        'id_emprestimo'       => $emprestimoId,
        'data_estimada_devolucao'      => $dataRetornoEstimado
    ]);
} catch (Exception $e) {
    // Em caso de erro na operação, retorna mensagem de falha
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao emprestar livro']);
}
