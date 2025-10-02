<?php
require __DIR__ . '/jwt_cookie.php';
clear_auth_cookie();
header('Location: /login.php');
exit;
