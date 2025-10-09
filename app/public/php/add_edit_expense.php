<?php
include 'db_connect.php'; // Include your DB connection

$date_time   = $_POST['date_time'] ?? null;
$amount      = $_POST['amount'] ?? null;
$company     = $_POST['company'] ?? null;
$description = $_POST['description'] ?? null;
$edit_id   = $_POST['edit_id'] ?? null;
header('Cache-Control: private, max-age=0');


// Convert "YYYY-MM-DDTHH:MM" to "YYYY-MM-DD HH:MM"
if ($date_time && strpos($date_time, 'T') !== false) {
    $date_time = str_replace('T', ' ', $date_time);
}


if (empty($edit_id)){
    $sql = "
    INSERT INTO expenses (date_time, amount, description, company, user_id)
    VALUES (
        $1,
        $2,
        $3,
        $4,
        (SELECT id FROM users WHERE email = $5)
    )";

    $result = pg_query_params($conn, $sql, [$date_time, $amount, $description, $company, $userEmail]);
}
else{
    $sql = "
    UPDATE expenses
    SET date_time = $1,
        amount = $2,
        description = $3,
        company = $4
    WHERE id = $5
      AND user_id = (SELECT id FROM users WHERE email = $6)
      ";
      
    $result = pg_query_params($conn, $sql, [$date_time, $amount, $description, $company, $edit_id, $userEmail]);
}


if ($result) {
    echo json_encode(["statusCode" => 200]);
} else {
    // Optional: expose pg_last_error($conn) while debugging
    echo json_encode(["statusCode" => 201]);
}
pg_close($conn);


?>
