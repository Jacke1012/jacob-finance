<?php
require __DIR__ . '/vendor/autoload.php';

session_start();

$client = new Google_Client();
$client->setClientId(getenv('GOOGLE_CLIENT_ID'));
$client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri('https://finance.jacobsweb.link/php/callback.php');
$client->setScopes(['openid','email','profile']);
$client->setPrompt('select_account');
$client->setAccessType('online'); // or 'offline' if you need refresh tokens
$client->setIncludeGrantedScopes(true);

$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
