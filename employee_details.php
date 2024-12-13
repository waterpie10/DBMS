<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<?php
// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');

// Check connection
if ($conn->connect_error) {
    die("<div class='alert alert-danger'>Connection failed: " . $conn->connect_error . "</div>");
}

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch employee details
$sql = "SELECT * FROM Employee WHERE id = $employee_id";
$result = $conn->query($sql);
if ($result) {
    $employee = $result->fetch_assoc();
} else {
    echo "<div class='alert alert-danger'>Error fetching employee details: " . $conn->error . "</div>";
}

// Fetch emergency contacts
$sql_contacts = "SELECT * FROM EmergencyContact WHERE employee_id = $employee_id";
$result_contacts = $conn->query($sql_contacts);
if (!$result_contacts) {
    echo "<div class='alert alert-danger'>Error fetching emergency contacts: " . $conn->error . "</div>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['promotion'])) {
        // Process promotion
        $new_salary = $employee['salary'] * 1.034; // Apply 3.4% pay increase
        $sql_promotion = "UPDATE Employee SET salary = $new_salary WHERE id = $employee_id";
        if ($conn->query($sql_promotion) === TRUE) {
            $employee['salary'] = $new_salary;
            echo "<div class='alert alert-success'>Employee promoted successfully.</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    } elseif (isset($_POST['update'])) {
        // Update general employee information
        $new_position = $conn->real_escape_string($_POST['position']);
        $new_department = $conn->real_escape_string($_POST['department']);
        $sql_update = "UPDATE Employee SET position = '$new_position', department = '$new_department' WHERE id = $employee_id";
        if ($conn->query($sql_update) === TRUE) {
            $employee['position'] = $new_position;
            $employee['department'] = $new_department;
            echo "<div class='alert alert-success'>Employee information updated successfully.</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container my-4">
    <?php if ($employee): ?>
        <h1><?php echo htmlspecialchars($employee['name']); ?></h1>
        <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee['id']); ?></p>
        <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['position']); ?></p>
        <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
        <p><strong>Office:</strong> <?php echo htmlspecialchars($employee['office']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($employee['email']); ?></p>
        <p><strong>Home Address:</strong> <?php echo htmlspecialchars($employee['home_address']); ?></p>
        <p><strong>Salary:</strong> &pound;<?php echo number_format($employee['salary'], 2); ?></p>
        
        <h3>Employment History</h3>
        <p><strong>Hired Date:</strong> <?php echo htmlspecialchars($employee['hired_date']); ?></p>
        <p><strong>Contract Type:</strong> <?php echo htmlspecialchars($employee['contract']); ?></p>
        <p><strong>National Insurance Number (NIN):</strong> <?php echo htmlspecialchars($employee['nin']); ?></p>

        <h3>Emergency Contacts</h3>
        <ul>
            <?php while ($contact = $result_contacts->fetch_assoc()): ?>
                <li><?php echo htmlspecialchars($contact['contact_name']) . ' (' . htmlspecialchars($contact['relationship']) . ') - ' . htmlspecialchars($contact['contact_phone']); ?></li>
            <?php endwhile; ?>
        </ul>
        <a href="employee_directory.php" class="btn btn-secondary mt-3">Back to Directory</a>

        <!-- Form for Updating Employee Information -->
        <h3 class="mt-4">Update Employee Information</h3>
        <form method="POST">
            <label>Position:</label>
            <input type="text" name="position" value="<?php echo htmlspecialchars($employee['position']); ?>" class="form-control mb-2">
            <label>Department:</label>
            <input type="text" name="department" value="<?php echo htmlspecialchars($employee['department']); ?>" class="form-control mb-2">
            <input type="submit" name="update" value="Update Employee" class="btn btn-primary mb-3">
            <input type="submit" name="promotion" value="Promote Employee" class="btn btn-success">
        </form>

        <!-- Form for Deleting Employee -->
        <h3 class="mt-4">Delete Employee</h3>
        <form method="POST" action="delete_employee.php" onsubmit="return confirm('Are you sure you want to delete this employee?');">
            <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
            <button type="submit" class="btn btn-danger">Delete Employee</button>
        </form>
    <?php else: ?>
        <p>Employee not found.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>