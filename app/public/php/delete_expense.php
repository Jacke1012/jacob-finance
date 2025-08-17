<?php
include 'db_connect.php'; // defines $conn and $mysql

header('Content-Type: application/json');

if (isset($expenseId)) {
    $expenseId = $expenseId;
}
elseif (isset($_POST['id'])) {
    $expenseId = $_POST['id'];
}
else{
    echo json_encode(["statusCode" => 400, "message" => "Missing expenseId"]);
    exit;
}

$sql = "
    DELETE
    FROM expenses
    WHERE id=$1
";

$result = pg_query_params($conn, $sql, [$expenseId]);

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
