<?php
require 'validate_jwt.php';
//$servername = "localhost";
//$username = "financeUser";
//$password = "financeUser";
//$dbname = "finance";

// Create connection
//$conn = new mysqli($servername, $username, $password, $dbname);

//$conn = new mysqli("mariadb", "user", "pass", "database");



$postgreshost = getenv('POSTGRES_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'finance';
$user = getenv('DB_USER') ?: 'financeuser';
$pass = getenv('DB_PASS') ?: 'supersecret';
$port = getenv('DB_PORT') ?: '5432';

#$mysql = getenv('MYSQL_BOOL') ?: false;


$conn = pg_connect("host=$postgreshost dbname=$db user=$user password=$pass port=$port");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

?>
