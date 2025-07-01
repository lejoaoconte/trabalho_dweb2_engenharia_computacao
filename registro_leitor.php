<?php
// definição do tipo de retorno JSON
header('Content-Type: application/json');

// Importação das configurações do banco de dados e de autenticação
require 'config.php';
require 'auth.php';

// Recuperação do token utilizado para autenticação
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
// Decodifica e valida o token, caso a validação seja inválida uma mensagem de erro é enviada
$tokenData = jwt_decode($jwt, JWT_SECRET);
if (!$tokenData) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido ou expirado']);
    exit;
}

// Verifica se a requisição é do tipo POST caso não seja uma mensagem de erro é enviada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método inválido']);
    exit;
}

// Extrai os campos esperados da requisição
$data = json_decode(file_get_contents('php://input'), true);
$matricula    = $data['matricula']    ?? '';
$curso      = $data['curso']       ?? '';
$nome        = $data['nome']         ?? '';
$afiliacao  = $data['afiliacao']  ?? '';
$dataBloqueio   = $data['block_date']   ?? null;
$cpf         = $data['cpf']          ?? '';

// Verifica se os campos obrigatórios foram preenchidos
if (!$matricula || !$curso || !$nome || !$afiliacao || !$cpf) {
    http_response_code(400);
    echo json_encode(['erro' => 'Campos obrigatórios ausentes (matricula, curso, nome, afiliacao, cpf)']);
    exit;
}

// Query para inserção de um novo leitor no banco de dados, caso tenha algum problema uma mensagem de erro é enviada
$stmt = $pdo->prepare('INSERT INTO leitor (matricula, curso, nome, vinculo, dataBloqueio, CPF) VALUES (?, ?, ?, ?, ?, ?)');
try {
    $stmt->execute([$matricula, $curso, $nome, $afiliacao, $dataBloqueio, $cpf]);
    echo json_encode(['successo' => true, 'matricula' => $matricula]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao registrar leitor']);
}
