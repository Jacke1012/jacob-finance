<?php
include 'db_connect.php'; // Include your DB connection

$date_time = $_POST['date_time'];
$amount = $_POST['amount'];
$description = $_POST['description'];

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
?>
