<?php
require_once('TCPDF-main/tcpdf.php');
session_start();

// Restrict access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    die("Unauthorized access.");
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_pdf'])) {
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $department = $_POST['department'] ?? '';

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

    $stmt = $conn->prepare($query);
    if ($department) {
        $stmt->bind_param("sss", $startDate, $endDate, $department);
    } else {
        $stmt->bind_param("ss", $startDate, $endDate);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize TCPDF
    $pdf = new TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Kilburnazon');
    $pdf->SetTitle('Payroll Report');
    $pdf->SetHeaderData('', 0, 'Payroll Report', "From: $startDate To: $endDate");

    // Set margins and add a page
    $pdf->SetMargins(10, 20, 10);
    $pdf->AddPage();

    // Prepare HTML content
    $html = '<h1 style="text-align: center;">Payroll Report</h1>';
    $html .= '<p><strong>Date Range:</strong> ' . htmlspecialchars($startDate) . ' to ' . htmlspecialchars($endDate) . '</p>';
    if ($department) {
        $html .= '<p><strong>Department:</strong> ' . htmlspecialchars($department) . '</p>';
    }

    $html .= '<table border="1" cellpadding="5" cellspacing="0" style="font-size: 10px;">
                <colgroup>
                    <col style="width: 20%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                </colgroup>
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
                <tbody>';

    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($row['employee_name']) . '</td>
                    <td>' . htmlspecialchars($row['department']) . '</td>
                    <td>' . htmlspecialchars($row['job_title']) . '</td>
                    <td>£' . number_format($row['base_salary'], 2) . '</td>
                    <td>£' . number_format($row['bonuses'], 2) . '</td>
                    <td>£' . number_format($row['deductions'], 2) . '</td>
                    <td>£' . number_format($row['net_salary'], 2) . '</td>
                    <td>' . htmlspecialchars($row['payroll_date']) . '</td>
                  </tr>';
    }

    $html .= '</tbody></table>';

    $pdf->writeHTML($html);
    $pdf->Output('payroll_report.pdf', 'D');
    exit();
}
?>