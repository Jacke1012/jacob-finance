<?php
//include 'db_connect.php'; 


require __DIR__ . '/auth_required.php';

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

//User table
$createExpensesTablePostgres = "
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    email TEXT UNIQUE NOT NULL
);
";

$result = pg_query($conn, $createExpensesTablePostgres);

if (!$result) {
    die("Error creating expenses table: " . pg_last_error($conn));
}
//Expenses tabble
$createExpensesTablePostgres = "
CREATE TABLE IF NOT EXISTS expenses (
    id SERIAL PRIMARY KEY,
    date_time TIMESTAMP NOT NULL,
    amount REAL NOT NULL,
    description TEXT,
    company TEXT,
    user_id INTEGER NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
";

$result = pg_query($conn, $createExpensesTablePostgres);

if (!$result) {
    die("Error creating expenses table: " . pg_last_error($conn));
}


?>
