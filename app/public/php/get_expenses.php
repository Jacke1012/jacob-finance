<?php

use Google\Service\CloudControlsPartnerService\Console;
include 'db_connect.php'; // should define $conn and $mysql

include 'functions.php';


$start_date  = $_GET['start_date']  ?? null;
$end_date = $_GET['end_date'] ?? null;

if ($end_date) {
    $date = new DateTime($end_date);
    $date->modify('+1 day');
    $end_date = $date->format('Y-m-d');
}


header('Content-Type: application/json');
header('Cache-Control: no-store, private');


if (!is_valid_date($start_date) || !is_valid_date($end_date)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

$sql = "
    SELECT e.amount, e.company, e.date_time, e.id, e.description
    FROM expenses e
    JOIN users u ON e.user_id = u.id
    WHERE u.email = $1
    AND e.date_time BETWEEN $2 AND $3
    ORDER BY e.date_time DESC
";
$result = pg_query_params($conn, $sql, [$userEmail, $start_date, $end_date]);

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

