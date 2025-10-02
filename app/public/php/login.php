<?php
require __DIR__ . '/../../vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(getenv('GOOGLE_CLIENT_ID'));
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(getenv('GOOGLE_REDIRECT_URL'));
$client->setScopes(['openid','email','profile']);
$client->setPrompt('select_account');
$client->setAccessType('online'); // or 'offline' if you need refresh tokens
$client->setIncludeGrantedScopes(true);

$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
