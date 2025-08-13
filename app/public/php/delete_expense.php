<?php
include 'db_connect.php'; // defines $conn and $mysql

header('Content-Type: application/json');

if (isset($_POST['id'])) {
    $expenseId = $_POST['id'];

    if ($mysql === true) {
        // MySQL branch
        $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->bind_param("i", $expenseId);

        if ($stmt->execute()) {
            echo json_encode([
                "statusCode" => 200,
                "message" => "Expense deleted successfully."
            ]);
        } else {
            echo json_encode([
                "statusCode" => 500,
                "message" => "Error deleting expense."
            ]);
        }

        $stmt->close();
        $conn->close();

    } else {
        // PostgreSQL branch
        $sql = "DELETE FROM expenses WHERE id = $1";
        $result = pg_query_params($conn, $sql, [$expenseId]);

        if ($result) {
            echo json_encode([
                "statusCode" => 200,
                "message" => "Expense deleted successfully."
            ]);
        } else {
            echo json_encode([
                "statusCode" => 500,
                "message" => "Error deleting expense: " . pg_last_error($conn)
            ]);
        }

        pg_close($conn);
    }

} else {
    echo json_encode([
        "statusCode" => 400,
        "message" => "Expense ID not provided."
    ]);
}
