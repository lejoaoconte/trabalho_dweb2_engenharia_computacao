<?php
// importação das configurações do banco de dados e de autenticação
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
$data      = json_decode(file_get_contents('php:// input'), true);
$isbn      = $data['isbn']      ?? '';
$titulo     = $data['titulo']     ?? '';
$autor    = $data['autor']    ?? '';
$categoria  = $data['categoria']  ?? '';
$status    = isset($data['status']) ? (int)$data['status'] : 1;

// Verifica se os campos obrigatórios foram preenchidos
if (!$isbn || !$titulo || !$autor || !$categoria) {
    http_response_code(400);
    echo json_encode(['erro' => 'Campos obrigatórios ausentes (isbn, titulo, autor, categoria)']);
    exit;
}

// Gera um id unico para cada livro
$idLivro = uniqid('book_');

// Query para inserção de um novo livro no banco de dados, caso tenha algum problema uma mensagem de erro é enviada
$stmt = $pdo->prepare('INSERT INTO livro (id_livro, isbn, titulo, autor, categoria, status) VALUES (?, ?, ?, ?, ?, ?)');
try {
    $stmt->execute([$idLivro, $isbn, $titulo, $autor, $categoria, $status]);
    echo json_encode(['successo' => true, 'id_livro' => $idLivro]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Falha ao registrar livro']);
}
