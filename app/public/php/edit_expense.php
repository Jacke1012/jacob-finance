<?php
include 'db_connect.php'; // Ensure this points to your actual database connection script

// Check if the necessary data is provided
if (isset($_POST['id']) && isset($_POST['date_time']) && isset($_POST['amount']) && isset($_POST['description'])) {
    $id = $_POST['id'];
    $date_time = $_POST['date_time'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];

    // Prepare an update statement
    $stmt = $conn->prepare("UPDATE expenses SET date_time = ?, amount = ?, description = ? WHERE id = ?");
    $stmt->bind_param("sdsi", $date_time, $amount, $description, $id); // 'sdsi' => string, double, string, integer

    // Execute the statement
    if ($stmt->execute()) {
        echo json_encode(array("statusCode" => 200, "message" => "Expense updated successfully."));
    } else {
        echo json_encode(array("statusCode" => 500, "message" => "Failed to update expense."));
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(array("statusCode" => 400, "message" => "Not all required fields were provided."));
}
?>
