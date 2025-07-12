<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// O navegador envia uma requisição OPTIONS primeiro para verificar a permissão (preflight request)
// Se for uma requisição OPTIONS, apenas retorne os cabeçalhos e saia.
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Definição das constantes de configuração do banco de dados MySQL
define('DB_HOST', '127.0.0.1'); // Endereço do servidor MySQL
define('DB_NAME', 'biblioteca'); // Nome do banco de dados
define('DB_USER', 'zelead');     // Usuário do banco de dados
define('DB_PASS', 'password');   // Senha do banco de dados

// Define a chave secreta usada para assinar e validar tokens JWT
define('JWT_SECRET', 'your_super_secret_key');

try {
    // Cria uma conexão PDO (PHP Data Objects) com o banco de dados MySQL
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] // Ativa exceções para erros SQL
    );
} catch (PDOException $e) {
    // Em caso de erro na conexão, retorna erro 500 e mensagem em JSON para o cliente
    http_response_code(500);
    echo json_encode(['erro' => 'Database connection failed']);
    exit;
}
?>

