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
    die("Connection failed: " . $conn->connect_error);
}

// Handle search and filter inputs
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Build the SQL query with search and filter
$sql = "SELECT * FROM Employee WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR position LIKE '%$search%' OR department LIKE '%$search%' OR office LIKE '%$search%')";
}
if (!empty($filter)) {
    $sql .= " AND department = '$filter'";
}

// Execute the query
$result = $conn->query($sql);

// Fetch unique departments and locations for filters
$departments = $conn->query("SELECT DISTINCT department FROM Employee");
$locations = $conn->query("SELECT DISTINCT office FROM Employee");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container my-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-center">Employee Directory</h1>
        <div>
            <a href="manager_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <a href="add_employee.php" class="btn btn-success">Add New Employee</a>
        </div>
    </div>
    <form method="GET" id="search-form" class="d-flex flex-wrap my-4">
    <input type="text" id="search-id" class="form-control me-2 mb-2" placeholder="Search by Employee ID...">
        <input type="text" id="search" class="form-control me-2 mb-2" placeholder="Search employees...">
        <select id="filter-department" class="form-select me-2 mb-2">
            <option value="">All Departments</option>
            <?php while ($row = $departments->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($row['department']); ?>"><?php echo htmlspecialchars($row['department']); ?></option>
            <?php endwhile; ?>
        </select>
        <select id="filter-location" class="form-select me-2 mb-2">
            <option value="">All Locations</option>
            <?php while ($row = $locations->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($row['office']); ?>"><?php echo htmlspecialchars($row['office']); ?></option>
            <?php endwhile; ?>
        </select>
        <button type="button" id="search-btn" class="btn btn-primary mb-2">Search</button>
        <input type="date" id="filter-start-date" class="form-control me-2 mb-2">
    </form>
    <div class="row" id="employee-results">
        <!-- Employee Cards will be dynamically inserted here -->
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // Attach event listeners for search and filter inputs
    $('#search, #filter-department, #filter-location, #search-id').on('input change', fetchEmployees);

    // Call fetchEmployees() on page load to display all employees
    fetchEmployees();

    function fetchEmployees() {
        const searchId = $('#search-id').val();
        const search = $('#search').val();
        const department = $('#filter-department').val();
        const location = $('#filter-location').val();

        $.ajax({
            url: 'fetch_employees.php',
            type: 'GET',
            data: {
                search_id: searchId, // Dynamically passing Employee ID
                search: search,
                filter_department: department,
                filter_location: location
            },
            success: function (response) {
                $('#employee-results').html(response);
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error: ' + error);
            }
        });
    }
});

</script>
</body>
</html>