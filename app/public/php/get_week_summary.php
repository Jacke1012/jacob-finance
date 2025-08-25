<?php
include 'db_connect.php'; // defines $conn and $mysql

//header('Cache-Control: public, max-age=60');


$date_one = $_GET["date_one"] ?? null;
$date_two = $_GET["date_two"] ?? null;

header('Content-Type: application/json');

if (!$date_one || !$date_two) {
    echo json_encode(["error" => "Missing date parameters"]);
    exit;
}

$sql = "
    SELECT COALESCE(SUM(amount),0) AS week_summary
    FROM expenses
    WHERE date_time BETWEEN $1 AND $2
";

$result = pg_query_params($conn, $sql, [$date_one, $date_two]);
if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode($row);
} else {
    echo json_encode(["error" => pg_last_error($conn)]);
}
pg_close($conn);

