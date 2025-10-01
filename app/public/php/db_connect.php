<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: /php/login.php');
    exit;
}

$user = $_SESSION['user'];

$userEmail = $user['email'];

$postgreshost = getenv('POSTGRES_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'finance';
$sqluser = getenv('DB_USER') ?: 'financeuser';
$sqlpass = getenv('DB_PASS') ?: 'supersecret';
$port = getenv('DB_PORT') ?: '5432';



$conn = pg_connect("host=$postgreshost dbname=$db user=$sqluser password=$sqlpass port=$port");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

?>
