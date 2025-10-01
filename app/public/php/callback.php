<?php
require __DIR__ . '/../../vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setClientId(getenv('GOOGLE_CLIENT_ID'));
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri('https://finance.jacobsweb.link/php/callback.php');

if (!isset($_GET['code'])) {
    http_response_code(400);
    echo "Missing authorization code.";
    exit;
}

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
    http_response_code(401);
    echo "OAuth error: " . htmlspecialchars($token['error_description'] ?? $token['error']);
    exit;
}

$client->setAccessToken($token);
$oauth = new Google_Service_Oauth2($client);
$me = $oauth->userinfo->get(); // has id, email, verifiedEmail, name, picture

// Basic allowlist example
$allowedDomains = ['yourcompany.com'];
$emailDomain = substr(strrchr($me->email, "@"), 1);
if (!in_array($emailDomain, $allowedDomains, true)) {
    http_response_code(403);
    echo "Email domain not allowed.";
    exit;
}

// Create your app session
$_SESSION['user'] = [
    'id' => $me->id,
    'email' => $me->email,
    'name' => $me->name,
    'picture' => $me->picture,
];
header('Location: /'); // go to your app
exit;
