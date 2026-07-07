<?php
// config/auth.php
$env = getenv('APP_ENV') ?: 'prod';
$jwtSecret = getenv('JWT_SECRET') ?: '';

if ($env !== 'dev' && $jwtSecret === '') {
  throw new RuntimeException('JWT_SECRET is required when APP_ENV is not dev.');
}

return [
  // keep in a Kubernetes Secret and inject via env
  'jwt_secret' => $jwtSecret !== '' ? $jwtSecret : 'dev-only-change-me',
  'cookie_name' => '__Host-financeauth',
  'cookie_domain' => '',         // Keep empty for a host-only __Host- cookie.
  'cookie_path' => '/',
  'cookie_secure' => true,       // HTTPS only in prod
  'cookie_httponly' => true,     // JS can't read it
  'cookie_samesite' => 'Lax',    // 'Lax' or 'Strict'/'None' (None requires Secure)
  'ttl' => 48 * 60 * 60,
  'refresh_if_remaining' => 24 * 60 * 60,
  'absolute_ttl' => 168 * 60 * 60,
  'iss' => 'https://app.jacobsweb.link',
  'aud' => 'finance-app',
];

//kubectl create secret generic finance-secrets \
// --from-literal=jwt-secret=$(openssl rand -hex 32) \
// -n finance
