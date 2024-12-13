<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get input values and sanitize them
    $name = $conn->real_escape_string($_POST['name']);
    $position = $conn->real_escape_string($_POST['position']);
    $salary = $conn->real_escape_string($_POST['salary']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $home_address = $conn->real_escape_string($_POST['home_address']);
    $contract = $conn->real_escape_string($_POST['contract']);
    $nin = $conn->real_escape_string($_POST['nin']);
    $emergency_name = $conn->real_escape_string($_POST['emergency_name']);
    $emergency_phone = $conn->real_escape_string($_POST['emergency_phone']);
    $emergency_relationship = $conn->real_escape_string($_POST['emergency_relationship']);
    $department = $conn->real_escape_string($_POST['department']);
    $office = $conn->real_escape_string($_POST['office']);

    // Auto-generate email
    $email = strtolower(str_replace(' ', '.', $name)) . "@kilburnazon.com";
    $hired_date = date("Y-m-d"); // Current date

    // Validate NIN format
    if (!preg_match('/^[A-Z]{2}[0-9]{6}[A-Z]$/', $nin)) {
        echo "<div class='alert alert-danger'>Invalid NIN format.</div>";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $emergency_phone)) {
        echo "<div class='alert alert-danger'>Invalid phone number.</div>";
    } else {
        // Check if NIN already exists
        $stmt_check_nin = $conn->prepare("SELECT id FROM Employee WHERE nin = ?");
        $stmt_check_nin->bind_param("s", $nin);
        $stmt_check_nin->execute();
        $stmt_check_nin->store_result();

        if ($stmt_check_nin->num_rows > 0) {
            echo "<div class='alert alert-danger'>NIN already exists.</div>";
        } else {
            // Prepare and bind for Employee table
            $stmt_employee = $conn->prepare(
                "INSERT INTO Employee (name, position, department, salary, email, dob, office, home_address, hired_date, contract, nin)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_employee->bind_param(
                "sssisssssss", // Corrected format string for 11 placeholders
                $name,
                $position,
                $department,
                $salary,
                $email,
                $dob,
                $office,
                $home_address,
                $hired_date,
                $contract,
                $nin
            );

            if ($stmt_employee->execute()) {
                $employee_id = $conn->insert_id;

                // Prepare and bind for EmergencyContact table
                $stmt_contact = $conn->prepare(
                    "INSERT INTO EmergencyContact (employee_id, contact_name, contact_phone, relationship)
                     VALUES (?, ?, ?, ?)"
                );
                $stmt_contact->bind_param("isss", $employee_id, $emergency_name, $emergency_phone, $emergency_relationship);

                if ($stmt_contact->execute()) {
                    echo "<div class='alert alert-success'>New employee added successfully.</div>";
                } else {
                    error_log("Error inserting contact: " . $stmt_contact->error);
                    echo "<div class='alert alert-danger'>Error adding emergency contact: " . $stmt_contact->error . "</div>";
                }

                $stmt_contact->close();
            } else {
                error_log("Error inserting employee: " . $stmt_employee->error);
                echo "<div class='alert alert-danger'>Error adding employee: " . $stmt_employee->error . "</div>";
            }

            $stmt_employee->close();
        }

        $stmt_check_nin->close();
    }
}

$conn->close();
?>

<!-- HTML Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Employee</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-4">
    <h1>Add New Employee</h1>
    <form id="employeeForm" method="POST">
        <label>Name:</label>
        <input type="text" name="name" class="form-control mb-2" required>
        <label>Position:</label>
        <input type="text" name="position" class="form-control mb-2" required>
        <!-- Department Dropdown -->
        <label for="department">Department:</label>
        <select name="department" id="department" class="form-control mb-2" required>
            <option value="Finance">Finance</option>
            <option value="Marketing">Marketing</option>
            <option value="Operations">Operations</option>
            <option value="Technology">Technology</option>
        </select>
        <label>Salary:</label>
        <input type="number" name="salary" class="form-control mb-2" required>
        <label>Date of Birth:</label>
        <input type="date" name="dob" class="form-control mb-2" required>
        <label>Home Address:</label>
        <input type="text" name="home_address" class="form-control mb-2" required>
        <!-- Office Dropdown -->
        <label for="office">Office:</label>
        <select name="office" id="office" class="form-control mb-2" required>
            <option value="Nurseries Road">Nurseries Road (Scotland Distribution Centre)</option>
            <option value="Musgrave Channel Road">Musgrave Channel Road (Northern Ireland Distribution Centre)</option>
            <option value="Burnside Distribution Ltd">Burnside Distribution Ltd (England North Distribution Centre)</option>
            <option value="Kilburn Building">Kilburn Building (Kilburnazon Head Office)</option>
            <option value="Lewis Building">Lewis Building (Birmingham Office for Kilburnazon)</option>
            <option value="Navigation St">Navigation St (England Central Distribution Centre)</option>
            <option value="Wentloog Corporate Park">Wentloog Corporate Park (Wales Distribution Centre)</option>
            <option value="Broadgate Tower">Broadgate Tower (The London Office for Kilburnazon)</option>
            <option value="FPS Distribution Ltd">FPS Distribution Ltd (England South Distribution Centre)</option>
        </select>
        <label>Contract Type:</label>
        <input type="text" name="contract" class="form-control mb-2" required>
        <label>National Insurance Number (NIN):</label>
        <input type="text" name="nin" class="form-control mb-2" required>
        <div id="ninError" class="text-danger"></div>
        <label>Emergency Contact Name:</label>
        <input type="text" name="emergency_name" class="form-control mb-2" required>
        <label>Emergency Contact Phone:</label>
        <input type="text" name="emergency_phone" class="form-control mb-2" required>
        <div id="phoneError" class="text-danger"></div>
        <label>Emergency Contact Relationship:</label>
        <input type="text" name="emergency_relationship" class="form-control mb-2" required>
        
        <input type="submit" value="Add Employee" class="btn btn-primary">
    </form>
    <a href="employee_directory.php" class="btn btn-secondary mt-3">Back to Directory</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('employeeForm').addEventListener('input', function (event) {
    const nin = document.querySelector('input[name="nin"]').value;
    const emergencyPhone = document.querySelector('input[name="emergency_phone"]').value;
    const ninError = document.getElementById('ninError');
    const phoneError = document.getElementById('phoneError');

    // Validate NIN format
    if (!/^[A-Z]{2}[0-9]{6}[A-Z]$/.test(nin)) {
        ninError.textContent = 'Invalid NIN format.';
    } else {
        ninError.textContent = '';
    }

    // Validate phone number format
    if (!/^[0-9]{10,15}$/.test(emergencyPhone)) {
        phoneError.textContent = 'Invalid phone number.';
    } else {
        phoneError.textContent = '';
    }
});
</script>
</body>
</html>