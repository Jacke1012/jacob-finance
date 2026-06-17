<?php
include 'db_connect.php'; // Ensure this points to your actual database connection script

$expenseId = $_GET['id'] ?? null;

header('Cache-Control: private, max-age=0');



$sql = "
    SELECT e.date_time, e.amount, e.description, e.company
    FROM expenses e
    JOIN users u ON e.user_id = u.id
    WHERE u.email = $1
    AND e.id=$2
";

$result = pg_query_params($conn, $sql, [$userEmail,$expenseId]);
if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode($row);
} else {
    echo json_encode(["error" => pg_last_error($conn)]);
}
pg_close($conn);

//ob_start();
//include __DIR__ . '/delete_expense.php';
//ob_end_clean(); 
?>
