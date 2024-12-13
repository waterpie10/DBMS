<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    die("Unauthorized access.");
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the export is triggered
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    // Get form inputs
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $department = $_POST['department'] ?? '';

    // Validate inputs
    if (!$startDate || !$endDate) {
        die("Start date and end date are required.");
    }

    // Build the query
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

    // Prepare CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payroll_report.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee Name', 'Department', 'Job Title', 'Base Salary', 'Bonuses', 'Deductions', 'Net Salary', 'Payroll Date']);

    // Write data to CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['employee_name'],
            $row['department'],
            $row['job_title'],
            number_format($row['base_salary'], 2),
            number_format($row['bonuses'], 2),
            number_format($row['deductions'], 2),
            number_format($row['net_salary'], 2),
            $row['payroll_date']
        ]);
    }

    fclose($output);
    exit();
}
?>
