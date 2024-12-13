<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SESSION['role'] !== 'manager') {
    echo "Access Denied!";
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get search and filter parameters
$search_id = isset($_GET['search_id']) ? $conn->real_escape_string($_GET['search_id']) : '';
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_department = isset($_GET['filter_department']) ? $conn->real_escape_string($_GET['filter_department']) : '';
$filter_location = isset($_GET['filter_location']) ? $conn->real_escape_string($_GET['filter_location']) : '';
$filter_start_date = isset($_GET['filter_start_date']) ? $conn->real_escape_string($_GET['filter_start_date']) : '';

// Build SQL query
$sql = "SELECT * FROM Employee WHERE 1=1";

if (!empty($search_id)) {
    $sql .= " AND id = $search_id"; // Search by Employee ID
} elseif (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR position LIKE '%$search%' OR department LIKE '%$search%' OR office LIKE '%$search%')";
}

if (!empty($filter_department)) {
    $sql .= " AND department = '$filter_department'";
}

if (!empty($filter_location)) {
    $sql .= " AND office = '$filter_location'";
}

if (!empty($filter_start_date)) {
    // Check if the input is only a year
    if (preg_match('/^\d{4}$/', $filter_start_date)) {
        $sql .= " AND hired_date LIKE '$filter_start_date%'"; // Matches dates starting with the year
    } else {
        $sql .= " AND hired_date >= '$filter_start_date'"; // Matches full dates
    }
}

// Execute the query
$result = $conn->query($sql);

// Generate HTML for employee cards
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div class="col-md-4 mb-4">';
        echo '<div class="card">';
        echo '<img src="default_profile.png" alt="Employee Photo" class="card-img-top">'; // Add this line
        echo '<div class="card-body">';
        echo '<h5 class="card-title">' . htmlspecialchars($row['name']) . '</h5>';
        echo '<p class="card-text">Position: ' . htmlspecialchars($row['position']) . '</p>';
        echo '<p class="card-text">Department: ' . htmlspecialchars($row['department']) . '</p>';
        echo '<p class="card-text">Office: ' . htmlspecialchars($row['office']) . '</p>';
        echo '<a href="employee_details.php?id=' . $row['id'] . '" class="btn btn-primary">View Details</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<p class="text-center">No employees found.</p>';
}

$conn->close();
?>