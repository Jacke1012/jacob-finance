<?php
include 'db_connect.php'; // defines $conn and $mysql

//header('Cache-Control: public, max-age=60');


#$date_one = $_GET['date_one'] ?? null;
#$date_two = $_GET['date_two'] ?? null;
$week_number = $_GET['week_number'] ?? null;
$year_input = $_GET['year_input'] ?? null;

$userEmail = $headers['Cf-Access-Authenticated-User-Email'] ?? 'invalid';


header('Content-Type: application/json');


$sql = "
    SELECT COALESCE(SUM(amount),0) AS week_summary
    FROM expenses
    WHERE user_email=$1
    AND EXTRACT(YEAR FROM date_time) = $2
    AND EXTRACT(WEEK FROM date_time) = $3
";

$result = pg_query_params($conn, $sql, [$userEmail, $year_input,$week_number]);
if ($result) {
    $row = pg_fetch_assoc($result);
    echo json_encode($row);
} else {
    echo json_encode(["error" => pg_last_error($conn)]);
}
pg_close($conn);

