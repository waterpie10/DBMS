<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Approve/Deny actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_id = intval($_POST['leave_id']);
    $action = $_POST['action']; // "approve" or "deny"

    $status = ($action === 'approve') ? 'approved' : 'denied';

    $stmt = $conn->prepare("UPDATE EmployeeLeave SET status = ? WHERE leave_id = ?");
    $stmt->bind_param("si", $status, $leave_id);

    if ($stmt->execute()) {
        $success_message = "Leave request updated successfully.";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch pending leave requests
$result = $conn->query("SELECT 
    EmployeeLeave.leave_id, Employee.name AS employee_name, EmployeeLeave.leave_type, 
    EmployeeLeave.start_date, EmployeeLeave.end_date, EmployeeLeave.comments 
    FROM EmployeeLeave 
    INNER JOIN Employee ON EmployeeLeave.employee_id = Employee.id 
    WHERE EmployeeLeave.status = 'pending'");

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Leave Requests</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Pending Leave Requests</h1>
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php elseif (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Leave Type</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Comments</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['start_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['end_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['comments']); ?></td>
                    <td>
                        <form action="approve_leave_requests.php" method="POST" style="display: inline;">
                            <input type="hidden" name="leave_id" value="<?php echo $row['leave_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <form action="approve_leave_requests.php" method="POST" style="display: inline;">
                            <input type="hidden" name="leave_id" value="<?php echo $row['leave_id']; ?>">
                            <input type="hidden" name="action" value="deny">
                            <button type="submit" class="btn btn-danger btn-sm">Deny</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
