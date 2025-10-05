<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require __DIR__ . '/../../vendor/autoload.php';

function auth_config() {
  static $cfg;
  if (!$cfg) $cfg = require __DIR__ . '/auth.php';
  return $cfg;
}

function issue_jwt(array $claims): string {
  $c = auth_config();
  $now = time();
  $payload = array_merge([
    'iss' => $c['iss'],
    'aud' => $c['aud'],
    'iat' => $now,
    'exp' => $now + $c['ttl'],
  ], $claims);
  return JWT::encode($payload, $c['jwt_secret'], 'HS256');
}

function verify_jwt(string $jwt): object {
  $c = auth_config();
  return JWT::decode($jwt, new Key($c['jwt_secret'], 'HS256'));
}

function set_auth_cookie(string $jwt): void {
  $c = auth_config();
  $params = [
    'expires'  => time() + $c['ttl'],
    'path'     => $c['cookie_path'],
    'domain'   => $c['cookie_domain'] ?: null,
    'secure'   => $c['cookie_secure'],
    'httponly' => $c['cookie_httponly'],
    'samesite' => $c['cookie_samesite'],
  ];
  setcookie($c['cookie_name'], $jwt, $params);
}

function clear_auth_cookie(): void {
  $c = auth_config();
  setcookie($c['cookie_name'], '', [
    'expires' => time() - 3600,
    'path'    => $c['cookie_path'],
    'domain'  => $c['cookie_domain'] ?: null,
    'secure'  => $c['cookie_secure'],
    'httponly'=> $c['cookie_httponly'],
    'samesite'=> $c['cookie_samesite'],
  ]);
}

function remaining_ttl(object $token): int {
  return max(0, (int)$token->exp - time());
}
