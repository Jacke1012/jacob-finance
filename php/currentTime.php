<?php
date_default_timezone_set('Europe/Stockholm'); // Set your server's timezone
echo json_encode(array("currentTime" => date('Y-m-d\TH:i')));
?>