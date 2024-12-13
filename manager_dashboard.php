<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Manager Dashboard</h1>
    <a href="logout.php" class="btn btn-danger mb-3">Logout</a>
    <a href="employee_directory.php" class="btn btn-primary w-100 mt-3">View Employee Directory</a>
    <a href="approve_leave_requests.php" class="btn btn-secondary w-100 mt-3">Approve Leave Requests</a>
    <a href="absenteeism_report.php" class="btn btn-info w-100 mt-3">Absenteeism Report</a>
    <a href="add_employee.php" class="btn btn-success w-100 mt-3">Add New Employee</a>
    <a href="payroll_report.php" class="btn btn-warning w-100 mt-3">Payroll Report</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
