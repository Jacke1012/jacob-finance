<?php
include 'db_connect.php'; // defines $conn and $mysql

header('Cache-Control: private, max-age=10');



#$date_one = $_GET['date_one'] ?? null;
#$date_two = $_GET['date_two'] ?? null;
$week_number = $_GET['week_number'] ?? null;
$year_input = $_GET['year_input'] ?? null;


header('Content-Type: application/json');


$sql = "
    SELECT e.amount
    FROM expenses e
    JOIN users u ON e.user_id = u.id
    WHERE u.email=$1
    AND EXTRACT(YEAR FROM e.date_time) = $2
    AND EXTRACT(WEEK FROM e.date_time) = $3
";

$result = pg_query_params($conn, $sql, [$userEmail, $year_input,$week_number]);
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

