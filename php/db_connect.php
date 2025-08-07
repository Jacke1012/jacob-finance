<?php
//$servername = "localhost";
//$username = "financeUser";
//$password = "financeUser";
//$dbname = "finance";

// Create connection
//$conn = new mysqli($servername, $username, $password, $dbname);

//$conn = new mysqli("mariadb", "user", "pass", "database");

$host = getenv('DB_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'finance';
$user = getenv('DB_USER') ?: 'financeuser';
$pass = getenv('DB_PASS') ?: 'supersecret';

$conn = new mysqli($host, $user, $pass, $db);


// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$createExpensesTable = "
CREATE TABLE IF NOT EXISTS expenses (
  id INT(11) NOT NULL AUTO_INCREMENT,
  date_time DATETIME NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  PRIMARY KEY (id)
);
";

if ($conn->query($createExpensesTable) === FALSE) {
    die("Error creating expenses table: " . $conn->error);
}


?>
