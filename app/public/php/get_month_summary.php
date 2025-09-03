<?php
include 'db_connect.php'; // defines $conn and $mysql

header('Cache-Control: public, max-age=10');


$year  = $_GET['year']  ?? null;
$month = $_GET['month'] ?? null;

$userEmail = $headers['Cf-Access-Authenticated-User-Email'] ?? 'invalid';


header('Content-Type: application/json');

if (!is_numeric($year) || !is_numeric($month)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

$sql = "
    SELECT COALESCE(SUM(amount), 0) AS month_summary
    FROM expenses
    WHERE user_email=$1
        AND EXTRACT(YEAR FROM date_time) = $2
        AND EXTRACT(MONTH FROM date_time) = $3
";

$result = pg_query_params($conn, $sql, [$userEmail, $year, $month]);
if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode($row);
} else {
    echo json_encode(["error" => pg_last_error($conn)]);
}
pg_close($conn);

