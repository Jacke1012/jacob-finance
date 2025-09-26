<?php
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

require __DIR__ . '/../../vendor/autoload.php';

// ---- CONFIG ----
$teamName = 'jjhomese';            // e.g. 'acme'
$expectedAud = '7766261b42c9544a9ca04b3f705f07e107278298a118104b454456e058dd8ba8';           // e.g. '9c0fd1b7b7c0a...'
$expectedIss = "https://{$teamName}.cloudflareaccess.com"; // issuer
$jwksUrl    = "https://{$teamName}.cloudflareaccess.com/cdn-cgi/access/certs";

// ---- GET TOKEN ----
// Cloudflare will send either the header or the cookie. Prefer the header.
$token = $_SERVER['HTTP_CF_ACCESS_JWT_ASSERTION'] ?? ($_COOKIE['CF_Authorization'] ?? null);

// Optional: support `Authorization: Bearer <token>` if you proxy it yourself.
if (!$token && isset($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
    $token = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
}

if (!$token) {
    http_response_code(401);
    echo 'Missing Cloudflare Access token';
    exit;
}

// ---- FETCH & CACHE JWK SET ----
// Cache this (APCu/file) to avoid fetching on every request.
$cacheFile = sys_get_temp_dir() . '/cf_access_jwks.json';
$cacheTtl  = 60 * 60; // 1 hour

$jwks = null;
if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
    $jwks = json_decode(file_get_contents($cacheFile), true);
} else {
    $jwksJson = file_get_contents($jwksUrl);
    if ($jwksJson === false) {
        http_response_code(500);
        echo 'Failed to fetch Cloudflare JWKs';
        exit;
    }
    $jwks = json_decode($jwksJson, true);
    file_put_contents($cacheFile, $jwksJson);
}

if (!isset($jwks['keys'])) {
    http_response_code(500);
    echo 'Invalid Cloudflare JWK set';
    exit;
}

// ---- PICK KEY BY KID ----
[$hdrB64] = explode('.', $token) + [null];
if (!$hdrB64) {
    http_response_code(401);
    echo 'Malformed JWT';
    exit;
}
$header = JWT::jsonDecode(JWT::urlsafeB64Decode($hdrB64));
$kid = $header->kid ?? null;

$keys = JWK::parseKeySet($jwks); // returns [kid => Key]
$key  = $kid && isset($keys[$kid]) ? $keys[$kid] : null;

if (!$key) {
    http_response_code(401);
    echo 'No matching JWK for token kid';
    exit;
}

// ---- VERIFY SIGNATURE & STANDARD CLAIMS ----
try {
    // RS256 is used by Cloudflare Access
    // OLD: $decoded = JWT::decode($token, $key, ['RS256']);
    $decoded = JWT::decode($token, $key);
} catch (Throwable $e) {
    http_response_code(401);
    echo 'Invalid token: ' . $e->getMessage();
    exit;
}

// ---- VALIDATE APP-SPECIFIC CLAIMS ----
// iss should be your team Access issuer; aud should contain your app’s AUD tag.
$issOk = (isset($decoded->iss) && rtrim($decoded->iss, '/') === rtrim($expectedIss, '/'));
$audOk = false;
if (isset($decoded->aud)) {
    // aud may be a string or array
    $audOk = is_array($decoded->aud) ? in_array($expectedAud, $decoded->aud, true) : ($decoded->aud === $expectedAud);
}

if (!$issOk || !$audOk) {
    http_response_code(403);
    echo 'Token not meant for this application';
    exit;
}

// (Optional) More checks:
if (isset($decoded->exp) && time() >= $decoded->exp) {
    http_response_code(401);
    echo 'Token expired';
    exit;
}

// ---- SUCCESS ----
// $decoded now has the claims (email, sub, country, iat, nbf, exp, etc.)
/*
Example useful claims:
$decoded->email        // user’s email
$decoded->identity_nonce
$decoded->sub          // subject
$decoded->nbf, ->iat, ->exp
*/
//header('X-Auth-User: ' . ($decoded->email ?? 'unknown'));
//echo 'OK, token valid';

