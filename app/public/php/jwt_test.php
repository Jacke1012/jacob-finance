<?php

require_once 'validate_jwt.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Cloudflare Access JWT Verified ✅</h1>";
echo "<p><strong>Email:</strong> " . htmlspecialchars($decoded->email ?? 'unknown') . "</p>";
echo "<pre>" . htmlspecialchars(json_encode($decoded, JSON_PRETTY_PRINT)) . "</pre>";
