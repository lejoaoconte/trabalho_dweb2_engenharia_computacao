
<?php
require 'config.php';
require 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método inválido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['erro' => 'E-mail e senha são obrigatórios']);
    exit;
}

$passwordMd5 = md5($password);
$stmt = $pdo->prepare('SELECT id_bibliotecario, nome, email FROM bibliotecario WHERE email = ? AND senha = ?');
$stmt->execute([$email, $passwordMd5]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(401);
    echo json_encode(['erro' => 'Credenciais inválidas']);
    exit;
}

$librarianId = $user['id_bibliotecario'];
$now = time();
$payload = [
    'librarian_id' => $librarianId,
    'name'         => $user['nome'],
    'email'        => $user['email'],
    'iat'          => $now,
    'exp'          => $now + 3600 // 1h
];

$token = jwt_encode($payload, JWT_SECRET);
echo json_encode(['token' => $token]);
?>

