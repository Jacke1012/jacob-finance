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
    $sql = "
        SELECT * FROM expenses
        WHERE date_time BETWEEN $1 AND $2
        ORDER BY date_time DESC
    ";

    $result = pg_query_params($conn, $sql, [$date_one, $date_two]);

    $expenses = [];
    if ($result) {
        while ($row = pg_fetch_assoc($result)) {
            $expenses[] = $row;
        }
    } else {
        echo json_encode(["error" => pg_last_error($conn)]);
        pg_close($conn);
        exit;
    }

    echo json_encode($expenses);
    pg_close($conn);
}
