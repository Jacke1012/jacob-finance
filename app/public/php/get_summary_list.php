<?php
include 'db_connect.php'; // defines $conn and $mysql

include 'functions.php';

header('Cache-Control: private, max-age=10');


$start_date  = $_GET['start_date']  ?? null;
$end_date = $_GET['end_date'] ?? null;


header('Content-Type: application/json');

if (!is_valid_date($start_date) || !is_valid_date($end_date)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

$sql = "
    SELECT e.amount
    FROM expenses e
    JOIN users u ON e.user_id = u.id
    WHERE u.email=$1
        AND e.date_time BETWEEN $2 AND $3
";

$result = pg_query_params($conn, $sql, [$userEmail, $start_date, $end_date]);
if (!$result) {
    echo json_encode(["error" => pg_last_error($conn)]);
    pg_close($conn);
    exit;
}
$amounts = [];
while($row = pg_fetch_assoc($result)){
    $amounts[] = $row;
}

echo json_encode($amounts);

pg_close($conn);

