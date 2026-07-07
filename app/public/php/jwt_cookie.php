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
  $config = auth_config();
  $now = time();
  $payload = array_merge([
    'iss' => $config['iss'],
    'aud' => $config['aud'],
    'iat' => $now,
    'exp' => $now + $config['ttl'],
  ], $claims);
  return JWT::encode($payload, $config['jwt_secret'], 'HS256');
}

function verify_jwt(string $jwt): object {
  $config = auth_config();
  return JWT::decode($jwt, new Key($config['jwt_secret'], 'HS256'));
}

function set_auth_cookie(string $jwt): void {
  $config = auth_config();
  $params = auth_cookie_options([
    'expires'  => time() + $config['ttl'],
    'path'     => $config['cookie_path'],
    'secure'   => $config['cookie_secure'],
    'httponly' => $config['cookie_httponly'],
    'samesite' => $config['cookie_samesite'],
  ], $config);
  setcookie($config['cookie_name'], $jwt, $params);
  clear_legacy_auth_cookie();
}

function clear_auth_cookie(): void {
  $config = auth_config();
  setcookie($config['cookie_name'], '', auth_cookie_options([
    'expires' => time() - 3600,
    'path'    => $config['cookie_path'],
    'secure'  => $config['cookie_secure'],
    'httponly'=> $config['cookie_httponly'],
    'samesite'=> $config['cookie_samesite'],
  ], $config));
  clear_legacy_auth_cookie();
}

function remaining_ttl(object $token): int {
  return max(0, (int)$token->exp - time());
}

function auth_cookie_options(array $options, array $config): array {
  if ($config['cookie_domain'] !== '') {
    $options['domain'] = $config['cookie_domain'];
  }

  return $options;
}

function clear_legacy_auth_cookie(): void {
  setcookie('financeauth', '', [
    'expires' => time() - 3600,
    'path' => '/',
    'domain' => 'app.jacobsweb.link',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}
