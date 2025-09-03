<?php
echo "<pre>";
echo "MYSQL_BOOL=" . getenv('MYSQL_BOOL') . "\n";
echo "DB_NAME=" . getenv('DB_NAME') . "\n";
//echo "DB_USER=" . getenv('DB_USER') . "\n";
//echo "DB_PASS=" . getenv('DB_PASS') . "\n";
echo "</pre>";


$headers = getallheaders();


foreach($headers as $name => $value){
    echo $name . ': ' . $value . "<br>";
}
?>
