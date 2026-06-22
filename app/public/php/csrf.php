<?php
require_once __DIR__ . '/jwt_cookie.php';

function csrf_token(): string {
    return signed_token('csrf', 2 * 60 * 60);
}

function require_csrf_token(): void {
    $sentToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!verify_signed_token($sentToken, 'csrf', 2 * 60 * 60)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

function oauth_state_token(): string {
    $state = signed_token('oauth-state', 10 * 60);

    setcookie('finance_oauth_state', $state, [
        'expires' => time() + 10 * 60,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    return $state;
}

function require_oauth_state(?string $state): void {
    $expectedState = $_COOKIE['finance_oauth_state'] ?? '';
    clear_oauth_state_cookie();

    if (
        $state === null
        || $expectedState === ''
        || !hash_equals($expectedState, $state)
        || !verify_signed_token($state, 'oauth-state', 10 * 60)
    ) {
        http_response_code(400);
        echo 'Invalid OAuth state.';
        exit;
    }
}

function signed_token(string $purpose, int $ttl): string {
    $issuedAt = time();
    $nonce = bin2hex(random_bytes(32));
    $payload = $purpose . '|' . $issuedAt . '|' . $ttl . '|' . $nonce;
    $signature = hash_hmac('sha256', $payload, auth_config()['jwt_secret']);

    return $issuedAt . '.' . $ttl . '.' . $nonce . '.' . $signature;
}

function verify_signed_token(string $token, string $purpose, int $maxTtl): bool {
    $parts = explode('.', $token);

    if (count($parts) !== 4) {
        return false;
    }

    [$issuedAt, $ttl, $nonce, $signature] = $parts;

    if (
        !preg_match('/^\d+$/', $issuedAt)
        || !preg_match('/^\d+$/', $ttl)
        || !preg_match('/^[a-f0-9]+$/i', $nonce)
    ) {
        return false;
    }

    $issuedAt = (int)$issuedAt;
    $ttl = (int)$ttl;

    if ($ttl <= 0 || $ttl > $maxTtl || $issuedAt + $ttl < time()) {
        return false;
    }

    $payload = $purpose . '|' . $issuedAt . '|' . $ttl . '|' . $nonce;
    $expectedSignature = hash_hmac('sha256', $payload, auth_config()['jwt_secret']);

    return hash_equals($expectedSignature, $signature);
}

function clear_oauth_state_cookie(): void {
    setcookie('finance_oauth_state', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function is_https_request(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}
