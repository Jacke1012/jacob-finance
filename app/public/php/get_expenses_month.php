<?php
include 'db_connect.php'; // should define $conn and $mysql

$year  = $_GET['year']  ?? null;
$month = $_GET['month'] ?? null;

header('Content-Type: application/json');

// Basic validation
if (!is_numeric($year) || !is_numeric($month)) {
    echo json_encode(["error" => "Invalid parameters"]);
    exit;
}

if ($mysql === true) {
    // MySQL branch
    $stmt = $conn->prepare(
        "SELECT * FROM expenses 
         WHERE YEAR(date_time) = ? AND MONTH(date_time) = ? 
         ORDER BY date_time DESC"
    );
    $stmt->bind_param("ii", $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();

    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }

    echo json_encode($expenses);

    $stmt->close();
    $conn->close();

} else {
    // PostgreSQL branch
    // normalize possible "YYYY-MM-DDTHH:MM:SS"
    //if (strpos($date_one, 'T') !== false) $date_one = str_replace('T', ' ', $date_one);
    //if (strpos($date_two, 'T') !== false) $date_two = str_replace('T', ' ', $date_two);

    // explicit casts avoid “text → timestamp” ambiguity
    $sql = "
        SELECT * FROM expenses
        WHERE date_time >= make_date($1::int, $2::int, 1)
        AND date_time <  (make_date($1::int, $2::int, 1) + INTERVAL '1 month')
        ORDER BY date_time DESC
    ";

    $result = pg_query_params($conn, $sql, [$year, $month]);
    if (!$result) {
        echo json_encode(["error" => pg_last_error($conn)]);
        pg_close($conn);
        exit;
    }

    $expenses = [];
    while ($row = pg_fetch_assoc($result)) {
        $expenses[] = $row;
    }

    echo json_encode($expenses);
    pg_close($conn);
}
