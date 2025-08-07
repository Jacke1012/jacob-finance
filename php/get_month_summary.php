<?php
include 'db_connect.php'; // Include your DB connection

$year = $_GET['year'];
$month = $_GET['month'];

// SQL to calculate the sum of amounts for the given year and month
$sql = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total_spent FROM expenses WHERE YEAR(date_time) = ? AND MONTH(date_time) = ?");
$sql->bind_param("ii", $year, $month);
$sql->execute();
$result = $sql->get_result()->fetch_assoc();

echo json_encode($result);

$conn->close();
?>
