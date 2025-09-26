<?php
/**
 * Validate a JWT without third-party packages.
 * Supports HS256 (shared secret) and RS256 (RSA public key).
 *
 * @param string      $jwt           The compact JWS string "header.payload.signature"
 * @param string|OpenSSLAsymmetricKey|resource $key  HS256: shared secret; RS256: PEM public key string/resource
 * @param array       $allowedAlgs   e.g. ['HS256'] or ['RS256'] or both
 * @param array       $options       ['leeway'=>0,'iss'=>null,'aud'=>null,'require'=>[]]
 * @return array|null Returns the payload (claims) if valid; null otherwise
 */
function jwt_validate(string $jwt, $key, array $allowedAlgs = ['HS256'], array $options = [])
{
    $opts = array_merge([
        'leeway'  => 0,           // seconds of clock skew allowed for exp/nbf/iat
        'iss'     => null,        // expected issuer (string or array)
        'aud'     => null,        // expected audience (string or array)
        'require' => [],          // list of claim names that MUST be present
        'now'     => time(),      // overrideable for testing
    ], $options);

    // 1) Split token
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return null;
    [$b64Header, $b64Payload, $b64Signature] = $parts;

    // 2) Decode JSON
    $header  = json_decode(b64url_decode($b64Header), true, 512, JSON_THROW_ON_ERROR);
    $payload = json_decode(b64url_decode($b64Payload), true, 512, JSON_THROW_ON_ERROR);
    $sig     = b64url_decode($b64Signature);

    // 3) Basic header checks
    if (!is_array($header) || !isset($header['alg'])) return null;
    $alg = $header['alg'];
    if ($alg === 'none') return null;                // never allow "none"
    if (!in_array($alg, $allowedAlgs, true)) return null;
    if (($header['typ'] ?? 'JWT') !== 'JWT') { /* optional strictness */ }

    // 4) Recompute signature
    $signingInput = $b64Header . '.' . $b64Payload;
    $validSig = false;

    switch ($alg) {
        case 'HS256':
            if (!is_string($key) || $key === '') return null;
            $calc = hash_hmac('sha256', $signingInput, $key, true);
            $validSig = hash_equals($calc, $sig);
            break;

        case 'RS256':
            // $key can be a PEM string or an OpenSSL key resource/object
            $pub = is_string($key) ? openssl_pkey_get_public($key) : $key;
            if (!$pub) return null;
            $ok = openssl_verify($signingInput, $sig, $pub, OPENSSL_ALGO_SHA256);
            if (is_string($key)) { openssl_free_key($pub); } // free only if we created it
            $validSig = ($ok === 1);
            break;

        default:
            return null; // unsupported alg
    }

    if (!$validSig) return null;

    // 5) Claims validation
    $now = (int)$opts['now'];
    $leeway = (int)$opts['leeway'];

    // Required claims (if any)
    foreach ($opts['require'] as $claim) {
        if (!array_key_exists($claim, $payload)) return null;
    }

    // exp (expiration): valid if now <= exp + leeway
    if (isset($payload['exp']) && is_numeric($payload['exp'])) {
        if ($now > (int)$payload['exp'] + $leeway) return null;
    }

    // nbf (not before): valid if now >= nbf - leeway
    if (isset($payload['nbf']) && is_numeric($payload['nbf'])) {
        if ($now < (int)$payload['nbf'] - $leeway) return null;
    }

    // iat (issued at): optional sanity check (not in the future beyond leeway)
    if (isset($payload['iat']) && is_numeric($payload['iat'])) {
        if ($now + $leeway < (int)$payload['iat']) return null;
    }

    // iss (issuer) check
    if ($opts['iss'] !== null) {
        $expectedIss = (array)$opts['iss'];
        if (!isset($payload['iss']) || !in_array($payload['iss'], $expectedIss, true)) {
            return null;
        }
    }

    // aud (audience) check
    if ($opts['aud'] !== null) {
        $expectedAud = (array)$opts['aud'];
        $audClaim = $payload['aud'] ?? null;
        $audList = is_array($audClaim) ? $audClaim : ($audClaim !== null ? [$audClaim] : []);
        if (empty(array_intersect($expectedAud, $audList))) return null;
    }

    // Passed all checks
    return $payload;
}

/** Base64url decoding (RFC 7515) */
function b64url_decode(string $data): string
{
    $data = strtr($data, '-_', '+/');
    $pad = strlen($data) % 4;
    if ($pad) { $data .= str_repeat('=', 4 - $pad); }
    $out = base64_decode($data, true);
    return $out === false ? '' : $out;
}

/** (Optional) Base64url encoding helper if you need to produce JWTs */
function b64url_encode(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}
