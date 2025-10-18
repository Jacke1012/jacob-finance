<?php
// config/auth.php
return [
  // keep in a Kubernetes Secret and inject via env
  'jwt_secret' => getenv('JWT_SECRET') ?: 'dev-only-change-me',
  'cookie_name' => 'financeauth',
  'cookie_domain' => 'app.finance.jacobsweb.link',         // e.g. '.jacobsweb.link' if you need subdomains
  'cookie_path' => '/',
  'cookie_secure' => true,       // HTTPS only in prod
  'cookie_httponly' => true,     // JS can't read it
  'cookie_samesite' => 'Lax',    // or 'Strict'/'None' (None requires Secure)
  'ttl' => 24 * 60 * 60,
  'refresh_if_remaining' => 12 * 60 * 60,
  'absolute_ttl' => 96 * 60 * 60,
  'iss' => 'https://app.finance.jacobsweb.link',
  'aud' => 'finance-app',
];

//kubectl create secret generic finance-secrets \
// --from-literal=jwt-secret=$(openssl rand -hex 32) \
// -n finance

