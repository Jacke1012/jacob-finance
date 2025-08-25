<?php
include 'db_connect.php'; // defines $conn and $mysql

header('Cache-Control: public, max-age=60');


$year  = $_GET['year']  ?? null;
$month = $_GET['month'] ?? null;

header('Content-Type: application/json');

if (!is_numeric($year) || !is_numeric($month)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

if ($mysql === true) {
    // MySQL branch
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS total_spent 
         FROM expenses 
         WHERE YEAR(date_time) = ? 
           AND MONTH(date_time) = ?"
    );
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode($result);

    $stmt->close();
    $conn->close();

} else {
    // PostgreSQL branch (YEAR/MONTH -> EXTRACT)
    $sql = "
        SELECT COALESCE(SUM(amount), 0) AS total_spent
        FROM expenses
        WHERE EXTRACT(YEAR FROM date_time) = $1
          AND EXTRACT(MONTH FROM date_time) = $2
    ";

    $result = pg_query_params($conn, $sql, [$year, $month]);
    if ($result) {
        $row = pg_fetch_assoc($result);
        echo json_encode($row);
    } else {
        echo json_encode(["error" => pg_last_error($conn)]);
    }
    pg_close($conn);
}
