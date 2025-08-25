<?php
header("Access-Control-Allow-Origin: *");
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




$createExpensesTablePostgres = "
CREATE TABLE IF NOT EXISTS expenses (
    id SERIAL PRIMARY KEY,
    date_time TIMESTAMP NOT NULL,
    amount NUMERIC(10,2) NOT NULL,
    description VARCHAR(255) NULL,
    compnay VARCHAR(255) NULL
);
";

// $createExpensesTablePostgres = "
// CREATE TABLE IF NOT EXISTS expenses (
//     id SERIAL PRIMARY KEY,
//     date_time TIMESTAMP NOT NULL,
//     amount NUMERIC(10,2) NOT NULL,
//     description VARCHAR(255) NOT NULL
// );
// ";

$result = pg_query($conn, $createExpensesTablePostgres);

if (!$result) {
    die("Error creating expenses table: " . pg_last_error($conn));
}


$alterTable = "
  ALTER TABLE expenses ADD COLUMN IF NOT EXISTS company VARCHAR(255);
  ";

$result = pg_query($conn, $alterTable);

if (!$result) {
    die("Error altering expenses table: " . pg_last_error($conn));
}

$alterTable = "
  ALTER TABLE expenses ALTER COLUMN description DROP NOT NULL;
  ";

$result = pg_query($conn, $alterTable);

if (!$result) {
    die("Error altering expenses table: " . pg_last_error($conn));
}


?>
