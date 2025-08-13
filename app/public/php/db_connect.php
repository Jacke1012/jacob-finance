<?php
//$servername = "localhost";
//$username = "financeUser";
//$password = "financeUser";
//$dbname = "finance";

// Create connection
//$conn = new mysqli($servername, $username, $password, $dbname);

//$conn = new mysqli("mariadb", "user", "pass", "database");

$postgreshost = getenv('POSTGRES_HOST') ?: 'localhost';
$mysqlhost = getenv('MYSQL_HOST') ?: 'localhost';
$db = getenv('DB_NAME') ?: 'finance';
$user = getenv('DB_USER') ?: 'financeuser';
$pass = getenv('DB_PASS') ?: 'supersecret';

#$mysql = getenv('MYSQL_BOOL') ?: false;

$mysql = false;


if ($mysql === true){
  $conn = new mysqli($mysqlhost, $user, $pass, $db);

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $createExpensesTableMySql = "
  CREATE TABLE IF NOT EXISTS expenses (
    id INT(11) NOT NULL AUTO_INCREMENT,
    date_time DATETIME NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    PRIMARY KEY (id)
  );
  ";

  
  if ($conn->query($createExpensesTableMySql) === FALSE) {
      die("Error creating expenses table: " . $conn->error);
  }
} else{
  $conn = pg_connect("host=$postgreshost dbname=$db user=$user password=$pass");

  if (!$conn) {
      die("Connection failed: " . pg_last_error());
  }

  $createExpensesTablePostgres = "
  CREATE TABLE IF NOT EXISTS expenses (
      id SERIAL PRIMARY KEY,
      date_time TIMESTAMP NOT NULL,
      amount NUMERIC(10,2) NOT NULL,
      description VARCHAR(255) NOT NULL
  );
  ";

  $result = pg_query($conn, $createExpensesTablePostgres);

  if (!$result) {
      die("Error creating expenses table: " . pg_last_error($conn));
  }
}

?>
