<?php

require __DIR__ . '/auth_required.php';
require __DIR__ . '/jwt_cookie.php';


echo verify_jwt($_COOKIE[$c['cookie_name']]);

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Cloudflare Access JWT Verified ✅</h1>";
echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email'] ?? 'unknown') . "</p>";
echo "<pre>" . htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>";
