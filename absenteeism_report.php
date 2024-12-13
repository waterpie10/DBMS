<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$report_data = [];
$summary = [
    'total_absences' => 0,
    'average_absences_per_employee' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $conn->real_escape_string($_POST['start_date']);
    $end_date = $conn->real_escape_string($_POST['end_date']);

    // Fetch report data grouped by department and leave type
    $query = "
        SELECT 
            Department.department_name AS department_name,
            EmployeeLeave.leave_type,
            COUNT(*) AS total_absences,
            SUM(DATEDIFF(EmployeeLeave.end_date, EmployeeLeave.start_date) + 1) AS total_days_absent
        FROM EmployeeLeave
        INNER JOIN Employee ON EmployeeLeave.employee_id = Employee.id
        INNER JOIN Department ON Employee.department = Department.department_name
        WHERE EmployeeLeave.start_date >= ? AND EmployeeLeave.end_date <= ? AND EmployeeLeave.status = 'approved'
        GROUP BY Department.department_name, EmployeeLeave.leave_type
    ";

    

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }

    // Fetch summary statistics
    // Query for summary data (total days absent and average absence rate per department)
    $summary_query = "
        SELECT 
            Department.department_name AS department_name,
            SUM(DATEDIFF(EmployeeLeave.end_date, EmployeeLeave.start_date) + 1) AS total_days_absent
        FROM EmployeeLeave
        INNER JOIN Employee ON EmployeeLeave.employee_id = Employee.id
        INNER JOIN Department ON Employee.department = Department.department_name
        WHERE EmployeeLeave.start_date >= ? AND EmployeeLeave.end_date <= ? AND EmployeeLeave.status = 'approved'
        GROUP BY Department.department_name
    ";

    // Prepare and execute the summary query
    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->bind_param('ss', $start_date, $end_date);
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();

    // Fetch all results into an array
    $summary = $summary_result->fetch_all(MYSQLI_ASSOC);

    $average_absence_query = "
        SELECT 
            Department.department_name,
            AVG(
                DATEDIFF(EmployeeLeave.end_date, EmployeeLeave.start_date) + 1
            ) AS average_absent_days
        FROM EmployeeLeave
        INNER JOIN Employee ON EmployeeLeave.employee_id = Employee.id
        INNER JOIN Department ON Employee.department = Department.department_name
        WHERE EmployeeLeave.start_date >= ? AND EmployeeLeave.end_date <= ? AND EmployeeLeave.status = 'approved'
        GROUP BY Department.department_name
    ";

    $average_stmt = $conn->prepare($average_absence_query);
    $average_stmt->bind_param('ss', $start_date, $end_date);
    $average_stmt->execute();
    $average_result = $average_stmt->get_result();

    // Fetch results
    $average_absence_rates = $average_result->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absenteeism Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="text-center">Absenteeism Report</h1>
    <form action="absenteeism_report.php" method="POST" class="mt-4">
        <div class="row mb-3">
            <div class="col">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="form-control" required>
            </div>
            <div class="col">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Generate Report</button>
    </form>
    <button onclick="window.location.href='manager_dashboard.php'" class="btn btn-secondary mt-3 w-100">Back to Dashboard</button>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <!-- Displaying the average absence rates in the summary -->
        <h3>Average Absence Rates Per Department:</h3>
        <ul>
            <?php
            if (!empty($average_absence_rates)) {
                foreach ($average_absence_rates as $department) {
                    echo '<li>' . htmlspecialchars($department['department_name']) . ': ' . 
                        number_format($department['average_absent_days'], 2) . ' days</li>';
                }
            } else {
                echo '<li>No data available</li>';
            }
            ?>
        </ul>

        <h2 class="mt-4">Details by Department and Leave Type</h2>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Leave Type</th>
                    <th>Total Absences</th>
                    <th>Total Days Absent</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $current_department = null;
                $department_total_days = 0;
                foreach ($report_data as $row): 
                    if ($current_department !== $row['department_name']): 
                        if ($current_department !== null): ?>
                            <tr>
                                <td colspan="4" class="bg-light"><strong>Total Days Absent for <?php echo htmlspecialchars($current_department); ?>:</strong> <?php echo $department_total_days; ?> days</td>
                            </tr>
                        <?php endif; 
                        $current_department = $row['department_name'];
                        $department_total_days = 0; // Reset for the new department
                ?>
                    <tr>
                        <td colspan="4" class="bg-light"><strong><?php echo htmlspecialchars($current_department); ?></strong></td>
                    </tr>
                <?php endif; ?>
                    <tr>
                        <td></td>
                        <td><?php echo htmlspecialchars($row['leave_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_absences']); ?></td>
                        <td><?php echo htmlspecialchars($row['total_days_absent']); ?></td>
                    </tr>
                <?php 
                    $department_total_days += $row['total_days_absent']; 
                endforeach; 
                ?>
                <tr>
                    <td colspan="4" class="bg-light"><strong>Total Days Absent for <?php echo htmlspecialchars($current_department); ?>:</strong> <?php echo $department_total_days; ?> days</td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>