<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Restrict access to managers only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    die("Unauthorized access.");
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$payrollData = [];
$totalPayroll = 0;
$averageSalary = 0;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form inputs
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $department = $_POST['department'] ?? '';

    // Validate inputs
    if (!$startDate || !$endDate) {
        $errorMessage = "Start date and end date are required.";
    } elseif ($startDate > $endDate) {
        $errorMessage = "Start date cannot be later than end date.";
    } else {
        // Build query
        $query = "
            SELECT 
                e.name AS employee_name,
                e.department,
                e.position AS job_title,
                p.base_salary,
                p.bonuses,
                p.deductions,
                p.net_salary,
                p.payroll_date
            FROM Payroll p
            JOIN Employee e ON p.employee_id = e.id
            WHERE p.payroll_date BETWEEN ? AND ?
        ";
        if ($department) {
            $query .= " AND e.department = ?";
        }

        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        if ($department) {
            $stmt->bind_param("sss", $startDate, $endDate, $department);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch data and calculate totals
        while ($row = $result->fetch_assoc()) {
            $payrollData[] = $row;
            $totalPayroll += $row['net_salary'];
        }
        $averageSalary = count($payrollData) > 0 ? $totalPayroll / count($payrollData) : 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <a href="manager_dashboard.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
    <h1 class="text-center">Payroll Report</h1>
    
    <!-- Form -->
    <form method="POST" action="payroll_report.php" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label for="department" class="form-label">Department (Optional)</label>
                <select id="department" name="department" class="form-select">
                    <option value="">All Departments</option>
                    <option value="Operations">Operations</option>
                    <option value="Marketing">Marketing</option>
                    <option value="Finance">Finance</option>
                    <option value="Technology">Technology</option>
                    <option value="Executive">Executive</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Generate Report</button>
    </form>

    <!-- Error Message -->
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <!-- Report Summary -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$errorMessage): ?>
        <h2>Summary</h2>
        <p>Total Payroll: £<?php echo number_format($totalPayroll, 2); ?></p>
        <p>Average Salary: £<?php echo number_format($averageSalary, 2); ?></p>

        <!-- Payroll Table -->
        <h2>Payroll Details</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Job Title</th>
                    <th>Base Salary</th>
                    <th>Bonuses</th>
                    <th>Deductions</th>
                    <th>Net Salary</th>
                    <th>Payroll Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payrollData as $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($data['employee_name']); ?></td>
                        <td><?php echo htmlspecialchars($data['department']); ?></td>
                        <td><?php echo htmlspecialchars($data['job_title']); ?></td>
                        <td>£<?php echo number_format($data['base_salary'], 2); ?></td>
                        <td>£<?php echo number_format($data['bonuses'], 2); ?></td>
                        <td>£<?php echo number_format($data['deductions'], 2); ?></td>
                        <td>£<?php echo number_format($data['net_salary'], 2); ?></td>
                        <td><?php echo htmlspecialchars($data['payroll_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Export Buttons -->
        <form method="POST" action="export_payroll.php">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
            <button type="submit" name="export_csv" class="btn btn-success">Export as CSV</button>
        </form>

        <form method="POST" action="export_payroll_pdf.php">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
            <button type="submit" name="export_pdf" class="btn btn-danger">Export as PDF</button>
        </form>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>