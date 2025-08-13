<?php
include 'db_connect.php'; // Include your DB connection

$date_time   = $_POST['date_time'] ?? null;
$amount      = $_POST['amount'] ?? null;
$description = $_POST['description'] ?? null;

// Convert "YYYY-MM-DDTHH:MM" to "YYYY-MM-DD HH:MM"
if ($date_time && strpos($date_time, 'T') !== false) {
    $date_time = str_replace('T', ' ', $date_time);
}

if($mysql === true){
    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO expenses (date_time, amount, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $date_time, $amount, $description); // 'sds' means string, double, string

    if ($stmt->execute()) {
        echo json_encode(array("statusCode" => 200));
    } else {
        echo json_encode(array("statusCode" => 201));
    }
    
    $stmt->close();
    $conn->close();
}else{

    // Parameterized insert ($1, $2, $3 are placeholders)
    $sql = 'INSERT INTO expenses (date_time, amount, description) VALUES ($1, $2, $3)';

    $result = pg_query_params($conn, $sql, [$date_time, $amount, $description]);

    if ($result) {
        echo json_encode(["statusCode" => 200]);
    } else {
        // Optional: expose pg_last_error($conn) while debugging
        echo json_encode(["statusCode" => 201]);
    }
    pg_close($conn);
}

?>
