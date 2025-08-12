<?php
// Include the database connection file
include 'db_connect.php';

// Check if the id POST variable is set
if (isset($_POST['id'])) {
    $expenseId = $_POST['id'];

    // Prepare a delete statement
    $sql = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $sql->bind_param("i", $expenseId); // 'i' specifies the variable type is integer

    // Attempt to execute the prepared statement
    if ($sql->execute()) {
        // If the query was successful
        echo json_encode(array("statusCode" => 200, "message" => "Expense deleted successfully."));
    } else {
        // If the query failed
        echo json_encode(array("statusCode" => 500, "message" => "Error deleting expense."));
    }

    // Close statement
    $sql->close();
} else {
    // If the required data was not provided
    echo json_encode(array("statusCode" => 400, "message" => "Expense ID not provided."));
}

// Close connection
$conn->close();
?>
