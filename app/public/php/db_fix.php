<?php
include 'db_connect.php'; 

$createExpensesTablePostgres = "
CREATE TABLE IF NOT EXISTS expenses (
    id SERIAL PRIMARY KEY,
    date_time TIMESTAMP NOT NULL,
    amount NUMERIC(10,2) NOT NULL,
    description VARCHAR(255) NULL,
    company VARCHAR(255) NULL
);
";

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
