<?php

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

$sql = "
    INSERT INTO users (email)
    VALUES ($1)
    ON CONFLICT(email) DO NOTHING;
";

$result = pg_query_params($conn, $sql, [$userEmail]);
if (!$result) {
    echo json_encode(["error" => pg_last_error($conn)]);
    pg_close($conn);
    exit;
}

?>
