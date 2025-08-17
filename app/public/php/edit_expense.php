<?php
include 'db_connect.php'; // Ensure this points to your actual database connection script

$expenseId = $_GET['id'] ?? null;


$sql = "
    SELECT *
    FROM expenses
    WHERE id=$1
";

$result = pg_query_params($conn, $sql, [$expenseId]);
if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode($row);
} else {
    echo json_encode(["error" => pg_last_error($conn)]);
}
pg_close($conn);

ob_start();
include __DIR__ . '/delete_expense.php';
ob_end_clean(); 
?>
