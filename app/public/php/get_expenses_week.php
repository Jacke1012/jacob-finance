<?php
include 'db_connect.php'; // Include your DB connection

//$year = $_GET['year'];
//$month = $_GET['month'];
$date_one = $_GET['date_one'];
$date_two = $_GET['date_two'];
//error_log($date_two);

//$sql = $conn->prepare("SELECT * FROM expenses WHERE YEAR(date_time) = ? AND MONTH(date_time) = ? AND date_time BETWEEN ? AND ? ORDER BY date_time ASC");
$sql = $conn->prepare("SELECT * FROM expenses WHERE date_time BETWEEN ? AND ? ORDER BY date_time DESC");
//$sql->bind_param("iiss", $year, $month, $date_one, $date_two); // 'ii' specifies that both parameters are integers
$sql->bind_param("ss", $date_one, $date_two);
$sql->execute();
$result = $sql->get_result();

$expenses = array();
while($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

header('Content-Type: application/json');
echo json_encode($expenses);

$conn->close();
?>
