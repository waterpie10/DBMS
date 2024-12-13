<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_SESSION['employee_id'];
    $leave_type = $conn->real_escape_string($_POST['leave_type']);
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);
    $comments = $conn->real_escape_string($_POST['comments']);
    $status = 'pending';

    // Insert into EmployeeLeave table
    $stmt = $conn->prepare("INSERT INTO EmployeeLeave (employee_id, leave_type, start_date, end_date, comments, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $employee_id, $leave_type, $start_date, $end_date, $comments, $status);

    if ($stmt->execute()) {
        $success_message = "Leave request submitted successfully.";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Leave</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Request Leave</h1>
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <form action="request_leave.php" method="POST" class="mt-4">
        <div class="mb-3">
            <label for="leave_type" class="form-label">Leave Type</label>
            <select name="leave_type" id="leave_type" class="form-select" required>
                <option value="sick">Sick</option>
                <option value="vacation">Vacation</option>
                <option value="personal">Personal</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="comments" class="form-label">Comments (Optional)</label>
            <textarea name="comments" id="comments" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-primary w-100">Submit Request</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
