<?php
require_once __DIR__ . '/csrf.php';

header('Content-Type: application/json');
header('Cache-Control: private, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

require_csrf_token();
include 'db_connect.php'; // defines $conn and $mysql

$expenseId = $_POST['id'] ?? null;


$sql = "
    DELETE FROM expenses e
    USING users u
    WHERE e.user_id = u.id
        AND u.email = $1
        AND e.id = $2;
";

$result = pg_query_params($conn, $sql, [$userEmail, $expenseId]);

if ($result) {
    echo json_encode([
        "statusCode" => 200,
        "message" => "Expense deleted successfully."
    ]);
} else {
    echo json_encode([
        "statusCode" => 500,
        "message" => "Error deleting expense: " . pg_last_error($conn)
    ]);
}

pg_close($conn);
