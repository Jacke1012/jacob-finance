<?php
// auth_required.php
require __DIR__ . '/jwt_cookie.php';

$c = auth_config();
$cookie = $_COOKIE[$c['cookie_name']] ?? null;

if (!$cookie) {
  header('Location: /php/login.php');  // or 401 JSON for APIs
  exit;
}

try {
  $token = verify_jwt($cookie);
} catch (Throwable $e) {
  clear_auth_cookie();
  header('Location: /php/login.php');
  exit;
}

// Optional: enforce iss/aud in case config changed
if (($token->iss ?? '') !== $c['iss'] || ($token->aud ?? '') !== $c['aud']) {
  clear_auth_cookie();
  header('Location: /php/login.php');
  exit;
}

// Sliding refresh: if < refresh window, reissue a new cookie
if (remaining_ttl($token) < $c['refresh_if_remaining']) {
  $jwt = issue_jwt([
    'email'   => $token->email,
    'name'    => $token->name ?? null,
  ]);
  set_auth_cookie($jwt);
}

// Expose a convenient $user array to the page
$user = [
  'email'   => $token->email,
  'name'    => $token->name ?? null,
];
error_log($user['email']);
