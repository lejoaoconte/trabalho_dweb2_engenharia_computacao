<?php
//definição do tipo de retorno JSON
header('Content-Type: application/json');

//Importação das configurações do banco de dados e de autenticação
require 'config.php';
require 'auth.php';

//Recuperação do token utilizado para autenticação
$authHeader = function_exists('apache_request_headers')
    ? (apache_request_headers()['Authorization'] ?? '')
    : ($_SERVER['HTTP_AUTHORIZATION'] ?? '');

//Verifica se o token foi enviado
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token não enviado']);
    exit;
}

//Realiza uma validação do token JWT
if (!jwt_decode($m[1], JWT_SECRET)) {
    http_response_code(401);
    echo json_encode(['erro' => 'Token inválido ou expirado']);
    exit;
}

//Coleta dos parâmetros para realização da busca através do método GET
$matricula = $_GET['matricula'] ?? '';
$nome      = $_GET['nome']      ?? '';

//Query para busca no banco de dados 
$sql   = "SELECT matricula, curso, nome, vinculo, dataBloqueio, CPF
            FROM leitor
           WHERE 1=1";
$params = [];

//As verificações a seguir são dos filtros com base nos parâmetros
if ($matricula !== '') {
    $sql .= " AND matricula LIKE ?";
    $params[] = "%{$matricula}%";
}
if ($nome !== '') {
    $sql .= " AND LOWER(nome) LIKE ?";
    $params[] = '%' . strtolower($nome) . '%';
}

//Executa as consultas de acordo com o filtro utilizado
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leitores = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Retorna os dados encontrados em formato JSON
echo json_encode(['leitores' => $leitores]);
