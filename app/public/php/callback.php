<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/jwt_cookie.php';


session_start();

$client = new Google_Client();
$client->setClientId(getenv('GOOGLE_CLIENT_ID'));
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(getenv('GOOGLE_REDIRECT_URL'));

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


$jwt = issue_jwt([
  'sub'     => $sub,        // stable user id from IdP
  'email'   => $email,
  'name'    => $name ?? null
]);

set_auth_cookie($jwt);

header('Location: /'); // go to your app
exit;
