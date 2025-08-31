<?php
include 'db_connect.php'; // should define $conn and $mysql

#$date_one = $_GET['date_one'] ?? null;
#$date_two = $_GET['date_two'] ?? null;
$week_number = $_GET['week_number'] ?? null;

header('Content-Type: application/json');


// PostgreSQL branch
// normalize possible "YYYY-MM-DDTHH:MM:SS"
//if (strpos($date_one, 'T') !== false) $date_one = str_replace('T', ' ', $date_one);
//if (strpos($date_two, 'T') !== false) $date_two = str_replace('T', ' ', $date_two);

// explicit casts avoid “text → timestamp” ambiguity
$sql = "
    SELECT *
    FROM expenses
    WHERE EXTRACT(WEEK FROM date_time) = $1
    ORDER BY date_time DESC
";

$result = pg_query_params($conn, $sql, [$week_number]);
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

