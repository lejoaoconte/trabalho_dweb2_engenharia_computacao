<?php
// Inclui as configurações de banco de dados e funções de autenticação
require 'config.php';
require 'auth.php';

//Verifica se a requisição é do tipo POST caso não seja uma mensagem de erro é enviada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método inválido']);
    exit;
}

//Lê e decodifica os dados JSON enviados na requisição
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// Verifica se os campos obrigatórios foram preenchidos
if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['erro' => 'E-mail e senha são obrigatórios']);
    exit;
}

// Aplica MD5 na senha
$passwordMd5 = md5($password);

//Busca no banco de dados se existe um bibliotecário cadastrado com email e senha fornecidos
$stmt = $pdo->prepare('SELECT id_bibliotecario, nome, email FROM bibliotecario WHERE email = ? AND senha = ?');
$stmt->execute([$email, $passwordMd5]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

//caso o usuário não seja encontrado, uma mensagem de erro é enviada
if (!$user) {
    http_response_code(401);
    echo json_encode(['erro' => 'Credenciais inválidas']);
    exit;
}

//preparação dos dados para criação do token JWT
$librarianId = $user['id_bibliotecario'];
$now = time();
$payload = [
    'librarian_id' => $librarianId,
    'name'         => $user['nome'],
    'email'        => $user['email'],
    'iat'          => $now,
    'exp'          => $now + 3600 // 1h
];

//criação do token com os dados do bibliotecário
$token = jwt_encode($payload, JWT_SECRET);

//retorna o token em formato json
echo json_encode(['token' => $token]);
?>

