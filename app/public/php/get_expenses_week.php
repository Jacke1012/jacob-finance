<?php
include 'db_connect.php'; // should define $conn and $mysql

#$date_one = $_GET['date_one'] ?? null;
#$date_two = $_GET['date_two'] ?? null;
$week_number = $_GET['week_number'] ?? null;
$year_input = $_GET['year_input'] ?? null;

$userEmail = $headers['Cf-Access-Authenticated-User-Email'] ?? 'invalid';


header('Content-Type: application/json');


$sql = "
    SELECT *
    FROM expenses
    WHERE user_email=$3
    AND EXTRACT(YEAR FROM date_time) = $1
    AND EXTRACT(WEEK FROM date_time) = $2
    ORDER BY date_time DESC
";

$result = pg_query_params($conn, $sql, [$year_input,$week_number,$userEmail]);
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

