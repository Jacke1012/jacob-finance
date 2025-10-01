<?php
include 'db_connect.php'; // defines $conn and $mysql

header('Cache-Control: private, max-age=10');


$year  = $_GET['year']  ?? null;
$month = $_GET['month'] ?? null;


header('Content-Type: application/json');

if (!is_numeric($year) || !is_numeric($month)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

$sql = "
    SELECT amount
    FROM expenses
    WHERE user_email=$1
        AND EXTRACT(YEAR FROM date_time) = $2
        AND EXTRACT(MONTH FROM date_time) = $3
";

$result = pg_query_params($conn, $sql, [$userEmail, $year, $month]);

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

