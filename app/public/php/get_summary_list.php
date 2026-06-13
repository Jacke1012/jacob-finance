<?php
include 'db_connect.php'; // defines $conn and $mysql

include 'functions.php';


$start_date  = $_GET['start_date']  ?? null;
$end_date = $_GET['end_date'] ?? null;

// Options: clientside, sqlside, serverside
$summary_addition_mode = 'sqlside';

if ($end_date) {
    $date = new DateTime($end_date);
    $date->modify('+1 day');
    $end_date = $date->format('Y-m-d');
}


header('Content-Type: application/json');
header('Cache-Control: no-store, private');


if (!is_valid_date($start_date) || !is_valid_date($end_date)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

$amounts = [];

if ($summary_addition_mode == 'clientside') {
    $sql = "
        SELECT e.amount
        FROM expenses e
        JOIN users u ON e.user_id = u.id
        WHERE u.email=$1
            AND e.date_time BETWEEN $2 AND $3
    ";

    $result = pg_query_params($conn, $sql, [$userEmail, $start_date, $end_date]);
    if (!$result) {
        echo json_encode(["error" => pg_last_error($conn)]);
        pg_close($conn);
        exit;
    }

    while($row = pg_fetch_assoc($result)){
        $amounts[] = $row;
    }
} elseif ($summary_addition_mode == 'sqlside') {
    $sql = "
        SELECT COALESCE(SUM(e.amount), 0) AS amount
        FROM expenses e
        JOIN users u ON e.user_id = u.id
        WHERE u.email=$1
            AND e.date_time BETWEEN $2 AND $3
    ";

    $result = pg_query_params($conn, $sql, [$userEmail, $start_date, $end_date]);
    if (!$result) {
        echo json_encode(["error" => pg_last_error($conn)]);
        pg_close($conn);
        exit;
    }

    while($row = pg_fetch_assoc($result)){
        $amounts[] = $row;
    }
} elseif ($summary_addition_mode == 'serverside') {
    $sql = "
        SELECT e.amount
        FROM expenses e
        JOIN users u ON e.user_id = u.id
        WHERE u.email=$1
            AND e.date_time BETWEEN $2 AND $3
    ";

    $result = pg_query_params($conn, $sql, [$userEmail, $start_date, $end_date]);
    if (!$result) {
        echo json_encode(["error" => pg_last_error($conn)]);
        pg_close($conn);
        exit;
    }

    $total = 0;
    while($row = pg_fetch_assoc($result)){
        $total += $row['amount'];
    }

    $amounts[] = ["amount" => (string)$total];
} else {
    echo json_encode(["error" => "Invalid summary addition mode"]);
    pg_close($conn);
    exit;
}

echo json_encode($amounts);

pg_close($conn);

