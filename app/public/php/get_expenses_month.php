<?php
include 'db_connect.php'; // should define $conn and $mysql

$year  = $_GET['year']  ?? null;
$month = $_GET['month'] ?? null;

$userEmail = $_SERVER['Cf-HTTP_CF_ACCESS_AUTHENTICATED_USER_EMAIL'] ?? 'invalid';


header('Content-Type: application/json');

// Basic validation
if (!is_numeric($year) || !is_numeric($month)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

$sql = "
    SELECT * FROM expenses
    WHERE user_email=$3
    AND date_time >= make_date($1::int, $2::int, 1)
    AND date_time <  (make_date($1::int, $2::int, 1) + INTERVAL '1 month')
    ORDER BY date_time DESC
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

