<?php
include 'db_connect.php'; // defines $conn and $mysql

header('Content-Type: application/json');
header('Cache-Control: private, max-age=0');


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
