<?php
// Endpoint responsável pelo processamento da devolução de um livro emprestado
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
$emprestimoId = $input['emprestimo_id'] ?? '';
// Verifica se o ID do empréstimo foi informado
if (!$emprestimoId) {
    http_response_code(400);
    echo json_encode(['erro' => 'Campo obrigatório ausente (emprestimo_id)']);
    exit;
}

// Busca as informações do empréstimo no banco de dados
$stmt = $pdo->prepare('
    SELECT fk_leitor, fk_livro, data_estimada_devolucao, data_devolucao
      FROM pegar_emprestado
     WHERE id_emprestimo = ?
');
$stmt->execute([$emprestimoId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);
// Caso o empréstimo não seja encontrado apresenta mensagem de erro
if (!$loan) {
    http_response_code(404);
    echo json_encode(['erro' => 'Empréstimo não encontrado']);
    exit;
}
// Caso o livro já tenha sido devolvido anteriormente apresenta mensagem de erro
if ($loan['data_devolucao'] !== null) {
    http_response_code(400);
    echo json_encode(['erro' => 'Este livro já foi devolvido']);
    exit;
}

// Calcula se houve atraso na devolução e apresenta a data de bloqueio
$today    = new DateTime('today');
$dataEstimadaDevolucao  = new DateTime($loan['data_estimada_devolucao']);
$diasPassadosDataDevolucao = 0;
if ($today > $dataEstimadaDevolucao) {
    $interval    = $dataEstimadaDevolucao->diff($today);
    $diasPassadosDataDevolucao = $interval->days;
}

// Atualiza o registro do empréstimo, marcando como devolvido
$upd = $pdo->prepare('
    UPDATE pegar_emprestado
       SET data_devolucao = CURDATE()
     WHERE id_emprestimo = ?
');
$upd->execute([$emprestimoId]);

$novaDataBloqueio = null;
// Caso haja atraso, aplica bloqueio ao leitor proporcional ao número de dias de atraso
if ($diasPassadosDataDevolucao > 0) {
    $chk = $pdo->prepare('SELECT dataBloqueio FROM leitor WHERE matricula = ?');
    $chk->execute([$loan['fk_leitor']]);
    $reader = $chk->fetch(PDO::FETCH_ASSOC);

    $baseDate = $today;
    // Se o leitor já estava bloqueado, soma o novo bloqueio ao período existente
    if (!empty($reader['dataBloqueio'])) {
        $existing = new DateTime($reader['dataBloqueio']);
        if ($existing > $today) {
            $baseDate = $existing;
        }
    }

    // Define a nova data de bloqueio
    $baseDate->modify('+' . $diasPassadosDataDevolucao . ' days');
    $novaDataBloqueio = $baseDate->format('Y-m-d');

    // Atualiza o cadastro do leitor com a nova data de bloqueio
    $updBlk = $pdo->prepare('
        UPDATE leitor
           SET dataBloqueio = ?
         WHERE matricula = ?
    ');
    $updBlk->execute([$novaDataBloqueio, $loan['fk_leitor']]);
}

// Retorna a resposta em formato JSON, informando o sucesso da operação, o ID do empréstimo, dias de atraso e data de bloqueio (se houver)
echo json_encode([
    'successo'       => true,
    'id_emprestimo'       => $emprestimoId,
    'data_devolucao'  => $diasPassadosDataDevolucao,
    'bloqueado_ate_data'   => $novaDataBloqueio 
]);
