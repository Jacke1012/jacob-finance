<?php
//$servername = "localhost";
//$username = "financeUser";
//$password = "financeUser";
//$dbname = "finance";

// Create connection
//$conn = new mysqli($servername, $username, $password, $dbname);

//$conn = new mysqli("mariadb", "user", "pass", "database");

$postgreshost = getenv('POSTGRES_HOST') ?: 'localhost';
$mysqlhost = getenv('MYSQL_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'finance';
$user = getenv('DB_USER') ?: 'financeuser';
$pass = getenv('DB_PASS') ?: 'supersecret';

#$mysql = getenv('MYSQL_BOOL') ?: false;


$conn = pg_connect("host=$postgreshost dbname=$db user=$user password=$pass");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

?>
