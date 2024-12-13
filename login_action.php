<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'KilburnazonDB');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    // Fetch user details
    $stmt = $conn->prepare("SELECT user_id, employee_id, password, role FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Store user information in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['employee_id'] = $user['employee_id'] ?? null;
        $_SESSION['username'] = $username; // Add the username to the session

        // Redirect based on role
        if ($user['role'] === 'employee') {
            header("Location: employee_dashboard.php");
        } elseif ($user['role'] === 'manager') {
            header("Location: manager_dashboard.php");
        }
        exit();
    } else {
        echo "<div class='alert alert-danger text-center'>Invalid username or password</div>";
    }
}
$conn->close();
?>
