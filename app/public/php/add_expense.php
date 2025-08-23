<?php
include 'db_connect.php'; // Include your DB connection

$date_time   = $_POST['date_time'] ?? null;
$amount      = $_POST['amount'] ?? null;
$description = $_POST['description'] ?? null;
$edit_id   = $_POST['edit_id'] ?? null;


// Convert "YYYY-MM-DDTHH:MM" to "YYYY-MM-DD HH:MM"
if ($date_time && strpos($date_time, 'T') !== false) {
    $date_time = str_replace('T', ' ', $date_time);
}


if (empty($edit_id)){
    $sql = 'INSERT INTO expenses (date_time, amount, description) VALUES ($1, $2, $3)';

    $result = pg_query_params($conn, $sql, [$date_time, $amount, $description]);
}
else{
    $sql = 'UPDATE expenses SET date_time=$1, amount=$2, description=$3 WHERE id=$4';

    $result = pg_query_params($conn, $sql, [$date_time, $amount, $description, $edit_id]);
}


if ($result) {
    echo json_encode(["statusCode" => 200]);
} else {
    // Optional: expose pg_last_error($conn) while debugging
    echo json_encode(["statusCode" => 201]);
}
pg_close($conn);


?>
