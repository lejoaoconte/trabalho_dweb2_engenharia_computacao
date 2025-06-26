<?php
function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data)
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_encode(array $payload, string $secret)
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $segments = [];
    $segments[] = base64url_encode(json_encode($header));
    $segments[] = base64url_encode(json_encode($payload));
    $signing_input = implode('.', $segments);
    $signature = hash_hmac('sha256', $signing_input, $secret, true);
    $segments[] = base64url_encode($signature);
    return implode('.', $segments);
}

function jwt_decode(string $jwt, string $secret)
{
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return null;
    }
    list($e_header, $e_payload, $e_signature) = $parts;

    $header = json_decode(base64url_decode($e_header), true);
    $payload = json_decode(base64url_decode($e_payload), true);
    $signature = base64url_decode($e_signature);

    $valid_sig = hash_hmac('sha256', "$e_header.$e_payload", $secret, true);
    if (!hash_equals($valid_sig, $signature)) {
        return null;
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}
