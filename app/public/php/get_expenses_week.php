<?php
include 'db_connect.php'; // should define $conn and $mysql

$date_one = $_GET['date_one'] ?? null;
$date_two = $_GET['date_two'] ?? null;

header('Content-Type: application/json');

// Basic validation (optional)
if (!$date_one || !$date_two) {
    echo json_encode(["error" => "Missing date parameters"]);
    exit;
}

if ($mysql === true) {
    // MySQL branch
    $stmt = $conn->prepare(
        "SELECT * FROM expenses 
         WHERE date_time BETWEEN ? AND ? 
         ORDER BY date_time DESC"
    );
    $stmt->bind_param("ss", $date_one, $date_two);
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
    if (strpos($date_one, 'T') !== false) $date_one = str_replace('T', ' ', $date_one);
    if (strpos($date_two, 'T') !== false) $date_two = str_replace('T', ' ', $date_two);

    // explicit casts avoid “text → timestamp” ambiguity
    $sql = "
        SELECT *
        FROM expenses
        WHERE date_time BETWEEN $1::timestamp AND $2::timestamp
        ORDER BY date_time DESC
    ";

    $result = pg_query_params($conn, $sql, [$date_one, $date_two]);
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
