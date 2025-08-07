<?php
$servername = "localhost";
$username = "financeUser";
$password = "financeUser";
$dbname = "finance";

// Create connection
//$conn = new mysqli($servername, $username, $password, $dbname);

$conn = new mysqli("mariadb", "user", "pass", "database");


// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
