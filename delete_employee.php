<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and authorized
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    die("Unauthorized access.");
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id'])) {
    $employee_id = intval($_POST['employee_id']);
    $deleted_by = $_SESSION['username']; // Store the username of the logged-in manager

    // Step 1: Fetch employee details into variables
    $query = "SELECT name FROM Employee WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();

    if ($employee) {
        $name = $employee['name'];

        // Debugging: Ensure values are being fetched correctly
        echo "Debug Info: Employee ID = $employee_id, Name = $name, Deleted By = $deleted_by<br>";

        // Step 2: Disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=0");

        // Step 3: Insert into TerminationLog
        $log_query = "
            INSERT INTO TerminationLog (employee_id, name, termination_date, deleted_by)
            VALUES (?, ?, NOW(), ?)
        ";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("iss", $employee_id, $name, $deleted_by);

        if (!$log_stmt->execute()) {
            echo "Error logging termination: " . $conn->error . "<br>";
            error_log("SQL Error: " . $conn->error);
            die("Failed to insert termination log.");
        }

        // Step 4: Delete the employee from the Employee table
        $delete_query = "DELETE FROM Employee WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $employee_id);

        if ($delete_stmt->execute()) {
            // Set success message in session
            $_SESSION['success_message'] = "Successfully deleted employee.";
            // Redirect to employee directory upon success
            header("Location: employee_directory.php");
            exit();
        } else {
            echo "Error deleting employee: " . $delete_stmt->error . "<br>";
        }

        // Step 5: Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
    } else {
        echo "Employee not found.";
    }
    $stmt->close();
    $conn->close();
}
?>