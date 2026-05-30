<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$postgreshost = getenv('POSTGRES_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'finance';
$sqluser = getenv('DB_USER') ?: 'financeuser';
$sqlpass = getenv('DB_PASS') ?: 'supersecret';
$port = getenv('DB_PORT') ?: '5432';

$conn = @pg_connect("host=$postgreshost dbname=$db user=$sqluser password=$sqlpass port=$port");

if (!$conn) {
    fwrite(STDERR, "Connection failed\n");
    exit(1);
}

$queries = [
    'users table' => "
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            email TEXT UNIQUE NOT NULL
        );
    ",
    'expenses table' => "
        CREATE TABLE IF NOT EXISTS expenses (
            id SERIAL PRIMARY KEY,
            date_time TIMESTAMP NOT NULL,
            amount REAL NOT NULL,
            description TEXT,
            company TEXT,
            user_id INTEGER NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ",
];

foreach ($queries as $label => $sql) {
    $result = pg_query($conn, $sql);

    if (!$result) {
        fwrite(STDERR, "Error creating $label: " . pg_last_error($conn) . PHP_EOL);
        pg_close($conn);
        exit(1);
    }
}

pg_close($conn);
fwrite(STDOUT, "db_fix completed\n");
?>
