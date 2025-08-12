<?php
include "db_connect.php";

$date_one = $_GET["date_one"];
$date_two = $_GET["date_two"];

$sql = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS week_summary FROM expenses WHERE date_time BETWEEN ? and ?");
$sql->bind_param("ss", $date_one,$date_two);
$sql->execute();
$result = $sql->get_result()->fetch_assoc();

echo json_encode($result);

$conn->close();

?>