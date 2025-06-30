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
    echo json_encode(['erro'=>'Token not provided']); exit;
}

//Realiza uma validação do token JWT
if (!jwt_decode($m[1], JWT_SECRET)) {
    http_response_code(401);
    echo json_encode(['erro'=>'Invalid or expired token']); exit;
}

//Coleta dos parâmetros para realização da busca através do método GET
$matricula = $_GET['matricula']   ?? '';
$reader    = $_GET['nome']        ?? '';
$bookId    = $_GET['id_livro']    ?? '';
$bookTitle = $_GET['titulo']      ?? '';

//Query para busca no banco de dados 
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

//As verificações a seguir são dos filtros com base nos parâmetros
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

//Executa as consultas de acordo com o filtro utilizado
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Retorna os dados encontrados em formato JSON
echo json_encode(['reservas' => $reservas]);
