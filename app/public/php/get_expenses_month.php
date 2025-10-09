<?php
include 'db_connect.php'; // should define $conn and $mysql

$year  = $_GET['year']  ?? null;
$month = $_GET['month'] ?? null;


header('Content-Type: application/json');
header('Cache-Control: private, max-age=0');

// Basic validation
if (!is_numeric($year) || !is_numeric($month)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

$sql = "
    SELECT e.amount, e.company, e.date_time, e.id, e.description
    FROM expenses e
    JOIN users u ON e.user_id = u.id
    WHERE u.email = $3
    AND e.date_time >= make_date($1::int, $2::int, 1)
    AND e.date_time <  (make_date($1::int, $2::int, 1) + INTERVAL '1 month')
    ORDER BY e.date_time DESC
";
$result = pg_query_params($conn, $sql, [$year, $month, $userEmail]);
if (!$result) {
    echo json_encode(["error" => pg_last_error($conn)]);
    pg_close($conn);
    exit;
}

$expenses = [];
while ($row = pg_fetch_assoc($result)) {
    $expenses[] = $row;
}

echo json_encode($expenses);
pg_close($conn);

