<?php
// Função para codificar dados em base64 URL-safe (sem caracteres especiais)
function base64url_encode($data)
{
    // Codifica em base64 e substitui caracteres para ser seguro em URLs
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Função para decodificar dados em base64 URL-safe
function base64url_decode($data)
{
    // Adiciona padding se necessário
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    // Decodifica base64 revertendo os caracteres
    return base64_decode(strtr($data, '-_', '+/'));
}

// Função para gerar um token JWT
// Recebe um array de dados (payload) e uma chave secreta
function jwt_encode(array $payload, string $secret)
{
    // Cabeçalho do JWT (tipo e algoritmo)
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [];
    // Codifica o cabeçalho e o payload em base64url
    $segments[] = base64url_encode(json_encode($header));
    $segments[] = base64url_encode(json_encode($payload));
    // Junta as partes para assinar
    $signing_input = implode('.', $segments);
    // Gera a assinatura HMAC-SHA256
    $signature = hash_hmac('sha256', $signing_input, $secret, true);
    // Codifica a assinatura
    $segments[] = base64url_encode($signature);
    // Retorna o token JWT completo (header.payload.signature)
    return implode('.', $segments);
}

// Função para validar e decodificar um token JWT
// Retorna o payload se válido, ou null se inválido
function jwt_decode(string $jwt, string $secret)
{
    // Separa o token em partes
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    list($e_header, $e_payload, $e_signature) = $parts;

    // Decodifica o cabeçalho, payload e assinatura
    $header = json_decode(base64url_decode($e_header), true);
    $payload = json_decode(base64url_decode($e_payload), true);
    $signature = base64url_decode($e_signature);

    // Recalcula a assinatura para verificar integridade
    $valid_sig = hash_hmac('sha256', "$e_header.$e_payload", $secret, true);
    if (!hash_equals($valid_sig, $signature)) {
        // Assinatura inválida
        return null;
    }

    // Verifica se o token expirou (caso tenha o campo 'exp')
    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }

    // Retorna os dados do payload se tudo estiver correto
    return $payload;
}
